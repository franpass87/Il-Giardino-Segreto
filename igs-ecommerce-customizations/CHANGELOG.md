# Changelog

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
