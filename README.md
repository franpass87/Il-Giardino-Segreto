# Il Giardino Segreto – Personalizzazioni WooCommerce

Questo repository contiene il plugin WordPress **IGS Ecommerce Customizations**, utilizzato dal team de Il Giardino Segreto per gestire il catalogo tour su WooCommerce.

## Funzionalità principali

- **Layout dedicato alla scheda tour** con hero a tutta larghezza, colonna riassuntiva e lista servizi; l’aspetto viene attivato solo per i prodotti identificati come tour.
- **Campi aggiuntivi in amministrazione** per intervalli di date, paese del tour e punteggi “garden”: i dati vengono salvati in modo sicuro con nonce e sanificazione.
- **CTA con modal di prenotazione** che consente di scegliere opzioni, quantità e di inviare richieste informazioni via AJAX senza perdere gli articoli già presenti nel carrello.
- **Shortcode front-end** per protagonista, barre dei livelli e mappa dell’itinerario basata su Leaflet caricato da CDN configurabile, con fallback lato interfaccia.
- **Schede portfolio arricchite** con formattazione delle date tramite `wp_date()` e possibilità di aggiungere un logo partner tramite filtro.
- **Gestione sostituzioni testuali globali** accessibile da “Impostazioni → Gestione testo”, con convalida e cache dei pattern.
- **Colonne e viste archivio personalizzate** per evidenziare date e metadati dei tour nelle liste prodotto.

## Installazione

1. Comprimi il contenuto della cartella `igs-ecommerce-customizations` in uno zip.
2. Carica l’archivio dal pannello **Plugin → Aggiungi nuovo** di WordPress e attivalo.
3. Dopo l’attivazione il plugin non richiede configurazioni aggiuntive: le funzionalità vengono applicate automaticamente ai prodotti tour.

## Requisiti minimi

- PHP **7.4**
- WordPress **6.0**
- WooCommerce **7.0**

## Disinstallazione

- La rimozione del plugin da **Plugin → Plugin installati** elimina automaticamente le opzioni di configurazione (`gw_string_replacements_global`) e svuota i transient utilizzati dalla funzione di geocoding, così da non lasciare dati orfani nel database.

## Dipendenze incluse

- **Leaflet 1.9.4** (JS/CSS) viene caricato da `https://unpkg.com/` tramite gli hook `igs_leaflet_style_url` e `igs_leaflet_script_url`, così da evitare asset binari nel repository mantenendo la possibilità di sostituire l’origine.
- **Stili data picker** custom sono forniti in `assets/css/admin-datepicker.css` per completare l’interfaccia jQuery UI fornita da WordPress.
- Gli script front-end principali si trovano in `assets/js/`, mentre gli stili sono organizzati in `assets/css/`.
- Il pacchetto lingua si trova in `languages/` e contiene il template `.pot` e la traduzione italiana `.po`; le stringhe vengono caricate a runtime senza bisogno del binario `.mo`.

## Struttura del codice

- Il bootstrap (`igs-ecommerce-customizations.php`) definisce le costanti e istanzia `IGS\Ecommerce\Plugin`, che a sua volta carica i moduli presenti in `includes/`.
- I file nella cartella `includes/Admin/` contengono le funzionalità di back-office (metabox, colonne, impostazioni testo, geocoding via AJAX).
- I file nella cartella `includes/Frontend/` gestiscono layout, shortcode, AJAX pubblico e personalizzazioni del negozio.
- Funzioni condivise (helper, sanificazione delle date, rilevamento tour) sono definite in `includes/helpers.php`.

## Sviluppo

- Esegui `php -l` sui file PHP toccati prima di proporre una pull request.
- Includi eventuali asset minificati generati dall’ambiente di build; gli strumenti non devono essere eseguiti in produzione.
- Per modificare i comportamenti del modal di prenotazione puoi agganciare i filtri:
  - `igs_booking_should_empty_cart` per forzare lo svuotamento del carrello prima dell’aggiunta.
  - `igs_portfolio_partner_logo` per impostare un logo partner personalizzato nelle schede portfolio.
  - `igs_tour_map_tile_layer` per definire URL, attributi e opzioni del tile provider utilizzato dalla mappa Leaflet.
- Se aggiungi o aggiorni stringhe localizzate esegui `xgettext` o un equivalente (`wp i18n make-pot`) per rigenerare `languages/igs-ecommerce.pot`, quindi aggiorna `languages/igs-ecommerce-it_IT.po`. Il plugin carica direttamente i file `.po`, ma puoi comunque compilare `.mo` se distribuisci il pacchetto tramite altri canali.

Per contributi o segnalazioni apri una issue o una pull request descrivendo in modo chiaro le modifiche proposte.
