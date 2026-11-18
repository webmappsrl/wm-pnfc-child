> Ticket: oc:8940

# Modifiche sito cartografico

## Cosa cambia

Sulla pagina della singola guida (post type `guide`, Page Template Impreza 4186) la colonna destra
prende lo stile della detail view delle tracce di `wm-package`
(es. `/track/1-trek-01-sul-cozzo-del-diavolo/`). La disposizione degli elementi resta quella di oggi.

- **Galleria** (`us_image_slider` del template 4186): cornice fissa uguale per tutte le foto, con la
  foto che la riempie e un ritaglio ai bordi, angoli arrotondati, frecce e pallini arancioni sopra la
  foto. In fullscreen la foto resta intera.
- **Contatti** (`[guide_contatti]`): l'output diventa una card come `.wm_info_details` delle tracce:
  barra con il titolo «Contatti», poi un `.wm_info_detail_item` per dato (icona, label, valore).
- **Social**: sei nuovi campi nel meta box `guide_contact_info` («Informazioni di Contatto»):
  Facebook, Instagram, LinkedIn, X, YouTube, TikTok, ognuno con uno o più indirizzi (uno per riga).
  In pagina compaiono in una riga «Social» della card, un profilo per riga con icona e nome del
  profilo, solo quelli compilati.
- **Riassunto**: il `post_excerpt` della guida (campo «Riassunto» dell'editor, già supportato dal post
  type) compare in una card «In breve», con lo stesso stile, tra la galleria e la card «Contatti».
- **«Abilitazioni» diventa «Attività proposte»**: nelle label della tassonomia `guide_abilitazioni` e
  nel filtro `us_grid_filter` della pagina «Guide» (4177).
- **Card della griglia delle guide** (`us_grid_layout` 4201): immagine 1:1, angoli arrotondati e
  sollevamento al passaggio del mouse, come `.wm_grid_track_item`. Le guide senza immagine in
  evidenza mostrano nella card l'icona del Parco su fondo verde come placeholder; nella pagina della
  guida al suo posto non compare nulla.
- **Guide senza foto nella galleria**: la galleria non compare. Oggi lo slider di Impreza, senza foto,
  mostra 3 immagini qualsiasi della libreria media (31 guide su 59, anche in produzione).
- **Migrazione dei social**: uno script sposta i link dal blocco Gutenberg `core/social-links` nel
  `post_content` delle guide ai nuovi meta, e toglie il blocco.

L'ordine casuale delle griglie (`orderby="rand"`) resta com'è: il cliente chiedeva solo una conferma.

## Perché

Richieste del cliente (Davide Alberti, 05/11/2026) sulla sezione delle guide escursionistiche, che il
Parco sta popolando:

1. social della guida nella colonna destra, o in campi dedicati delle informazioni di contatto;
2. filtro «Abilitazioni» rinominato «Attività proposte»;
3. un testo libero, nella zona dei contatti, che tenga «in evidenza alcune informazioni di base»;
4. foto delle gallerie che oggi hanno cornici diverse da una guida all'altra;
5. conferma dell'ordine casuale.

Dal dialogo con il dev: lo stile della pagina guida segue quello delle pagine generate da
`wm-package` (tracce e POI), senza cambiare la posizione degli elementi.

Situazione verificata in sola lettura sul sito di produzione (18/11/2026):

- 59 guide pubblicate (58 it, 1 en).
- Il blocco `core/social-links` è in 31 guide: Instagram in 24, Facebook in 20, LinkedIn in 1
  (Nicola Busi, 5285). Nessun link a Instagram o Facebook fuori da quel blocco. Il blocco è sempre al
  primo livello del contenuto, mai dentro gruppi o colonne. Tre guide hanno due profili dello stesso
  social: Martina Miscioscia (5216), Marta Bellante (5175), Emiliano Conficoni (5025).
- Nelle 31 guide non ci sono barre inverse, `iframe`, `script` né blocchi `core/html`, e
  `serialize_blocks(parse_blocks())` restituisce il contenuto identico.
- Il post type `guide` non supporta le revisioni (`supports` senza `revisions`).
- Il meta `_guide_sito` («Url:») è compilato in 18 guide. In una (Alberto Naldini, 4925) contiene un
  profilo Instagram, lo stesso del blocco social della guida.
- Gallerie: 28 guide, 162 foto. 90 sono verticali, e in 12 gallerie la prima foto è verticale. Lo
  slider prende l'altezza dalla prima foto (381×226 px nella guida Stefano Severi a 1440 px).
- Immagini in evidenza: 40 guide su 59, tutte quadrate. La card 4201 ha il placeholder spento, quindi
  le altre 19 hanno card senza immagine e più basse.
- `post_excerpt` vuoto in tutte le guide. Né il template 4186 né il layout 4201 lo mostrano.
- `us_grid_layout` 4201 è usato solo dalle griglie delle guide: pagine 4177 e 4209 (private),
  template d'archivio 4194 e 4220.
- Il filtro `us_grid_filter` è solo nella pagina italiana 4177. La pagina inglese 4209 non ha filtri.
- Il CSS di `wm-package` (`wm-package-default.css`) non viene caricato sulle pagine delle guide.
- Impreza 8.22 carica Font Awesome Pro 5.15.4: ci sono `fa-facebook-f`, `fa-instagram`,
  `fa-linkedin-in`, `fa-youtube`, `fa-tiktok`, ma non l'icona di X.

## Requisiti

### Meta box e salvataggio (`functions.php`)

- [ ] Il meta box `guide_contact_info` ha sei nuove `textarea`, sotto «Url:», con l'indicazione «Se la
      guida ha più profili su questo social, inserisci un indirizzo per riga.»: Facebook, Instagram, LinkedIn, X, YouTube, TikTok. Meta
      `_guide_facebook`, `_guide_instagram`, `_guide_linkedin`, `_guide_x`, `_guide_youtube`,
      `_guide_tiktok`, con gli URL separati da un a capo.
- [ ] Salvataggio nella stessa `save_guide_contact_info` dei campi esistenti (nonce, autosave,
      capability). Ogni riga viene corretta prima di `esc_url_raw`: spazi ai bordi tolti, `https://`
      aggiunto se manca, un nome di profilo da solo (`wilder_trekk`, `@wilder_trekk`) trasformato
      nell'indirizzo del profilo su quel social. Nessun dato si perde: una riga ancora non valida
      resta salvata, non compare in pagina e sotto il campo compare l'avviso «Indirizzo non
      riconosciuto». Le righe vuote vengono ignorate.
- [ ] Per tutti e nove i campi: un campo presente nel form ma vuoto cancella il meta, un campo assente
      dal form non lo tocca. Oggi un campo assente viene cancellato (`delete_post_meta`), e un editor
      aperto con il form di prima del rilascio cancellerebbe i social appena migrati.
- [ ] I sei meta sono configurati in WPML come i tre esistenti («Translate», valore 2), e
      `[guide_contatti]` usa lo stesso ripiego sulla guida originale quando la traduzione ha il
      campo vuoto.
- [ ] Il campo «Url:» (`_guide_sito`) resta com'è: stessa label, stesso comportamento.
- [ ] Label della tassonomia `guide_abilitazioni`: «Attività proposte» / «Attività proposta». Lo slug
      di rewrite (`guide-abilitazioni`) e il nome della tassonomia non cambiano.

### Output di `[guide_contatti]`

- [ ] Struttura e aspetto della card `.wm_info_details` delle tracce: titolo «Contatti» in una barra,
      poi un `.wm_info_detail_item` per dato, con icona, label in maiuscolo e valore.
- [ ] Due card nell'output: «In breve» con il Riassunto, poi «Contatti» con telefono, email, sito,
      social, Attività proposte, Lingue parlate, Aree operative. I dati vuoti non compaiono; una card
      senza dati non compare.
- [ ] Nell'editor della guida, sotto il campo «Riassunto», un contatore dei caratteri: «N caratteri ·
      consigliati circa 150», in rosso oltre i 150. È un'indicazione, non un limite: la card mostra il
      Riassunto per intero e Yoast lo usa come meta description.
- [ ] Riassunto: `post_excerpt` come testo semplice (`esc_html`, a capo mantenuti) nella card «In
      breve». Senza Riassunto la card non compare. Una traduzione senza Riassunto usa quello della
      guida originale, come i meta.
- [ ] Social: una riga «Social» della card con un profilo per riga, icona e nome del profilo preso
      dall'indirizzo (es. «altrevie»; «Facebook» se l'indirizzo non contiene un nome, come
      `profile.php?id=…`), nell'ordine Facebook, Instagram, LinkedIn, X, YouTube, TikTok. Font
      Awesome di Impreza (`fab`) per cinque social, un SVG inline nel tema per X. Link con
      `target="_blank"`, `rel="noopener noreferrer"` e `aria-label` con social e nome (es.
      «Instagram: altrevie»), icona con `aria-hidden="true"` (WCAG 2.1 – 1.1.1, 2.4.4).
- [ ] Le label della card passano da `__()` con text domain `wm-pnfc-child`. La traduzione
      inglese si inserisce in WPML String Translation.

### Stile (`style.css` del child theme)

- [ ] Le regole della card `.wm_info_details` sono copiate da `wm-package-default.css` nel
      `style.css` e valgono solo dentro l'output di `[guide_contatti]`. Le regole `.wm_info_*` già
      presenti nel `style.css` senza ambito (righe 108-118, grassetto e colore delle icone) valgono
      anche per la card delle guide, che quindi resta uguale a quella delle tracce.
- [ ] Galleria `.pnfc-hiking-gallery`: cornice fissa quasi quadrata (circa 381×400 px a 1440 px di
      larghezza), foto che la riempie, angoli arrotondati da 8 px, frecce e pallini arancioni sopra la
      foto, come `.wm_gallery`.
- [ ] Guida senza foto esistenti nella galleria (`wm_pnfc_guide_has_gallery()`): classe
      `wm-guide-no-gallery` sul `body` e galleria nascosta.
- [ ] Guida senza immagine in evidenza: nella pagina l'immagine con il placeholder non compare
      (`.single-guide .w-image:has(img.g-placeholder)`).
- [ ] Il fullscreen della galleria mostra la foto intera (da verificare nella copia locale: la
      royalSlider calcola le dimensioni in JavaScript).
- [ ] Frecce e pallini della galleria con contrasto di almeno 3:1 sulle foto (WCAG 2.1 – 1.4.11),
      calcolato nella verifica.

### Database (template e pagine Impreza, WPML)

- [ ] Template 4186: le impostazioni di `us_image_slider` che servono alla cornice fissa (riempimento
      «cover»), se il solo CSS non basta.
- [ ] `us_grid_layout` 4201: immagine con rapporto 1:1 e placeholder acceso, angoli arrotondati da
      10 px, sollevamento e ombra al passaggio del mouse come `.wm_grid_track_item`, e lo stesso
      effetto quando la card ha il focus da tastiera (WCAG 2.1 – 2.4.7).
- [ ] Placeholder delle immagini di Impreza (opzione `img_placeholder`): l'icona del Parco su fondo
      verde (`assets/images/guide-placeholder.png` del child theme, 1024×1024), importata nella
      libreria media dallo script. Sul sito il placeholder compare solo nelle card delle guide.
- [ ] Prima di modificarli, il contenuto di template 4186, layout 4201 e pagina 4177 viene salvato
      nella cartella del backup del lavoro, per poterlo rimettere.
- [ ] Pagina 4177: label del filtro `tax|guide_abilitazioni` da «Abilitazioni» ad «Attività proposte».
      Stringa WPML della pagina e del tema aggiornate.

### Migrazione dei social (script WP-CLI)

- [ ] Uno script letto da `wp eval-file`, in modalità prova (stampa cosa farebbe) e in modalità
      applica.
- [ ] Prima di toccare una guida, lo script salva in un file JSON il suo `post_content` e i meta di
      prima. Una modalità «ripristina» rimette quei valori solo sulle guide toccate. Il file resta
      nella cartella del backup del lavoro, fuori dal repo.
- [ ] Per ogni guida non nel cestino, legge i blocchi `core/social-link` dentro `core/social-links`
      del `post_content`, mette nei meta tutti gli indirizzi dei servizi riconosciuti (più profili
      dello stesso social compresi) e toglie il blocco `core/social-links` dal contenuto. Il resto del
      contenuto resta identico.
- [ ] Gli indirizzi passano dalla stessa correzione del meta box. Il 18/11 i casi sono 5: 5171
      (Instagram e Facebook senza `https://`), 5145 (spazio finale), 4944 (solo `wilder_trekk`), 4925
      (senza `https://`). L'output elenca ogni correzione.
- [ ] Un servizio fuori dai sei previsti, o un link non correggibile, resta in un blocco
      `core/social-links` con i soli link non migrati, e la guida viene segnalata nell'output. Oggi
      non ce ne sono.
- [ ] Guida 4925: il profilo Instagram in `_guide_sito` passa in `_guide_instagram`, e `_guide_sito`
      si svuota.
- [ ] Un meta già compilato non perde indirizzi: lo script aggiunge solo quelli che mancano.
- [ ] Rilanciato una seconda volta, lo script non cambia nulla. Rilanciato dopo che un editor aperto
      ha rimesso il blocco, lo toglie di nuovo.

### Verifica

- [ ] Tutto provato prima nella copia locale (Docker) del sito: pagine guida con e senza galleria,
      con foto verticali, con e senza social e Riassunto, guida inglese, griglie 4177 e archivio, a
      320, 1024, 1280, 1440 e 1920 px.

## Rischi

- **Lo script modifica il contenuto di 31 guide in produzione, e il post type non ha revisioni.** Si
  prova prima nella copia locale, si lancia in produzione dopo il backup del database, prima in
  modalità prova. Il file JSON del prima e la modalità «ripristina» permettono di tornare indietro
  sulle sole guide toccate, senza ripristinare l'intero database e perdere le guide inserite nel
  frattempo.
- **Un editor aperto da prima del rilascio può rimettere il blocco social.** Il salvataggio non cancella
  più i campi assenti dal form. Al Parco chiediamo di non modificare guide durante il rilascio, e lo
  script, rilanciato dopo, toglie di nuovo i blocchi tornati.
- **Più profili dello stesso social** (oggi 3 guide): accanto a ogni icona c'è il nome del profilo,
  quindi si distinguono anche sul telefono. Per LinkedIn il nome è l'identificativo intero
  dell'indirizzo (es. «nicola-busi-72576b22a»).
- **Il cliente sta ancora inserendo guide** (55 all'analisi, 59 il 18/11). I numeri della copia
  locale invecchiano: in produzione lo script si lancia sui dati del giorno, e la modalità prova
  mostra cosa trova.
- **La cornice fissa ritaglia le foto verticali** (90 su 162) in alto e in basso. Il formato quasi
  quadrato limita il taglio, e in fullscreen la foto resta intera.
- **19 guide senza immagine in evidenza** mostrano nella griglia l'icona del Parco come placeholder,
  e nella pagina nessuna immagine. **31 guide senza galleria** non mostrano la galleria. La risposta
  al cliente lo segnala: caricando le foto compaiono.
- **Il CSS della card è copiato da `wm-package`:** se il plugin cambia lo stile delle tracce, le guide
  non lo seguono da sole. Le regole copiate sono confinate a `[guide_contatti]`, quindi non toccano le
  pagine delle tracce.
- **Template 4186, layout 4201 e pagina 4177 stanno nel database, non nel repo.** Le modifiche si
  applicano a mano in produzione, dopo il codice, e sono descritte nel `plan.md` per poterle rifare.
  Il loro contenuto di prima viene salvato nel backup del lavoro.
- **Le pagine «Guide» 4177 e 4209 sono private:** il filtro rinominato si vedrà solo quando il Parco
  le pubblica.
- **Il Riassunto è letto anche da Yoast** (verificato in locale): diventa `meta name="description"` e
  `og:description`, quindi compare nei risultati di Google e nelle anteprime dei link condivisi. Da
  dire al cliente; il contatore nell'editor consiglia circa 150 caratteri.

## Out of scope

- L'ordine casuale delle griglie, che resta com'è.
- Un filtro nella pagina inglese 4209, che oggi non esiste.
- Il caricamento dei file `.mo` del child theme: le traduzioni inglesi passano da WPML String
  Translation, come oggi.
- Lo stile delle pagine delle tracce e dei POI, che restano come sono.

## Moduli toccati

Repo `wm-pnfc-child`:

- `functions.php`: meta box `guide_contact_info`, `save_guide_contact_info`, configurazione WPML dei
  meta, `guide_contatti_shortcode`, label di `guide_abilitazioni`.
- `style.css`: card dei contatti, galleria `.pnfc-hiking-gallery`, card della griglia.
- `docs/features/8940-modifiche-sito-cartografico/`: overview, piano, note e script di migrazione.

Database del sito (fuori dal repo):

- Page Template Impreza 4186, `us_grid_layout` 4201, pagina 4177.
- Stringhe WPML (pagina 4177, tema `wm-pnfc-child`).
- `post_content` e meta delle guide con social.
