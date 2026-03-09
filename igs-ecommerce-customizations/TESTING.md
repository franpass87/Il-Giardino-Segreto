# IGS Ecommerce – Guida al testing completo

## Setup prodotto test

1. Crea un prodotto WooCommerce (o usa "Tour Test Italia" ID 695).
2. Esegui lo script di popolamento:
   - Accedi come admin.
   - Visita: `[tuo-sito]/wp-content/plugins/igs-ecommerce-customizations/dev-tools/populate-test-product.php`

Lo script imposta tutti i meta e la descrizione con shortcode.

---

## Checklist funzionalità da testare

### Admin – Metabox e colonne

| Componente | Cosa testare | Meta coinvolti |
|------------|--------------|----------------|
| **TourProductMetabox** (Dati prodotto) | Date del tour (Aggiungi/Rimuovi righe), Paese | `_date_ranges`, `_paese_tour` |
| **GardenMetabox** (Dettagli Garden Tour) | Pianta, Cultura/Passeggiata/Comfort/Esclusività (1–5) | `_protagonista_tour`, `_livello_*` |
| **MapMetabox** (Mappa del Viaggio) | Paese, Tappe (nome, lat, lon, descrizione), Trova coordinate | `_mappa_paese`, `_mappa_tappe` |
| **ProductColumns** | Colonna "Date tour" nella lista prodotti | `_date_ranges` |

### Frontend – Shop / lista prodotti

| Componente | Cosa verificare |
|------------|-----------------|
| **ProductLoop** | Barre durata/paese, date (primo range), "Vai al tour", card cliccabile |
| **PriceDisplay** | Prezzo da X per variabili, "info in arrivo" se prezzo 0 |
| **CountryFlags** | Bandiera paese in card e hero |
| **ShopCustomizations** | CSS globale shop |

### Frontend – Singolo prodotto

| Componente | Cosa verificare |
|------------|-----------------|
| **TourLayout** | Hero con immagine lazy, titolo, paese, date, durata, servizi, sidebar prezzo |
| **GardenShortcodes** | `[protagonista_tour]`, `[livello_culturale]`, ecc. nella descrizione |
| **MapShortcode** | `[mappa_viaggio id="695"]` – mappa Leaflet con tappe, popup, polyline |
| **BookingModal** | CTA "Scopri e Prenota", tab Prenotazione/Richiedi info, aggiungi al carrello, form info |

### Altri moduli

| Componente | Cosa verificare |
|------------|-----------------|
| **ReturnToShop** | Ritorno allo shop dopo checkout |
| **GlobalStringsSettings** | Testi personalizzabili (se presenti) |
| **PortfolioDateMetabox** | Solo su post type portfolio |
| **WooCommerceDisabler** | Disabilita funzioni WC non usate |

---

## Suggerimenti di miglioramento

### 1. **GardenMetabox – validazione livelli**
- I numeri 1–5 non sono validati al save; inserimenti come 6 o testo restano salvati.
- Suggerimento: `absint()` + clamp tra 1 e 5.

### 2. **MapMetabox – coordinate**
- Lat/lon non vengono validate; valori non numerici possono rompere la mappa.
- Suggerimento: `floatval()` e controllo intervalli (lat -90/90, lon -180/180).

### 3. **TourProductMetabox – date**
- Il regex `dd/mm/yyyy` è rigido; altri formati (es. `Y-m-d`) non passano.
- Suggerimento: accettare più formati e normalizzare in salvataggio.

### 4. **ProductLoop – "Vai al tour"** ✅
- Filtro `igs_loop_vai_al_tour_label` (implementato in v2.1.1).

### 5. **TourLayout – servizi** ✅
- Filtro `igs_tour_services` per personalizzare la lista (implementato in v2.1.1).

### 6. **MapShortcode – stile**
- CSS inline nello shortcode.
- Suggerimento: `wp_enqueue_style` con file CSS dedicato.

### 7. **BookingModal – carrello pieno**
- Messaggio di avviso quando si prosegue con carrello già pieno.
- Verificare che sia sempre visibile e leggibile.

### 8. **CountryFlags**
- Supporto paesi con nomi diversi (es. "Germania" vs "Germany").
- Suggerimento: mapping o lista alias per codici ISO.

### 9. **Shortcode in excerpt**
- `post_excerpt` (short description) non processa shortcode di default.
- Se serve mappa/garden nell’excerpt: `apply_filters('the_excerpt', get_the_excerpt())` o `do_shortcode()`.

### 10. **Performance Leaflet**
- Leaflet da CDN unpkg; ritardi di caricamento possibili.
- Suggerimento: copia locale in `assets/` e `wp_enqueue_script` con versioning.

---

## Ordine consigliato di test

1. Shop → card prodotto, date, durata, paese, link.
2. Singolo prodotto → hero, descrizione, shortcode garden, mappa.
3. Modal → Prenotazione (aggiungi al carrello), Richiedi info (form).
4. Admin → tutti i metabox, salvataggio, colonna Date tour.
