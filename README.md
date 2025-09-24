# Il Giardino Segreto – Personalizzazioni WooCommerce

Questo repository contiene il plugin WordPress **IGS Ecommerce Customizations**, utilizzato per concentrare in un unico pacchetto tutte le personalizzazioni richieste dal team de Il Giardino Segreto per il proprio ecommerce.

## Funzionalità principali

- **Pagina prodotto su misura** con hero full-width, sidebar informativa e servizi evidenziati tramite emoji.
- **Campi ripetibili per date e paese del tour** completi di datepicker in amministrazione e colonna riepilogativa nella lista prodotti.
- **Metadati “Garden tour” e shortocode dedicati** per mostrare pianta protagonista e livelli (cultura, passeggiata, comfort, esclusività).
- **Card archivio ottimizzate** con date, durata calcolata, paese, stile coerente, rimozione del pulsante standard e overlay cliccabile.
- **Modale di prenotazione personalizzata** con calcolo prezzo, gestione varianti, invio rapido al checkout e form di richiesta informazioni via AJAX.
- **Portfolio con date tour e logo partner** direttamente nel titolo e metabox per inserire le informazioni dal back-office.
- **Pagina Shop semplificata** con breadcrumb rimosso, titolo personalizzato e stile centrato.
- **Traduttore globale** per sostituire qualsiasi stringa del sito tramite un’unica pagina in “Impostazioni → Gestione Testo”.
- **Shortcode mappa itinerario** basato su Leaflet con tappe gestite da metabox drag & drop e ricerca coordinate.

## Installazione

1. Comprimi l’intera cartella `igs-ecommerce-customizations` in un file `.zip`.
2. Carica l’archivio da **Plugin → Aggiungi nuovo → Carica plugin** all’interno di WordPress e attivalo.
3. Non sono necessarie impostazioni aggiuntive: le funzionalità vengono caricate automaticamente dopo l’attivazione.

## Sviluppo

- Tutta la logica è raccolta nella classe `IGS_Ecommerce_Customizations\Plugin` (file `includes/class-igs-plugin.php`).
- Gli snippet sono organizzati in metodi dedicati per facilitare eventuali modifiche o estensioni future.
- Prima di inviare modifiche, esegui sempre un controllo sintattico con `php -l` sui file PHP interessati.
