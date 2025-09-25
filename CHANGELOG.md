# Changelog

Tutte le modifiche rilevanti del plugin **IGS Ecommerce Customizations** sono documentate in questo file.

## [1.3.3] - 2025-09-27
- Ottimizzato il calcolo runtime delle regole plurali nelle traduzioni per ridurre le valutazioni ripetute e garantire
  fallback sicuri.
- Svuotata anche la cache dei calcolatori dei plurali quando viene invalidata la cache delle traduzioni.
- Introdotte routine di upgrade automatiche che svuotano le cache persistenti dopo ogni aggiornamento del plugin.

## [1.3.2] - 2025-09-26
- Esteso il sistema di sostituzioni globali con supporto per regole regex, validando pattern e flag consentiti.
- Aggiunta cache dei risultati delle sostituzioni per migliorare le prestazioni in produzione.
- Aggiornate le istruzioni dell'interfaccia di amministrazione per documentare la sintassi delle regex.
- Incrementata la versione del plugin a 1.3.2.

## [1.3.1] - 2025-09-25
- Aggiornata la documentazione ufficiale con panoramica completa delle funzionalità e delle procedure operative.
- Impostati i dati autore su Francesco Passeri con relativi riferimenti di contatto.

## [1.3.0] - 2025-09-24
- Aggiunto il comando WP-CLI `wp igs flush-caches` per ripulire cache e transient del plugin durante le operazioni di
  manutenzione.
- Rafforzati i controlli di bootstrap e di attivazione sulle dipendenze (versioni minime di WordPress, WooCommerce e PHP).
- Migliorata la normalizzazione delle date tour, la validazione dei metadati portfolio e la gestione delle email di
  fallback per il form informazioni.
- Ottimizzata la gestione del geocoding con transients dedicati, rate limiting più preciso e funzioni di pulizia
  centralizzate.
- Potenziato il sistema di traduzioni con cache persistenti, invalidazione automatica agli aggiornamenti e lookup delle
  sorgenti più robusto.
- Introdotta una cache per l’individuazione dei prodotti tour con invalidazione su aggiornamenti di prodotto.

## [1.2.1] - 2025-09-24
- Corretto il comportamento della modal di prenotazione in presenza di selezioni multiple o incoerenti.
- Sanificati gli attributi dati inviati dal front-end per ridurre il rischio di input non validi.
- Migliorata la gestione delle traduzioni contestuali e dei plurali caricati a runtime.
- Risolto un problema di caricamento helper che impediva la corretta resa delle schede portfolio.

## [1.2.0] - 2025-09-24
- Riscrittura completa del plugin con architettura modulare (`includes/Admin` e `includes/Frontend`).
- Introdotti metabox avanzati per metadati tour, form geocoding con cache e colonne personalizzate in amministrazione.
- Realizzato un layout front-end dedicato ai tour, inclusi shortcode, modal di prenotazione AJAX e componenti Leaflet.
- Creato un gestore di sostituzioni testuali globali e un loader traduzioni compatibile con contesti e plurali.
- Aggiunti asset CSS/JS organizzati per area (amministrazione, front-end, shortcode, mappe, portfolio).

## [1.1.0] - 2025-09-24
- Sostituita la precedente architettura con un set di personalizzazioni WooCommerce richieste dal progetto Il Giardino
  Segreto, raggruppando gli snippet in un unico plugin.

## [1.0.0] - 2025-09-24
- Versione iniziale del plugin con le prime personalizzazioni WooCommerce dedicate al catalogo tour.
