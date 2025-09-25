# IGS Ecommerce Customizations

Plugin WordPress per gestire il catalogo tour e le personalizzazioni WooCommerce del progetto **Il Giardino Segreto**.
Offre metabox dedicati, layout front-end su misura, strumenti di internazionalizzazione e utility operative pensate per un
ambiente di produzione.

## Metadati del plugin

- **Versione:** 1.3.2
- **Autore:** Francesco Passeri
- **Sito web:** [francescopasseri.com](https://francescopasseri.com/)
- **Email di riferimento:** [info@francescopasseri.com](mailto:info@francescopasseri.com)

## Funzionalità principali

### Gestione catalogo e dati
- Layout dedicato alla scheda tour con hero a tutta larghezza, colonna riassuntiva e sezione servizi attivata solo per i
  prodotti identificati come tour.
- Metabox avanzati per date, paese, punteggi "garden" e coordinate, con sanificazione dei dati, validazioni dedicate e
  cache per ridurre l’accesso ai servizi di geocoding.
- Schede portfolio arricchite con formattazione automatica delle date e supporto per loghi partner personalizzati tramite
  filtro.

### Esperienza di prenotazione e front-end
- Modal di prenotazione AJAX che permette di raccogliere richieste senza svuotare il carrello, con controlli lato client e
  lato server per evitare selezioni inconsistenti.
- Shortcode per protagonista, livelli di difficoltà e mappa dell’itinerario basata su Leaflet caricabile da CDN
  configurabile tramite filtri, con fallback per ambienti offline.
- Personalizzazioni della lista prodotti, colonne amministrative e viste archivio pensate per mettere in evidenza i
  metadati chiave dei tour.

### Internazionalizzazione e contenuti
- Gestione centralizzata delle sostituzioni testuali tramite pagina "Impostazioni → Gestione testo", con pattern regex
  validati e cache dei risultati.
- Loader delle traduzioni compatibile con contesti e plurali, con cache persistente e invalidazione automatica al cambio
  di versione.
- Pacchetti lingua `.pot` e `.po` già pronti per l’italiano, caricati direttamente senza necessità del binario `.mo`.

### Operatività, performance e sicurezza
- Validazione preventiva delle dipendenze minime (WordPress, WooCommerce, PHP) sia all’attivazione sia durante il
  bootstrap, con messaggistica chiara per gli amministratori.
- Cache di individuazione dei prodotti tour con invalidazione automatica sugli aggiornamenti per ridurre gli accessi al
  database.
- Rate limiting per le richieste di geocoding e per il form informazioni, con strumenti di monitoraggio tramite log e
  transients dedicati.
- Comando WP-CLI `wp igs flush-caches` per svuotare cache di tour, geocoding, rate limiting e traduzioni in ambienti con
  cache persistenti.
- Migliorie sulla gestione email (fallback dell’indirizzo amministratore) e sui processi AJAX per minimizzare errori e
  dati inconsistenti.

## Requisiti minimi

- PHP **7.4** o superiore
- WordPress **6.0** o superiore
- WooCommerce **7.0** o superiore

## Installazione

1. Comprimi il contenuto della cartella `igs-ecommerce-customizations` in un archivio `.zip`.
2. Carica il pacchetto da **Plugin → Aggiungi nuovo** nel back-office WordPress e attivalo.
3. Le personalizzazioni vengono applicate automaticamente ai prodotti tour; non è richiesta una configurazione iniziale.

## Aggiornamento

- Prima di aggiornare effettua un backup completo di file e database.
- Sostituisci la cartella del plugin con la nuova versione e assicurati di eseguire nuovamente `wp igs flush-caches` se
  utilizzi cache persistenti.
- Dopo l’aggiornamento verifica la sezione **Impostazioni → Gestione testo** e i metadati tour per confermare che non ci
  siano avvisi o campi mancanti.

## Disinstallazione

La disinstallazione dal menu **Plugin → Plugin installati** rimuove automaticamente le opzioni di configurazione
(`gw_string_replacements_global`) e azzera i transient creati dal plugin, evitando residui nel database.

## Comandi WP-CLI

Esegui `wp igs flush-caches` per ripulire cache e transient relativi a:

- risultati della classificazione tour;
- risposte di geocoding e relativi limiti;
- limitatori di richiesta del form informazioni;
- cataloghi di traduzioni caricati a runtime.

Il comando restituisce un riepilogo puntuale degli elementi rimossi e termina con un messaggio di successo.

## Localizzazione

Se aggiungi o modifichi stringhe localizzate rigenera il template con `wp i18n make-pot` (o tool equivalente) e aggiorna
`languages/igs-ecommerce-it_IT.po`. Il plugin carica direttamente i file `.po`, ma puoi includere un eventuale `.mo` se
necessario per la distribuzione.

## Supporto e contatti

Per assistenza, richieste di personalizzazione o segnalazioni scrivi a
[info@francescopasseri.com](mailto:info@francescopasseri.com) oppure visita
[francescopasseri.com](https://francescopasseri.com/).

## Documentazione storica

Consulta il file [CHANGELOG.md](CHANGELOG.md) per la panoramica completa delle versioni e delle modifiche introdotte nel
tempo.
