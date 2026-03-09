<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Shortcodes;

use IGS\Ecommerce\Helper\Locale;

class MapShortcode
{
    public function register(): void
    {
        add_shortcode('mappa_viaggio', [$this, 'render']);
    }

    public function render(array $atts): string
    {
        $atts = shortcode_atts(['id' => ''], $atts);
        $postId = (int) $atts['id'];
        if (!$postId) {
            return '';
        }

        $tappe = get_post_meta($postId, '_mappa_tappe', true);
        if (!is_array($tappe) || empty($tappe)) {
            return '';
        }

        $isIt = Locale::isIt();
        $tappeForJs = [];
        foreach ($tappe as $t) {
            $tappeForJs[] = [
                'nome' => $t['nome'] ?? '',
                'descrizione' => $t['descrizione'] ?? '',
                'lat' => $t['lat'] ?? '',
                'lon' => $t['lon'] ?? '',
                'nomeSafe' => esc_html($t['nome'] ?? ''),
                'descrizioneSafe' => esc_html($t['descrizione'] ?? ''),
            ];
        }

        wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], null, true);
        wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');

        ob_start();
        ?>
        <style>
        .mappa-viaggio-wrapper { margin: 1.5em 0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,.08); position: relative; }
        #mappa-viaggio-<?php echo (int) $postId; ?> { width: 100%; height: 500px; max-width: 100%; background: #f5f5f5; }
        @media (max-width: 768px) { #mappa-viaggio-<?php echo (int) $postId; ?> { height: 350px; } }
        .custom-pin { display:flex; justify-content:center; align-items:center; font-weight:bold; background-color:#0e5763; color:#fff;
            border-radius:50%; width:38px; height:38px; text-align:center; line-height:38px; font-size:14px;
            box-shadow: 0 2px 8px rgba(0,0,0,.2); border: 2px solid #fff; }
        .leaflet-popup-content-wrapper { border-radius: 10px; box-shadow: 0 4px 16px rgba(0,0,0,.12); }
        .leaflet-popup-content { margin: 14px 18px; font-size: 0.95rem; line-height: 1.5; }
        .mappa-viaggio-loading { position:absolute; top:0; left:0; right:0; bottom:0; background:#f5f5f5; display:flex; align-items:center; justify-content:center; z-index:500; transition:opacity .3s; }
        .mappa-viaggio-loading.hidden { opacity:0; pointer-events:none; }
        .mappa-viaggio-loading::after { content:""; width:36px; height:36px; border:3px solid #ddd; border-top-color:#0e5763; border-radius:50%; animation:igs-spin .8s linear infinite; }
        .mappa-viaggio-error { position:absolute; top:0; left:0; right:0; bottom:0; background:#f5f5f5; display:none; flex-direction:column; align-items:center; justify-content:center; gap:12px; padding:20px; z-index:600; }
        .mappa-viaggio-error.visible { display:flex; }
        .mappa-viaggio-error p { margin:0; color:#555; text-align:center; }
        .mappa-viaggio-error .button { padding:10px 20px; background:#0e5763; color:#fff; border:none; border-radius:6px; cursor:pointer; font-size:0.95rem; }
        .mappa-viaggio-error .button:hover { background:#0a434c; }
        @keyframes igs-spin { to { transform: rotate(360deg); } }
        </style>
        <div class="mappa-viaggio-wrapper">
        <div class="mappa-viaggio-loading" id="mappa-loading-<?php echo (int) $postId; ?>"></div>
        <div class="mappa-viaggio-error" id="mappa-error-<?php echo (int) $postId; ?>">
            <p><?php echo esc_html($isIt ? 'Errore nel caricamento della mappa.' : 'Map loading error.'); ?></p>
            <button type="button" class="button"><?php echo esc_html($isIt ? 'Riprova' : 'Retry'); ?></button>
        </div>
        <div id="mappa-viaggio-<?php echo (int) $postId; ?>"></div>
        </div>
        <script>
        document.addEventListener("DOMContentLoaded", function(){
            var mapEl = document.getElementById('mappa-viaggio-<?php echo (int) $postId; ?>');
            var loader = document.getElementById('mappa-loading-<?php echo (int) $postId; ?>');
            var errEl = document.getElementById('mappa-error-<?php echo (int) $postId; ?>');
            function showError(){ if(loader) loader.style.display='none'; if(errEl) errEl.classList.add('visible'); }
            function initMap(){
                if(typeof L === 'undefined'){ showError(); return null; }
                try {
            var map = L.map('mappa-viaggio-<?php echo (int) $postId; ?>', { scrollWheelZoom: false, tap: true });
            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>',
                subdomains: 'abcd',
                maxZoom: 19
            }).addTo(map);

            var tappe = <?php echo wp_json_encode($tappeForJs); ?>;
            var punti = [];

            tappe.forEach(function(tappa, i){
                if(!tappa.lat || !tappa.lon) return;
                var marker = L.marker([tappa.lat, tappa.lon], {
                    icon: L.divIcon({
                        className: 'custom-pin',
                        html: '<div>'+(i+1)+'</div>',
                    iconSize: [38, 38],
                    popupAnchor: [0, -19]
                    })
                }).addTo(map).bindPopup('<b>'+ (tappa.nomeSafe || '') +'</b><br>'+ (tappa.descrizioneSafe || ''), { maxWidth: 250 });
                punti.push([tappa.lat, tappa.lon]);
            });

            if (punti.length) {
                L.polyline(punti, {color: '#0e5763', dashArray: '5, 10'}).addTo(map);
                map.fitBounds(punti);
            }

            map.whenReady(function(){
                if(loader) { loader.classList.add('hidden'); setTimeout(function(){ loader.style.display='none'; }, 300); }
            });

            map.on('popupopen', function(e){
                var closeBtn = e.popup._closeButton;
                if (closeBtn) {
                    closeBtn.addEventListener('click', function(evt){ evt.preventDefault(); evt.stopPropagation(); });
                }
            });
            return map;
                } catch(e){ showError(); return null; }
            }
            var map = initMap();
            if(errEl){
                errEl.querySelector('.button').addEventListener('click', function(){
                    errEl.classList.remove('visible');
                    if(loader) loader.style.display='flex';
                    location.reload();
                });
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }
}
