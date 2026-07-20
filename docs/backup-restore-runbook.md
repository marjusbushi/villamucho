# Lora PMS — backup off-server dhe restore drill

## Arkitektura e aprovuar

- Production mbetet në Hetzner.
- Kopja e enkriptuar ruhet në bucket-in privat Backblaze B2 EU
  `lora-pms-prod-eu-backup-7c14a9`.
- Endpoint-i S3 është `s3.eu-central-003.backblazeb2.com`.
- Restic enkripton çdo backup në client para upload-it; bucket-i ka edhe SSE-B2.
- Object Lock është aktiv në bucket-in burim, por retention default mbahet i çaktivizuar.
  Backblaze paralajmëron se retention-i default në një repository aktiv backup-i mund
  të shkaktojë humbje të paparashikueshme të të dhënave gjatë mirëmbajtjes së tij.
- Kopja immutable vendoset në një bucket të dytë B2 me retention `compliance` dhe
  Cloud Replication nga bucket-i burim. Kështu Restic punon normalisht në burim,
  ndërsa replica nuk mund të fshihet para skadimit të retention-it.
- Bucket-i destinacion `lora-pms-prod-eu-immutable-7c14a9` u krijua privat më
  2026-07-14 me SSE-B2, Object Lock dhe retention default 30 ditë `compliance`.
- Cloud Replication `lora-prod-immutable-30d` kopjon automatikisht nga
  `lora-pms-prod-eu-backup-7c14a9` te bucket-i immutable, duke përfshirë edhe
  skedarët ekzistues. Rregulli u aktivizua më 2026-07-14.
- Snapshot-i test `a2473916` u krijua pas aktivizimit dhe skedarët e tij të rinj
  u shfaqën në bucket-in immutable, duke verifikuar rrjedhën fund-më-fund.
- Backblaze replikon skedarët ekzistues në ciklin ditor pas mesnatës UTC. Numri
  i objekteve burim/destinacion kontrollohet pas këtij cikli para se 9.2 të
  shënohet i përfunduar.

## Secrets në server

Secrets e backup-it nuk futen në git, chat ose `.env` të aplikacionit. Ato ruhen vetëm në:

- `/etc/lora-backup/restic.env` — `root:root`, mode `0600`;
- `/etc/lora-backup/restic-password` — `root:root`, mode `0600`;
- `/etc/lora-backup/mysql.cnf` — credential i dedikuar vetëm për backup,
  `root:root`, mode `0600`.

Çelësat Laravel Passport ruhen jashtë release-it në `/etc/lora-passport`, directory
`root:www-data` mode `0750`; `oauth-private.key` dhe `oauth-public.key` janë
`root:www-data` mode `0440`. Production `.env` duhet të ketë ekzaktësisht një herë:

```text
PASSPORT_PRIVATE_KEY=file:///etc/lora-passport/oauth-private.key
PASSPORT_PUBLIC_KEY=file:///etc/lora-passport/oauth-public.key
```

Rehearsal-i i parë adopton çiftin ekzistues nga `storage/` pa e rrotulluar; gjeneron
një çift të ri vetëm kur të dy skedarët mungojnë dhe refuzon gjendje të pjesshme ose
çelësa që nuk përputhen. Çelësat realë nuk futen në git ose në këtë dokument.

Çdo snapshot përfshin edhe `application-key.txt` si file `root:root 0600` brenda
repository-t të enkriptuar nga Restic. Ky file nuk printohet në log dhe mbulohet nga
`SHA256SUMS`; pa të nuk mund të dekriptohen kolonat Laravel `encrypted:*`, përfshirë
`tenant_integrations.credentials`. `APP_KEY` duhet të mbahet edhe në password manager
si escrow i dytë, i ndarë nga fjalëkalimi Restic.

Shembulli i strukturës (pa vlera reale):

```bash
AWS_ACCESS_KEY_ID=<restricted-bucket-key-id>
AWS_SECRET_ACCESS_KEY=<restricted-bucket-application-key>
RESTIC_REPOSITORY=s3:s3.eu-central-003.backblazeb2.com/lora-pms-prod-eu-backup-7c14a9
RESTIC_PASSWORD_FILE=/etc/lora-backup/restic-password
RESTIC_CACHE_DIR=/var/cache/restic
```

Application Key duhet të ketë akses vetëm te ky bucket. Master Key nuk përdoret.
Fjalëkalimi Restic ruhet edhe në password manager; humbja e tij e bën backup-in të
parikuperueshëm.

MySQL event scheduler duhet të jetë globalisht `OFF` ose `DISABLED`; një event i
përcaktuar në një schema tjetër mund të shkruajë në production, prandaj kontrolli vetëm
i `event_schema` nuk mjafton. Konfiguroje si administrator dhe verifikoje:

```sql
SET PERSIST event_scheduler = OFF;
SELECT @@GLOBAL.event_scheduler;
```

Llogaria MySQL e backup-it duhet të lidhet me të njëjtin server/schema si aplikacioni
dhe të ketë vetëm allowlist-in e mëposhtëm. `EVENT` përdoret për të dump-uar definicionet,
jo si provë që scheduler-i është i ndalur. Shembull me placeholder-a:

```sql
CREATE USER 'lora_backup'@'localhost' IDENTIFIED BY '<password-from-vault>';
GRANT SELECT, SHOW VIEW, TRIGGER, EVENT ON `<production_database>`.* TO 'lora_backup'@'localhost';
GRANT SHOW_ROUTINE ON *.* TO 'lora_backup'@'localhost';
```

`/etc/lora-backup/mysql.cnf` përmban vetëm seksionin `[client]` me `user`,
`password` dhe `socket` ose `host`/`port`; vlera reale nuk futet në git.

Më 2026-07-14, fjalëkalimi i rikuperimit u ruajt si hyrje personale në Zoho Vault
me emrin `Lora PMS Production — Restic Recovery`. Vlera sekrete nuk ruhet në git
ose në këtë dokument.

## Instalimi në production

Instalimi production u krye më 2026-07-14. Komandat e riprodhueshme janë:

```bash
sudo apt-get update
sudo apt-get install -y default-mysql-client restic rsync util-linux
sudo install -d -m 0700 -o root -g root /etc/lora-backup /var/lib/lora-backup /var/cache/restic
sudo install -m 0700 -o root -g root /var/www/villamucho/ops/backup/run-offsite-backup.sh /usr/local/sbin/lora-offsite-backup
sudo cp /var/www/villamucho/ops/backup/lora-backup.service /etc/systemd/system/
sudo cp /var/www/villamucho/ops/backup/lora-backup.timer /etc/systemd/system/
sudo systemctl daemon-reload
```

Pas vendosjes së secrets:

```bash
sudo bash -c 'set -a; source /etc/lora-backup/restic.env; set +a; restic init'
sudo systemctl start lora-backup.service
sudo systemctl status lora-backup.service
sudo systemctl enable --now lora-backup.timer
sudo systemctl list-timers lora-backup.timer
```

## Çfarë ruhet

- dump MySQL me transaction konsistente;
- `storage/app/private` (dokumente private të tenant-ëve);
- `storage/app/public` (logo dhe imazhe);
- snapshot PII-free me numra rekordesh dhe totale financiare;
- `APP_KEY` në `application-key.txt`, vetëm brenda snapshot-it Restic të enkriptuar;
- çiftin Passport në `passport/oauth-private.key` dhe `passport/oauth-public.key`,
  `root:root` mode `0600`, të lidhur me checksum dhe fingerprint publik në metadata;
- SHA-256 për dump-in dhe snapshot-in.

Gjatë snapshot-it, PHP-FPM dhe queue bllokohen me systemd runtime fences, cron-i
ndalet, scheduler-i mbahet në një file root-only dhe aplikacioni qëndron në maintenance.
Storage lidhet në bind-mount-e read-only të izoluara dhe skanohet edhe destinacioni.
Skedarët dhe DB-ja kapen vetëm pasi fence verifikohet; writer-at rikthehen e
verifikohen para `artisan up` për backup-et normale të timer-it.

Gjatë një production release, deploy-i krijon një request root:root 0600 me nonce
unik dhe mban `production-release.lock`. Ai pre-armon start-fences për PHP-FPM,
queue dhe cron. Pas upload-it dhe `restic check`, backup-i publikon atomikisht një
ready marker root:root 0600 me `snapshot_id`, kohën reale të snapshot-it, kohën e
upload-it dhe identitetet e shërbimeve. Në këtë mode writer-at nuk rifillojnë:
maintenance, scheduler hold dhe të tre fences i kalojnë deploy-it pa boshllëk.
Request/ready marker-at nuk krijohen kurrë manualisht. Një dështim para ready
marker-it rifillon versionin e vjetër; një handoff aktiv ose i paqartë mbetet
fail-closed për operator recovery.

Script-i dështon pa upload nëse integriteti multitenant/storage nuk kalon, MySQL event
scheduler është aktiv, mungon ndonjë storage root, ekziston nested mount/symlink, dump-i është
bosh, config-u lexohet nga përdorues të tjerë ose një backup tjetër është në punë.

Për RPO afër zeros pa maintenance të gjatë, hapi afatgjatë është MySQL PITR me
ROW binlog + GTID të replikuar të enkriptuar offsite, së bashku me storage të
versionuar/replikuar. DB PITR vetëm nuk rikthen upload-et e skedarëve.

## Restore drill i detyrueshëm

Restore-i nuk bëhet mbi production. Përdoret një host ose container i izoluar:

1. `restic check --read-data` për lexim të plotë të repository-t.
2. Zgjidh nga `restic snapshots --host <production-host> --tag lora-production`
   snapshot-in e saktë 64-karakterësh dhe ekzekuto
   `restic restore <snapshot-id> --target /restore/lora`; mos përdor `latest` pa e pin-uar ID-në.
3. Në folderin `run.*`, ekzekuto `sha256sum -c SHA256SUMS`, pastaj
   `(cd storage && sha256sum -c ../storage-SHA256SUMS)`. Nëse manifesti storage është
   bosh, verifiko që edhe `storage/app` nuk përmban asnjë file.
4. Verifiko që `application-key.txt` është `0600`, ka vetëm një rresht dhe përdore
   vetëm si `APP_KEY` në ambientin e izoluar; mos e printo.
5. Nëse kandidati përdor Passport, verifiko që të dy skedarët e restauruar në
   `passport/` janë `root:root 0600`, formojnë të njëjtin çift dhe fingerprint-i
   publik përputhet me `passport_public_key_sha256` në `metadata.txt`.
6. Krijo databazë MySQL testuese bosh dhe importo `database.sql`.
7. Konfiguro një checkout të të njëjtit commit kundrejt DB-së së restauruar dhe
   monto ose kopjo ekzaktësisht `storage/app/private` dhe `storage/app/public` të
   restauruara te disqet `local`/`public` të checkout-it.
8. Lexo të paktën një model `TenantIntegration` me `credentials` jo-null dhe provo
   që cast-i `encrypted:array` dekriptohet pa nxjerrë vlerën në terminal/log. Nëse
   nuk ka rreshta të tillë, bëj një encrypt/decrypt round-trip në container.
9. Ekzekuto `php artisan tenants:verify-integrity --verify-storage --snapshot=/tmp/before.json`.
10. Ekzekuto migrimet kandidate me `php artisan migrate --force`.
11. Ekzekuto `php artisan tenants:verify-integrity --verify-storage --compare=/tmp/before.json --allow-additive-schema --allow-additive-settings`.
   Kjo lejon vetëm tabela të reja, rritje të permissions dhe settings të reja
   për tenantët ekzistues; fshirjet dhe totalet financiare duhet të mbeten identike.
12. Kontrollo manualisht rezervime, pagesa, financë, POS dhe skedarë.
13. Fshi ambientin testues, `application-key.txt`, kopjet e çelësave Passport dhe
    regjistro datën/rezultatin.

Restore drill-i është porta kryesore e sigurisë. Hapi 12 mbyllet pasi, përveç këtij
testi, të aktivizohen replica immutable dhe alarmi i jashtëm për dështimet.

## Restore drill — 2026-07-14

- Snapshot-i Restic `182c2066` u lexua plotësisht me `restic check --read-data`.
- SHA-256 i dump-it dhe metadata-s kaloi pa gabime.
- Dump-i production u importua në MySQL 8.4 të izoluar.
- Kontrolli i integritetit kaloi para migrimeve.
- Të gjitha migrimet e branch-it u aplikuan mbi kopjen production.
- Krahasimi me `--allow-additive-schema` konfirmoi se numrat dhe totalet financiare
  ekzistuese nuk ndryshuan; u lejuan vetëm tabelat dhe permissions e reja.
- Container-i, kopja me PII dhe secrets e përkohshme u fshinë pas testit.

Timer-i production është aktiv dhe ekzekutohet çdo natë rreth orës 02:38
`Europe/Tirane`. Gjendja kontrollohet me:

```bash
sudo systemctl status lora-backup.timer
sudo cat /var/lib/lora-backup/last-success
```
