# Lora PMS — release staging → main dhe rollback

Production-i nuk promovohet pa CI green, rehearsal të freskët mbi kopjen production,
backup off-server dhe approval të production Environment.

## Porta para approval-it

1. main duhet të përmbajë commit-in ekzakt që u testua. Deploy-i pranon vetëm
   workflow_run.head_sha == origin/main.
2. Kërkohen checks application, mysql-migrations, mysql-tenancy dhe mysql-upgrade.
3. Production duhet të ketë APP_ENV=production, APP_DEBUG=false, APP_URL=https://...,
   SESSION_SECURE_COOKIE=true dhe SESSION_HTTP_ONLY=true.
   Kur kandidati përdor Laravel Passport, `/etc/lora-passport` duhet të përmbajë
   çiftin ekzistues root:www-data me mode 0440 dhe `.env` duhet të përdorë URI-të
   `file:///etc/lora-passport/oauth-private.key` dhe `file:///etc/lora-passport/oauth-public.key`.
4. GitHub secret SERVER_FINGERPRINT duhet të jetë fingerprint-i SHA256 i host key.
   SERVER_IP, SSH_PRIVATE_KEY dhe SERVER_FINGERPRINT duhet të jenë vetëm secrets të
   Environment-eve `production` / `production-rehearsal`, jo repo-level secrets.
5. Queue, cron dhe /etc/cron.d/villamucho-scheduler duhet të jenë aktive; production
   nuk duhet të ketë migrime pending.
6. Restore backup-in në MySQL të izoluar dhe provo upgrade + rollback me commit-in
   ekzakt kandidat kundrejt commit-it aktual production. Verifiko skemën,
   checksum-et e DB/storage, integritetin multitenant dhe hapjen me kodin e vjetër.
   Versioni dhe parametrat kritikë të MySQL duhet të përputhen me production, ndërsa
   user-i i rehearsal-it përdor të njëjtin minimum privilegjesh që kërkon deploy-i.

Vetëm rehearsal script-i krijon marker-in per-SHA
`/var/lib/lora-backup/rehearsals/<40-char-main-SHA>.ok` me këtë përmbajtje:

~~~text
candidate_sha=<40-char main SHA>
production_sha=<40-char current production SHA>
snapshot_id=<64-char Restic snapshot ID>
mysql_image=<pinned MySQL image with digest>
app_image=<pinned non-root PHP image with digest>
verified_tables=<positive integer>
schema_sha256=<64-char SHA-256>
checksums_sha256=<64-char SHA-256>
mysql_fingerprint_sha256=<64-char SHA-256>
vendor_sha256=<64-char SHA-256>
assets_sha256=<64-char SHA-256>
passport_public_key_sha256=<64-char SHA-256 or not-required>
completed_utc=<YYYY-MM-DDTHH:MM:SSZ>
~~~

Marker-i duhet të ketë saktësisht këto trembëdhjetë rreshta, owner root:root, mode 0600,
të mos jetë symlink, të lidhet me kandidat + production aktual dhe të jetë maksimumi
24 orë i vjetër. Një rerun e nxjerr marker-in e vjetër jashtë përdorimit dhe e
zëvendëson atomikisht vetëm pas suksesit. Mos e krijo manualisht.

## Rendi i saktë i GitHub gates

1. Fingerprint-i merret jashtë SSH-së nga Hetzner Console dhe ruhet si
   `SERVER_FINGERPRINT`; lidhja e parë nuk lejohet me fingerprint bosh.
2. Pasi harness-i ekziston në `main`, label-i `production-preflight` përdor vetëm
   harness-in nga base SHA e mbrojtur. `production-rehearsal` ekzekuton kandidatin
   non-root në container pa production mounts dhe vetëm në rrjetin e brendshëm të
   MySQL-it të izoluar.
3. Para merge-it, Environment-et `production` dhe `production-rehearsal` duhet të
   kenë required reviewer + branch policy; secrets repo-level të SSH-së fshihen.
4. Pas merge/squash/rebase, prit katër checks green në `main`. SHA finale zakonisht
   ndryshon nga PR head SHA.
5. Nga workflow `Production Release Rehearsal`, përdor `workflow_dispatch` me SHA-në
   ekzakte që është aktualisht në majë të `main`. Ky run krijon marker-in që deploy-i
   mund të konsumojë.
6. Vetëm pasi marker-i final exact-SHA është green, aprovo Environment `production`.

Marker-i i provës së PR-së nuk zëvendëson marker-in final të `main`; kjo është
qëllimisht fail-closed.

Për release-in e parë, bëj fillimisht një bootstrap PR atomik vetëm me workflow-et,
harness-in dhe backup/control scripts të besuar. Blloko Environment-et para merge-it,
mos aprovo deploy-in e bootstrap-it, pastaj rebase release PR mbi atë `main`. Vetëm
release PR kalon preflight + rehearsal para merge-it.

## Çfarë garanton deploy-i

- Merr lock-un e përbashkët `/var/lib/lora-backup/production-release.lock`, të njëjtin
  që përdor rehearsal-i.
- Verifikon marker-in e rehearsal-it dhe ekzekuton backup të ri
  `lora-backup.service`. Një request/ready handshake root:root 0600, i lidhur me
  nonce + candidate SHA dhe lock-un aktiv, mban PHP-FPM, queue, cron dhe scheduler
  të bllokuar nga snapshot-i deri te deploy-i. Ready marker-i jep snapshot ID,
  kohën reale të snapshot-it dhe kohën e upload-it; `last-success` duhet të
  përputhet me upload-in dhe të jetë maksimumi 900 sekonda i vjetër.
- Release handoff ka kufij më të shkurtër se timer backup-i: 20 minuta për secilin
  pre-copy storage, 10 minuta për secilin final-copy, 5 minuta për integrity,
  15 minuta për dump, deri në dy tentativa nga 45 minuta për Restic dhe dy nga
  5 minuta për `restic check`. SSH është 350 minuta brenda job-it 360 minuta.
  Timeout para ready marker-it rikthen versionin e vjetër; pas ready mbetet fail-closed.
- Verifikon hash-et dhe formatin e artefakteve `vendor`/`public/build` të ndërtuara
  nga runner-i i rehearsal-it; production nuk ekzekuton `composer`, `npm` ose build
  me rrjet. Artefaktet ruhen root-only sipas SHA-së së kandidatit.
- Kur kandidati përdor Passport, verifikon të njëjtin fingerprint të çiftit të
  çelësave para backup-it, pas backup-it, pas cache-it dhe menjëherë para ekspozimit
  të PHP-FPM. Deploy-i nuk gjeneron dhe nuk rrotullon çelësa.
- Pre-armon dhe pastaj forcon runtime systemd start-fences para maintenance; ndalon queue/scheduler dhe
  bën hard-stop PHP-FPM para code/DB swap-it. Fence-i i PHP-FPM hiqet vetëm pas
  marker-it durable; fence-i i queue vetëm pas smoke test-it dhe ai i cron-it vetëm
  pasi scheduler file rikthehet.
- Kodi i aplikacionit ekzekutohet si `www-data`; `vendor` dhe `public/build`
  promovohen root-owned, të lexueshme nga runtime dhe jo të shkrueshme prej tij.
- Root ekzekuton script-in e kontroll-panelit vetëm pasi script-i dhe template-i
  përputhen me SHA-256 allowlist të workflow-it të mbrojtur.
- Ruan state dhe snapshot unikë në /var/lib/lora-backup/releases/.
- Shkruan atomikisht SHA-në aktive në `/var/lib/lora-release/current`, nën directory
  root:www-data 0750 dhe file root:www-data 0640.
- Teston /up dhe /up/release me HTTPS/TLS, fillimisht me bypass dhe pastaj publik.
- Menjëherë para ristartimit të PHP-FPM shkruan konservativisht
  WRITERS_MAY_HAVE_RUN=1 në state; pas tij DB rollback automatik refuzohet.
- Në çdo dështim, EXIT trap ndalon PHP-FPM si hard fence, rikthen maintenance mode
  dhe ndalon queue/scheduler.

State file-i i saktë printohet në fund të logut dhe përmban të paktën:
PRE_DEPLOY_COMMIT, CANDIDATE_COMMIT, PREVIOUS_BATCH, RELEASE_BATCH,
INTEGRITY_SNAPSHOT, BACKUP_SNAPSHOT_ID, BACKUP_SNAPSHOT_CREATED_AT_UTC,
BACKUP_COMPLETED_AT_UTC, PHP_FPM_SERVICE, PHP_FPM_SOCKET dhe, kur kodi mund të jetë
ekspozuar, WRITERS_MAY_HAVE_RUN=1.

## Recovery kur handoff-i ndërpritet

Mos bëj kurrë `rm` mbi request/ready/sentinel/drop-ins dhe mos nis manualisht
PHP-FPM, queue ose cron pa marrë të dy lock-et dhe pa verifikuar gjendjen e plotë.
Drop-ins e release-it janë në `/etc/systemd/system`, ndërsa sentinel/marker-at në
`/var/lib/lora-backup`; prandaj fence-i mbetet aktiv edhe pas reboot-it.

- Pa ready marker: ndal me timeout `lora-backup.service` nëse është
  `active|activating|deactivating`, prit `inactive|failed` me `MainPID=0` dhe lër
  cleanup-in e backup-it të rikthejë versionin e vjetër. Pastro pre-arm vetëm nëse
  nonce/request/sentinel përputhen ekzakt, të tre shërbimet janë aktive me PID,
  socket-i PHP ekziston, scheduler file është aktiv, backup hold mungon dhe pas
  `daemon-reload` asnjë nga tre drop-ins nuk figuron më i ngarkuar.
- Me ready marker: kjo është gjendje durable fail-closed. Ready duhet të ketë ekzakt
  12 rreshtat, të njëjtin nonce/candidate me request-in, upload të freskët,
  snapshot ID 64-hex dhe identitetet ekzakte të PHP-FPM/queue/cron. Sentinel duhet
  të jetë vetëm te `.handoff`, të tre shërbimet/proceset të jenë ndalur dhe
  scheduler-i vetëm te `.backup-paused`. Mbaje sistemin down dhe përdor nga console
  vetëm procedurën e rishikuar të adoptimit për të njëjtin SHA, ose procedurën e
  rikthimit të old production pas verifikimit që Git HEAD është ende commit-i i
  vjetër dhe working tree është clean. Çdo mospërputhje trajtohet si incident;
  nuk lejohet fshirje apo start “për ta provuar”.

## Rollback i sigurt

Përdor state file-in ekzakt nga logu; mos zgjidh automatikisht “latest”.

~~~bash
set -Eeuo pipefail
umask 077

cd /var/www/villamucho
exec 9>/var/lib/lora-backup/production-release.lock
flock -n 9 || { echo 'Një operacion tjetër production është aktiv.' >&2; exit 1; }
exec 8>/var/lib/lora-backup/backup.lock
flock -n 8 || { echo 'Backup-i është aktiv; rollback u refuzua.' >&2; exit 1; }

# Zëvendëso vetëm vlerën më poshtë me path-in ekzakt të printuar nga deploy-i.
state_file=/var/lib/lora-backup/releases/STATE_FILE_FROM_DEPLOY_LOG.env
test -f "$state_file"
test ! -L "$state_file"
test "$(stat -c '%U:%G:%a' "$state_file")" = root:root:600

# Source lejohet vetëm pasi çdo rresht është verifikuar si key/value inert.
if grep -Evq '^((PRE_DEPLOY_COMMIT|CANDIDATE_COMMIT|RELEASE_COMMIT)=[0-9a-f]{40}|BACKUP_SNAPSHOT_ID=[0-9a-f]{64}|(PREVIOUS_BATCH|RELEASE_BATCH)=[0-9]+|INTEGRITY_SNAPSHOT=/var/lib/lora-backup/releases/[0-9TZ]+-[0-9a-f]{40}-integrity\.json|PHP_FPM_SERVICE=php[0-9]+([.][0-9]+)?-fpm[.]service|PHP_FPM_SOCKET=/run/php/php[0-9]+([.][0-9]+)?-fpm[.]sock|(REHEARSAL_COMPLETED_AT_UTC|BACKUP_SNAPSHOT_CREATED_AT_UTC|BACKUP_COMPLETED_AT_UTC|WRITERS_ENABLED_AT_UTC|TRAFFIC_OPENED_AT_UTC|DEPLOYED_AT_UTC)=[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z|WRITERS_MAY_HAVE_RUN=1)$' "$state_file"; then
  echo 'State file përmban rreshta të palejuar.' >&2
  exit 1
fi
for key in PRE_DEPLOY_COMMIT CANDIDATE_COMMIT PREVIOUS_BATCH \
  INTEGRITY_SNAPSHOT RELEASE_COMMIT RELEASE_BATCH BACKUP_SNAPSHOT_ID \
  BACKUP_SNAPSHOT_CREATED_AT_UTC BACKUP_COMPLETED_AT_UTC \
  PHP_FPM_SERVICE PHP_FPM_SOCKET; do
  test "$(grep -c "^${key}=" "$state_file")" -eq 1
done

. "$state_file"
test "$RELEASE_COMMIT" = "$CANDIDATE_COMMIT"
test "$PRE_DEPLOY_COMMIT" != "$CANDIDATE_COMMIT"

# Ky recovery path është i sigurt vetëm para ekspozimit të PHP-FPM kandidat.
# Refuzo para çdo mutacioni; pas marker-it përdor forward-fix ose restore.
if grep -qx 'WRITERS_MAY_HAVE_RUN=1' "$state_file"; then
  echo 'STOP: DB rollback is unsafe because candidate PHP may have served traffic.' >&2
  exit 1
fi

queue_service=villamucho-queue.service
if test "$(systemctl show --property=LoadState --value cron.service 2>/dev/null || true)" = loaded; then
  cron_service=cron.service
elif test "$(systemctl show --property=LoadState --value crond.service 2>/dev/null || true)" = loaded; then
  cron_service=crond.service
else
  echo 'Cron service nuk u gjet.' >&2
  exit 1
fi
scheduler_file=/etc/cron.d/villamucho-scheduler
scheduler_hold=/var/lib/lora-backup/villamucho-scheduler.deploy-paused
app_user=www-data
build_uid=20001
build_gid=20001
build_workspace_bytes=$((3 * 1024 * 1024 * 1024))
build_image_margin_bytes=$((4 * 1024 * 1024 * 1024))
build_state_dir=/var/lib/lora-build
build_php_image='serversideup/php:8.4-cli@sha256:7b669c4fbb70ca392cdbfa61b0aee6f95530445a67f2a814c0692c992971de2c'
build_node_image='node:22.20.0-bookworm-slim@sha256:b21fe589dfbe5cc39365d0544b9be3f1f33f55f3c86c87a76ff65a02f8f5848e'
release_dir=/var/lib/lora-release
release_file="$release_dir/current"
php_fpm_service="$PHP_FPM_SERVICE"
php_fpm_socket="$PHP_FPM_SOCKET"
writer_fence_sentinel=/var/lib/lora-backup/release-writers-enabled
php_fpm_fence_file="/etc/systemd/system/${php_fpm_service}.d/lora-release-writer-fence.conf"
queue_fence_file="/etc/systemd/system/${queue_service}.d/lora-release-writer-fence.conf"
cron_fence_file="/etc/systemd/system/${cron_service}.d/lora-release-writer-fence.conf"
rollback_succeeded=false
rollback_cookie=
rollback_handoff=
rollback_integrity_file=
build_home=
build_root=
build_image=
build_loop=
build_mount=
vendor_stage=
assets_stage=
release_tmp=

run_as_app() {
  runuser --user "$app_user" -- \
    env -i HOME=/var/www/villamucho/storage/framework \
      PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin "$@"
}

run_composer_build() {
  docker run --rm --name "lora-rollback-composer-${PRE_DEPLOY_COMMIT:0:12}" \
    --label com.lora.production-rollback=true --network bridge --read-only \
    --cap-drop ALL --security-opt no-new-privileges --pids-limit 256 \
    --memory 1g --memory-swap 1g --cpus 2 --user "$build_uid:$build_gid" \
    --log-driver none --tmpfs "/tmp:rw,nosuid,nodev,noexec,size=268435456,uid=$build_uid,gid=$build_gid,mode=0700" \
    --env HOME=/tmp --env COMPOSER_HOME=/tmp/composer \
    --mount "type=bind,source=$build_root,target=/workspace" --workdir /workspace \
    --entrypoint /usr/bin/composer --pull never "$build_php_image" "$@"
}

run_npm_build() {
  local phase="$1"
  local network="$2"
  shift 2
  docker run --rm --name "lora-rollback-${phase}-${PRE_DEPLOY_COMMIT:0:12}" \
    --label com.lora.production-rollback=true --network "$network" --read-only \
    --cap-drop ALL --security-opt no-new-privileges --pids-limit 256 \
    --memory 2g --memory-swap 2g --cpus 2 --user "$build_uid:$build_gid" \
    --log-driver none --tmpfs "/tmp:rw,nosuid,nodev,noexec,size=536870912,uid=$build_uid,gid=$build_gid,mode=0700" \
    --env HOME=/tmp --env npm_config_cache=/tmp/npm \
    --env "VITE_BUILD_ID=$PRE_DEPLOY_COMMIT" \
    --mount "type=bind,source=$build_root,target=/workspace" --workdir /workspace \
    --entrypoint /usr/local/bin/npm --pull never "$build_node_image" "$@"
}

rollback_fail() {
  echo "Rollback refused: $*" >&2
  exit 1
}

php_fpm_is_stopped() {
  local active_state main_pid

  active_state="$(systemctl show --property=ActiveState --value "$php_fpm_service" 2>/dev/null || true)"
  main_pid="$(systemctl show --property=MainPID --value "$php_fpm_service" 2>/dev/null || true)"
  case "$active_state" in inactive|failed) ;; *) return 1 ;; esac
  test "$main_pid" = 0
}

hard_stop_php_fpm() {
  timeout 30s systemctl stop "$php_fpm_service" >/dev/null 2>&1 || true
  if ! php_fpm_is_stopped; then
    timeout 10s systemctl kill --kill-who=all --signal=KILL \
      "$php_fpm_service" >/dev/null 2>&1 || true
    timeout 15s systemctl stop "$php_fpm_service" >/dev/null 2>&1 || true
  fi
  php_fpm_is_stopped
}

record_possible_exposure() {
  local exposed_at quarantine state_directory

  if ! test -f "$state_file" || test -L "$state_file"; then
    return 1
  fi

  if grep -qx 'WRITERS_MAY_HAVE_RUN=1' "$state_file"; then
    sync "$state_file" && return 0
  fi

  exposed_at="$(date --utc +%Y-%m-%dT%H:%M:%SZ)"
  if printf 'WRITERS_MAY_HAVE_RUN=1\nWRITERS_ENABLED_AT_UTC=%s\n' \
      "$exposed_at" >> "$state_file" \
    && sync "$state_file" \
    && grep -qx 'WRITERS_MAY_HAVE_RUN=1' "$state_file" \
    && grep -qx "WRITERS_ENABLED_AT_UTC=$exposed_at" "$state_file"; then
    return 0
  fi

  state_directory="${state_file%/*}"
  quarantine="${state_file}.rollback-unsafe-${exposed_at//[:T-]/}-$$"
  chmod 000 -- "$state_file" >/dev/null 2>&1 || true
  if mv -- "$state_file" "$quarantine" >/dev/null 2>&1; then
    sync "$state_directory" >/dev/null 2>&1 || true
  fi
  echo 'CRITICAL: marker-i nuk u bë durable; state u karantinua dhe nuk mund të ripërdoret.' >&2
  return 1
}

queue_is_stopped() {
  local active_state main_pid

  active_state="$(systemctl show --property=ActiveState --value "$queue_service" 2>/dev/null || true)"
  main_pid="$(systemctl show --property=MainPID --value "$queue_service" 2>/dev/null || true)"
  case "$active_state" in inactive|failed) ;; *) return 1 ;; esac
  test "$main_pid" = 0
}

cron_is_stopped() {
  local active_state main_pid

  active_state="$(systemctl show --property=ActiveState --value "$cron_service" 2>/dev/null || true)"
  main_pid="$(systemctl show --property=MainPID --value "$cron_service" 2>/dev/null || true)"
  case "$active_state" in inactive|failed) ;; *) return 1 ;; esac
  test "$main_pid" = 0
}

hard_stop_cron() {
  timeout 30s systemctl stop "$cron_service" >/dev/null 2>&1 || true
  if ! cron_is_stopped; then
    timeout 10s systemctl kill --kill-who=all --signal=KILL \
      "$cron_service" >/dev/null 2>&1 || true
    timeout 15s systemctl stop "$cron_service" >/dev/null 2>&1 || true
  fi
  cron_is_stopped
}

queue_process_is_absent() {
  local status

  if pgrep -f '[a]rtisan (queue:(work|listen)|horizon)([[:space:]]|$)' >/dev/null; then
    return 1
  else
    status=$?
    test "$status" -eq 1
  fi
}

scheduler_process_is_absent() {
  local status

  if pgrep -f '[a]rtisan schedule:(run|work|finish-command)([[:space:]]|$)' >/dev/null; then
    return 1
  else
    status=$?
    test "$status" -eq 1
  fi
}

php_fpm_process_is_absent() {
  local status

  if pgrep -f '(^|/)[p]hp-fpm([0-9.]*)?(:|[[:space:]]|$)' >/dev/null; then
    return 1
  else
    status=$?
    test "$status" -eq 1
  fi
}

php_fpm_listener_is_absent() {
  local listeners status

  listeners="$(ss -H -xl)" || return 1
  if awk -v target="$php_fpm_socket" '
    { for (field = 1; field <= NF; field++) if ($field == target) found = 1 }
    END { exit found ? 0 : 1 }
  ' <<< "$listeners"; then
    return 1
  else
    status=$?
    test "$status" -eq 1
  fi
}

scheduler_entry_is_absent() {
  test ! -e "$scheduler_file" && test ! -L "$scheduler_file"
}

scheduler_hold_is_safe() {
  test -f "$scheduler_hold" && test ! -L "$scheduler_hold" \
    && test "$(stat -c '%U:%G:%a' "$scheduler_hold")" = root:root:644
}

production_writer_is_observed() {
  ! php_fpm_is_stopped || ! php_fpm_process_is_absent \
    || ! php_fpm_listener_is_absent || ! queue_is_stopped || ! queue_process_is_absent \
    || ! cron_is_stopped || ! scheduler_entry_is_absent || ! scheduler_process_is_absent
}

runtime_fence_file_is_safe() {
  local path="$1" expected

  expected="[Unit]
RefuseManualStart=yes
ConditionPathExists=$writer_fence_sentinel"
  test -f "$path" && test ! -L "$path" \
    && test "$(stat -c '%U:%G:%a' "$path")" = root:root:644 \
    && test "$(<"$path")" = "$expected"
}

service_uses_runtime_fence() {
  local service="$1" fence_file="$2" dropins

  dropins="$(systemctl show --property=DropInPaths --value "$service" 2>/dev/null || true)"
  [[ " $dropins " == *" $fence_file "* ]]
}

runtime_writer_fence_is_loaded() {
  test ! -e "$writer_fence_sentinel" && test ! -L "$writer_fence_sentinel" \
    && runtime_fence_file_is_safe "$php_fpm_fence_file" \
    && runtime_fence_file_is_safe "$queue_fence_file" \
    && runtime_fence_file_is_safe "$cron_fence_file" \
    && service_uses_runtime_fence "$php_fpm_service" "$php_fpm_fence_file" \
    && service_uses_runtime_fence "$queue_service" "$queue_fence_file" \
    && service_uses_runtime_fence "$cron_service" "$cron_fence_file"
}

ensure_runtime_writer_fence() {
  local fence_file

  test ! -e "$writer_fence_sentinel" && test ! -L "$writer_fence_sentinel" \
    || return 1
  for fence_file in "$php_fpm_fence_file" "$queue_fence_file" "$cron_fence_file"; do
    if test -e "$fence_file" || test -L "$fence_file"; then
      runtime_fence_file_is_safe "$fence_file" || return 1
      continue
    fi
    install -d -o root -g root -m 0755 "${fence_file%/*}"
    test -d "${fence_file%/*}" && test ! -L "${fence_file%/*}" || return 1
    install -o root -g root -m 0644 /dev/null "$fence_file"
    printf '[Unit]\nRefuseManualStart=yes\nConditionPathExists=%s\n' \
      "$writer_fence_sentinel" > "$fence_file"
  done
  systemctl daemon-reload
  runtime_writer_fence_is_loaded
}

remove_runtime_fence() {
  local service="$1" fence_file="$2"

  rm -f -- "$fence_file"
  rmdir "${fence_file%/*}" >/dev/null 2>&1 || true
  systemctl daemon-reload
  test ! -e "$fence_file" && test ! -L "$fence_file" \
    && ! service_uses_runtime_fence "$service" "$fence_file"
}

runtime_writer_fence_is_intact() {
  runtime_writer_fence_is_loaded \
    && php_fpm_is_stopped && php_fpm_process_is_absent \
    && php_fpm_listener_is_absent && queue_is_stopped && queue_process_is_absent \
    && cron_is_stopped && scheduler_entry_is_absent && scheduler_hold_is_safe \
    && scheduler_process_is_absent
}

assert_runtime_writer_fence() {
  if ! runtime_writer_fence_is_intact; then
    record_possible_exposure || true
    rollback_fail 'writer fence u thye; state u mbyll për DB rollback.'
  fi
}

assert_rollback_build_capacity() {
  local available total reserve=$((10 * 1024 * 1024 * 1024)) required
  local docker_root docker_available docker_total docker_reserve=$((5 * 1024 * 1024 * 1024))

  available="$(df --output=avail -B1 "$build_state_dir" | awk 'NR == 2 {print $1}')"
  total="$(df --output=size -B1 "$build_state_dir" | awk 'NR == 2 {print $1}')"
  [[ "$available" =~ ^[0-9]+$ && "$total" =~ ^[0-9]+$ ]] \
    || rollback_fail 'nuk u lexua kapaciteti i build filesystem.'
  if test $((total / 5)) -gt "$reserve"; then
    reserve=$((total / 5))
  fi
  docker_root="$(docker info --format '{{.DockerRootDir}}')"
  test -d "$docker_root" || rollback_fail 'Docker root mungon.'
  if test "$(stat -c %d "$docker_root")" = "$(stat -c %d "$build_state_dir")"; then
    required=$((build_workspace_bytes * 2 + build_image_margin_bytes + reserve))
    test "$available" -gt "$required" \
      || rollback_fail 'disku nuk ka rezervë për build-in e kufizuar dhe staging.'
  else
    required=$((build_workspace_bytes * 2 + reserve))
    test "$available" -gt "$required" \
      || rollback_fail 'build filesystem nuk ka rezervën minimale.'
    docker_available="$(df --output=avail -B1 "$docker_root" | awk 'NR == 2 {print $1}')"
    docker_total="$(df --output=size -B1 "$docker_root" | awk 'NR == 2 {print $1}')"
    [[ "$docker_available" =~ ^[0-9]+$ && "$docker_total" =~ ^[0-9]+$ ]] \
      || rollback_fail 'nuk u lexua kapaciteti i Docker filesystem.'
    if test $((docker_total / 5)) -gt "$docker_reserve"; then
      docker_reserve=$((docker_total / 5))
    fi
    test "$docker_available" -gt $((build_image_margin_bytes + docker_reserve)) \
      || rollback_fail 'Docker filesystem nuk ka rezervën minimale.'
  fi
}

cleanup_rollback_build() {
  local failed=0
  local -a containers=()

  if timeout 15s docker info >/dev/null 2>&1; then
    mapfile -t containers < <(
      timeout 15s docker ps -aq --filter 'label=com.lora.production-rollback=true'
    )
    if test "${#containers[@]}" -gt 0; then
      timeout 30s docker rm -f "${containers[@]}" >/dev/null 2>&1 || failed=1
    fi
  else
    failed=1
  fi
  sync >/dev/null 2>&1 || true
  if test -n "$build_mount" && mountpoint -q "$build_mount"; then
    timeout 30s umount "$build_mount" >/dev/null 2>&1 || failed=1
  fi
  if test -n "$build_loop" \
    && { test -z "$build_mount" || ! mountpoint -q "$build_mount"; } \
    && losetup "$build_loop" >/dev/null 2>&1; then
    timeout 15s losetup --detach "$build_loop" >/dev/null 2>&1 || failed=1
  fi
  if test -n "$build_mount" && ! mountpoint -q "$build_mount"; then
    rmdir "$build_mount" >/dev/null 2>&1 || true
    test ! -e "$build_mount" && build_mount=
  fi
  if test -n "$build_loop" && ! losetup "$build_loop" >/dev/null 2>&1; then
    build_loop=
  fi
  if test -n "$build_image" && test -z "$build_loop"; then
    rm -f -- "$build_image"
    test ! -e "$build_image" && build_image=
  fi
  if test -z "$build_mount" && test -z "$build_loop" && test -z "$build_image"; then
    build_home=
    build_root=
  else
    failed=1
  fi
  test "$failed" -eq 0
}

command -v runuser >/dev/null
command -v timeout >/dev/null
command -v pgrep >/dev/null
command -v ss >/dev/null
id "$app_user" >/dev/null
! getent passwd "$build_uid" >/dev/null
! getent group "$build_gid" >/dev/null
test "$(stat -c '%U:%G:%a' /var/www/villamucho)" = root:root:755
test "$(stat -c '%U:%G:%a' /var/www/villamucho/.env)" = root:www-data:640
test -d .git
test ! -L .git
if find .git -xdev \( ! -user root -o -perm /022 \) -print -quit | grep -q .; then
  echo 'Git metadata nuk është root-owned/immutable.' >&2
  exit 1
fi
if find . -xdev \
  \( -path './.git' -o -path './vendor' -o -path './node_modules' \
     -o -path './storage' -o -path './bootstrap/cache' \
     -o -path './public/build' -o -path './public/storage' \) -prune \
  -o \( -type l -o ! -user root -o -perm /022 \) -print -quit | grep -q .; then
  echo 'Source boundary nuk është root-owned/immutable.' >&2
  exit 1
fi
install -d -o root -g "$app_user" -m 0750 "$release_dir"
install -d -o root -g root -m 0700 "$build_state_dir"
[[ "$php_fpm_socket" =~ ^/run/php/php[0-9]+([.][0-9]+)?-fpm[.]sock$ ]]
if production_writer_is_observed; then
  record_possible_exposure || true
  rollback_fail 'production writer u pa aktiv/unknown në hyrje; DB rollback është i pasigurt.'
fi
test "$(systemctl show --property=LoadState --value "$php_fpm_service")" = loaded
test "$(systemctl show --property=LoadState --value "$queue_service")" = loaded
test "$(systemctl show --property=LoadState --value "$cron_service")" = loaded
scheduler_hold_is_safe \
  || rollback_fail 'scheduler hold mungon ose nuk është root:root:644.'

rollback_secret="$(php -r 'echo bin2hex(random_bytes(32));')"
rollback_fail_safe() {
  local status=$?
  local writers_fenced=true
  local queue_state queue_pid
  trap - EXIT HUP INT TERM
  if test "$rollback_succeeded" != true; then
    if ! ensure_runtime_writer_fence; then
      writers_fenced=false
    fi
    if ! hard_stop_php_fpm; then
      writers_fenced=false
    fi
    cd /var/www/villamucho || true
    run_as_app php artisan down --retry=15 --secret="$rollback_secret" --quiet || true
    if ! systemctl stop "$queue_service"; then
      writers_fenced=false
    fi
    queue_is_stopped || writers_fenced=false
    hard_stop_cron || writers_fenced=false
    if test -e "$scheduler_file"; then
      destination="$scheduler_hold"
      if test -e "$destination"; then
        destination="${scheduler_hold}.rollback-conflict.$(date --utc +%Y%m%dT%H%M%SZ)"
      fi
      mv -- "$scheduler_file" "$destination" || writers_fenced=false
    fi
    run_as_app php artisan schedule:interrupt --quiet || writers_fenced=false
    for attempt in $(seq 1 30); do
      if scheduler_process_is_absent; then
        break
      fi
      test "$attempt" -eq 30 || sleep 1
    done
    scheduler_process_is_absent || writers_fenced=false
    php_fpm_process_is_absent || writers_fenced=false
    php_fpm_listener_is_absent || writers_fenced=false
    queue_process_is_absent || writers_fenced=false
    scheduler_entry_is_absent || writers_fenced=false
    scheduler_hold_is_safe || writers_fenced=false
    runtime_writer_fence_is_loaded || writers_fenced=false
    if test "$writers_fenced" = true; then
      echo 'Rollback dështoi; runtime fences mbeten dhe writers janë të bllokuar.' >&2
    else
      record_possible_exposure || true
      echo 'CRITICAL: writer fence nuk u provua; rollback marker u mbyll.' >&2
    fi
  fi
  if test -n "$rollback_cookie"; then
    rm -f -- "$rollback_cookie"
  fi
  if test -n "$rollback_handoff"; then
    rm -rf -- "$rollback_handoff"
  fi
  if test -n "$release_tmp"; then
    rm -f -- "$release_tmp"
  fi
  cleanup_rollback_build || true
  if test -n "$vendor_stage"; then
    rm -rf -- "$vendor_stage"
  fi
  if test -n "$assets_stage"; then
    rm -rf -- "$assets_stage"
  fi
  exit "$status"
}
trap rollback_fail_safe EXIT
trap 'exit 129' HUP
trap 'exit 130' INT
trap 'exit 143' TERM

ensure_runtime_writer_fence
assert_runtime_writer_fence
docker info >/dev/null
assert_rollback_build_capacity
docker pull "$build_php_image" >/dev/null
docker pull "$build_node_image" >/dev/null
assert_runtime_writer_fence
assert_rollback_build_capacity
rollback_handoff="$(mktemp -d /run/lora-rollback.XXXXXXXX)"
chmod 0711 "$rollback_handoff"
rollback_integrity_file="$rollback_handoff/integrity.json"

rollback_cookie="$(mktemp /run/lora-rollback-smoke.XXXXXXXX)"
chmod 0600 "$rollback_cookie"
run_as_app php artisan down --retry=15 --secret="$rollback_secret"
systemctl stop "$queue_service"
queue_state="$(systemctl show --property=ActiveState --value "$queue_service")"
queue_pid="$(systemctl show --property=MainPID --value "$queue_service")"
case "$queue_state:$queue_pid" in
  inactive:0|failed:0) ;;
  *) rollback_fail "queue nuk u ndal ($queue_state/$queue_pid)." ;;
esac
hard_stop_cron || rollback_fail 'cron nuk u ndal para DB rollback.'
if test -e "$scheduler_file"; then
  test ! -e "$scheduler_hold"
  mv "$scheduler_file" "$scheduler_hold"
fi
run_as_app php artisan schedule:interrupt
for attempt in $(seq 1 30); do
  if scheduler_process_is_absent; then
    break
  fi
  test "$attempt" -ne 30 \
    || rollback_fail 'scheduler është ende aktiv pas 30 sekondash.'
  sleep 1
done
hard_stop_php_fpm || rollback_fail 'PHP-FPM nuk u hard-fence para DB rollback.'
assert_runtime_writer_fence
~~~

### Ndalim absolut për rollback të DB-së

Nëse state përmban WRITERS_MAY_HAVE_RUN=1, mos ekzekuto migrate:rollback. Edhe nëse
deploy-i dështoi pak pas marker-it, queue, scheduler ose HTTP mund të kenë shkruar.
Mbaje sistemin down dhe zgjidh forward-fix ose restore të backup-it të verifikuar.
Restore mund të humbasë transaksione pas snapshot-it.

Edhe pa writer marker, mos bëj rollback automatik nëse mungon RELEASE_BATCH, batch-i
aktual nuk përputhet, ose DDL ka dështuar pjesërisht. Trigger/FK auto-commit në MySQL;
kërkohet inventarizim/cleanup nga DBA ose restore.

Vetëm kur writer marker mungon dhe state-i është i plotë:

~~~bash
assert_runtime_writer_fence
test "$(git rev-parse HEAD)" = "$CANDIDATE_COMMIT"
current_batch="$(run_as_app php artisan tinker --execute='echo DB::table("migrations")->max("batch") ?? 0;')"
test "$current_batch" = "$RELEASE_BATCH"

install -o root -g "$app_user" -m 0640 \
  "$INTEGRITY_SNAPSHOT" "$rollback_integrity_file"

if test "$RELEASE_BATCH" -gt "$PREVIOUS_BATCH"; then
  assert_runtime_writer_fence
  run_as_app php artisan migrate:rollback --batch="$RELEASE_BATCH" --force
fi
run_as_app php artisan tenants:verify-integrity --compare="$rollback_integrity_file"
rm -f -- "$rollback_integrity_file"

assert_rollback_build_capacity
build_image="$build_state_dir/.rollback-${PRE_DEPLOY_COMMIT}-$$.img"
build_mount="/run/lora-rollback-build-${PRE_DEPLOY_COMMIT:0:12}-$$"
fallocate -l "$build_workspace_bytes" "$build_image"
chmod 0600 "$build_image"
build_loop="$(losetup --find --show "$build_image")"
mkfs.ext4 -q -F "$build_loop"
install -d -o root -g root -m 0700 "$build_mount"
mount -o nodev,nosuid "$build_loop" "$build_mount"
build_home="$build_mount"
build_root="$build_home/source"
install -d -o root -g root -m 0755 "$build_root"
git archive --format=tar "$PRE_DEPLOY_COMMIT" \
  | tar --extract --no-same-owner --directory="$build_root"
test ! -e "$build_root/.env"
chown -R "$build_uid:$build_gid" "$build_home"
chmod 0700 "$build_home"
run_composer_build install --no-dev --no-interaction --prefer-dist --no-progress \
  --no-audit --no-scripts --no-plugins --optimize-autoloader
run_npm_build dependencies bridge ci --ignore-scripts --no-audit --no-fund
run_npm_build assets none run build -- --mode=production
test -d "$build_root/vendor"
test ! -L "$build_root/vendor"
test -d "$build_root/public/build"
test ! -L "$build_root/public/build"
! find "$build_root/public/build" -type l -print -quit | grep -q .
grep -q -- "-${PRE_DEPLOY_COMMIT:0:12}" "$build_root/public/build/manifest.json"
rm -rf -- "$build_root/node_modules"
chown -R root:root "$build_root/vendor" "$build_root/public/build"
chmod -R go-w "$build_root/vendor" "$build_root/public/build"
vendor_stage="/var/www/villamucho/.rollback-vendor-${PRE_DEPLOY_COMMIT}-$$"
assets_stage="/var/www/villamucho/public/.rollback-assets-${PRE_DEPLOY_COMMIT}-$$"
cp -a -- "$build_root/vendor" "$vendor_stage"
cp -a -- "$build_root/public/build" "$assets_stage"
cleanup_rollback_build \
  || rollback_fail 'workspace-i i kufizuar nuk u pastrua plotësisht.'

git reset --hard "$PRE_DEPLOY_COMMIT"
if git ls-files -s | grep -q '^120000 '; then
  echo 'Tracked symlinks nuk lejohen në rollback.' >&2
  exit 1
fi
while IFS= read -r -d '' path; do
  test -f "$path"
  test ! -L "$path"
  chown --no-dereference root:root -- "$path"
  chmod go-w -- "$path"
done < <(git ls-files -z)
find . -xdev \
  \( -path './storage' -o -path './bootstrap/cache' \
     -o -path './vendor' -o -path './public/build' \) -prune \
  -o -type d -exec chown root:root -- {} +
find . -xdev \
  \( -path './storage' -o -path './bootstrap/cache' \
     -o -path './vendor' -o -path './public/build' \) -prune \
  -o -type d -exec chmod go-w -- {} +
rm -rf -- vendor node_modules public/build
mv -- "$vendor_stage" vendor
vendor_stage=
mv -- "$assets_stage" public/build
assets_stage=
run_as_app php artisan package:discover --ansi
run_as_app php artisan config:cache
run_as_app php artisan route:cache
run_as_app php artisan view:cache
run_as_app php artisan queue:restart
test "$(git rev-parse HEAD)" = "$PRE_DEPLOY_COMMIT"
run_as_app php artisan migrate:status --pending=1 --no-ansi | grep -q 'No pending migrations'

chown -R www-data:www-data storage bootstrap/cache
release_tmp="$release_dir/.current-${PRE_DEPLOY_COMMIT}-$$"
install -o root -g "$app_user" -m 0640 /dev/null "$release_tmp"
printf '%s\n' "$PRE_DEPLOY_COMMIT" > "$release_tmp"
mv -- "$release_tmp" "$release_file"
test "$(stat -c '%U:%G:%a' "$release_file")" = root:www-data:640

[ -f deploy/nginx/fastcgi-buffers.conf ]
[ ! -L deploy/nginx/fastcgi-buffers.conf ]
test "$(stat -c '%U:%G' deploy/nginx/fastcgi-buffers.conf)" = root:root
printf '%s  %s\n' \
  '0b3156320c5e3076ec300d6abaad7005650ebfae0cce835df1c7ac167c1775dd' \
  deploy/nginx/fastcgi-buffers.conf | sha256sum --check --strict
install -m 0644 deploy/nginx/fastcgi-buffers.conf \
  /etc/nginx/conf.d/villamucho-fastcgi-buffers.conf
nginx -t
systemctl reload nginx

# Ky është ekspozimi i parë i kodit të rikthyer ndaj HTTP. Nga ky çast,
# rollback tjetër i DB-së refuzohet pa restore/forward-fix të kontrolluar.
record_possible_exposure \
  || rollback_fail 'marker-i i ekspozimit nuk u ruajt; state është karantinuar.'
remove_runtime_fence "$php_fpm_service" "$php_fpm_fence_file"
test "$(systemctl show --property=LoadState --value "$php_fpm_service")" = loaded
timeout 30s systemctl start "$php_fpm_service"
systemctl is-active --quiet "$php_fpm_service"
test -S "$php_fpm_socket"
~~~

Testo `/up` nën maintenance bypass. Nëse commit-i i vjetër përmban endpoint-in,
`/up/release` duhet të kthejë ekzakt `PRE_DEPLOY_COMMIT`; për release-in e parë që
shton endpoint-in, verifiko `git rev-parse HEAD` dhe suffix-in e manifest-it.

Vetëm pas smoke-it:

~~~bash
app_url="$(run_as_app php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo rtrim((string) config("app.url"), "/");')"
case "$app_url" in https://*) ;; *) exit 1 ;; esac
curl --fail --silent --show-error --proto '=https' --proto-redir '=https' --tlsv1.2 \
  --cookie-jar "$rollback_cookie" "$app_url/$rollback_secret" >/dev/null
curl --fail --silent --show-error --proto '=https' --tlsv1.2 \
  --cookie "$rollback_cookie" "$app_url/up" >/dev/null

if run_as_app php artisan route:list --path=up/release --json | grep -q '"uri":"up/release"'; then
  test "$(curl --fail --silent --show-error --proto '=https' --tlsv1.2 \
    --cookie "$rollback_cookie" "$app_url/up/release")" = "$PRE_DEPLOY_COMMIT"
else
  test "$(git rev-parse HEAD)" = "$PRE_DEPLOY_COMMIT"
  grep -q -- "-${PRE_DEPLOY_COMMIT:0:12}" public/build/manifest.json
fi
rm -f -- "$rollback_cookie"
remove_runtime_fence "$queue_service" "$queue_fence_file"
test "$(systemctl show --property=LoadState --value "$queue_service")" = loaded
systemctl start "$queue_service"
test ! -e "$scheduler_file"
scheduler_hold_is_safe
mv "$scheduler_hold" "$scheduler_file"
test "$(stat -c '%U:%G:%a' "$scheduler_file")" = root:root:644
remove_runtime_fence "$cron_service" "$cron_fence_file"
timeout 30s systemctl start "$cron_service"
systemctl is-active --quiet "$queue_service"
systemctl is-active --quiet "$cron_service"
run_as_app php artisan up

curl --fail --silent --show-error --proto '=https' --tlsv1.2 "$app_url/up" >/dev/null
if run_as_app php artisan route:list --path=up/release --json | grep -q '"uri":"up/release"'; then
  test "$(curl --fail --silent --show-error --proto '=https' --tlsv1.2 \
    "$app_url/up/release")" = "$PRE_DEPLOY_COMMIT"
fi

rollback_succeeded=true
rm -rf -- "$rollback_handoff"
trap - EXIT HUP INT TERM
~~~

Ky bllok duhet ekzekutuar në të njëjtën root shell me blloqet e mësipërme, që
`EXIT trap` të mbetet aktiv deri pas smoke-it publik. Mos vazhdo me rollback tjetër
të DB-së nëse ndonjë hap dështon.
