# Lora PMS — backup off-server dhe restore drill

## Arkitektura e aprovuar

- Production mbetet në Hetzner.
- Kopja e enkriptuar ruhet në bucket-in privat Backblaze B2 EU
  `lora-pms-prod-eu-backup-7c14a9`.
- Endpoint-i S3 është `s3.eu-central-003.backblazeb2.com`.
- Restic enkripton çdo backup në client para upload-it; bucket-i ka edhe SSE-B2.
- Object Lock është aktiv, por retention-i final vendoset vetëm pasi backup/restore
  të provohet, sepse një periudhë e gabuar nuk mund të shkurtohet për objektet e kyçura.

## Secrets në server

Secrets nuk futen në git, chat ose `.env` të aplikacionit. Ato ruhen vetëm në:

- `/etc/lora-backup/restic.env` — `root:root`, mode `0600`;
- `/etc/lora-backup/restic-password` — `root:root`, mode `0600`.

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

## Instalimi në production

Këto komanda ekzekutohen vetëm pasi branch-i të kalojë review/testet:

```bash
sudo apt-get update
sudo apt-get install -y restic
sudo install -d -m 0700 -o root -g root /etc/lora-backup /var/lib/lora-backup /var/cache/restic
sudo chmod 0700 /var/www/villamucho/ops/backup/run-offsite-backup.sh
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
- SHA-256 për dump-in dhe snapshot-in.

Script-i dështon pa upload nëse integriteti multitenant nuk kalon, dump-i është
bosh, config-u lexohet nga përdorues të tjerë ose një backup tjetër është në punë.

## Restore drill i detyrueshëm

Restore-i nuk bëhet mbi production. Përdoret një host ose container i izoluar:

1. `restic check --read-data` për lexim të plotë të repository-t.
2. `restic restore latest --tag lora-production --target /restore/lora`.
3. Verifiko `sha256sum -c SHA256SUMS` në folderin e run-it të restauruar.
4. Krijo databazë MySQL testuese bosh dhe importo `database.sql`.
5. Konfiguro një checkout të të njëjtit commit kundrejt DB-së së restauruar.
6. Ekzekuto `php artisan tenants:verify-integrity --snapshot=/tmp/before.json`.
7. Ekzekuto migrimet kandidate me `php artisan migrate --force`.
8. Ekzekuto `php artisan tenants:verify-integrity --compare=/tmp/before.json`.
9. Kontrollo manualisht rezervime, pagesa, financë, POS dhe skedarë.
10. Fshi ambientin testues dhe regjistro datën/rezultatin e drill-it.

Hapi 9 i planit mbyllet vetëm kur ky restore real kalon pa gabime.
