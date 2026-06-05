<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Frontend;

use IGS\Ecommerce\Helper\CountryFlags;
use IGS\Ecommerce\Helper\Locale;
use IGS\Ecommerce\Helper\TrustBadges;
use WC_Product;

class TourLayout
{
    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueStyles']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('woocommerce_before_single_product_summary', [$this, 'render'], 1);
        add_action('woocommerce_after_single_product_summary', [$this, 'renderTourContent'], 15);
    }

    /**
     * JS dell'esperienza tour interattiva (vanilla, nessuna dipendenza): scroll-reveal,
     * nav interna con scroll-spy, lightbox galleria, accordion info, barra prenotazione
     * sticky. Caricato solo sulle schede prodotto.
     */
    public function enqueueScripts(): void
    {
        if (!is_product()) {
            return;
        }
        wp_enqueue_script(
            'igs-tour-experience',
            IGS_URL . 'assets/js/tour-experience.js',
            [],
            IGS_VERSION,
            true
        );
    }

    public function enqueueStyles(): void
    {
        if (!is_product()) {
            return;
        }
        $css = '
            :root {
                --igs-brand: #0b5764;
                --igs-brand-light: #0e6b7a;
                --igs-brand-bg: rgba(11,87,100,0.08);
                --igs-brand-bg-strong: rgba(11,87,100,0.14);
                --igs-text: #2d3748;
                --igs-text-muted: #64748b;
                --igs-border: #e2e8f0;
                --igs-shadow: 0 4px 20px rgba(0,0,0,0.06);
                --igs-shadow-hover: 0 8px 30px rgba(0,0,0,0.1);
                --igs-radius: 12px;
                --igs-radius-sm: 8px;
            }
            .custom-hero {
                position: relative;
                left: 50%; right: 50%;
                width: 100vw;
                margin-left: -50vw;
                margin-right: -50vw;
                height: 65vh;
                min-height: 380px;
                background-size: cover;
                background-position: center;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
            }
            .custom-hero::before {
                content: "";
                position: absolute; top:0; left:0;
                width:100%; height:100%;
                background: linear-gradient(180deg, rgba(0,0,0,0.15) 0%, rgba(0,0,0,0.5) 100%);
                transition: opacity 0.4s ease;
            }
            .custom-hero-content {
                position: relative; z-index:1;
                text-align:center; color:#fff; padding:0 24px;
                max-width: 900px;
                animation: igs-hero-fade 0.8s ease-out;
            }
            @keyframes igs-hero-fade {
                from { opacity: 0; transform: translateY(12px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .custom-hero-content h1 {
                font-size: clamp(32px, 5vw, 52px);
                line-height: 1.15;
                margin-bottom: 0.5em;
                color: #fff;
                text-shadow: 0 2px 24px rgba(0,0,0,0.35);
                letter-spacing: -0.02em;
            }
            .custom-hero-content .country,
            .custom-hero-content .dates {
                font-size: clamp(16px, 2vw, 21px);
                margin: 0 5px 10px;
                background: rgba(11,87,100,0.82);
                border: 1px solid rgba(255,255,255,0.20);
                border-radius: 999px;
                padding: 9px 22px;
                font-weight: 600;
                display: inline-flex;
                align-items: center;
                vertical-align: middle;
                letter-spacing: 0.02em;
                backdrop-filter: blur(5px);
                box-shadow: 0 4px 16px rgba(0,0,0,0.18);
            }
            .custom-hero-content ul,
            .custom-hero-content ol { list-style: none; margin: 0; padding: 0; }
            .custom-hero-content li { list-style: none; }
            .custom-hero-content li::marker,
            .custom-hero-content li::before { content: none; }
            .custom-tour-wrapper { max-width: 1200px; margin: 48px auto; padding: 0 24px; }
            .custom-tour-columns { display: flex; flex-wrap: nowrap; gap: 48px; align-items: flex-start; }
            .custom-tour-desc {
                flex: 2; min-width: 0;
                font-size: 1.1em; line-height: 1.75;
                color: var(--igs-text);
            }
            .custom-tour-desc p { margin-bottom: 1em; }
            .custom-tour-sidebar {
                flex: 1;
                min-width: 280px;
                max-width: 360px;
                background: linear-gradient(165deg, var(--igs-brand-bg) 0%, var(--igs-brand-bg-strong) 100%);
                border-radius: var(--igs-radius);
                box-shadow: var(--igs-shadow);
                padding: 40px 32px;
                text-align: center;
                display: flex;
                flex-direction: column;
                align-items: center;
                border: 1px solid var(--igs-border);
                transition: box-shadow 0.3s ease;
            }
            .custom-tour-sidebar:hover { box-shadow: 0 8px 32px rgba(11,87,100,0.12); }
            .custom-tour-sidebar .price {
                font-size: 2rem;
                font-weight: 700;
                margin-bottom: 8px;
                color: var(--igs-brand);
            }
            .custom-tour-sidebar .installment {
                font-size: 1.2rem;
                color: var(--igs-text);
                margin-bottom: 24px;
                font-weight: 600;
            }
            .custom-tour-sidebar .duration {
                display: inline-block;
                background: var(--igs-brand-bg);
                color: var(--igs-brand);
                font-size: 1rem;
                font-weight: 700;
                padding: 6px 18px;
                border-radius: 999px;
                margin-bottom: 22px;
                letter-spacing: 0.02em;
            }
            .custom-tour-sidebar .tour-services {
                display: flex;
                flex-direction: column;
                gap: 10px;
                margin: 4px 0 20px;
                padding-top: 18px;
                border-top: 1px solid var(--igs-border);
                width: 100%;
                text-align: left;
            }
            .custom-tour-sidebar .tour-services span {
                font-size: 1.2rem;
                color: var(--igs-text);
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 6px 0;
                border-bottom: 1px solid var(--igs-border);
            }
            .custom-tour-sidebar .tour-services span:last-child { border-bottom: none; }
            .custom-tour-sidebar .tour-services .igs-service-svg { width: 22px; height: 22px; color: var(--igs-brand); flex-shrink: 0; }
            .custom-tour-sidebar .tour-services .igs-service-text { line-height: 1.3; text-align: left; }
            .custom-tour-sidebar .sidebar-section-title {
                font-size: 1rem;
                font-weight: 700;
                color: var(--igs-text);
                margin-bottom: 10px;
                text-align: center;
                letter-spacing: 0.02em;
            }
            .custom-tour-sidebar .country-band {
                background: var(--igs-brand);
                padding: 14px 40px;
                border-radius: var(--igs-radius-sm);
                margin: 24px -32px -40px -32px;
                font-size: 0.95rem;
                font-weight: 700;
                color: #fff;
                text-transform: uppercase;
                letter-spacing: 0.08em;
            }
            .custom-hero.igs-hero-lazy { background-color: #e5e7eb; }
            .custom-hero-content .country .igs-country, .country-band .igs-country { display:inline-flex; align-items:center; gap:.45em; }
            .igs-flag { width:1.15em; height:auto; border-radius:2px; box-shadow:0 0 0 1px rgba(0,0,0,.18); vertical-align:middle; }
            #gs-open-modal {
                font-size: 1.8rem !important;
                font-family: \'the-seasons-regular\' !important;
                background-color: #8fb159 !important;
                text-shadow: 2px 2px 8px black !important;
            }
            body[data-header-resize="1"] .container-wrap,
            body[data-header-resize="1"] .project-title { margin-top: 0; padding-top: 0; }
            body[data-slide-out-widget-area-style=slide-out-from-right] .nectar-social.fixed,
            .nectar-social.fixed { display: none; }
            .single-product .nectar-prod-wrap,
            .single-product .product[data-gallery-style=left_thumb_sticky] .nectar-sticky-prod-wrap,
            .woocommerce .product[data-gallery-style=left_thumb_sticky][data-tab-pos*=fullwidth]>.summary.entry-summary,
            .woocommerce .product[data-gallery-style=left_thumb_sticky][data-tab-pos=in_sidebar] .single-product-summary>div.summary,
            .woocommerce div.product[data-gallery-style=left_thumb_sticky] div.images .woocommerce-product-gallery__image:nth-child(n+2) {
                float: none; width: 100%; display: none;
            }
            .woocommerce div.product .woocommerce-tabs[data-tab-style=fullwidth_stacked] { padding-top: 0; margin-top: 0; }
            .woocommerce #ajax-content-wrap .woocommerce-tabs[data-tab-style=fullwidth_stacked] #tab-reviews>#reviews { padding-top: 40px; display: none; }
            #tab-additional_information { display: none !important; }
            div[data-style="default"] .toggle .toggle-title a {
                border-radius: 10px;
                font-weight: bold;
                transition: background .2s ease, color .2s ease;
            }
            div[data-style="default"] .toggle .toggle-title a:hover {
                background: var(--igs-brand-bg) !important;
            }
            .nectar-fancy-box[data-style=default] .inner *,
            .nectar-fancy-box[data-style=hover_desc] .inner *,
            .nectar-fancy-box[data-style=parallax_hover] .inner * {
                text-shadow: 2px 2px 10px black;
                font-size: 35px;
                line-height: 40px;
            }
            .toggle > .toggle-title a i {
                position: absolute;
                left: 13px;
                top: 50%;
                transform: translateY(-50%);
                background-color: transparent;
                color: #888;
                width: 18px;
                height: 16px;
                line-height: 18px;
                font-size: 20px;
                transition: all .2s linear;
            }
            .bottom_controls {
                background-color: rgba(0,0,0,.03);
                padding: 40px 0;
                margin-top: 40px;
                position: relative;
                z-index: 1;
                display: none;
            }
            .gs-form-group > label {
                font-family: \'foundersgrotesk\' !important;
                font-size: 22px !important;
            }
            .igs-trust-badges {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 12px 20px;
                padding: 24px;
                background: linear-gradient(135deg, var(--igs-brand) 0%, var(--igs-brand-light) 100%);
                position: relative;
                left: 50%;
                right: 50%;
                width: 100vw;
                margin-left: -50vw;
                margin-right: -50vw;
            }
            .igs-trust-badge {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 12px 20px;
                background: rgba(255,255,255,0.98);
                border-radius: 999px;
                font-size: 0.9rem;
                font-weight: 600;
                color: var(--igs-brand);
                box-shadow: 0 2px 12px rgba(0,0,0,0.08);
                transition: transform 0.25s ease, box-shadow 0.25s ease, background 0.2s;
            }
            .igs-trust-badge:hover {
                transform: translateY(-3px) scale(1.02);
                box-shadow: 0 6px 20px rgba(0,0,0,0.15);
                background: #fff;
            }
            .igs-trust-badge span.igs-badge-icon { font-size: 1.2em; transition: transform 0.2s; }
            .igs-trust-badge:hover span.igs-badge-icon { transform: scale(1.1); }
            .igs-tour-programma { padding: 8px 0; }
            .igs-programma-day {
                margin-bottom: 32px;
                padding-bottom: 32px;
                border-bottom: 1px solid var(--igs-border);
                transition: padding 0.2s ease;
            }
            .igs-programma-day:hover { padding-left: 4px; }
            .igs-programma-day:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
            .igs-programma-day h3 {
                font-size: 1.2rem;
                color: var(--igs-brand);
                margin-bottom: 14px;
                font-weight: 700;
            }
            .igs-programma-content { color: var(--igs-text); line-height: 1.7; }
            .igs-programma-content p { margin-bottom: 1em; }
            .igs-tour-dettagli { padding: 8px 0; }
            .igs-tour-dettagli section { margin-bottom: 36px; }
            .igs-tour-dettagli section:last-child { margin-bottom: 0; }
            .igs-tour-dettagli h3 {
                font-size: 1.15rem;
                color: var(--igs-brand);
                margin-bottom: 14px;
                font-weight: 700;
                padding-bottom: 8px;
                border-bottom: 2px solid var(--igs-brand-bg-strong);
            }
            .igs-caratteristiche-cards {
                display: flex;
                flex-wrap: wrap;
                gap: 20px;
                justify-content: center;
                padding: 8px 0;
            }
            .igs-caratteristica-card {
                background: #fff;
                border-radius: var(--igs-radius);
                padding: 28px 22px;
                min-width: 170px;
                max-width: 220px;
                text-align: center;
                box-shadow: var(--igs-shadow);
                border: 1px solid var(--igs-border);
                transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.2s;
            }
            .igs-caratteristica-card:hover {
                transform: translateY(-6px);
                box-shadow: 0 12px 40px rgba(11,87,100,0.15);
                border-color: rgba(11,87,100,0.2);
            }
            .igs-car-icon {
                width: 60px; height: 60px;
                margin: 0 auto 14px;
                border-radius: 50%;
                background: linear-gradient(135deg, var(--igs-brand) 0%, var(--igs-brand-light) 100%);
                color: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 28px;
                overflow: hidden;
                transition: transform 0.3s ease;
            }
            .igs-caratteristica-card:hover .igs-car-icon { transform: scale(1.08); }
            .igs-car-icon img { width: 100%; height: 100%; object-fit: contain; padding: 12px; }
            /* Badge caratteristiche (icone che hanno già il proprio cerchio): nessun
               cerchio aggiuntivo, immagine grande e centrata. */
            .igs-car-badge { display: flex; align-items: center; justify-content: center; height: 96px; margin: 0 auto 14px; }
            .igs-caratteristica-card .igs-car-badge img {
                width: auto !important;
                height: 96px !important;
                max-width: 100% !important;
                object-fit: contain !important;
                padding: 0 !important;
                border-radius: 0 !important;
                background: none !important;
                box-shadow: none !important;
            }
            .igs-car-badge-emoji { font-size: 60px; line-height: 1; }
            /* ===================== Rifiniture grafiche tour ===================== */
            /* Accento decorativo sotto i titoli di sezione */
            .igs-tour-content .igs-tour-section > h2 { position: relative; padding-bottom: 18px; }
            .igs-tour-content .igs-tour-section > h2::after {
                content: "";
                position: absolute; left: 50%; bottom: 0; transform: translateX(-50%);
                width: 58px; height: 3px; border-radius: 3px;
                background: linear-gradient(90deg, var(--igs-brand) 0%, var(--igs-brand-light) 100%);
            }
            /* Programma: ogni giorno è una scheda con accento a sinistra */
            .igs-tour-content .igs-tour-programma { padding: 0; }
            .igs-tour-content .igs-programma-day {
                border-bottom: none;
                background: #fff;
                border: 1px solid var(--igs-border);
                border-left: 4px solid var(--igs-brand);
                border-radius: var(--igs-radius);
                padding: 22px 26px;
                margin-bottom: 16px;
                box-shadow: var(--igs-shadow);
                transition: box-shadow 0.25s ease, transform 0.25s ease;
            }
            .igs-tour-content .igs-programma-day:last-child { margin-bottom: 0; }
            .igs-tour-content .igs-programma-day:hover { box-shadow: var(--igs-shadow-hover); transform: translateX(4px); }
            .igs-tour-content .igs-programma-day h3 { margin: 0 0 12px; }
            /* Liste "devi sapere" con icone tonde: • generico, ✓ incluso, ✕ escluso */
            .igs-tour-dettagli section ul { list-style: none; margin: 0; padding: 0; }
            .igs-tour-dettagli section ul li {
                position: relative; padding-left: 32px; margin-bottom: 11px;
                line-height: 1.6; color: var(--igs-text);
            }
            .igs-tour-dettagli section ul li::before {
                position: absolute; left: 0; top: 1px;
                width: 21px; height: 21px; border-radius: 50%;
                display: inline-flex; align-items: center; justify-content: center;
                font-size: 12px; font-weight: 700; line-height: 1;
                content: "\2022"; color: var(--igs-brand); background: var(--igs-brand-bg);
            }
            .igs-tour-dettagli .igs-quota-comprende ul li::before { content: "\2713"; color: #fff; background: #5a8a3c; }
            .igs-tour-dettagli .igs-quota-non-comprende ul li::before { content: "\2715"; color: #fff; background: #c0563f; }
            .igs-car-title { font-weight: 700; font-size: 1rem; margin-bottom: 4px; color: var(--igs-text); }
            .igs-car-subtitle { font-size: 0.85rem; color: var(--igs-text-muted); margin-bottom: 10px; }
            .igs-car-rating { display: flex; gap: 5px; justify-content: center; }
            .igs-car-rating span { width: 8px; height: 8px; border-radius: 50%; }
            .igs-tour-dettagli ul { margin: 0 0 0 20px; padding: 0; }
            .igs-tour-dettagli li { margin-bottom: 8px; color: var(--igs-text); line-height: 1.6; }
            .igs-documenti-content,
            .igs-voli-content { color: var(--igs-text); line-height: 1.7; }
            .igs-documenti-content p,
            .igs-voli-content p { margin-bottom: 0.8em; }
            .mappa-viaggio-wrapper { margin: 0; border-radius: var(--igs-radius); overflow: hidden; box-shadow: var(--igs-shadow); border: 1px solid var(--igs-border); }
            .igs-tour-galleria {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
                gap: 16px;
                padding: 16px 0;
            }
            .igs-gallery-item {
                display: block;
                height: 200px;
                border-radius: var(--igs-radius-sm);
                overflow: hidden;
                box-shadow: var(--igs-shadow);
                transition: transform 0.3s ease, box-shadow 0.3s ease;
            }
            .igs-gallery-item:hover {
                transform: scale(1.03);
                box-shadow: 0 12px 32px rgba(0,0,0,0.12);
            }
            /* !important per battere la regola del tema (li.product img / images img { height:auto })
               che lasciava le immagini di altezze diverse con spazio bianco sotto. */
            .igs-tour-galleria .igs-gallery-item img {
                width: 100% !important;
                height: 100% !important;
                max-width: none !important;
                object-fit: cover !important;
                display: block;
                margin: 0;
            }
            @media (max-width: 768px) { .igs-gallery-item { height: 160px; } }
            /* Contenitore unico di TUTTA la parte interna del tour: stessa larghezza
               centrata della descrizione, così ogni sezione (galleria, caratteristiche,
               itinerario, programma, info) è allineata lungo la pagina. */
            .igs-tour-content { max-width: 1200px; margin: 0 auto; padding: 0 24px; box-sizing: border-box; }
            .igs-tour-content > .igs-tour-section { margin: 56px 0; }
            .igs-tour-content > .igs-tour-section:first-child { margin-top: 32px; }
            .igs-tour-content .igs-tour-section > h2 {
                text-align: center;
                font-size: clamp(26px, 3.4vw, 34px);
                color: var(--igs-brand);
                margin-bottom: 28px;
                letter-spacing: -0.01em;
            }
            /* Le sezioni interne occupano tutto il contenitore (niente larghezze proprie). */
            .igs-tour-content .igs-tour-programma,
            .igs-tour-content .igs-tour-dettagli,
            .igs-tour-content .igs-tour-galleria { max-width: none; margin: 0; padding: 0; }
            .igs-tour-content .igs-tour-dettagli section > h3 { font-size: 1.2rem; }
            @media (max-width: 768px) {
                .custom-hero { height: 50vh; min-height: 300px; }
                .custom-tour-columns { flex-direction: column; }
                .custom-tour-sidebar { max-width: none; }
                .igs-trust-badges { padding: 16px; gap: 10px; }
                .igs-trust-badge { font-size: 0.85rem; padding: 8px 14px; }
                .igs-caratteristica-card { min-width: 130px; max-width: 160px; padding: 20px 16px; }
                .igs-tour-galleria { grid-template-columns: repeat(2, 1fr); gap: 12px; }
            }
            @media (min-width: 769px) { .custom-hero { height: 65vh; } }

            /* ===================================================================
               RESTYLING IMMERSIVO BOTANICO (pagina tour)
               =================================================================== */
            :root {
                --igs-forest: #14352a;
                --igs-green: #2c5a44;
                --igs-green-soft: #edf2ea;
                --igs-cream: #f7f3ea;
                --igs-gold: #c89b54;
                --igs-ink: #22302a;
                --igs-on-dark: #f3efe3;
            }
            /* La descrizione + box prezzo su fondo crema a tutta larghezza */
            .custom-tour-wrapper { position: relative; max-width: none; margin: 0; padding: 72px 24px; }
            .custom-tour-wrapper::before {
                content: ""; position: absolute; top: 0; bottom: 0; left: 50%;
                width: 100vw; margin-left: -50vw; background: var(--igs-cream); z-index: 0;
            }
            .custom-tour-columns { position: relative; z-index: 1; max-width: 1180px; margin: 0 auto; }
            .custom-tour-desc { color: var(--igs-ink); }
            /* Box prezzo: card verde profondo con accenti oro (lusso botanico) */
            .custom-tour-sidebar {
                background: linear-gradient(165deg, var(--igs-green) 0%, var(--igs-forest) 100%);
                color: var(--igs-on-dark);
                border: 1px solid rgba(255,255,255,0.10);
                box-shadow: 0 18px 45px rgba(20,53,42,0.22);
            }
            .custom-tour-sidebar:hover { box-shadow: 0 22px 55px rgba(20,53,42,0.30); }
            .custom-tour-sidebar .price { color: #ffffff; }
            .custom-tour-sidebar .installment { color: rgba(243,239,227,0.85); }
            .custom-tour-sidebar .duration { background: rgba(255,255,255,0.12); color: #fff; }
            .custom-tour-sidebar .sidebar-section-title { color: rgba(243,239,227,0.9); }
            .custom-tour-sidebar .tour-services { border-top-color: rgba(255,255,255,0.16); }
            .custom-tour-sidebar .tour-services span { color: var(--igs-on-dark); border-bottom-color: rgba(255,255,255,0.14); }
            .custom-tour-sidebar .tour-services .igs-service-svg { color: var(--igs-gold); }
            .custom-tour-sidebar .country-band { background: var(--igs-gold); color: var(--igs-forest); }
            /* Pillole hero in tinta */
            .custom-hero-content .country, .custom-hero-content .dates {
                background: rgba(20,53,42,0.78); border-color: rgba(200,155,84,0.55);
            }
            /* Contenitore: le fasce gestiscono la larghezza */
            .igs-tour-content { max-width: none; margin: 0; padding: 0; }
            .igs-band {
                position: relative; left: 50%; right: 50%; width: 100vw;
                margin-left: -50vw; margin-right: -50vw; padding: 84px 24px;
            }
            .igs-band-inner { max-width: 1180px; margin: 0 auto; }
            .igs-band--cream { background: var(--igs-cream); color: var(--igs-ink); }
            .igs-band--sage { background: var(--igs-green-soft); color: var(--igs-ink); }
            .igs-band--green { background: var(--igs-green); color: var(--igs-on-dark); }
            .igs-band--forest { background: var(--igs-forest); color: var(--igs-on-dark); }
            .igs-band-title {
                display: flex; align-items: center; justify-content: center; gap: 14px;
                font-family: \'the-seasons-regular\', Georgia, serif;
                font-size: clamp(30px, 4.2vw, 46px); line-height: 1.1; text-align: center;
                margin: 0 0 46px; letter-spacing: -0.01em; font-weight: 400;
            }
            .igs-band-title .igs-leaf { width: 0.8em; height: 0.8em; flex-shrink: 0; color: var(--igs-gold); }
            .igs-band--cream .igs-band-title, .igs-band--sage .igs-band-title { color: var(--igs-green); }
            .igs-band--green .igs-band-title, .igs-band--forest .igs-band-title { color: var(--igs-on-dark); }
            /* Sezioni interne occupano la fascia */
            .igs-band .igs-tour-programma, .igs-band .igs-tour-dettagli, .igs-band .igs-tour-galleria { max-width: none; margin: 0; padding: 0; }
            /* Galleria su foresta: immagini grandi protagoniste */
            .igs-band--forest .igs-tour-galleria { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 14px; }
            .igs-band--forest .igs-gallery-item { height: 230px; box-shadow: 0 12px 32px rgba(0,0,0,0.35); border: 1px solid rgba(255,255,255,0.08); }
            /* Caratteristiche */
            .igs-band .igs-caratteristica-card { background: #fff; border: 1px solid rgba(20,53,42,0.08); box-shadow: 0 12px 30px rgba(20,53,42,0.07); }
            .igs-band .igs-car-title { color: var(--igs-ink); }
            .igs-band .igs-car-subtitle { color: var(--igs-gold); font-style: italic; }
            /* Itinerario su verde: mappa incorniciata */
            .igs-band--green .mappa-viaggio-wrapper { border: 5px solid rgba(255,255,255,0.14); box-shadow: 0 16px 42px rgba(0,0,0,0.30); }
            /* Programma su crema: schede organiche con accento oro */
            .igs-band--cream .igs-programma-day { background: #fff; border: 1px solid rgba(20,53,42,0.08); border-left: 4px solid var(--igs-gold); box-shadow: 0 10px 26px rgba(20,53,42,0.06); }
            .igs-band .igs-programma-day h3 { color: var(--igs-green); font-family: \'the-seasons-regular\', Georgia, serif; font-size: 1.4rem; }
            .igs-band .igs-programma-content { color: var(--igs-ink); }
            /* Devi sapere su sage */
            .igs-band--sage .igs-tour-dettagli section > h3 { color: var(--igs-green); border-bottom-color: rgba(20,53,42,0.14); }
            .igs-band--sage .igs-tour-dettagli, .igs-band--sage .igs-tour-dettagli li { color: var(--igs-ink); }
            @media (max-width: 768px) {
                .igs-band { padding: 56px 18px; }
                .custom-tour-wrapper { padding: 48px 18px; }
            }

            /* ===================================================================
               ESPERIENZA TOUR INTERATTIVA (JS: tour-experience.js)
               =================================================================== */
            /* Scroll-reveal: entra dal basso quando intersecato (igs-in da JS).
               Senza JS / con reduce-motion resta tutto visibile. */
            .igs-reveal { opacity: 0; transform: translateY(26px); transition: opacity .7s ease, transform .7s cubic-bezier(.16,.84,.44,1); will-change: opacity, transform; }
            .igs-reveal.igs-in { opacity: 1; transform: none; }
            @media (prefers-reduced-motion: reduce) {
                .igs-reveal { opacity: 1 !important; transform: none !important; transition: none !important; }
            }

            /* --- Nav interna sticky con scroll-spy --- */
            .igs-tour-nav {
                position: sticky; top: 0; z-index: 30;
                background: rgba(20,53,42,0.96);
                backdrop-filter: blur(8px);
                box-shadow: 0 6px 22px rgba(20,53,42,0.18);
            }
            .igs-tour-nav-inner {
                max-width: 1180px; margin: 0 auto;
                display: flex; gap: 6px; flex-wrap: nowrap;
                overflow-x: auto; -webkit-overflow-scrolling: touch;
                padding: 4px 16px; scrollbar-width: none;
            }
            .igs-tour-nav-inner::-webkit-scrollbar { display: none; }
            .igs-tour-nav a {
                position: relative; white-space: nowrap;
                padding: 16px 18px; font-size: 0.95rem; font-weight: 600;
                color: rgba(243,239,227,0.72); text-decoration: none;
                letter-spacing: 0.01em; transition: color .2s ease;
            }
            .igs-tour-nav a::after {
                content: ""; position: absolute; left: 18px; right: 18px; bottom: 10px;
                height: 2px; border-radius: 2px; background: var(--igs-gold);
                transform: scaleX(0); transform-origin: left; transition: transform .25s ease;
            }
            .igs-tour-nav a:hover { color: #fff; }
            .igs-tour-nav a.is-active { color: #fff; }
            .igs-tour-nav a.is-active::after { transform: scaleX(1); }

            /* --- Programma come timeline verticale --- */
            .igs-timeline { position: relative; padding-left: 6px; }
            .igs-timeline::before {
                content: ""; position: absolute; left: 21px; top: 8px; bottom: 8px;
                width: 2px; background: linear-gradient(180deg, var(--igs-gold) 0%, rgba(200,155,84,0.25) 100%);
            }
            .igs-timeline-item { position: relative; display: flex; gap: 24px; padding: 0 0 26px 0; }
            .igs-timeline-item:last-child { padding-bottom: 0; }
            .igs-timeline-marker {
                position: relative; z-index: 1; flex-shrink: 0;
                width: 44px; height: 44px; border-radius: 50%;
                display: flex; align-items: center; justify-content: center;
                font-family: \'the-seasons-regular\', Georgia, serif;
                font-size: 1.15rem; font-weight: 400; color: #fff;
                background: linear-gradient(135deg, var(--igs-green) 0%, var(--igs-forest) 100%);
                box-shadow: 0 6px 16px rgba(20,53,42,0.25), 0 0 0 5px var(--igs-cream);
            }
            .igs-timeline-body {
                flex: 1; min-width: 0; background: #fff;
                border: 1px solid rgba(20,53,42,0.08); border-radius: var(--igs-radius);
                padding: 20px 24px; box-shadow: 0 10px 26px rgba(20,53,42,0.06);
                transition: box-shadow .25s ease, transform .25s ease;
            }
            .igs-timeline-body:hover { box-shadow: 0 16px 38px rgba(20,53,42,0.12); transform: translateY(-2px); }
            .igs-timeline-body h3 {
                margin: 0 0 10px; color: var(--igs-green);
                font-family: \'the-seasons-regular\', Georgia, serif; font-size: 1.35rem; font-weight: 400;
            }
            .igs-timeline-body .igs-programma-content { color: var(--igs-ink); line-height: 1.7; }
            .igs-timeline-body .igs-programma-content p { margin-bottom: 0.8em; }
            .igs-timeline-body .igs-programma-content p:last-child { margin-bottom: 0; }

            /* --- Accordion "tutto quello che devi sapere" --- */
            .igs-accordion { max-width: 860px; margin: 0 auto; display: flex; flex-direction: column; gap: 14px; }
            .igs-acc-item {
                background: #fff; border: 1px solid rgba(20,53,42,0.10);
                border-radius: var(--igs-radius); overflow: hidden;
                box-shadow: 0 8px 22px rgba(20,53,42,0.05); transition: box-shadow .25s ease;
            }
            .igs-acc-item.is-open { box-shadow: 0 14px 34px rgba(20,53,42,0.10); }
            .igs-acc-head {
                width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 16px;
                background: none; border: 0; cursor: pointer; text-align: left;
                padding: 20px 24px; font-size: 1.1rem; font-weight: 700; color: var(--igs-green);
                font-family: inherit;
            }
            .igs-acc-head:hover { color: var(--igs-forest); }
            .igs-acc-chevron { width: 22px; height: 22px; flex-shrink: 0; color: var(--igs-gold); transition: transform .3s ease; }
            .igs-acc-item.is-open .igs-acc-chevron { transform: rotate(180deg); }
            .igs-acc-panel { max-height: 0; overflow: hidden; transition: max-height .4s cubic-bezier(.4,0,.2,1); }
            .igs-acc-item.is-open .igs-acc-panel { max-height: 2400px; }
            .igs-acc-panel-inner { padding: 0 24px 24px; color: var(--igs-ink); line-height: 1.7; }
            .igs-acc-panel-inner ul { list-style: none; margin: 0; padding: 0; }
            .igs-acc-panel-inner ul li {
                position: relative; padding-left: 30px; margin-bottom: 10px; line-height: 1.6; color: var(--igs-ink);
            }
            .igs-acc-panel-inner ul li::before {
                position: absolute; left: 0; top: 2px; width: 20px; height: 20px; border-radius: 50%;
                display: inline-flex; align-items: center; justify-content: center;
                font-size: 11px; font-weight: 700; line-height: 1;
                content: "\2022"; color: var(--igs-green); background: var(--igs-green-soft);
            }
            .igs-acc-item.igs-quota-comprende .igs-acc-panel-inner ul li::before { content: "\2713"; color: #fff; background: #5a8a3c; }
            .igs-acc-item.igs-quota-non-comprende .igs-acc-panel-inner ul li::before { content: "\2715"; color: #fff; background: #c0563f; }
            .igs-acc-panel-inner .igs-caratteristiche-cards { margin-top: 4px; }

            /* --- Lightbox galleria --- */
            body.igs-lb-lock { overflow: hidden; }
            .igs-lb {
                position: fixed; inset: 0; z-index: 100000;
                display: none; align-items: center; justify-content: center;
                background: rgba(12,24,18,0.92); backdrop-filter: blur(4px);
                opacity: 0; transition: opacity .25s ease;
            }
            .igs-lb.is-open { display: flex; opacity: 1; }
            .igs-lb-stage { margin: 0; max-width: 90vw; max-height: 86vh; display: flex; flex-direction: column; align-items: center; gap: 14px; }
            .igs-lb-img { max-width: 90vw; max-height: 80vh; border-radius: 6px; box-shadow: 0 20px 60px rgba(0,0,0,0.5); object-fit: contain; }
            .igs-lb-count { color: rgba(243,239,227,0.8); font-size: 0.95rem; letter-spacing: 0.05em; }
            .igs-lb-btn {
                position: absolute; background: rgba(255,255,255,0.10); border: 1px solid rgba(255,255,255,0.2);
                color: #fff; cursor: pointer; border-radius: 50%;
                width: 52px; height: 52px; font-size: 30px; line-height: 1;
                display: flex; align-items: center; justify-content: center;
                transition: background .2s ease, transform .2s ease;
            }
            .igs-lb-btn:hover { background: rgba(255,255,255,0.22); transform: scale(1.06); }
            .igs-lb-close { top: 22px; right: 22px; font-size: 26px; }
            .igs-lb-prev { left: 22px; top: 50%; transform: translateY(-50%); }
            .igs-lb-next { right: 22px; top: 50%; transform: translateY(-50%); }
            .igs-lb-prev:hover, .igs-lb-next:hover { transform: translateY(-50%) scale(1.06); }
            @media (max-width: 768px) {
                .igs-lb-btn { width: 42px; height: 42px; font-size: 24px; }
                .igs-lb-prev { left: 8px; } .igs-lb-next { right: 8px; } .igs-lb-close { top: 12px; right: 12px; }
            }

            @media (max-width: 768px) {
                .igs-timeline-item { gap: 14px; }
                .igs-timeline-marker { width: 38px; height: 38px; font-size: 1rem; box-shadow: 0 6px 16px rgba(20,53,42,0.25), 0 0 0 4px var(--igs-cream); }
                .igs-timeline::before { left: 18px; }
                .igs-acc-head { padding: 16px 18px; font-size: 1rem; }
                .igs-acc-panel-inner { padding: 0 18px 18px; }
            }
        ';
        wp_add_inline_style('woocommerce-general', $css);
    }

    public function render(): void
    {
        if (!is_product()) {
            return;
        }

        global $product;
        if (!$product instanceof WC_Product) {
            return;
        }

        $isIt = Locale::isIt();
        $id = $product->get_id();
        $ranges = get_post_meta($id, '_date_ranges', true);
        $paese = get_post_meta($id, '_paese_tour', true);
        $excerpt = apply_filters('woocommerce_short_description', $product->get_short_description());

        $imgId = $product->get_image_id();
        $imgUrl = $imgId ? wp_get_attachment_image_url($imgId, 'full') : wc_placeholder_img_src();

        echo '<div class="custom-hero igs-hero-lazy" data-bg="' . esc_attr($imgUrl) . '">';
        echo '<div class="custom-hero-content">';
        echo '<h1>' . esc_html(get_the_title()) . '</h1>';
        $countryDisplay = $paese ? CountryFlags::withFlagHtml($paese) : esc_html__('Paese non specificato', 'igs-ecommerce');
        echo '<div class="country">' . $countryDisplay . '</div>';
        if (is_array($ranges) && count($ranges) > 0) {
            echo '<div class="dates">' . esc_html($ranges[0]['start']) . ' → ' . esc_html($ranges[0]['end']) . '</div>';
        } else {
            echo '<div class="dates">' . esc_html__('Date non disponibili', 'igs-ecommerce') . '</div>';
        }
        echo '</div></div>';

        $trustBadges = TrustBadges::getForProduct($product);
        if (!empty($trustBadges)) {
            echo '<div class="igs-trust-badges">';
            foreach ($trustBadges as $badge) {
                $rawText = $isIt ? ($badge['it'] ?? $badge['en']) : ($badge['en'] ?? $badge['it']);
                $text = __($rawText, 'igs-ecommerce');
                $icon = $badge['icon'] ?? '';
                echo '<span class="igs-trust-badge">';
                if ($icon !== '') {
                    echo '<span class="igs-badge-icon" aria-hidden="true">' . esc_html($icon) . '</span>';
                }
                echo '<span>' . esc_html($text) . '</span></span>';
            }
            echo '</div>';
        }

        echo '<div class="custom-tour-wrapper">';
        echo '<div class="custom-tour-columns">';
        echo '<div class="custom-tour-desc">' . wp_kses_post($excerpt) . '</div>';
        echo '<div class="custom-tour-sidebar">';

        $priceHtml = apply_filters('woocommerce_get_price_html', '', $product);
        echo '<div class="price">' . $priceHtml . '</div>';

        $sidebarTitleIt = get_post_meta($id, '_igs_sidebar_title_it', true);
        $sidebarTitleEn = get_post_meta($id, '_igs_sidebar_title_en', true);
        $installmentIt = get_post_meta($id, '_igs_installment_text_it', true);
        $installmentEn = get_post_meta($id, '_igs_installment_text_en', true);
        $sidebarTitle = $isIt ? ($sidebarTitleIt ?: $sidebarTitleEn) : ($sidebarTitleEn ?: $sidebarTitleIt);
        $installmentText = $isIt ? ($installmentIt ?: __('Pagamento a rate disponibile', 'igs-ecommerce')) : ($installmentEn ?: __('Installment payment available', 'igs-ecommerce'));
        if ($sidebarTitle !== '') {
            echo '<div class="sidebar-section-title">' . esc_html($sidebarTitle) . '</div>';
        }
        echo '<div class="installment">' . esc_html($installmentText) . '</div>';

        if (is_array($ranges) && count($ranges) > 0) {
            $d1 = \DateTime::createFromFormat('d/m/Y', $ranges[0]['start']);
            $d2 = \DateTime::createFromFormat('d/m/Y', $ranges[0]['end']);
            if ($d1 && $d2 && $d2 >= $d1) {
                $days = $d1->diff($d2)->days + 1;
                $label = sprintf(
                    /* translators: %d: number of days */
                    _n('%d giorno', '%d giorni', $days, 'igs-ecommerce'),
                    $days
                );
                echo '<div class="duration"><strong>' . esc_html($label) . '</strong></div>';
            }
        }

        $defaultServices = [
            ['svg' => 'ticket', 'it' => __('Ingressi ai siti e giardini', 'igs-ecommerce'), 'en' => __('Entrance to sites and gardens', 'igs-ecommerce')],
            ['svg' => 'bed', 'it' => __('Pernottamento incluso', 'igs-ecommerce'), 'en' => __('Overnight stay included', 'igs-ecommerce')],
            ['svg' => 'bus', 'it' => __('Trasferimenti in loco', 'igs-ecommerce'), 'en' => __('Local transfers', 'igs-ecommerce')],
            ['svg' => 'meal', 'it' => __('Pasti da itinerario', 'igs-ecommerce'), 'en' => __('Meals as per itinerary', 'igs-ecommerce')],
            ['svg' => 'guide', 'it' => __('Guida locale', 'igs-ecommerce'), 'en' => __('Local guide', 'igs-ecommerce')],
        ];
        $customServices = get_post_meta($id, '_igs_tour_services', true);
        $services = is_array($customServices) && !empty($customServices) ? $customServices : $defaultServices;
        $services = apply_filters('igs_tour_services', $services, $product);
        echo '<div class="tour-services">';
        foreach ((array) $services as $s) {
            if (is_string($s)) {
                echo '<span>' . $this->serviceIcon('check') . '<span class="igs-service-text">' . esc_html($s) . '</span></span>';
                continue;
            }
            $text = $isIt ? ($s['it'] ?? $s['en'] ?? '') : ($s['en'] ?? $s['it'] ?? '');
            if ($text === '') {
                continue;
            }
            $svgKey = isset($s['svg']) ? (string) $s['svg'] : 'check';
            echo '<span>' . $this->serviceIcon($svgKey) . '<span class="igs-service-text">' . esc_html($text) . '</span></span>';
        }
        echo '</div>';

        $countryBandDisplay = $paese ? CountryFlags::withFlagHtml($paese) : esc_html__('Paese non specificato', 'igs-ecommerce');
        echo '<div class="country-band">' . $countryBandDisplay . '</div>';
        echo '</div></div></div>';
        $this->renderHeroLazyScript();
    }

    /**
     * Rende TUTTA la parte interna del tour (galleria, caratteristiche, itinerario,
     * programma, "tutto quello che devi sapere") in un unico contenitore coerente,
     * a partire dai meta del prodotto. Sostituisce il vecchio contenuto WPBakery e
     * i tab WooCommerce, garantendo lo stesso allineamento per ogni tour.
     */
    public function renderTourContent(): void
    {
        if (!is_product()) {
            return;
        }
        global $product;
        if (!$product instanceof WC_Product) {
            return;
        }

        $id = $product->get_id();

        // Render la parte interna SOLO per i tour "gestiti dal plugin", cioè quelli
        // con il programma nei meta (migrati): così le pagine ancora costruite in
        // WPBakery (es. versioni EN non ancora migrate) restano intatte e non si
        // creano sezioni duplicate o vuote.
        $programma = get_post_meta($id, '_igs_tour_programma', true);
        if (!is_array($programma) || empty($programma)) {
            return;
        }

        $tabs = new TourProductTabs();

        // Quali sezioni esistono → serve sia per la nav interna sia per decidere cosa rendere.
        $hasGalleria = !empty($product->get_gallery_image_ids());
        $hasLevels = $this->hasLevels($product);
        $tappe = get_post_meta($id, '_mappa_tappe', true);
        $hasItinerario = is_array($tappe) && !empty($tappe);
        $hasInfo = $this->hasDettagli($id);

        $sections = [];
        if ($hasGalleria) {
            $sections[] = ['id' => 'igs-sec-galleria', 'label' => __('Galleria', 'igs-ecommerce')];
        }
        if ($hasLevels) {
            $sections[] = ['id' => 'igs-sec-caratteristiche', 'label' => __('Caratteristiche', 'igs-ecommerce')];
        }
        if ($hasItinerario) {
            $sections[] = ['id' => 'igs-sec-itinerario', 'label' => __('Itinerario', 'igs-ecommerce')];
        }
        $sections[] = ['id' => 'igs-sec-programma', 'label' => __('Programma', 'igs-ecommerce')];
        if ($hasInfo) {
            $sections[] = ['id' => 'igs-sec-info', 'label' => __('Info utili', 'igs-ecommerce')];
        }

        echo '<div class="igs-tour-content">';

        $this->renderTourNav($sections);

        if ($hasGalleria) {
            $this->openBand('forest', 'igs-sec-galleria');
            $this->bandTitle(__('Galleria', 'igs-ecommerce'));
            $tabs->renderGalleria();
            $this->closeBand();
        }

        if ($hasLevels) {
            $this->openBand('cream', 'igs-sec-caratteristiche');
            $this->bandTitle(__('Caratteristiche del Tour', 'igs-ecommerce'));
            $this->renderCaratteristicheLivelli($product);
            $this->closeBand();
        }

        if ($hasItinerario) {
            $this->openBand('green', 'igs-sec-itinerario');
            $this->bandTitle(__('Itinerario di Viaggio', 'igs-ecommerce'));
            echo '<div class="igs-reveal">' . do_shortcode('[mappa_viaggio id="' . (int) $id . '"]') . '</div>';
            $this->closeBand();
        }

        $this->openBand('cream', 'igs-sec-programma');
        $this->bandTitle(__('Programma del Tour', 'igs-ecommerce'));
        $tabs->renderProgramma();
        $this->closeBand();

        if ($hasInfo) {
            $this->openBand('sage', 'igs-sec-info');
            $this->bandTitle(__('Tutto quello che devi sapere', 'igs-ecommerce'));
            $tabs->renderDettagliViaggio();
            $this->closeBand();
        }

        echo '</div>';
    }

    /**
     * Nav interna sticky: àncore alle sezioni del tour. Lo scroll fluido e lo
     * scroll-spy (link attivo) sono gestiti da tour-experience.js.
     *
     * @param array<int, array{id: string, label: string}> $sections
     */
    private function renderTourNav(array $sections): void
    {
        if (count($sections) < 2) {
            return;
        }
        echo '<nav class="igs-tour-nav" aria-label="' . esc_attr__('Sezioni del tour', 'igs-ecommerce') . '">';
        echo '<div class="igs-tour-nav-inner">';
        foreach ($sections as $s) {
            echo '<a href="#' . esc_attr($s['id']) . '">' . esc_html($s['label']) . '</a>';
        }
        echo '</div></nav>';
    }

    /** Apre una fascia a tutta larghezza (variante colore) con contenuto centrato. */
    private function openBand(string $variant, string $id = ''): void
    {
        $idAttr = $id !== '' ? ' id="' . esc_attr($id) . '"' : '';
        echo '<section class="igs-band igs-band--' . esc_attr($variant) . '"' . $idAttr . '><div class="igs-band-inner">';
    }

    private function closeBand(): void
    {
        echo '</div></section>';
    }

    /** Titolo di fascia con piccolo accento botanico (foglia). Animato allo scroll. */
    private function bandTitle(string $text): void
    {
        $leaf = '<svg class="igs-leaf" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C7 4 4 8 4 13c0 4 3 8 8 9 0-5 1-8 4-11-2 1-4 2-5 4 0-5 1-9 1-13z"/></svg>';
        echo '<h2 class="igs-band-title igs-reveal">' . $leaf . '<span>' . esc_html($text) . '</span></h2>';
    }

    private function hasLevels(WC_Product $product): bool
    {
        $id = $product->get_id();
        if (trim((string) get_post_meta($id, '_protagonista_tour', true)) !== '') {
            return true;
        }
        foreach (['_livello_culturale', '_livello_passeggiata', '_livello_piuma', '_livello_esclusivita'] as $k) {
            if ((int) get_post_meta($id, $k, true) >= 1) {
                return true;
            }
        }

        return false;
    }

    private function hasDettagli(int $id): bool
    {
        $keys = [
            '_igs_tour_cosa_portare',
            '_igs_tour_documenti',
            '_igs_tour_quota_comprende',
            '_igs_tour_quota_non_comprende',
            '_igs_tour_voli',
            '_igs_tour_info',
        ];
        foreach ($keys as $k) {
            $v = get_post_meta($id, $k, true);
            if (is_string($v) && trim($v) !== '') {
                return true;
            }
        }
        $c = get_post_meta($id, '_igs_tour_caratteristiche', true);

        return is_array($c) && !empty($c);
    }

    /**
     * "Caratteristiche del Tour" generate dai meta livelli/protagonista (le stesse
     * 5 card di prima, ma rese dal plugin e allineate al contenitore).
     */
    /**
     * Icona SVG a linee per un servizio incluso nella sidebar. Colore via CSS
     * (currentColor): coerente e affidabile su tutti i sistemi (a differenza delle emoji).
     */
    private function serviceIcon(string $key): string
    {
        $paths = [
            'ticket' => '<path d="M4 8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4V8z"/><path d="M10 6v12"/>',
            'bed' => '<path d="M3 18v-6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v6"/><path d="M3 14h18"/><path d="M7 10V8a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v2"/>',
            'bus' => '<rect x="4" y="5" width="16" height="11" rx="2"/><path d="M4 11h16"/><circle cx="8" cy="18" r="1.4"/><circle cx="16" cy="18" r="1.4"/>',
            'meal' => '<path d="M7 3v7a2 2 0 0 0 4 0V3"/><path d="M9 10v11"/><path d="M17 3c-1.4 0-2.4 1.6-2.4 4s1 4 2.4 4v10"/>',
            'guide' => '<path d="M20 10c0 5.5-8 11-8 11s-8-5.5-8-11a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="2.4"/>',
            'check' => '<path d="M20 6 9 17l-5-5"/>',
        ];
        $p = $paths[$key] ?? $paths['check'];

        return '<svg class="igs-service-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
    }

    private function renderCaratteristicheLivelli(WC_Product $product): void
    {
        $id = $product->get_id();
        $isIt = Locale::isIt();
        $protagonista = get_post_meta($id, '_protagonista_tour', true);

        $cards = [
            ['icon_id' => 226, 'emoji' => '🌱', 'label_it' => 'Pianta', 'label_en' => 'Plant', 'kind' => 'text', 'value' => is_string($protagonista) ? $protagonista : ''],
            ['icon_id' => 225, 'emoji' => '🏛️', 'label_it' => 'Cultura', 'label_en' => 'Culture', 'kind' => 'rating', 'value' => (int) get_post_meta($id, '_livello_culturale', true)],
            ['icon_id' => 224, 'emoji' => '👟', 'label_it' => 'Passeggiata', 'label_en' => 'Walking', 'kind' => 'rating', 'value' => (int) get_post_meta($id, '_livello_passeggiata', true)],
            ['icon_id' => 222, 'emoji' => '🪶', 'label_it' => 'Comfort', 'label_en' => 'Comfort', 'kind' => 'rating', 'value' => (int) get_post_meta($id, '_livello_piuma', true)],
            ['icon_id' => 223, 'emoji' => '🗝️', 'label_it' => 'Esclusività', 'label_en' => 'Exclusivity', 'kind' => 'rating', 'value' => (int) get_post_meta($id, '_livello_esclusivita', true)],
        ];

        $renderable = array_filter($cards, static function (array $c): bool {
            return $c['kind'] === 'text' ? ($c['value'] !== '') : ($c['value'] >= 1 && $c['value'] <= 5);
        });
        if (empty($renderable)) {
            return;
        }

        echo '<div class="igs-caratteristiche-cards igs-reveal">';
        foreach ($renderable as $c) {
            $label = $isIt ? $c['label_it'] : $c['label_en'];
            echo '<div class="igs-caratteristica-card">';
            // Le icone-badge (226/225/...) hanno già il loro cerchio: niente cerchio
            // aggiuntivo del plugin, solo l'immagine a dimensione piena.
            echo '<div class="igs-car-badge">';
            $img = $c['icon_id'] > 0
                ? wp_get_attachment_image($c['icon_id'], 'medium', false, ['class' => 'igs-car-badge-img', 'alt' => ''])
                : '';
            echo $img !== '' ? wp_kses_post($img) : '<span class="igs-car-badge-emoji">' . esc_html($c['emoji']) . '</span>';
            echo '</div>';
            echo '<div class="igs-car-title">' . esc_html__($label, 'igs-ecommerce') . '</div>';
            if ($c['kind'] === 'text') {
                echo '<div class="igs-car-subtitle">' . esc_html($c['value']) . '</div>';
            } else {
                echo '<div class="igs-car-rating">';
                for ($i = 1; $i <= 5; $i++) {
                    $fill = $i <= $c['value'] ? 'var(--igs-brand)' : '#e2e8f0';
                    echo '<span style="background:' . esc_attr($fill) . ';"></span>';
                }
                echo '</div>';
            }
            echo '</div>';
        }
        echo '</div>';
    }

    private function renderHeroLazyScript(): void
    {
        ?>
        <script>
        (function(){
            var el = document.querySelector('.igs-hero-lazy');
            if(!el || !el.dataset.bg) return;
            if('IntersectionObserver' in window){
                var io = new IntersectionObserver(function(entries){
                    if(entries[0].isIntersecting){
                        el.style.backgroundImage = 'url(' + el.dataset.bg + ')';
                        el.classList.remove('igs-hero-lazy');
                        io.disconnect();
                    }
                }, { rootMargin: '50px' });
                io.observe(el);
            } else { el.style.backgroundImage = 'url(' + el.dataset.bg + ')'; el.classList.remove('igs-hero-lazy'); }
        })();
        </script>
        <?php
    }
}
