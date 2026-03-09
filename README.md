# IGS Ecommerce Customizations

Plugin WordPress per le personalizzazioni WooCommerce del progetto **Il Giardino Segreto**: catalogo tour, date, prenotazioni, mappa itinerario.

## Metadati del plugin

- **Versione:** 2.0.1
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

1. Comprimi la cartella `igs-ecommerce-customizations` in un archivio `.zip` (includi `vendor/` se presente, oppure esegui `composer install` dopo l’upload)
2. Carica da **Plugin → Aggiungi nuovo** e attivalo
3. Se la cartella `vendor/` non è presente: esegui `composer install` nella cartella del plugin

## Migrazione da Code Snippets

Se usavi snippet PHP in Code Snippets per le personalizzazioni IGS:

1. **Backup** del database e dei file
2. **Installa e attiva** il plugin IGS Ecommerce Customizations (dopo `composer install`)
3. **Verifica** che l’opzione `gw_string_replacements_global` sia presente in Opzioni (Impostazioni → Gestione Testo)
4. **Disattiva** gli snippet IGS in Code Snippets (non eliminarli subito: tieni un backup)
5. **Controlla** le pagine shop, singolo prodotto, carrello vuoto e portfolio

### Checklist funzionalità (parità con gli snippet)

| Funzionalità | Verifica |
|--------------|----------|
| Layout prodotto: hero, sidebar, servizi | Pagina singolo tour |
| Date tour, paese, prezzo senza decimali | Scheda prodotto + frontend |
| Modal prenotazione + richiesta info | Bottone "Scopri e Prenota" |
| Loop: date, durata, paese, card cliccabile | Pagina shop/archivio |
| Shortcode `[protagonista_tour]`, `[livello_*]` | Nei template |
| Shortcode `[mappa_viaggio id=""]` | Dove usato |
| Portfolio: date nel titolo, logo partner | Loop portfolio |
| Shop: titolo, no breadcrumb | Pagina shop |
| Carrello vuoto: "Ritorna al sito web" | Carrello vuoto |
| Gestione Testo (sostituzioni) | Impostazioni → Gestione Testo |

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
