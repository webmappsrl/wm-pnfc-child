> Ticket: oc:8940

# Modifiche sito cartografico — piano di implementazione

> **Per chi esegue:** sotto-skill `superpowers:executing-plans` (o `superpowers:subagent-driven-development`),
> task per task. I passi usano le checkbox (`- [ ]`).

**Obiettivo:** pagina guida con social, Riassunto e card dei contatti nello stile delle tracce di
`wm-package`; galleria e card della griglia con cornici uniformi; filtro «Attività proposte»; social
migrati dal contenuto ai meta.

**Architettura:** il codice sta nel child theme (`functions.php`, `style.css`). Le modifiche al
database (template e pagine Impreza, stringhe WPML, contenuto delle guide) passano da script
`wp eval-file` versionati in `docs/features/8940-modifiche-sito-cartografico/scripts/`, ripetibili
nella copia locale e in produzione. Si prova tutto nella copia Docker del sito.

**Stack:** WordPress 6.8.8, PHP 8.1, MySQL 8.0.46, Impreza 8.22 (WPBakery, Font Awesome Pro
5.15.4), WPML 4.9.7 con String Translation, Yoast 25.3, WP-CLI.

**Spec:** [overview.md](overview.md)

## Vincoli globali

- Identificatori del codice in inglese (funzioni, variabili, meta key, nomi dei file). Testi rivolti a
  persone in italiano, con `__()` e text domain `wm-pnfc-child`.
- Il repo è pubblico: niente indirizzi del server, alias ssh, percorsi del server, credenziali né
  dati della copia locale nei file del repo. Quei dati e i comandi completi del server stanno nel
  `CLAUDE.md` privato della cartella del sito (`Wordpress/pnfc/CLAUDE.md`, creato nel Task 1).
- Nessun commit, push o branch senza il via esplicito di Rubens: i comandi git del piano sono
  istruzioni.
- Branch: `develop` creato da `main`; lavoro su `feature/oc-8940-modifiche-sito-cartografico`; PR
  verso `develop` con squash; `develop` → `main` con merge commit.
- Produzione solo con il via esplicito di Rubens, dopo il backup del lavoro.
- Meta dei social: `_guide_facebook`, `_guide_instagram`, `_guide_linkedin`, `_guide_x`,
  `_guide_youtube`, `_guide_tiktok`; URL separati da `\n`.
- Ordine dei social, ovunque: Facebook, Instagram, LinkedIn, X, YouTube, TikTok.
- Icone: `fa fa-facebook-f`, `fa fa-instagram`, `fa fa-linkedin-in`, `fa fa-youtube`,
  `fa fa-tiktok`; X come SVG inline.
- Le verifiche girano nella copia Docker con
  `docker compose exec -T -w <docroot> web wp --allow-root …` (comando esatto nel `CLAUDE.md`
  privato).

## Punti da guardare in review

1. **Indirizzo social scritto male** (senza `https://`, con spazi ai bordi, solo il nome del profilo):
   va corretto, mai perso. Una riga che resta non valida resta salvata e viene segnalata. Casi veri
   del 18/11: 5171, 5145, 4944, 4925. Test nel Task 2 e nel Task 6.
2. **Guida senza contatti né tassonomie, o con le sole tassonomie**: nessuna card vuota, oppure una card
   con le sole tassonomie. Test nel Task 3.
3. **Guida inglese** (traduzione WPML) con meta e Riassunto vuoti: mostra i valori della guida
   italiana, label in inglese. Test nel Task 3 e verifica nel Task 7.
4. **Editor aperto con il form vecchio**: un salvataggio senza i nuovi campi nel `$_POST` non li
   cancella. Test nel Task 2.
5. **Script rilanciato** (dopo un salvataggio che ha rimesso il blocco, oppure due volte di fila): non
   duplica indirizzi e non tocca guide già a posto. Test nel Task 6.

---

### Task 1: Ambiente di lavoro

**File:**
- Crea: `Wordpress/pnfc/CLAUDE.md` (privato, fuori dal repo)
- Crea: `Wordpress/pnfc/backup/php8-AAAA-MM-GG-oc8940/` (backup completo)
- Crea: `Wordpress/pnfc/docker-oc8940/` (copia locale, si cancella a fine lavoro)

**Interfacce:**
- Produce: copia locale su `http://localhost:8870` con un utente di prova amministratore; comando
  WP-CLI locale scritto nel `CLAUDE.md` privato.

- [ ] **Passo 1: `CLAUDE.md` privato del sito.** Stessa struttura di quello di acquasorgente: il sito
  (server, docroot, versioni, database, plugin, WP-CLI), le cartelle, la procedura per ogni ticket
  (backup, Docker, lavoro in locale, produzione con `git pull` del child theme, pulizia), le trappole,
  le regole sui ticket, lo storico dei lavori. Dati verificati il 18/11/2026: WordPress 6.8.8,
  PHP 8.1, MySQL 8.0.46, database `pnfc`, sito di 1,2 GB, child theme clone git di
  `webmappsrl/wm-pnfc-child` su `main`.
- [ ] **Passo 2: backup completo.** File del sito (tar.gz), dump del database (`--single-transaction`,
  `utf8mb4`), configurazione Apache del sito. Atteso: `gzip -t` del dump ok, numero di file nel tar
  scritto nel `CLAUDE.md`.
- [ ] **Passo 3: copia Docker.** `php:8.1-apache` con le estensioni di acquasorgente, `mysql:8.0.46`,
  porta `8870:80`, docroot con lo stesso percorso del server. Import del dump, `DB_HOST` → `db`,
  `wp search-replace` del dominio verso `http://localhost:8870` (`--skip-columns=guid`), utente di
  prova amministratore (credenziali solo nel `CLAUDE.md` privato).
- [ ] **Passo 4: controllo della copia.** `curl` con risposta 200 su `/`, `/guide-escursionistiche/`,
  `/guide-escursionistiche/stefano-severi/`, `/track/1-trek-01-sul-cozzo-del-diavolo/`, `/en/`.
- [ ] **Passo 5: branch (istruzione per Rubens).**

```bash
git checkout main && git pull --ff-only
git checkout -b develop && git push -u origin develop
git checkout -b feature/oc-8940-modifiche-sito-cartografico
```

- [ ] **Passo 6: il tema della copia locale** punta alla copia di lavoro del repo (bind mount su
  `wp-content/themes/wm-pnfc-child`), così ogni modifica si vede subito su `localhost:8870`.

---

### Task 2: Campi social nel meta box e salvataggio

**File:**
- Modifica: `functions.php` (meta box `render_guide_contact_meta_box`, `save_guide_contact_info`,
  `configure_guide_contact_fields_for_wpml` e filtro `wpml_custom_field_translation_settings`)
- Test: `docs/features/8940-modifiche-sito-cartografico/scripts/check-contact-fields.php`

**Interfacce:**
- Produce:
  - `wm_pnfc_guide_social_networks(): array` — chiave del social ⇒
    `['label' => string, 'meta_key' => string, 'icon' => string]`, nell'ordine dei vincoli globali.
    Chiavi: `facebook`, `instagram`, `linkedin`, `x`, `youtube`, `tiktok`; `icon` vuota per `x`.
  - `wm_pnfc_normalize_social_url(string $line, string $network): ?string` — l'URL corretto, oppure
    `null` se la riga non è riconoscibile.
  - `wm_pnfc_parse_social_urls(string $raw, string $network): array` —
    `['valid' => string[], 'invalid' => string[]]`: URL corretti senza doppioni e righe non
    riconosciute, nell'ordine di inserimento.
  - Campi del form: `guide_social_<chiave>` (es. `guide_social_instagram`).

- [ ] **Passo 1: test che fallisce.** `check-contact-fields.php` stampa `OK`/`FAIL` per ogni
  asserzione ed esce con codice 1 se ce n'è una fallita:

```php
// wm_pnfc_normalize_social_url — casi veri del 18/11
assert_same('https://www.instagram.com/boschiromagnoli', wm_pnfc_normalize_social_url('www.instagram.com/boschiromagnoli', 'instagram'));
assert_same('https://www.instagram.com/bagnodiforesta', wm_pnfc_normalize_social_url('https://www.instagram.com/bagnodiforesta ', 'instagram'));
assert_same('https://www.instagram.com/wilder_trekk/', wm_pnfc_normalize_social_url('wilder_trekk', 'instagram'));
assert_same('https://www.instagram.com/wilder_trekk/', wm_pnfc_normalize_social_url('@wilder_trekk', 'instagram'));
assert_same('https://www.facebook.com/ltrevie/?locale=it_IT', wm_pnfc_normalize_social_url('https://www.facebook.com/ltrevie/?locale=it_IT', 'facebook'));
assert_same(null, wm_pnfc_normalize_social_url('due parole', 'instagram'));
// wm_pnfc_parse_social_urls
assert_same(['valid' => ['https://www.instagram.com/altrevie/', 'https://www.instagram.com/eticaselvatica/'], 'invalid' => []],
    wm_pnfc_parse_social_urls("https://www.instagram.com/altrevie/\n\n https://www.instagram.com/eticaselvatica/ \n", 'instagram'));
assert_same(['valid' => ['https://x.com/a'], 'invalid' => ['due parole']],
    wm_pnfc_parse_social_urls("https://x.com/a\nhttps://x.com/a\ndue parole", 'x'));
// salvataggio: con un utente amministratore, nonce valido, su una guida di prova creata dal test
//  - $_POST con guide_social_instagram = due righe  → meta = le due righe unite da "\n"
//  - $_POST con una riga valida e «due parole»       → meta con tutte e due le righe (nulla si perde)
//  - $_POST con guide_social_instagram = ''         → meta cancellato
//  - $_POST senza la chiave guide_social_instagram  → meta invariato
//  - $_POST senza la chiave guide_telefono          → _guide_telefono invariato
```

  La guida di prova viene creata e cancellata (`wp_delete_post(…, true)`) dal test stesso, solo nella
  copia locale.
- [ ] **Passo 2: eseguire e vedere fallire.**
  `wp eval-file docs/features/8940-modifiche-sito-cartografico/scripts/check-contact-fields.php`
  Atteso: `FAIL` (funzioni non definite).
- [ ] **Passo 3: implementazione.**
  - `wm_pnfc_normalize_social_url`: `trim`; un nome di profilo da solo (senza `/` né `.`, con o senza
    `@`, senza spazi) diventa `https://<dominio del social>/<nome>/` (domini: `www.facebook.com`,
    `www.instagram.com`, `www.linkedin.com/in`, `x.com`, `www.youtube.com/@`, `www.tiktok.com/@`);
    senza `http(s)://` si aggiunge `https://`; poi `esc_url_raw`; `null` se dopo le correzioni l'host
    non ha un punto o la riga contiene spazi.
  - `wm_pnfc_parse_social_urls`: una riga alla volta, righe vuote ignorate, doppioni tolti.
  - Meta box: una `textarea` (3 righe) per social sotto «Url:», con la label del social e la
    descrizione `__('Un indirizzo per riga', 'wm-pnfc-child')`.
  - Salvataggio: per i nove campi, presente e vuoto → `delete_post_meta`; presente e pieno →
    `update_post_meta`; assente → nessuna azione. Social salvati come righe `valid` seguite dalle
    righe `invalid` così come sono state scritte (nessun dato perso), uniti da `\n`.
  - Meta box: sotto ogni `textarea` con righe non riconosciute, l'avviso
    `__('Indirizzo non riconosciuto:', 'wm-pnfc-child')` seguito dalle righe. In pagina compaiono solo
    le righe `valid`.
  - WPML: i sei meta aggiunti all'elenco dei campi impostati su «Translate» (2), con la stessa logica
    dei tre esistenti.
- [ ] **Passo 4: eseguire e vedere passare.** Stesso comando. Atteso: solo `OK`, codice 0.
- [ ] **Passo 5: prova in admin.** Nella copia locale, guida Marta Bellante: le sei textarea compaiono
  sotto «Url:»; salvando con due Instagram il meta ha due righe; il form ricaricato le mostra.

---

### Task 3: Card dei contatti, Riassunto e social in `[guide_contatti]`

> ⚠️ L'implementazione ha deviato da questo task: [notes.md](notes.md#task-3-icone-dei-social)
> ⚠️ L'implementazione ha deviato da questo task: [notes.md](notes.md#task-3-contatore-del-riassunto)
> ⚠️ L'implementazione ha deviato da questo task: [notes.md](notes.md#task-3-card-in-breve)

**File:**
- Modifica: `functions.php` (`guide_contatti_shortcode`, label della tassonomia `guide_abilitazioni`)
- Test: `docs/features/8940-modifiche-sito-cartografico/scripts/check-contact-shortcode.php`

**Interfacce:**
- Consuma: `wm_pnfc_guide_social_networks()`, meta del Task 2.
- Produce:
  - `wm_pnfc_social_profile_name(string $url): string` — ultimo segmento non vuoto del percorso,
    senza `@` (es. `https://www.facebook.com/ltrevie/?locale=it_IT` → `ltrevie`); host se il percorso
    è vuoto.
  - `wm_pnfc_x_icon_svg(): string` — SVG inline del logo X, `aria-hidden="true"`, `fill="currentColor"`.
  - Markup:

```html
<div class="wm_guide_contact_info">
  <div class="wm_info_details">
    <div class="wm_info_details_title">Contatti</div>
    <div class="wm_guide_excerpt">…</div>
    <div class="wm_info_details_grid">
      <div class="wm_info_detail_item">
        <span class="wm_info_detail_label"><i class="fa fa-phone" aria-hidden="true"></i> Telefono</span>
        <span class="wm_info_detail_value"><a href="tel:…">…</a></span>
      </div>
      … email, sito …
      <div class="wm_info_detail_item wm_guide_social">
        <a href="…" target="_blank" rel="noopener noreferrer" title="Instagram: altrevie" aria-label="Instagram: altrevie"><i class="fa fa-instagram" aria-hidden="true"></i></a>
        …
      </div>
      … Attività proposte, Lingue parlate, Aree operative …
    </div>
  </div>
</div>
```

- [ ] **Passo 1: test che fallisce.** `check-contact-shortcode.php` crea guide di prova, chiama
  `do_shortcode('[guide_contatti id="…"]')`, verifica e cancella le guide:
  - guida con telefono, Riassunto «Riga 1\nRiga 2», due Instagram e un X: l'output contiene, in
    quest'ordine, `wm_guide_excerpt`, `fa-phone`, `fa-instagram` due volte, l'SVG di X; il Riassunto
    contiene `Riga 1<br />` ed è passato da `esc_html` (un `<b>` nel Riassunto esce come `&lt;b&gt;`);
    i link social hanno `rel="noopener noreferrer"` e `aria-label="Instagram: altrevie"`;
  - guida senza meta, Riassunto e termini: output `''`;
  - guida con i soli termini di `guide_lingue`: card con la sola riga «Lingue parlate»;
  - `wm_pnfc_social_profile_name('https://www.facebook.com/ltrevie/?locale=it_IT') === 'ltrevie'`;
  - traduzione inglese di una guida di prova (via `wpml_set_element_language_details`) con meta e
    Riassunto vuoti: l'output contiene il telefono e il Riassunto della guida italiana.
- [ ] **Passo 2: eseguire e vedere fallire.** Atteso: `FAIL`.
- [ ] **Passo 3: implementazione.** Riassunto da `post_excerpt` grezzo (mai `get_the_excerpt()`, che
  genererebbe un estratto dal contenuto), con lo stesso ripiego sulla guida originale dei meta. Label
  con `__()`: «Contatti», «Telefono», «Email», «Sito», «Attività proposte», «Lingue parlate», «Aree
  operative». Le icone delle tassonomie restano quelle di oggi (`fa-certificate`, `fa-language`,
  `fa-map-marker-alt`). Label della tassonomia `guide_abilitazioni`: `name` «Attività proposte»,
  `singular_name` «Attività proposta»; slug invariato.
- [ ] **Passo 4: eseguire e vedere passare.** Atteso: solo `OK`.

---

### Task 4: Stile della card, della galleria e della griglia

> ⚠️ L'implementazione ha deviato da questo task: [notes.md](notes.md#task-4-galleria-solo-con-css)

**File:**
- Modifica: `style.css` (sostituisce le regole `.wm_guide_contact_*` di righe 314-354; aggiunge le
  regole della galleria e della griglia)

**Interfacce:**
- Consuma: markup del Task 3; classe `pnfc-hiking-gallery` del template 4186; layout 4201 del Task 5.

- [ ] **Passo 1: card.** Regole `.wm_info_details`, `.wm_info_details_title`, `.wm_info_details_grid`,
  `.wm_info_detail_item`, `.wm_info_detail_label`, `.wm_info_detail_value` copiate da
  `wm-package-default.css` (versione 2.0) e prefissate con `.wm_guide_contact_info`. Più
  `.wm_guide_excerpt` (padding come gli item, testo normale) e `.wm_guide_social` (icone in fila,
  spazio 12 px, area cliccabile di almeno 24×24 px).
  Le regole `.wm_guide_contact_*` di oggi vengono tolte.
- [ ] **Passo 2: galleria.** `.pnfc-hiking-gallery`: cornice `aspect-ratio: 20 / 21` (circa 381×400 a
  1440 px), foto con `object-fit: cover`, `border-radius: 8px`, frecce e pallini sopra la foto, colori
  quelli già usati per `.rsArrow` e `.rsBullet`. Le regole non valgono in fullscreen
  (`.rsFullscreen`), dove la foto resta intera. Se la royalSlider impone dimensioni inline che il CSS
  non batte, si passa al Task 5, Passo 3 (`img_fit` del template).
- [ ] **Passo 3: card della griglia.** Sulle card del layout 4201 (classe del layout, da leggere nel
  markup): `border-radius: 10px`, `overflow: hidden`, sollevamento `translateY(-5px)` e ombra
  `0 4px 8px rgba(0,0,0,.1)` su `:hover` e `:focus-within` (WCAG 2.1 – 2.4.7).
- [ ] **Passo 4: verifica visiva nel browser**, a 320, 1024, 1280, 1440, 1920 px: guida con galleria
  verticale (prima foto verticale), guida senza galleria, guida con più profili (Martina Miscioscia),
  archivio `/guide-escursionistiche/`. Contrasto di frecce e pallini sulle foto calcolato
  (WCAG 2.1 – 1.4.11, almeno 3:1): se non basta, contorno o ombra scura sotto l'icona.
- [ ] **Passo 5: le pagine delle tracce non cambiano.** Screenshot di
  `/track/1-trek-01-sul-cozzo-del-diavolo/` prima e dopo, a 1440 px: identici.

---

### Task 5: Modifiche al database (template, layout, filtro, stringhe)

> ⚠️ L'implementazione ha deviato da questo task: [notes.md](notes.md#task-4-galleria-solo-con-css)
> ⚠️ L'implementazione ha deviato da questo task: [notes.md](notes.md#task-5-placeholder-delle-immagini)

**File:**
- Crea: `docs/features/8940-modifiche-sito-cartografico/scripts/apply-db-changes.php`

**Interfacce:**
- Uso: `wp eval-file apply-db-changes.php <cartella-backup> [dry-run|apply]`. Senza argomenti:
  `dry-run`.

- [ ] **Passo 1: salvataggio del prima.** In `apply`, prima di ogni modifica: `post_content` di 4186,
  4201 e 4177 e i valori delle stringhe WPML toccate in `<cartella-backup>/db-before-oc8940.json`.
  Se il file esiste già non viene sovrascritto (un secondo `apply` non perde il prima vero).
- [ ] **Passo 2: layout 4201.** Nel JSON di `post_image:1`: `has_ratio` → `1`, `ratio` → `"1x1"`,
  `placeholder` → `1`. Nessun altro campo cambia.
- [ ] **Passo 3: template 4186.** Solo se il Task 4, Passo 2 lo ha reso necessario:
  `[us_image_slider … img_fit="cover"]`.
- [ ] **Passo 4: pagina 4177.** Nel `filter_items` di `[us_grid_filter]` (JSON url-encoded), la label
  dell'elemento con `source` `tax|guide_abilitazioni` da «Abilitazioni» ad «Attività proposte».
  Stringa WPML `page-builder-shortcode-strings-4177` aggiornata di conseguenza.
- [ ] **Passo 5: eseguire.** `dry-run` stampa le differenze; `apply` le scrive; un secondo `apply`
  stampa «nessuna modifica». Nella copia locale: griglia con card 1:1 e placeholder, filtro «Attività
  proposte» nella pagina 4177 (privata, da utente loggato).

---

### Task 6: Script di migrazione dei social

> ⚠️ L'implementazione ha deviato da questo task: [notes.md](notes.md#task-6-libreria-della-migrazione)

**File:**
- Crea: `docs/features/8940-modifiche-sito-cartografico/scripts/migrate-social-links.php`
- Test: `docs/features/8940-modifiche-sito-cartografico/scripts/check-migration.php`

**Interfacce:**
- Consuma: `wm_pnfc_guide_social_networks()`, `wm_pnfc_parse_social_urls()` (Task 2).
- Uso: `wp eval-file migrate-social-links.php <cartella-backup> [dry-run|apply|restore] --user=<admin>`.
  Senza modalità: `dry-run`.
- Produce: `<cartella-backup>/social-migration-before.json` — id della guida ⇒
  `['post_content' => string, 'meta' => [meta_key => string|null]]`.

- [ ] **Passo 1: test che fallisce.** `check-migration.php`, solo nella copia locale, su guide di prova:
  - contenuto «paragrafo + `core/social-links` (instagram, facebook) + paragrafo»: dopo `apply` i due
    meta sono pieni, il blocco non c'è più, i due paragrafi sono identici byte per byte;
  - due Instagram nel blocco: `_guide_instagram` con due righe;
  - un servizio non previsto (`mastodon`) insieme a Instagram: meta Instagram pieno, nel contenuto
    resta un `core/social-links` con il solo `mastodon`, la guida è nell'elenco «da controllare»;
  - `_guide_instagram` già con un indirizzo diverso: dopo `apply` ha i due indirizzi;
  - `_guide_sito` = `www.instagram.com/prova`: passa in `_guide_instagram` come
    `https://www.instagram.com/prova`, `_guide_sito` cancellato;
  - `_guide_sito` e blocco con lo stesso Instagram (caso 4925): un solo indirizzo nel meta;
  - link nel blocco `wilder_trekk` (instagram) e `www.facebook.com/x`: nei meta
    `https://www.instagram.com/wilder_trekk/` e `https://www.facebook.com/x`;
  - link nel blocco «due parole» (instagram): resta in un `core/social-links` con il solo link non
    riconosciuto, guida in «da controllare»;
  - secondo `apply`: «0 guide modificate»;
  - blocco rimesso nel contenuto dopo il primo `apply`, poi `apply`: blocco tolto, nessun doppione nei
    meta;
  - `restore`: `post_content` e meta identici a prima del primo `apply`.
- [ ] **Passo 2: eseguire e vedere fallire.** Atteso: `FAIL`.
- [ ] **Passo 3: implementazione.**
  - Guide: `post_type = guide`, tutti gli stati tranne `trash` e `auto-draft`, tutte le lingue
    (`suppress_filters`).
  - Lettura dei blocchi con `parse_blocks`; si lavora solo sui `core/social-links` di primo livello.
    Un `core/social-links` annidato fa finire la guida in «da controllare» senza toccarla.
  - Contenuto nuovo con `serialize_blocks` dei blocchi rimasti; scrittura con
    `wp_update_post(wp_slash([...]))`, eseguito con `--user` di un amministratore (niente filtri kses).
  - Meta: unione di quelli esistenti con quelli trovati, via `wm_pnfc_parse_social_urls`; i link
    `invalid` restano nel blocco come i servizi non previsti.
  - L'output elenca ogni correzione fatta a un indirizzo (prima → dopo).
  - JSON del prima scritto prima della prima modifica; voci di guide già presenti nel file non
    sovrascritte.
  - Output: per ogni guida toccata id, titolo e social migrati; poi l'elenco «da controllare»;
    poi il totale.
- [ ] **Passo 4: eseguire e vedere passare.** Atteso: solo `OK`.
- [ ] **Passo 5: sui dati veri della copia locale.** `dry-run`: le 31 guide con blocco (la 4925 è tra
  queste), le tre guide con più profili (5216, 5175, 5025) con due indirizzi per social, le 5
  correzioni di indirizzo (5171 ×2, 5145, 4944, 4925) e nessuna guida «da controllare». `apply`, poi controllo
  nel browser delle pagine di Martina Miscioscia, Nicola Busi, Alberto Naldini. `restore` e di nuovo
  `apply`, per provare il ritorno.

---

### Task 7: Lingua inglese e Yoast

> ⚠️ L'implementazione ha deviato da questo task: [notes.md](notes.md#task-7-traduzioni-con-uno-script)

**File:** nessun file del repo; stringhe in WPML String Translation (database).

- [ ] **Passo 1:** scansione del child theme in WPML String Translation; traduzioni inglesi: Contatti
  → Contacts, Telefono → Phone, Email → Email, Sito → Website, Attività proposte → Proposed activities,
  Attività proposta → Proposed activity, Lingue parlate → Spoken languages, Aree operative →
  Operating areas, Un indirizzo per riga → One address per line. Le stesse traduzioni si rifanno in
  produzione (Task 9): vanno nel `notes.md` con i passi esatti.
- [ ] **Passo 2:** la guida inglese pubblicata mostra la card con le label inglesi e i dati della guida
  italiana dove i suoi sono vuoti.
- [ ] **Passo 3: Yoast.** Su una guida con Riassunto compilato, leggere nel sorgente della pagina
  `og:description` e `meta name="description"`. Scrivere l'esito in `notes.md`: se il Riassunto ci
  finisce, va detto nella risposta al cliente.

---

### Task 8: Verifica completa in locale e approvazione

- [ ] **Passo 1:** tutti gli script di controllo di nuovo: solo `OK`.
- [ ] **Passo 2:** `php -l functions.php`; nessun avviso PHP nel log di Apache della copia durante la
  navigazione delle pagine provate.
- [ ] **Passo 3:** Rubens confronta su `http://localhost:8870` le pagine elencate nel Task 4, Passo 4,
  in italiano e in inglese, e approva.
- [ ] **Passo 4:** `notes.md` aggiornato con le divergenze dal piano e con l'esito dei controlli di
  contrasto e Yoast.

---

### Task 9: Commit, PR e produzione

Ogni passo solo dopo il via di Rubens. Comandi del server nel `CLAUDE.md` privato.

- [ ] **Passo 1: commit e PR.**

```bash
git add functions.php style.css docs/features/8940-modifiche-sito-cartografico/
git commit -m "feat(oc:8940): social, Riassunto e card dei contatti nella pagina guida"
git push -u origin feature/oc-8940-modifiche-sito-cartografico
gh pr create --base develop --title "oc:8940 modifiche sito cartografico"
```

  PR chiusa con «Squash and merge». Poi `develop` → `main` con merge commit
  (`git merge --no-ff develop`), dopo aver controllato `git log origin/develop..origin/main`.
- [ ] **Passo 2: avviso al Parco** di non modificare guide nella finestra del rilascio (testo
  approvato da Rubens).
- [ ] **Passo 3: backup del giorno** del database (stesso comando del Task 1, cartella del giorno del
  rilascio).
- [ ] **Passo 4: codice.** Sul server, nel child theme: `git status` pulito, poi `git pull --ff-only`.
  `curl` 200 su home, archivio guide, una guida, una traccia; 302 su `/wp-admin/`. Con un 500:
  `git checkout <commit di prima>` nel child theme.
- [ ] **Passo 5: database.** `apply-db-changes.php` in `dry-run`, controllo, `apply`. Traduzioni WPML
  del Task 7.
- [ ] **Passo 6: migrazione.** `migrate-social-links.php` in `dry-run` sui dati del giorno; Rubens
  guarda l'elenco; `apply`; poi `verify-migration.php` sulla stessa cartella: atteso «nessun dato
  perso». Il JSON del prima resta nella cartella del backup.
- [ ] **Passo 7: controllo in produzione.** Pagine di Martina Miscioscia, Nicola Busi, Alberto Naldini,
  Stefano Severi, l'archivio e la guida inglese.
- [ ] **Passo 8: dopo qualche giorno**, `migrate-social-links.php` in `dry-run`: se un editor rimasto
  aperto ha rimesso un blocco, compare qui e si rilancia `apply`.
- [ ] **Passo 9: pulizia** della copia Docker quando Rubens lo dice (procedura nel `CLAUDE.md`
  privato). Restano `backup/` e `docs/`.
