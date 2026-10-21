<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Shortcodes;

use IGS\Ecommerce\Data\Repertorio;

class RepertorioShortcode
{
    public function register(): void
    {
        add_shortcode('igs_repertorio', [$this, 'render']);
    }

    /**
     * @param array<string, mixed>|string $atts
     */
    public function render($atts = []): string
    {
        $atts = shortcode_atts([
            'lang' => 'it',
        ], (array) $atts, 'igs_repertorio');

        $lang = strtolower(trim((string) ($atts['lang'] ?? 'it')));
        if ($lang !== 'en') {
            $lang = 'it';
        }

        // Anti-doppione: un solo render per pagina.
        static $rendered = false;
        if ($rendered) {
            return '';
        }
        $rendered = true;

        wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], null, true);
        wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');

        wp_enqueue_script('leaflet-markercluster', 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js', ['leaflet'], null, true);
        wp_enqueue_style('leaflet-markercluster-css', 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css');
        wp_enqueue_style('leaflet-markercluster-default-css', 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css');

        $areas = Repertorio::getAreas();
        $rawItems = Repertorio::getItems();

        $allLabel = ($lang === 'en') ? 'All' : 'Tutti';
        $upcomingBadgeText = ($lang === 'en') ? 'Upcoming' : 'In calendario';
        $discoverLinkText = ($lang === 'en') ? 'Discover the tour' : 'Scopri il viaggio';
        $askLinkText = ($lang === 'en') ? 'Ask for this itinerary' : 'Chiedi questo itinerario su misura';
        $loadingText = ($lang === 'en') ? 'Loading map...' : 'Caricamento mappa...';
        $fallbackText = ($lang === 'en')
            ? 'Map currently unavailable. You can browse the complete tour catalogue below.'
            : 'Mappa non disponibile al momento. Puoi consultare il repertorio completo dei viaggi qui sotto.';
        $mapAriaLabel = ($lang === 'en') ? 'Interactive map of tour repertoire' : 'Mappa interattiva del repertorio viaggi';
        $filtersAriaLabel = ($lang === 'en') ? 'Filter tours by area' : 'Filtra i viaggi per area geografica';
        $listAriaLabel = ($lang === 'en') ? 'Tour directory' : 'Elenco del repertorio viaggi';

        $defaultContactUrl = ($lang === 'en')
            ? (function_exists('home_url') ? home_url('/en/contacts/') : '/en/contacts/')
            : (function_exists('home_url') ? home_url('/contatti/') : '/contatti/');

        $processedItems = [];
        $countsByArea = [];
        foreach ($areas as $k => $v) {
            $countsByArea[$k] = 0;
        }

        foreach ($rawItems as $index => $item) {
            $areaKey = (string) $item['area'];
            $title = ($lang === 'en') ? (string) $item['title_en'] : (string) $item['title_it'];
            $areaLabel = $areas[$areaKey][$lang] ?? $areaKey;

            $hasProduct = false;
            $url = $defaultContactUrl;
            $linkText = $askLinkText;

            if (!empty($item['product'])) {
                $productId = (int) $item['product'];
                $isPublished = true;
                if (function_exists('get_post_status')) {
                    $isPublished = (get_post_status($productId) === 'publish');
                }

                if ($isPublished) {
                    $permalink = function_exists('get_permalink') ? (string) get_permalink($productId) : '';
                    if ($permalink !== '') {
                        $hasProduct = true;
                        $url = $permalink;
                        $linkText = $discoverLinkText;
                    }
                }
            }

            $countsByArea[$areaKey] = ($countsByArea[$areaKey] ?? 0) + 1;

            $processedItems[] = [
                'id'         => $index,
                'area'       => $areaKey,
                'areaLabel'  => $areaLabel,
                'title'      => $title,
                'lat'        => (float) $item['lat'],
                'lon'        => (float) $item['lon'],
                'hasProduct' => $hasProduct,
                'url'        => $url,
                'linkText'   => $linkText,
            ];
        }

        $totalCount = count($processedItems);

        ob_start();
        ?>
        <style>
        .igs-rep-container {
            margin: 2em 0;
            border-radius: 12px;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            position: relative;
            box-sizing: border-box;
            color: #1e293b;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        .igs-rep-container *, .igs-rep-container *::before, .igs-rep-container *::after {
            box-sizing: border-box;
        }

        /* Barra filtri */
        .igs-rep-filters {
            padding: 14px 18px;
            background: #ffffff;
            border-bottom: 1px solid #edf2f7;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .igs-rep-filter-scroll {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        @media (max-width: 899px) {
            .igs-rep-filter-scroll {
                flex-wrap: nowrap;
                padding-bottom: 4px;
            }
        }
        .igs-rep-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            color: #334155;
            font-family: inherit;
            font-size: 0.82rem;
            font-weight: 500;
            cursor: pointer;
            white-space: nowrap;
            line-height: 1.2;
            transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
        }
        .igs-rep-chip:hover {
            border-color: #0e5763;
            color: #0e5763;
            background-color: #f1f7f8;
        }
        .igs-rep-chip:focus-visible {
            outline: 2px solid #0e5763;
            outline-offset: 2px;
        }
        .igs-rep-chip.is-active,
        .igs-rep-chip[aria-pressed="true"] {
            background-color: #0e5763;
            border-color: #0e5763;
            color: #ffffff;
            font-weight: 600;
        }
        .igs-rep-chip-count {
            display: inline-block;
            padding: 1px 6px;
            font-size: 0.72rem;
            font-weight: 600;
            border-radius: 999px;
            background: rgba(0, 0, 0, 0.08);
            line-height: 1.2;
        }
        .igs-rep-chip.is-active .igs-rep-chip-count,
        .igs-rep-chip[aria-pressed="true"] .igs-rep-chip-count {
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff;
        }

        /* Layout principale */
        .igs-rep-body {
            display: flex;
            height: 560px;
            position: relative;
        }
        @media (max-width: 899px) {
            .igs-rep-body {
                flex-direction: column;
                height: auto;
            }
        }

        /* Colonna mappa (desktop 2/3) */
        .igs-rep-map-col {
            flex: 0 0 66.666%;
            width: 66.666%;
            height: 560px;
            position: relative;
            background: #f8fafc;
        }
        @media (max-width: 899px) {
            .igs-rep-map-col {
                flex: none;
                width: 100%;
                height: 380px;
            }
        }
        .igs-rep-map-wrapper {
            position: relative;
            width: 100%;
            height: 100%;
        }
        #igs-rep-map {
            width: 100%;
            height: 100%;
            background-color: #f8fafc;
        }

        /* Loader & Fallback */
        .igs-rep-loading {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            z-index: 500;
            transition: opacity 0.3s ease;
        }
        .igs-rep-loading.is-hidden {
            opacity: 0;
            pointer-events: none;
        }
        .igs-rep-spinner {
            width: 36px;
            height: 36px;
            border: 3px solid #e2e8f0;
            border-top-color: #0e5763;
            border-radius: 50%;
            animation: igs-rep-spin 0.8s linear infinite;
        }
        .igs-rep-loading-text {
            font-size: 0.85rem;
            color: #64748b;
            font-weight: 500;
        }
        @keyframes igs-rep-spin {
            to { transform: rotate(360deg); }
        }
        .igs-rep-map-fallback {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #f8fafc;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
            text-align: center;
            z-index: 450;
        }
        .igs-rep-map-fallback.is-visible {
            display: flex;
        }
        .igs-rep-map-fallback p {
            margin: 0;
            font-size: 0.95rem;
            color: #475569;
            max-width: 360px;
            line-height: 1.5;
        }

        /* Colonna elenco (desktop 1/3) */
        .igs-rep-list-col {
            flex: 0 0 33.334%;
            width: 33.334%;
            height: 560px;
            background: #ffffff;
            border-left: 1px solid #edf2f7;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        @media (max-width: 899px) {
            .igs-rep-list-col {
                flex: none;
                width: 100%;
                height: 440px;
                border-left: none;
                border-top: 1px solid #edf2f7;
            }
        }
        .igs-rep-list {
            flex: 1;
            overflow-y: auto;
            padding: 12px 14px;
            scroll-behavior: smooth;
        }
        .igs-rep-area-group {
            margin-bottom: 16px;
        }
        .igs-rep-area-heading {
            margin: 8px 0 6px 0;
            padding: 4px 6px;
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #64748b;
            border-bottom: 1px solid #f1f5f9;
        }
        .igs-rep-items {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        .igs-rep-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            text-align: left;
            background: transparent;
            border: 1px solid transparent;
            border-radius: 8px;
            padding: 7px 9px;
            font-family: inherit;
            font-size: 0.88rem;
            color: #1e293b;
            cursor: pointer;
            transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
            line-height: 1.35;
        }
        .igs-rep-item:hover {
            background-color: #f1f7f8;
            color: #0e5763;
        }
        .igs-rep-item:focus-visible {
            outline: 2px solid #0e5763;
            outline-offset: 1px;
            background-color: #f1f7f8;
        }
        .igs-rep-item.is-active {
            background-color: #e6f1f3;
            border-color: #0e5763;
            color: #0e5763;
            font-weight: 600;
        }
        .igs-rep-item-title {
            flex: 1;
            padding-right: 8px;
        }
        .igs-rep-item.is-upcoming .igs-rep-item-title {
            font-weight: 600;
        }
        .igs-rep-item-badge {
            flex-shrink: 0;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            background-color: #fcf8ee;
            color: #745719;
            border: 1px solid #d4af37;
            padding: 2px 6px;
            border-radius: 999px;
            white-space: nowrap;
            line-height: 1.2;
        }

        /* Marker & Pin */
        .igs-rep-div-icon {
            background: transparent !important;
            border: none !important;
        }
        .igs-rep-pin {
            display: block;
            box-sizing: border-box;
            border-radius: 50%;
            border: 2px solid #ffffff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            cursor: pointer;
        }
        .igs-rep-pin--standard {
            width: 20px;
            height: 20px;
            background-color: #0e5763;
        }
        .igs-rep-pin--upcoming {
            width: 28px;
            height: 28px;
            background-color: #b08d3c;
            border: 2.5px solid #ffffff;
            box-shadow: 0 3px 10px rgba(176, 141, 60, 0.5), 0 2px 6px rgba(0, 0, 0, 0.25);
            position: relative;
        }
        .igs-rep-pin--upcoming::after {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 8px;
            height: 8px;
            background-color: #ffffff;
            border-radius: 50%;
        }
        .igs-rep-pin.is-highlighted,
        .igs-rep-pin:hover {
            transform: scale(1.3);
            box-shadow: 0 0 0 4px rgba(14, 87, 99, 0.35), 0 4px 12px rgba(0, 0, 0, 0.35);
            z-index: 9999 !important;
        }
        .igs-rep-pin--upcoming.is-highlighted,
        .igs-rep-pin--upcoming:hover {
            box-shadow: 0 0 0 4px rgba(176, 141, 60, 0.45), 0 4px 14px rgba(0, 0, 0, 0.4);
        }

        /* Leaflet MarkerCluster brand override (#0e5763, nessun verde/giallo/arancio) */
        .igs-rep-container .marker-cluster-small,
        .igs-rep-container .marker-cluster-medium,
        .igs-rep-container .marker-cluster-large {
            background-color: rgba(14, 87, 99, 0.22) !important;
        }
        .igs-rep-container .marker-cluster-small div,
        .igs-rep-container .marker-cluster-medium div,
        .igs-rep-container .marker-cluster-large div {
            background-color: #0e5763 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 13px !important;
            font-family: inherit !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        .igs-rep-container .marker-cluster:hover,
        .igs-rep-container .marker-cluster.is-highlighted {
            background-color: rgba(14, 87, 99, 0.38) !important;
        }
        .igs-rep-container .marker-cluster:hover div,
        .igs-rep-container .marker-cluster.is-highlighted div {
            background-color: #0a434c !important;
        }

        /* Popup */
        .igs-rep-leaflet-popup .leaflet-popup-content-wrapper {
            border-radius: 10px;
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.15);
            padding: 0;
            overflow: hidden;
        }
        .igs-rep-leaflet-popup .leaflet-popup-content {
            margin: 14px 16px;
            line-height: 1.4;
        }
        .igs-rep-popup-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 4px;
        }
        .igs-rep-popup-area {
            font-size: 0.72rem;
            font-weight: 700;
            font-variant: small-caps;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
        }
        .igs-rep-popup-badge {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            background-color: #fcf8ee;
            color: #745719;
            border: 1px solid #d4af37;
            padding: 1px 6px;
            border-radius: 999px;
            white-space: nowrap;
        }
        .igs-rep-popup-title {
            margin: 4px 0 10px 0;
            font-family: var(--wp--preset--font-family--serif, Georgia, "Times New Roman", serif, inherit);
            font-size: 1.05rem;
            font-weight: 600;
            color: #0e5763;
            line-height: 1.3;
        }
        .igs-rep-popup-link {
            display: inline-block;
            padding: 7px 14px;
            background-color: #0e5763;
            color: #ffffff !important;
            text-decoration: none !important;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 600;
            line-height: 1.2;
            transition: background-color 0.15s ease;
        }
        .igs-rep-popup-link:hover,
        .igs-rep-popup-link:focus-visible {
            background-color: #0a434c;
            color: #ffffff !important;
            text-decoration: none !important;
        }
        .igs-rep-popup-link--product {
            background-color: #987526;
        }
        .igs-rep-popup-link--product:hover,
        .igs-rep-popup-link--product:focus-visible {
            background-color: #7b5e1c;
        }

        /* Accessibilità: reduced motion */
        @media (prefers-reduced-motion: reduce) {
            .igs-rep-spinner {
                animation: none !important;
            }
            .igs-rep-pin,
            .igs-rep-item,
            .igs-rep-chip,
            .igs-rep-loading,
            .igs-rep-list,
            .igs-rep-container .marker-cluster {
                transition: none !important;
                scroll-behavior: auto !important;
            }
        }
        </style>

        <noscript>
            <style>
                .igs-rep-loading { display: none !important; }
                .igs-rep-map-fallback { display: flex !important; }
            </style>
        </noscript>

        <div class="igs-rep-container">
            <!-- Barra filtri per area -->
            <div class="igs-rep-filters" role="toolbar" aria-label="<?php echo esc_attr($filtersAriaLabel); ?>">
                <div class="igs-rep-filter-scroll">
                    <button type="button" class="igs-rep-chip is-active" data-area="all" aria-pressed="true">
                        <span><?php echo esc_html($allLabel); ?></span>
                        <span class="igs-rep-chip-count"><?php echo (int) $totalCount; ?></span>
                    </button>
                    <?php foreach ($areas as $areaKey => $areaData): ?>
                        <button type="button" class="igs-rep-chip" data-area="<?php echo esc_attr($areaKey); ?>" aria-pressed="false">
                            <span><?php echo esc_html($areaData[$lang] ?? $areaKey); ?></span>
                            <span class="igs-rep-chip-count"><?php echo (int) ($countsByArea[$areaKey] ?? 0); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Corpo principale: Mappa e Elenco -->
            <div class="igs-rep-body">
                <!-- Colonna Mappa -->
                <div class="igs-rep-map-col">
                    <div class="igs-rep-map-wrapper">
                        <div class="igs-rep-loading" id="igs-rep-loading">
                            <div class="igs-rep-spinner" aria-hidden="true"></div>
                            <span class="igs-rep-loading-text"><?php echo esc_html($loadingText); ?></span>
                        </div>
                        <div class="igs-rep-map-fallback" id="igs-rep-fallback">
                            <p><?php echo esc_html($fallbackText); ?></p>
                        </div>
                        <div id="igs-rep-map" role="region" aria-label="<?php echo esc_attr($mapAriaLabel); ?>" tabindex="0"></div>
                    </div>
                </div>

                <!-- Colonna Elenco (Progressive Enhancement) -->
                <div class="igs-rep-list-col">
                    <div class="igs-rep-list" id="igs-rep-list" role="region" aria-label="<?php echo esc_attr($listAriaLabel); ?>">
                        <?php foreach ($areas as $areaKey => $areaData):
                            $areaItems = array_filter($processedItems, static fn(array $it): bool => $it['area'] === $areaKey);
                            if (empty($areaItems)) {
                                continue;
                            }
                        ?>
                            <div class="igs-rep-area-group" data-area="<?php echo esc_attr($areaKey); ?>">
                                <h3 class="igs-rep-area-heading"><?php echo esc_html($areaData[$lang] ?? $areaKey); ?></h3>
                                <div class="igs-rep-items">
                                    <?php foreach ($areaItems as $item): ?>
                                        <button type="button"
                                                class="igs-rep-item<?php echo $item['hasProduct'] ? ' is-upcoming' : ''; ?>"
                                                data-id="<?php echo (int) $item['id']; ?>"
                                                data-area="<?php echo esc_attr($item['area']); ?>"
                                                aria-label="<?php echo esc_attr($item['title'] . ($item['hasProduct'] ? ' (' . $upcomingBadgeText . ')' : '')); ?>">
                                            <span class="igs-rep-item-title"><?php echo esc_html($item['title']); ?></span>
                                            <?php if ($item['hasProduct']): ?>
                                                <span class="igs-rep-item-badge"><?php echo esc_html($upcomingBadgeText); ?></span>
                                            <?php endif; ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Fallback SEO e No-JS per link diretti -->
            <noscript>
                <div class="igs-rep-noscript-links" style="padding: 16px 20px; background: #ffffff; border-top: 1px solid #edf2f7;">
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($processedItems as $item): ?>
                            <li style="margin-bottom: 6px;">
                                <a href="<?php echo esc_url($item['url']); ?>">
                                    <?php echo esc_html($item['title']); ?>
                                </a>
                                <span style="color: #64748b;">(<?php echo esc_html($item['areaLabel']); ?>)</span>
                                <?php if ($item['hasProduct']): ?>
                                    - <strong style="color: #745719;"><?php echo esc_html($upcomingBadgeText); ?></strong>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </noscript>
        </div>

        <script>
        document.addEventListener("DOMContentLoaded", function() {
            var mapEl = document.getElementById('igs-rep-map');
            var loader = document.getElementById('igs-rep-loading');
            var fallback = document.getElementById('igs-rep-fallback');
            var listEl = document.getElementById('igs-rep-list');
            if (!mapEl || !listEl) return;

            function showFallback() {
                if (loader) loader.style.display = 'none';
                if (fallback) fallback.classList.add('is-visible');
            }

            if (typeof L === 'undefined' || typeof L.markerClusterGroup === 'undefined') {
                showFallback();
                return;
            }

            try {
                var items = <?php echo wp_json_encode($processedItems); ?>;
                var upcomingBadgeLabel = <?php echo wp_json_encode($upcomingBadgeText); ?>;
                var prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                var map = L.map('igs-rep-map', {
                    scrollWheelZoom: false,
                    tap: true,
                    zoomControl: true
                });

                L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions" target="_blank" rel="noopener">CARTO</a>',
                    subdomains: 'abcd',
                    maxZoom: 19
                }).addTo(map);

                var clusterGroup = L.markerClusterGroup({
                    spiderfyOnMaxZoom: true,
                    showCoverageOnHover: false,
                    zoomToBoundsOnClick: true,
                    maxClusterRadius: 40,
                    spiderfyDistanceMultiplier: 1.3
                });
                map.addLayer(clusterGroup);

                function escapeHtml(str) {
                    if (!str) return '';
                    return String(str)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#39;');
                }

                function escapeUrl(str) {
                    if (!str) return '#';
                    var s = String(str);
                    if (/^(https?:|\/)/i.test(s)) {
                        return s.replace(/"/g, '&quot;');
                    }
                    return '#';
                }

                var standardIcon = L.divIcon({
                    className: 'igs-rep-div-icon',
                    html: '<span class="igs-rep-pin igs-rep-pin--standard"></span>',
                    iconSize: [20, 20],
                    iconAnchor: [10, 10],
                    popupAnchor: [0, -12]
                });

                var upcomingIcon = L.divIcon({
                    className: 'igs-rep-div-icon',
                    html: '<span class="igs-rep-pin igs-rep-pin--upcoming"></span>',
                    iconSize: [28, 28],
                    iconAnchor: [14, 14],
                    popupAnchor: [0, -16]
                });

                function buildPopupHtml(item) {
                    var badgeHtml = item.hasProduct
                        ? '<span class="igs-rep-popup-badge">' + escapeHtml(upcomingBadgeLabel) + '</span>'
                        : '';
                    var linkClass = item.hasProduct
                        ? 'igs-rep-popup-link igs-rep-popup-link--product'
                        : 'igs-rep-popup-link';
                    return '<div class="igs-rep-popup">' +
                        '<div class="igs-rep-popup-header">' +
                            '<span class="igs-rep-popup-area">' + escapeHtml(item.areaLabel) + '</span>' +
                            badgeHtml +
                        '</div>' +
                        '<h4 class="igs-rep-popup-title">' + escapeHtml(item.title) + '</h4>' +
                        '<a href="' + escapeUrl(item.url) + '" class="' + linkClass + '">' +
                            escapeHtml(item.linkText) +
                        '</a>' +
                    '</div>';
                }

                var markersById = {};
                var allMarkers = [];
                var allLatLngs = [];

                items.forEach(function(item) {
                    var icon = item.hasProduct ? upcomingIcon : standardIcon;
                    var marker = L.marker([item.lat, item.lon], {
                        icon: icon,
                        title: item.title,
                        zIndexOffset: item.hasProduct ? 500 : 0
                    });

                    marker.bindPopup(buildPopupHtml(item), {
                        maxWidth: 280,
                        className: 'igs-rep-leaflet-popup'
                    });

                    marker.on('click', function() {
                        setActiveListItem(item.id, true);
                    });

                    markersById[item.id] = marker;
                    allMarkers.push(marker);
                    allLatLngs.push([item.lat, item.lon]);
                });

                clusterGroup.addLayers(allMarkers);

                if (allLatLngs.length > 0) {
                    var initialBounds = L.latLngBounds(allLatLngs);
                    map.fitBounds(initialBounds, { padding: [40, 40], animate: false });
                }

                map.whenReady(function() {
                    if (loader) {
                        loader.classList.add('is-hidden');
                        setTimeout(function() { loader.style.display = 'none'; }, 300);
                    }
                });

                // scrollWheelZoom: attivo solo dopo click su mappa, disattivato al mouseleave
                map.on('click', function() {
                    map.scrollWheelZoom.enable();
                });
                mapEl.addEventListener('mouseleave', function() {
                    map.scrollWheelZoom.disable();
                });

                // Fix preventDefault su chiusura popup
                map.on('popupopen', function(e) {
                    var closeBtn = e.popup._closeButton;
                    if (closeBtn) {
                        closeBtn.addEventListener('click', function(evt) {
                            evt.preventDefault();
                            evt.stopPropagation();
                        });
                    }
                });

                var itemButtons = listEl.querySelectorAll('.igs-rep-item');
                var chipButtons = document.querySelectorAll('.igs-rep-chip');
                var areaGroups = listEl.querySelectorAll('.igs-rep-area-group');

                function highlightMarker(id, enable) {
                    var marker = markersById[id];
                    if (!marker) return;
                    var markerEl = marker.getElement();
                    if (markerEl) {
                        var pin = markerEl.querySelector('.igs-rep-pin');
                        if (pin) pin.classList.toggle('is-highlighted', enable);
                    }
                    if (typeof clusterGroup.getVisibleParent === 'function') {
                        var parent = clusterGroup.getVisibleParent(marker);
                        if (parent && parent !== marker) {
                            var parentEl = parent.getElement();
                            if (parentEl) parentEl.classList.toggle('is-highlighted', enable);
                        }
                    }
                }

                function setActiveListItem(id, shouldScroll) {
                    itemButtons.forEach(function(btn) {
                        var isCurrent = (btn.getAttribute('data-id') === String(id));
                        btn.classList.toggle('is-active', isCurrent);
                        if (isCurrent && shouldScroll) {
                            btn.scrollIntoView({
                                behavior: prefersReducedMotion ? 'auto' : 'smooth',
                                block: 'nearest'
                            });
                        }
                    });
                }

                function zoomToMarker(id) {
                    var marker = markersById[id];
                    var item = items.find(function(it) { return it.id === id; });
                    if (!marker || !item) return;

                    setActiveListItem(id, false);

                    clusterGroup.zoomToShowLayer(marker, function() {
                        var targetZoom = Math.max(map.getZoom(), 8);
                        if (prefersReducedMotion) {
                            map.setView([item.lat, item.lon], targetZoom, { animate: false });
                            marker.openPopup();
                        } else {
                            if (map.getZoom() < 8) {
                                map.flyTo([item.lat, item.lon], 8, { duration: 0.9 });
                                map.once('moveend', function() { marker.openPopup(); });
                            } else {
                                map.flyTo([item.lat, item.lon], targetZoom, { duration: 0.7 });
                                map.once('moveend', function() { marker.openPopup(); });
                            }
                            // Fallback per apertura immediata se già centrato
                            if (map.getCenter().distanceTo(L.latLng(item.lat, item.lon)) < 20) {
                                marker.openPopup();
                            }
                        }
                    });
                }

                itemButtons.forEach(function(btn) {
                    var id = parseInt(btn.getAttribute('data-id'), 10);

                    btn.addEventListener('mouseenter', function() {
                        highlightMarker(id, true);
                    });
                    btn.addEventListener('mouseleave', function() {
                        highlightMarker(id, false);
                    });
                    btn.addEventListener('focus', function() {
                        highlightMarker(id, true);
                    });
                    btn.addEventListener('blur', function() {
                        highlightMarker(id, false);
                    });
                    btn.addEventListener('click', function() {
                        zoomToMarker(id);
                    });
                });

                function filterByArea(selectedArea) {
                    chipButtons.forEach(function(chip) {
                        var isCurrent = (chip.getAttribute('data-area') === selectedArea);
                        chip.setAttribute('aria-pressed', isCurrent ? 'true' : 'false');
                        chip.classList.toggle('is-active', isCurrent);
                    });

                    areaGroups.forEach(function(group) {
                        var groupArea = group.getAttribute('data-area');
                        if (selectedArea === 'all' || groupArea === selectedArea) {
                            group.style.display = '';
                        } else {
                            group.style.display = 'none';
                        }
                    });

                    clusterGroup.clearLayers();
                    var filteredMarkers = [];
                    var filteredLatLngs = [];

                    items.forEach(function(item) {
                        if (selectedArea === 'all' || item.area === selectedArea) {
                            var m = markersById[item.id];
                            if (m) {
                                filteredMarkers.push(m);
                                filteredLatLngs.push([item.lat, item.lon]);
                            }
                        }
                    });

                    clusterGroup.addLayers(filteredMarkers);

                    if (filteredLatLngs.length > 0) {
                        var bounds = L.latLngBounds(filteredLatLngs);
                        map.fitBounds(bounds, {
                            padding: [40, 40],
                            maxZoom: selectedArea === 'all' ? 19 : 11,
                            animate: !prefersReducedMotion
                        });
                    }
                }

                chipButtons.forEach(function(chip) {
                    chip.addEventListener('click', function() {
                        var area = this.getAttribute('data-area');
                        filterByArea(area);
                    });
                });

            } catch (err) {
                if (window.console && console.error) {
                    console.error('IGS Repertorio error:', err);
                }
                showFallback();
            }
        });
        </script>
        <?php
        return (string) ob_get_clean();
    }
}
