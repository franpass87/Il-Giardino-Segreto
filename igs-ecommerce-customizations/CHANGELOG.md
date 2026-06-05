# Changelog

## [2.4.1] - 2026-06-05

### Fixed

- Bandiera paese leggermente disallineata in verticale rispetto al nome (nella fascia paese delle card tour): la sola `vertical-align:middle` sull'`<img>` la centrava sull'altezza-x del font, lasciandola un po' bassa. Ora il contenitore `.igs-country` è un `inline-flex` con `align-items:center` (gap `.4em`), così bandiera e nome restano centrati come unità a prescindere dalle metriche del font.

## [2.4.0] - 2026-06-05

### Added

- Nuovo `Integration\RemoteBridge`: rende i meta dei prodotti-tour scrivibili da remoto via FP Remote Bridge (content-action `set_post_meta`), così date, itinerari e dettagli dei tour si possono aggiornare dal flusso MCP senza entrare in wp-admin. Si aggancia ai due hook generici del Bridge (≥ 1.169.0): `fp_remote_bridge_content_actions_meta_keys_for_post` (aggiunge le chiavi tour all'allowlist solo sui `product`) e `fp_remote_bridge_content_actions_sanitize_meta_value` (sanitizza ogni valore con le stesse regole delle metabox admin). Chiavi coperte: `_date_ranges` (date partenza, normalizzate gg/mm/aaaa), `_paese_tour`, `_protagonista_tour`, `_mappa_paese`, `_mappa_tappe` (tappe con coordinate validate), i livelli `_livello_*` (1–5), la sidebar (`_igs_sidebar_*`, `_igs_installment_*`, `_igs_tour_services`) e la struttura itinerario (`_igs_tour_programma`, `_igs_tour_caratteristiche`, `_igs_tour_cosa_portare`, `_igs_tour_documenti`, `_igs_tour_quota_comprende`, `_igs_tour_quota_non_comprende`, `_igs_tour_voli`) e `_igs_trust_badges`. La conoscenza dei campi resta nel plugin che li possiede; il Bridge rimane generico.

## [2.3.21] - 2026-06-01

### Fixed

- Su mobile (e in generale nei caroselli prodotto Nectar flickity) la bandiera del paese si gonfiava a tutta larghezza della card — la SVG Spagna di flagcdn diventava una fascia rosso-giallo-rosso enorme. Causa: la regola che limita `.igs-flag` a ~1em vive nel CSS inline agganciato all'handle `woocommerce-general`, che il carosello flickity non sempre accoda; senza quel CSS vinceva la regola del tema `li.product img { width:100% }`. Fix: dimensione della bandiera forzata via attributo `style` inline sull'`<img>` stesso (in `CountryFlags`), così non dipende più dal caricamento di alcun foglio di stile e batte la regola del tema. Vale ovunque (loop, hero, country-band).

## [2.3.18] - 2026-05-31

### Fixed

- Stringa WooCommerce "This product has multiple variants..." mostrata in inglese sulle pagine prodotto/shop italiane (il .mo it_IT non veniva applicato a quella stringa). Nuovo `Frontend\WooStrings`: filtro gettext che sulle pagine IT (Locale::isIt, dominio woocommerce) la forza in italiano; su /en/ resta inglese. Mappa puntuale, nessun impatto su altre stringhe.

## [2.3.17] - 2026-05-31

### Fixed

- Date dei post in inglese sulle pagine italiane ("May 7, 2026") nonostante WPLANG=it_IT: il tema rende la data senza applicare il locale italiano. Nuovo `Frontend\DateLocalizer`: sulle pagine IT (Locale::isIt) converte i nomi dei mesi inglesi in italiano e riordina "Mese G, AAAA" → "G mese AAAA" (es. "7 maggio 2026"). Mappa mesi autosufficiente (non dipende dal file di lingua); agisce solo se la stringa contiene un mese inglese, quindi innocuo sugli orari.

## [2.3.16] - 2026-05-31

### Fixed

- Le etichette delle "Tour Features" generate dagli shortcode Garden (protagonista_tour, livello_culturale, livello_passeggiata, livello_piuma, livello_esclusivita) erano hardcoded in italiano (Pianta/Cultura/Passeggiata/Comfort/Esclusivita) e comparivano in IT anche su /en/. Ora sono bilingui via Locale::isIt(): Plant/Culture/Walking/Comfort/Exclusivity in inglese. (Le card "Tour Features" del sito usano questi shortcode, non il meta _igs_tour_caratteristiche, risultato vuoto sui prodotti.)

## [2.3.15] - 2026-05-31

### Fixed

- Su pagine in lingua target (/en/) comparivano in italiano le etichette delle "Tour Features" (Pianta/Cultura/Passeggiata/Comfort/Esclusivita) e il prefisso prezzo ("da" invece di "from"). Causa: Locale::isIt() si fidava prima dell'API fpml_get_language()->get_current_language(), che su questo sito non distingue IT/EN al render. Ora Locale::isIt() usa PRIMA il prefisso URL (segnale affidabile del routing a segmento FP-ML), poi l'API e infine il locale WP. Le Tour Features sono gia bilingui nel dato (campi it/en) quindi ora escono nella lingua giusta.
- PriceDisplay non dipende piu dal gettext (.mo non caricato per lingua su questo sito): il prefisso "da "/"from " e "info in arrivo"/"coming soon" sono scelti via Locale::isIt().

## [2.3.14] - 2026-05-31

### Added

- **Bandiere paese come immagini SVG** (flagcdn.com) accanto al nome: si vedono su tutti i sistemi, Windows incluso (le flag-emoji erano invisibili su Windows). Applicate a hero, country-band della sidebar e loop prodotti. L'ISO del paese è ricavato dai codepoint dell'emoji già mappata.

### Changed

- **Sidebar prodotto: testo più grande** — voci servizi e "pagamento a rate" a 1.2rem, durata a 1.25rem.

## [2.3.13] - 2026-05-31

### Changed

- **Restyling "Scopri e Prenota" (banner + modale)**: banner con bordo verde accento, prezzo "da €X" + nome tour a sinistra (desktop), pulsante a pillola con gradiente brand e freccia animata, slide-up all'ingresso. Modale con bordo accento superiore, opzioni di prenotazione come card selezionabili (stato hover/selezionato), quantità e totale più grandi, tab a pillola, campi e bottoni rifiniti, bottom-sheet su mobile. Nessuna modifica alla logica (AJAX, svuotamento carrello, nonce, tracking).

## [2.3.12] - 2026-05-31

### Fixed

- **Card "Related products" disallineate** sulle pagine prodotto: la normalizzazione dell'altezza del titolo del loop (3 righe) ora si applica anche su `is_product()`, non solo su shop/categoria, così prezzo, date e bande (giorni/paese) sono allineati tra le card.

### Changed

- **Sidebar prodotto più leggibile**: testo "pagamento a rate" a 1.1rem e voci servizi a 1.05rem.

## [2.3.11] - 2026-05-31

### Fixed

- **Rilevamento lingua (FP-Multilanguage)**: `Locale::isIt()` ora usa l'API di FP-Multilanguage (`fpml_get_language()` / `\FPML_Language::instance()->get_current_language()`) con fallback su prefisso URL; `determine_locale()` restituiva il locale base del sito, mostrando servizi/badge/sidebar in inglese su pagine italiane.
- **Immagine singola "orfana" in fondo alla pagina prodotto**: la sezione Galleria viene mostrata solo se il prodotto ha vere immagini di galleria WooCommerce; con la sola immagine in evidenza (già nell'hero) non viene più generata.

## [2.3.10] - 2026-05-31

### Fixed

- **Mappa duplicata / errore "Map container already initialized"**: rimossa la tab "Mappa" in `TourProductTabs` (lo shortcode `[mappa_viaggio]` è già nel contenuto della pagina); aggiunto guard anti-doppione in `MapShortcode` (una sola mappa per post per richiesta). Risolve sia l'errore di caricamento sia la sezione "spinner" infinita.
- **Lingua errata (testi in inglese su pagine IT)**: `Locale::isIt()` ora rileva la lingua corrente da WPML (`wpml_current_language`) con fallback a `determine_locale()`; servizi, badge e testi sidebar tornano coerenti con la lingua della pagina.

### Changed

- **Bandierine paese rimosse**: le flag-emoji non vengono disegnate su Windows (apparivano come "IE", "ES", ...). Ora si mostra il solo nome del paese, uniforme su tutti i sistemi.
- Sidebar prodotto: testo "pagamento a rate" leggermente più grande e più leggibile.

## [2.3.9] - 2026-05-31

### Fixed

- **`vendor/` ora versionato** (rimosso da `.gitignore`): senza la cartella `vendor/` nel repo, l'installazione via Master/git-updater da GitHub non includeva `vendor/autoload.php`, quindi il plugin si attivava ma rientrava subito (autoload mancante) senza registrare alcun modulo. Committando `vendor/` il deploy da GitHub è ora completo e funzionante.

## [2.3.8] - 2026-05-31

### Changed

- **Ripristinata l'integrazione git-updater** nell'header (`GitHub Plugin URI` + `Primary Branch`): il plugin è ora gestito dal Master FP Updater (aggiunto alla config del Master), quindi torna a essere tracciato per gli aggiornamenti automatici.

## [2.3.7] - 2026-05-31

### Changed

- **Rimossa l'integrazione GitHub/git-updater** dall'header del plugin (direttive `GitHub Plugin URI` e `Primary Branch`): IGS è ora disaccoppiato dal git-updater e si installa/aggiorna manualmente via zip. Il client del sito non è gestito dal Master per questo plugin.

## [2.3.6] - 2026-05-31

### Changed

- **Ripristinate le migliorie UX/estetiche** rimosse in 2.3.5: hover card del loop (ombra + sollevamento), pin mappa 38px con bordo bianco e ombra, box mappa con angoli arrotondati e ombra, spinner di caricamento mappa, tile CARTO light, hero desktop 65vh. Scelta deliberata: il plugin porta una resa più curata rispetto ai code snippet originali, accettando un cambio visivo (in meglio) allo switch. Restano invariati il fix i18n del placeholder durata (`%d`) e il `.mo` EN.

## [2.3.5] - 2026-05-31

### Changed

- **Resa visiva allineata 1:1 ai code snippet originali (pixel-perfect)**, in vista della sostituzione degli snippet sul sito live:
  - Hero pagina prodotto: altezza desktop `65vh` → `70vh`.
  - Loop prodotti: `border-radius` card `12px` → `10px`; rimossi `:hover` (ombra/translate), `cursor` e `transition` aggiuntivi non presenti negli snippet.
  - Mappa itinerario: tile `OpenStreetMap` standard (al posto di CARTO), colore pin/polyline `#0c5764` (al posto di `#0e5763`), pin `36px` senza bordo/ombra, angoli netti (rimossi wrapper `border-radius`/`box-shadow`), popup di default Leaflet, rimosso overlay di caricamento. Mantenuti escaping `esc_html` dei popup e fallback d'errore (non visivi).

## [2.3.4] - 2026-05-31

### Fixed

- **i18n EN**: aggiunto il file `.mo` compilato (`languages/igs-ecommerce-en_US.mo`), mancante in repo: senza di esso WordPress non caricava le traduzioni inglesi e su locale EN tutte le stringhe ricadevano in italiano.
- **Durata tour nel loop**: allineato il placeholder di `_n()` in `ProductLoop` da `%s` a `%d` (`%d giorno`/`%d giorni`), così combacia con il `msgid` del `.po` e con `TourLayout`; in precedenza la durata nel loop non veniva mai tradotta in inglese.

## [2.3.3] - 2026-04-26

### Added

- **FP Marketing Tracking Layer**: emissione `do_action( 'fp_tracking_event', … )` dopo aggiunta al carrello da modale tour (`add_to_cart`) e dopo invio riuscito richiesta informazioni tour (`generate_lead`, `lead_type` = `info_request`).

### Changed

- Admin lista prodotti: colonna date tour con freccia ASCII e trattino se vuoto; stile colonna emesso via `echo` unico.
- `ReturnToShop`: `unset()` su parametri filtro non usati per compatibilità analisi statica.

## [2.3.2] - 2026-03-25

### Fixed

- **WP 6.7+ / WooCommerce**: notice `_load_textdomain_just_in_time` per il dominio `woocommerce` (bootstrap WC prima di `after_setup_theme`). Precaricamento `load_textdomain` da `wp-content/languages` quando esiste il `.mo` e, in fallback, filtro mirato su `doing_it_wrong_trigger_error` solo per quel dominio.

## [2.3.1] - 2026-03-09

### Changed

- **Estetica**: hero con animazione fade-in e backdrop-filter su country/dates; trust badges hover con scale e icona animata; sidebar hover shadow; card caratteristiche con gradiente icona e lift; galleria zoom; programma hover padding; modale con bordi arrotondati, blur backdrop, tab e bottoni migliorati; loop shop con gradienti e shadow brand; toggle tab hover.

### Added

- **demo-preview.html**: file HTML per anteprima visiva componenti (hero, trust badges, sidebar, card, modale, loop).

## [2.3.0] - 2026-03-09

### Added

- **i18n IT/EN**: tutte le stringhe utente ora utilizzano WordPress `__()`, `esc_html__()`, `esc_attr__()`, `_x()`, `_n()` con text domain `igs-ecommerce`. `load_plugin_textdomain` in bootstrap. File `languages/igs-ecommerce.pot` e `languages/igs-ecommerce-en_US.po` per traduzioni.

### Changed

- Sostituito pattern `Locale::isIt() ? 'IT' : 'EN'` con chiamate `__()` basate su locale WordPress. `Locale::isIt()` resta solo per scegliere tra campi `it`/`en` nei dati (badge, caratteristiche, sidebar, servizi).

## [2.2.8] - 2026-03-09

### Added

- **Import WPBakery**: script `dev-tools/import-from-wpbakery.php` per migrare dati da post_content (WPBakery) ai metabox IGS. Estrae: toggle → Programma e Dettagli, vc_gallery → galleria, image_with_animation + shortcode → caratteristiche. Parametri: `?product_id=N`, `?all=1`, `?dry_run=1`.

## [2.2.7] - 2026-03-09

### Changed

- **Pagina prodotto**: revisione estetica completa. Variabili CSS, hero con overlay gradiente, sidebar raffinata, trust badges con hover, card caratteristiche e galleria con transizioni, tipografia e spaziatura coerenti, design responsive migliorato.

## [2.2.6] - 2026-03-09

### Added

- **Caratteristiche**: supporto icona da immagine (ID da Media Library). Campo "ID" accanto all'emoji: se valorizzato usa l'immagine, altrimenti l'emoji.

## [2.2.5] - 2026-03-09

### Changed

- **Caratteristiche del Tour**: design a card con icona circolare, titolo, sottotitolo opzionale e rating 1-5 (pallini). Campi aggiuntivi in metabox: icona emoji, sottotitolo IT/EN, rating.

## [2.2.4] - 2026-03-09

### Added

- **Tab Mappa**: mostra la mappa del viaggio (da metabox "Mappa del Viaggio") nella pagina prodotto.
- **Tab Galleria**: mostra la galleria immagini del prodotto (immagine principale + immagini gallery).

## [2.2.3] - 2026-03-09

### Added

- **Struttura contenuto tour**: metabox "Struttura contenuto tour" nella modifica prodotto, come su [italiangardentour.com](https://www.italiangardentour.com/prodotto/canarie-fuoco-verde-e-storia/).
- Sezioni: Programma del Tour (giorni ripetibili), Caratteristiche, Cosa portare, Documenti, Quota comprende/non comprende, Voli.
- Tab frontend: "Programma del Tour" e "Tutto quello che devi sapere" con contenuto strutturato.

## [2.2.2] - 2026-03-09

### Added

- **Sidebar info editabile**: nella modifica prodotto è possibile personalizzare titolo sezione, testo pagamento a rate e lista servizi inclusi.
- Servizi: icona (emoji) + testo IT/EN, add/remove. Se vuoto, usa i valori predefiniti.

## [2.2.1] - 2026-03-09

### Added

- **TrustBadges**: badge di fiducia attivabili nella pagina prodotto WooCommerce (metabox "Badge di fiducia").
- Badge predefiniti: Pagamento sicuro, Cancellazione flessibile, Miglior prezzo garantito, Recensioni verificate, Prenota senza carta, Assistenza 24/7.
- Filtro `igs_trust_badges` per personalizzare/aggiungere badge.

## [2.2.0] - 2026-03-09

### Added

- **EmailSettings**: pagina admin (Impostazioni → IGS Email) per configurare email destinatarie, mittente (From), template oggetto/corpo e SMTP.
- Supporto SMTP (host, porta, crittografia TLS/SSL, username, password) e pulsante "Invia email di test".
- Placeholder template: `{tour_title}`, `{nome}`, `{email}`, `{messaggio}`.

### Changed

- **BookingModal**: invio richieste "Richiedi info" ora usa le impostazioni dalla pagina IGS Email (destinatari, mittente, template).

## [2.1.1] - 2026-03-09

### Added

- **GardenMetabox**: validazione livelli 1–5 (clamp su save).
- **MapMetabox**: validazione lat/lon (range -90/90, -180/180) e `sanitizeCoord()`.
- **TourProductMetabox**: supporto formati data multipli (dd/mm/yyyy, yyyy-mm-dd, parse libero) con normalizzazione a dd/mm/yyyy.
- **ProductLoop**: filtro `igs_loop_vai_al_tour_label` per personalizzare "Vai al tour".
- **TourLayout**: filtro `igs_tour_services` per personalizzare servizi inclusi (default: Ingressi, Pernottamento, Trasferimenti, Pasti, Guida).

### Fixed

- **GardenMetabox**: valori fuori range (es. 6) non più salvati; clamp a 1–5.
- **MapMetabox**: coordinate non numeriche non più salvate; validazione intervalli.
- **TourProductMetabox**: date in formato yyyy-mm-dd ora accettate e convertite.
