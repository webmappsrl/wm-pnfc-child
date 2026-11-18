# Pagina della guida escursionistica: contatti, social, galleria

## Come funziona oggi

- **Dati della guida** (post type `guide`): meta box `guide_contact_info` («Informazioni di Contatto»)
  con telefono, email, sito (`_guide_telefono`, `_guide_email`, `_guide_sito`) e sei social
  (`_guide_facebook`, `_guide_instagram`, `_guide_linkedin`, `_guide_x`, `_guide_youtube`,
  `_guide_tiktok`), uno o più indirizzi per riga. Il «Riassunto» è il `post_excerpt` di WordPress, con
  un contatore dei caratteri nell'editor (`assets/js/guide-excerpt-counter.js`).
- **Indirizzi social**: `wm_pnfc_normalize_social_url()` corregge spazi, `https://` mancante e nomi di
  profilo da soli; una riga non riconoscibile resta salvata, non compare in pagina e il meta box la
  segnala. Nessun dato scritto si perde.
- **Salvataggio**: un campo assente dal form non si tocca, uno presente e vuoto si cancella.
- **Pagina** (Page Template Impreza 4186): `[guide_contatti]` produce due card nello stile del
  riquadro «Informazioni» delle tracce di wm-package: «In breve» (Riassunto) e «Contatti» (telefono,
  email, sito, social con icona e nome del profilo, Attività proposte, Lingue parlate, Aree operative).
  Ogni dato e ogni card compaiono solo se ci sono.
- **Galleria** (`us_image_slider`, royalSlider): cornice fissa 20:21 con la foto che la riempie, intera
  in fullscreen. Senza foto la galleria è nascosta (`wm_pnfc_guide_has_gallery()`, classe
  `wm-guide-no-gallery`); senza immagine in evidenza la pagina non mostra immagini.
- **Griglia** (`us_grid_layout` 4201): card con immagine 1:1; senza immagine in evidenza, il
  placeholder di Impreza (`img_placeholder`) è l'icona del Parco (`assets/images/guide-placeholder.png`).
- **Tassonomia** `guide_abilitazioni`: si chiama «Attività proposte» (slug invariato).
- **Inglese**: label tradotte in WPML String Translation; la guida tradotta senza dati usa quelli
  della guida italiana.
- Il CSS del child theme ha come versione la data di modifica del file (Impreza usava la propria).

## Perché così

- **Social come meta e non come blocco nel testo** (oc:8940): nel testo finivano nella colonna sinistra
  e ogni guida li scriveva a modo suo; i meta li mettono nella card a destra, uguali per tutti.
- **Icona e nome per ogni profilo** (oc:8940): con le sole icone due profili dello stesso social non si
  distinguevano, e sul telefono il tooltip non si vede.
- **Riassunto di WordPress invece di un campo nuovo** (oc:8940): il post type lo supportava già e WPML lo
  traduce senza configurazione. Yoast lo usa come meta description e `og:description`: per questo il
  contatore consiglia circa 150 caratteri, senza limite rigido.
- **Galleria nascosta se vuota** (oc:8940): senza foto `us_image_slider` mostra 3 immagini qualsiasi
  della libreria media.
- **Campo assente non cancellato** (oc:8940): un editor aperto da prima di un rilascio cancellava dati.
- **Stile copiato da wm-package** (oc:8940): il suo CSS sulle pagine delle guide non viene caricato; le
  regole sono confinate a `.wm_guide_contact_info` per non toccare le tracce.

## Come ci siamo arrivati

- **Social come sole icone in fila** (oc:8940, superata): illeggibili con più profili dello stesso
  social.
- **Riassunto dentro la card «Contatti»** (oc:8940, superata): spostato in una card «In breve» a sé.
- **Schede cliccabili «In breve | Contatti»** (oc:8940, scartata): avrebbero nascosto i contatti e
  richiesto JavaScript e gestione da tastiera.
- **Placeholder col disegno di Impreza o col logo dell'header** (oc:8940, superati): il disegno non era
  adatto, il logo esiste solo a 148×77 px.
- **Indirizzi non validi scartati** (oc:8940, superata): si perdevano dati; ora si correggono o restano
  salvati.
