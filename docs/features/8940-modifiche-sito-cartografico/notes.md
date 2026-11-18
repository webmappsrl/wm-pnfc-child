> Ticket: oc:8940

# Notes — Modifiche sito cartografico

## Divergenze dal piano, task per task

### Task 3 icone dei social

Il piano (vincoli globali) indicava le classi `fa fa-facebook-f`, `fa fa-instagram`… In Font Awesome
5 le icone dei marchi stanno nella famiglia «Font Awesome 5 Brands» e vogliono la classe `fab`: con
`fa` la riga dei social era vuota. Si è visto solo nel browser; ora `check-contact-shortcode.php`
controlla `class="fab fa-instagram"`. Anche l'header del sito usa `fab`.

Il titolo della card è un `<h2 class="wm_info_details_title">` e le label finiscono con «:», come nel
markup di wm-package delle tracce: così il titolo prende da Impreza lo stesso carattere e colore.

### Task 4 galleria solo con CSS

La cornice fissa della galleria è fatta solo nel `style.css` (`aspect-ratio: 20 / 21` sulla
`.royalSlider`, `object-fit: cover` sulle foto, tutto fuori da `.rsFullscreen`). Il CSS batte gli
stili inline della royalSlider, e in fullscreen restano le sue regole, con la foto intera. Per questo
il template Impreza 4186 non si tocca (Task 5, passo 3 saltato).

### Task 4 contrasto di frecce e pallini

Frecce e pallini sono arancioni `#F39200`: sulle foto il contrasto scende a 1,19:1 su cielo azzurro
(rgb 135,190,235) e 2,35:1 su bianco, sotto il 3:1 di WCAG 2.1 – 1.4.11. Un contorno scuro
(`drop-shadow` nero) li separa dalla foto: nero su cielo azzurro circa 11:1.

### Task 4 versione del CSS del child theme

Impreza carica lo `style.css` del child theme (handle `theme-style`) con la propria versione,
`?ver=8.22`, e il server di produzione non manda né `Cache-Control` né `Expires`: dopo il rilascio i
browser avrebbero potuto tenere il CSS vecchio per settimane, con la card nuova senza stile.
`wm_pnfc_child_style_version()` imposta come versione la data di modifica del file.

### Task 6 libreria della migrazione

La logica della migrazione sta in `scripts/social-migration-lib.php`, richiesta sia da
`migrate-social-links.php` sia da `check-migration.php`: il controllo deve poterla eseguire sulle sole
guide di prova. Togliendo il blocco `core/social-links` si toglie anche la riga vuota che lo
separava dal blocco successivo.

### Task 7 traduzioni con uno script

Le traduzioni inglesi si scrivono con `scripts/apply-translations.php` invece che a mano in WPML,
così in produzione si ripetono uguali. Due stringhe già esistenti del tema («Lingue parlate», «Aree
operative») erano registrate in WPML con lingua di origine inglese, anche se il testo è italiano, e
WPML ignorava la loro traduzione inglese: lo script porta la loro lingua di origine a `it` e rigenera
i file di `wp-content/languages/wpml/` (`wpml_st_language_of_strings_changed`,
`wpml_st_refresh_domain`).

### Task 5 placeholder delle immagini

Modifica chiesta da Rubens dopo la prova nel browser: il disegno di Impreza (sole e montagne) non
andava bene. Il placeholder è l'icona del Parco su fondo verde (`icon.png` delle pubblicazioni
Webmapp per il PNFC, copiata in `assets/images/guide-placeholder.png`), importata nella libreria
media da `apply-db-changes.php` e impostata nell'opzione `img_placeholder` di Impreza. Il logo
dell'header era stato scartato perché esiste solo a 148×77 px. Poi Rubens ha chiesto di usarlo solo
nelle card: nella pagina della guida, senza immagine in evidenza, non compare nulla
(`.single-guide .w-image:has(img.g-placeholder)`; con un browser senza `:has()` si vedrebbe ancora
l'icona). L'allegato si riconosce dal meta
`_oc8940_guide_placeholder`, così un secondo lancio non lo importa di nuovo.

### Task 3 card «In breve»

Modifica chiesta da Rubens dopo la prova con un Riassunto vero (Martina Miscioscia, 232 caratteri):
dentro la card «Contatti» non andava bene. Il Riassunto sta ora in una card separata «In breve», con
lo stesso stile, tra la galleria e «Contatti». Scartata la soluzione a schede cliccabili nella stessa
card: nasconderebbe i contatti finché non si clicca e richiederebbe JavaScript e gestione da
tastiera.

### Task 3 contatore del Riassunto

Aggiunta chiesta da Rubens dopo la prova: un contatore dei caratteri sotto il «Riassunto»
(`assets/js/guide-excerpt-counter.js`, caricato solo nell'editor delle guide). In WordPress 6.8 il
Riassunto si scrive in un popover della barra laterale: lo slot `PluginPostExcerpt` (in 6.8 ancora
`wp.editPost.__experimentalPluginPostExcerpt`) lì non arriva, e il testo passa all'editor solo
all'uscita dal campo. Il contatore si aggancia quindi al campo con un `MutationObserver` e conta il
suo valore a ogni `input`. Soglia consigliata: 150 caratteri (indicazione di Yoast per la meta
description, circa 155). La traduzione inglese della frase del contatore è nello script delle
traduzioni ma non è stata verificata con un admin in inglese.

## Bug trovati

- **Galleria vuota (esisteva già in produzione):** senza foto nella galleria, `us_image_slider` di
  Impreza non sparisce ma mostra 3 immagini qualsiasi della libreria media
  (`us-core/templates/elements/image_slider.php`, `'numberposts' => empty( $ids ) ? 3 : -1`): oggi tre
  loghi. Riguarda 31 guide su 59 (es. Stefano Severi, Massimo Guerra). Ora le guide senza foto
  esistenti hanno la classe `wm-guide-no-gallery` sul `body` e la galleria è nascosta.

- Il salvataggio del meta box cancellava ogni campo assente dal form (`delete_post_meta`): un editor
  aperto con un form vecchio avrebbe cancellato dati. Corretto per tutti e nove i campi.
- Prima di oc:8940 un campo telefono/email/sito svuotato restava salvato come stringa vuota; ora
  viene cancellato.

## Review indipendente prima del rilascio

Nessun problema critico. Corretti prima del rilascio, ognuno con un controllo che prima falliva:

- `sanitize_text_field` / `sanitize_textarea_field` toglievano le sequenze `%XX` dagli indirizzi
  (es. `citt%C3%A0` → `citt`) al salvataggio della guida: social e campo «Url:» ora passano da
  `wp_strip_all_tags` e, per i social, da `esc_url_raw` nella normalizzazione. Il difetto sul campo
  «Url:» esisteva già prima di oc:8940.
- La migrazione andava avanti anche se il JSON del prima non si scriveva, e con un JSON illeggibile lo
  riscriveva perdendo le voci precedenti: ora si ferma prima di toccare la guida
  (`oc8940_read_before`, `oc8940_write_before`); lo stesso in `apply-db-changes.php`.
- Scritture della migrazione: prima i meta, poi il contenuto; valori a `update_post_meta` con
  `wp_slash` (le barre inverse non si perdono).
- `apply-db-changes.php` verifica dopo l'apply layout 4201, filtro della pagina 4177 e placeholder.

Lasciati com'erano, consapevolmente:

- La modalità «ripristina» rimette il contenuto e i meta di prima della migrazione: se il Parco ha
  modificato una delle 31 guide dopo la migrazione, quelle modifiche si perdono. Va usata subito, non
  giorni dopo.
- `[guide_contatti id="…"]` mostra i dati anche di guide in bozza o private: era già così per i meta,
  serve qualcuno che possa inserire shortcode.

## Decisioni

- `develop` esisteva già su GitHub, fermo a `fa72618` (gennaio 2026), due commit dietro `main`. Creando
  il branch del ticket è stato portato a `6e71a29` con un fast-forward: nessun commit riscritto.
- Indirizzi social scritti male: si correggono, mai si perdono (richiesta di Rubens). Una riga non
  riconoscibile resta salvata nel meta, non compare nella pagina e sotto il campo compare l'avviso.
  Un nome di profilo con il punto (`romagna.selvatica`) è trattato come nome di profilo, non come
  dominio, se non inizia con `www.` e non finisce con un dominio comune (`.com`, `.it`, `.org`,
  `.net`, `.eu`, `.me`, `.tv`).
- Social in pagina, modifica chiesta da Rubens dopo la prova nel browser: con le sole icone due
  profili dello stesso social non si distinguevano (il `title` sul telefono non si vede). Ora c'è una
  riga «Social» della card con un profilo per riga, icona e nome del profilo; «Facebook» quando
  l'indirizzo non contiene un nome (`profile.php?id=…`).
- Gli script in `docs/…/scripts/` finiscono nel docroot di produzione con il `git pull` del tema:
  ognuno esce subito se non gira in WP-CLI.
- Yoast usa il Riassunto (`post_excerpt`) sia per `meta name="description"` sia per
  `og:description`: compilato, il testo compare anche nei risultati di Google e nelle anteprime dei
  link condivisi. Senza Riassunto `og:description` usa il testo della guida.

- Rilascio: niente avviso al Parco prima del rilascio (decisione di Rubens, 18/11). Contro la perdita
  di dati: backup del database del giorno subito prima, JSON del prima con «ripristina», e
  `scripts/verify-migration.php`, che dopo la migrazione confronta guida per guida prima e dopo con
  controlli indipendenti dalla logica della migrazione (provato anche con una perdita simulata).

## Dati verificati sul sito di produzione (18/11/2026)

- Migrazione in prova sulla copia locale: 31 guide da modificare, 6 correzioni di indirizzo: i 5
  link scritti male più il campo «Url:» della 4925 (5171
  Instagram e Facebook senza `https://`, 5145 spazio finale, 4944 solo `wilder_trekk`, 4925 senza
  `https://` nel blocco e nel campo «Url:»), nessuna guida da controllare.
- La guida 4925 (Alberto Naldini) ha lo stesso Instagram sia nel blocco social sia nel campo «Url:».

## Follow-up

- Campo «Url:» (`_guide_sito`): in 5201 (Marta Signi) contiene due siti in una riga
  (`www.altertrek.it - www.gliamicidellasino.it`) e il link è rotto; 12 siti non hanno `https://`
  (funzionano perché `esc_url` aggiunge `http://`).
- Le frecce arancioni delle gallerie delle tracce (`.wm_gallery`, Swiper) hanno lo stesso problema di
  contrasto delle guide (WCAG 2.1 – 1.4.11).
- `configure_guide_contact_fields_for_wpml` chiama `save_settings()` di WPML a ogni `admin_init`, anche
  quando non cambia nulla.
- Identificatori in italiano già presenti nel `functions.php` (`$telefono`, `$abilitazioni`,
  `guide_contatti_shortcode`…): non rinominati, fuori dallo scope.
