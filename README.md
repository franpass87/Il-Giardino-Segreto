# IGS Ecommerce Customizations

Plugin WordPress per le personalizzazioni WooCommerce del progetto **Il Giardino Segreto**: catalogo tour, date, prenotazioni, mappa itinerario.

## Metadati del plugin

- **Versione:** 2.0.0
- **Autore:** Francesco Passeri
- **Sito web:** [francescopasseri.com](https://francescopasseri.com)
- **Email:** [info@francescopasseri.com](mailto:info@francescopasseri.com)

## Funzionalità principali

### Admin

- Metabox date tour (repeater con datepicker) e paese
- Metabox Dettagli Garden Tour (protagonista, livelli cultura/passeggiata/comfort/esclusività)
- Metabox Date del Tour sui portfolio
- Metabox Mappa del Viaggio (tappe con geocoding Nominatim)
- Colonna "Date tour" in Prodotti
- Impostazioni → Gestione Testo (sostituzioni globali pipe-separate)

### Frontend

- Layout tour: hero full-width, sidebar con prezzo, durata, servizi, country band
- Prezzi senza decimali, "da/from" per variabili
- Loop prodotti: meta date/durata/paese, card cliccabile, no add-to-cart
- Shop: titolo personalizzato IT/EN, breadcrumb nascosto
- Shortcode `[protagonista_tour]` e `[livello_*]` per garden features
- Shortcode `[mappa_viaggio id=""]` con Leaflet
- Modal prenotazione: add-to-cart (svuota carrello) + richiesta informazioni
- Portfolio: date nel titolo, logo partner per categoria `tour-in-partnership`
- Carrello vuoto: pulsante "Ritorna al sito web" → homepage

## Requisiti minimi

- PHP **8.0** o superiore
- WordPress **6.0** o superiore
- WooCommerce **7.0** o superiore
- CPT `portfolio` e tassonomia `portfolio_category` (es. Salient Portfolio)

## Installazione

1. Comprimi la cartella `igs-ecommerce-customizations` in un archivio `.zip`
2. Carica da **Plugin → Aggiungi nuovo** e attivalo
3. Esegui `composer install` nella cartella del plugin (per autoload PSR-4)

## Struttura

```
igs-ecommerce-customizations/
├── igs-ecommerce-customizations.php
├── composer.json
├── uninstall.php
└── src/
    ├── Core/Plugin.php
    ├── Helper/Locale.php
    ├── Admin/ (TourProductMetabox, GardenMetabox, MapMetabox, ecc.)
    ├── Frontend/ (WooCommerceDisabler, TourLayout, ProductLoop, ecc.)
    ├── Shortcodes/ (GardenShortcodes, MapShortcode)
    ├── Booking/BookingModal.php
    ├── Portfolio/PortfolioTitleFilter.php
    └── Cart/ReturnToShop.php
```

## Disinstallazione

Rimuove l'opzione `gw_string_replacements_global`.

## Documentazione storica

Vedi [CHANGELOG.md](CHANGELOG.md).

## Autore

**Francesco Passeri**

- Sito: [francescopasseri.com](https://francescopasseri.com)
- Email: [info@francescopasseri.com](mailto:info@francescopasseri.com)
- GitHub: [github.com/franpass87](https://github.com/franpass87)
