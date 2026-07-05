# POK Pagesat me Kartë — Runbook: nga Testi (staging) → Prodhim

> Qëllimi: mysafirët në **villamucho.com** paguajnë rezervimin me kartë **brenda faqes**
> (forma embedded e POK-ut), me parapagim të plotë të detyrueshëm. Ky dokument është
> lista e saktë e hapave për ta kaluar LIVE — dhe si kthehemi mbrapsht po të duhet.
>
> Gjendja e testuar: git tag **`pok-ready-for-prod`** (dega `feat/pok-payments`).

---

## 0. Çfarë është gati (ura teknike — s'ka pse ndryshohet)

- **Flow i plotë, i testuar në staging me kartat e testit:**
  rezervim → krijohet porosia POK (EUR, autoCapture) → `/book/pay/{token}` hap formën
  embedded (të dhënat e mysafirit të para-mbushura, karta e vetmja gjë që futet) →
  pagesa → verifikim në server (shuma + valuta + statusi te POK, kurrë s'i besojmë
  browser-it) → rezervimi **confirmed** + pagesa "card" në folio.
- **Mbrojtjet:** webhook idempotent (s'konfirmon dy herë), rezervim i anuluar s'ringjallet,
  shumë e gabuar refuzohet, `UNIQUE pok_order_id`, rimbursimi në POK e liron dhomën vetë,
  cron-i `pok:release-unpaid` (çdo 5 min) pyet **POK-un i pari** para se të lirojë një dhomë
  të papaguar — një mysafir që ka paguar s'anullohet KURRË.
- **17 teste automatike** (`tests/Feature/PokPaymentTest.php`) + prova live në staging.
- **Config:** `config/services.php` blloku `pok` — çelësat vijnë VETËM nga `.env` i serverit.
- **SDK:** forma ngarkohet nga CDN zyrtare e POK-ut (`static.pokpay.io/.../pok-payment.js`).
  KURRË mos e kthe te importi npm i bundle-uar me Vite — e prish formën (mësim i dokumentuar).
  Nëse forma dështon në browser, mysafiri kalon vetë te faqja e sigurt e POK-ut (fallback).

## 1. Referencë: si u provua në TEST

- Çelësat e testit (`api-staging.pokpay.io`) + kartat `4242 4242 4242 4242` (pa 3DS)
  dhe `4000 0000 0000 1091` (me 3DS), skadencë e ardhshme, CVV çfarëdo.
- Pagesa u krye embedded brenda faqes; rezervimi doli **confirmed** me pagesë në folio.

## 2. RRUGA NË PRODHIM (hapat me radhë)

### Hapi 1 — Merr çelësat e PRODHIMIT nga POK *(TI)*
Nga llogaria/dashboard-i i POK-ut (llogari tregtari e verifikuar për Villa Mucho):
**Merchant ID**, **Key ID**, **Key Secret** të **prodhimit** (jo të testit).
⚠️ Mos i dërgo kurrë në chat, email të pambrojtur, apo git — vendosen vetëm në serverin.

### Hapi 2 — Bashkimi me `main` *(UNË, me OK-në tënde)*
`feat/pok-payments` → `main`. Push-i në `main` bën **deploy automatik** në villamucho.com
(GitHub Actions: `npm run build`, `migrate --force`, `config:cache`, `route:cache`,
`queue:restart`). Migrimet e POK-ut janë idempotente — s'prekin të dhënat ekzistuese.
NB: deri sa serveri s'ka çelësa POK, faqja vazhdon si sot (rezervim pa pagesë) — pra
bashkimi është i SIGURT edhe para Hapit 3.

### Hapi 3 — Çelësat në server *(UNË ose TI, `ssh villamucho`)*
Te `/var/www/villamucho/.env` shto/ndrysho:
```
POK_PRODUCTION=true
POK_MERCHANT_ID=<prodhimi>
POK_KEY_ID=<prodhimi>
POK_KEY_SECRET=<prodhimi>
```
Pastaj **detyrimisht**: `php artisan config:cache` (config-u është i kesh-uar — pa këtë
hap çelësat e rinj s'lexohen).

### Hapi 4 — Verifiko cron + queue *(UNË)*
- Cron-i i scheduler-it duhet të ekzistojë: `crontab -l` → një rresht
  `* * * * * cd /var/www/villamucho && php artisan schedule:run ...`
  (e mban gjallë `pok:release-unpaid` çdo 5 min — lirimin e dhomave të papaguara).
- Punëtori i radhës (queue worker) duhet të xhirojë (e përdor edhe Channex):
  `php artisan queue:work` nën supervisor/systemd — kontrollo statusin.

### Hapi 5 — Prova e zjarrit me kartë REALE *(TI + UNË)*
1. Bëj një rezervim real në villamucho.com për natën më të lirë (ose ul përkohësisht
   çmimin e një dhome te Çmimet për një datë të afërt, p.sh. €5).
2. Paguaj me kartën TËNDE reale në formën embedded.
3. Verifiko: rezervimi **confirmed** në admin + pagesa "card" në folio + kapja në
   dashboard-in e POK-ut (shuma e saktë, EUR).
4. Rimburso pagesën e provës **manualisht në dashboard-in e POK-ut** dhe anullo
   rezervimin e provës në admin.

### Hapi 6 — Dil live *(TI)*
Asgjë tjetër për të bërë — që nga Hapi 3 çdo rezervim i ri në faqe kërkon pagesë të plotë
me kartë para konfirmimit. Ndiq ditët e para në admin (rezervimet + folio).

## 3. Kush bën çfarë

| Hapi | Kush |
|---|---|
| Çelësat e prodhimit nga POK | **TI** |
| Merge → main (deploy) | **UNË** (me OK-në tënde) |
| .env + config:cache në server | **UNË** (ose TI me udhëzim) |
| Cron/queue verifikim | **UNË** |
| Prova me kartë reale + rimbursimi | **TI** (unë verifikoj në admin) |

## 4. ROLLBACK (nëse diçka shkon keq live)

**Rruga e shpejtë (30 sekonda, pa deploy):** hiq/komento 3 rreshtat `POK_*` nga `.env`
i serverit + `php artisan config:cache`. → Faqja kthehet VETË te flow-i pa pagesë
(rezervim i thjeshtë me konfirmim nga stafi) — e ndërtuar enkas si fallback i sigurt.
Rezervimet dhe pagesat e kryera mbeten të paprekura.

**Rruga e plotë:** `git revert` i merge-commit-it në `main` → deploy automatik.

## 5. Shënime operative (mbaji mend)

- **Rimbursimet bëhen MANUALISHT** në dashboard-in e POK-ut (s'ka buton në admin).
  Kur POK shënon rimbursim/chargeback, sistemi ynë e liron dhomën + anullon pagesën
  në folio automatikisht (webhook).
- **Dhoma mbahet 30 min** pa pagesë; pas ~35 min lirohet nga cron-i (që më parë pyet
  POK-un nëse është paguar vërtet).
- **Shumat janë EUR në njësi të plota** (€120 = `120`, JO qindarka) — verifikuar live;
  gabimi i njësive ngarkon 100-fish.
- **Webhook-u** regjistrohet vetë për çdo porosi (s'ka konfigurim në dashboard):
  `POST https://villamucho.com/pok/webhook` — gjithmonë kthen 200, verifikon te POK.
- **Logs:** `storage/logs/laravel.log` në server; gabimet e POK-ut raportohen aty.

## 6. Kontroll i shpejtë "a punon gjithçka" (prodhim)

```bash
ssh villamucho
cd /var/www/villamucho
php artisan tinker --execute='var_dump(app(App\Services\PokClient::class)->configured());'  # true
php artisan tinker --execute='echo config("services.pok.base_url");'                        # https://api.pokpay.io
php artisan schedule:list | grep pok                                                        # pok:release-unpaid çdo 5 min
crontab -l | grep schedule:run                                                              # cron ekziston
# webhook nga jashtë:
curl -s -o /dev/null -w "%{http_code}\n" -X POST https://villamucho.com/pok/webhook         # 200
```
