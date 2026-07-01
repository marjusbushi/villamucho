# Channex — Runbook: nga Testi (staging) → Prodhim + lidhja e një OTA

> Udhëzues i plotë për të kaluar integrimin e Channex-it nga sandbox-i (ku është provuar)
> në **prodhim**, dhe për të lidhur një OTA reale (Expedia / Booking.com / Airbnb).
> **Kodi është 100% i ndërtuar dhe live.** Ky dokument është vetëm **procedura operative**
> (llogaritë, çelësat, panelet). Villa Mucho, `admin.villamucho.com`.

---

## 0. Çfarë është gati (ura teknike — s'ka pse ndryshohet)

| Pjesa | Çfarë bën | Statusi |
|------|-----------|---------|
| Foundation | `App\Services\ChannexClient` + tabelat `channel_mappings`/`channel_sync_logs` + komandat | ✅ live |
| Copa 2 (PMS→Channex) | `App\Services\ChannelSync` + job `PushRoomTypeAri` + observer/pricing triggers → çmimet & liria shkojnë **vetë, në çast** | ✅ live |
| Copa 3 (OTA→PMS) | `POST /channex/webhook` + `App\Services\ChannexBookingImporter` → rezervimet OTA **hyjnë vetë** | ✅ live |
| Copa 3.5 (pagesa) | Kap `payment_collect` → "Paguar online" vs "Paguhet në hotel"; mysafiri s'paguan dy herë | ✅ live |
| Infra (server) | systemd `villamucho-queue` (punëtori) + cron `/etc/cron.d/villamucho-scheduler` (scheduler) | ✅ xhiron |

**Komandat artisan** (në server, `/var/www/villamucho`):
- `php artisan channex:ping` — provon lidhjen (liston pronat).
- `php artisan channex:link-rooms` — lidh dhomat e PMS-së me ato te Channex (me emër).
- `php artisan channex:push-ari [--days=365] [--queue]` — shtyn disponueshmërinë + çmimet.
- `php artisan channex:pull-bookings` — merr rezervimet e reja nga feed-i (catch-up).

---

## 1. Referencë: si e bëmë në TEST (staging) — që ta përsërisim

- **Llogari:** `staging.channex.io`, pronë **Villa Mucho**, id `e543d52a-822a-4966-bb69-b1ef399825d1`.
- **Server `.env`:** `CHANNEX_API_KEY` + `CHANNEX_BASE_URL=https://staging.channex.io/api/v1` + `CHANNEX_PROPERTY_ID` + `CHANNEX_WEBHOOK_SECRET`.
- **8 dhomat** u krijuan te Channex (identike me PMS) dhe u lidhën (`channel_mappings`).
- **Webhook** u regjistrua: id `49302cb1-4588-4cb6-b23b-bc3fe7783c0d` → `https://villamucho.com/channex/webhook`.
- **Verifikuar live:** lidhja OK, ARI push OK, webhook fail-closed (pa sekret → 403).
- **Bllokimi:** Expedia në staging → *"Channel is not Available"* — sepse **sandbox-i s'lidh OTA reale** (pritej).

Kontrata e verifikuar (mos e harro):
- Çmimi te Channex është në **cent** (80 € = `8000`).
- Rate plan `per_room` = **saktësisht 1 opsion**.
- ARI push është **async** (200 → task në sfond).
- Rezervimi vjen: webhook `{event, payload:{revision_id}}` → `GET /booking_revisions/:id` → import → `POST .../ack`.

---

## 2. RRUGA NË PRODHIM (hapat me radhë)

### Hapi 1 — Hap llogari PRODHIMI te Channex *(TI)*
1. Shko te **`app.channex.io`** (jo staging), regjistrohu (plani me pagesë, ~$30–50/muaj).
2. Krijo pronën **Villa Mucho** (timezone Europe/Tirane, currency EUR).
3. Merr: **API key** (prodhimi) + **Property ID** (te *Properties*).
4. **MOS e ngjit çelësin live në chat** — ma jep me OK-në për ta futur unë te `.env`, ose e fut ti.

### Hapi 2 — Drejto aplikacionin te prodhimi *(UNË)*
Te server `.env` (`/var/www/villamucho/.env`), ndrysho në vlerat e prodhimit:
```
CHANNEX_API_KEY="<çelësi-i-prodhimit>"
CHANNEX_PROPERTY_ID=<property-id-i-prodhimit>
CHANNEX_BASE_URL=https://app.channex.io/api/v1     # ose hiqe fare (default = prodhimi)
CHANNEX_WEBHOOK_SECRET="<sekret-i-ri>"             # gjenero një të ri për prodhimin
```
Pastaj: `php artisan config:clear` (ose `config:cache`).

### Hapi 3 — Rikrijo + lidh + shtyj dhomat te prodhimi *(UNË)*
1. Krijo 8 tipet e dhomave + rate plans te Channex prodhimi (me API, si te testi).
2. `php artisan channex:link-rooms` → lidh (mbush `channel_mappings`).
3. `php artisan channex:push-ari` → shtyn disponueshmërinë reale + çmimet sezonale.
4. Verifiko: `php artisan channex:ping` → duhet të listojë Villa Mucho.

### Hapi 4 — Regjistro webhook-un e prodhimit *(UNË)*
Regjistro `POST /webhooks` me `callback_url = https://villamucho.com/channex/webhook`,
`event_mask = booking`, `send_data = true`, header `X-Channex-Webhook-Secret = <sekreti-i-ri>`.
Verifiko live: `POST` pa sekret → **403**, me sekret jo-booking → **200**.

### Hapi 5 — Lidh OTA-n (Expedia) — proces DY-ANËSH *(TI, unë të udhëzoj)*
> Kjo është pjesa që dështoi në staging. Në prodhim bëhet kështu:

**(a) Ana e Expedia-s — Expedia Partner Central:**
- Hyr te **Expedia Partner Central** (llogaria jote e Expedia-s për Villa Mucho).
- Shko te **Connectivity Settings** (ose "Manage connectivity provider").
- Zgjidh / kërko **Channex** si "connectivity provider" për hotelin **`38729421`**.
- Expedia e **aktivizon** lidhjen (ndonjëherë brenda pak orësh / kërkon konfirmim me email).

**(b) Ana e Channex-it — dashboard prodhimi:**
- Channels → **Create Channel** → Channel: **Expedia** → Property: Villa Mucho → Currency: EUR.
- **Hotel ID:** `38729421`.
- **Test Connection** → do bëhet **jeshile** vetëm PASI Expedia ta ketë aktivizuar nga ana e vet (hapi a).

**(c) Mapimi (te tab-i "Mapping"):** *(UNË të ndihmoj)*
- Lidh çdo **tip dhome + rate plan të Expedia-s** me tipin/rate plan-in tonë te Channex.
- Ruaj.

### Hapi 6 — Verifiko + dil live *(TI + UNË)*
- "Test Connection" jeshile ✅.
- Bëj (ose prit) një **rezervim test/real** → duhet të shfaqet **vetë** te PMS-ja jote (Rezervimet), me burimin "Expedia", brenda sekondash.
- **Kujdes në fillim:** lidh **një kanal të vetëm**, ndiq rezervimet e para nga afër, dhe **mban një dhomë rezervë** (mos shit dhomën e fundit te OTA-t derisa të kesh besim).

---

## 3. Infrastruktura (tashmë xhiron — vetëm për kontroll)
- **Punëtori:** `systemctl status villamucho-queue` → `active`. Proceson job-et në çast.
- **Scheduler:** `/etc/cron.d/villamucho-scheduler` → çdo minutë `schedule:run`:
  - `model:prune` (pastron logun > 90 ditë), `channex:pull-bookings` /15min, `channex:push-ari` natën 04:00.
- **Log-et e sinkronizimit:** tabela `channel_sync_logs` (vetëm ID/ref, pa PII).

---

## 4. Kush bën çfarë
| Detyra | Kush |
|--------|------|
| Llogari prodhimi Channex + Property | **Ti** |
| Kërkesa e lidhjes te Expedia Partner Central | **Ti** (unë s'kam akses) |
| Çelësi te `.env`, dhomat, mapimi, webhook, verifikimi | **Unë** |
| Klikimet te dashboard-i i Channex/Expedia | **Ti** (unë të udhëzoj) |

---

## 5. Gjëra për të mbajtur mend (mësime)
- **Testi (sandbox) s'lidh OTA reale** — për Expedia/Booking duhet **prodhim**.
- **Lidhja e OTA-s është dy-anëshe** — Expedia duhet të aktivizojë Channex nga ana e vet, s'mjafton Hotel ID-ja.
- **Statistika vizitorësh/shikimesh:** JO nga Channex — te paneli i vetë OTA-s (Expedia Partner Central "Performance", Booking "Analytics"). Ne marrim **rezervimet**, jo trafikun.
- **Pagesa:** rezervimet "Paguar online" (payment_collect=ota) → mysafiri s'paguan dhomën në hotel; "Paguhet në hotel" → paguan te recepsioni. Sistemi e trajton vetë.
- **Sekreti i webhook-ut është fail-closed** — pa të, endpoint-i s'pranon asgjë (siguri).
- **Çelësat kurrë në chat/git** — vetëm te `.env` në server.
- **Komisioni:** ruhet te `reservations.commission_amount`; "Neto" = total − komision.

---

## 6. Kontroll i shpejtë "a punon gjithçka" (prodhim)
```bash
ssh villamucho
cd /var/www/villamucho
php artisan channex:ping                 # duhet: connection OK, Villa Mucho
systemctl is-active villamucho-queue     # duhet: active
php artisan channex:push-ari             # duhet: OK për 8 dhomat
# webhook (nga jashtë):
curl -s -o /dev/null -w '%{http_code}' -X POST https://villamucho.com/channex/webhook -d '{"event":"ari"}'   # duhet: 403
```

*Përditësuar: 2026-07-01. Referencë kodi: commits `a9e3c13` (foundation) · `e7b76ef` (copa 2) · `990e3d4` (copa 3) · `5ba0f31` (pagesa).*
