<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Shortcodes;

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
        .mappa-viaggio-wrapper { position: relative; }
        #mappa-viaggio-<?php echo (int) $postId; ?> { width: 100%; height: 500px; max-width: 100%; }
        @media (max-width: 768px) { #mappa-viaggio-<?php echo (int) $postId; ?> { height: 350px; } }
        .custom-pin { display:flex; justify-content:center; align-items:center; font-weight:bold; background-color:#0c5764; color:#fff;
            border-radius:50%; width:36px; height:36px; text-align:center; line-height:36px; font-size:14px; }
        .mappa-viaggio-error { position:absolute; top:0; left:0; right:0; bottom:0; background:#f5f5f5; display:none; flex-direction:column; align-items:center; justify-content:center; gap:12px; padding:20px; z-index:600; }
        .mappa-viaggio-error.visible { display:flex; }
        .mappa-viaggio-error p { margin:0; color:#555; text-align:center; }
        .mappa-viaggio-error .button { padding:10px 20px; background:#0c5764; color:#fff; border:none; border-radius:6px; cursor:pointer; font-size:0.95rem; }
        .mappa-viaggio-error .button:hover { background:#0a434c; }
        </style>
        <div class="mappa-viaggio-wrapper">
        <div class="mappa-viaggio-error" id="mappa-error-<?php echo (int) $postId; ?>">
            <p><?php echo esc_html__('Errore nel caricamento della mappa.', 'igs-ecommerce'); ?></p>
            <button type="button" class="button"><?php echo esc_html__('Riprova', 'igs-ecommerce'); ?></button>
        </div>
        <div id="mappa-viaggio-<?php echo (int) $postId; ?>"></div>
        </div>
        <script>
        document.addEventListener("DOMContentLoaded", function(){
            var errEl = document.getElementById('mappa-error-<?php echo (int) $postId; ?>');
            function showError(){ if(errEl) errEl.classList.add('visible'); }
            function initMap(){
                if(typeof L === 'undefined'){ showError(); return null; }
                try {
            var map = L.map('mappa-viaggio-<?php echo (int) $postId; ?>', { scrollWheelZoom: false, tap: true });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors'
            }).addTo(map);

            var tappe = <?php echo wp_json_encode($tappeForJs); ?>;
            var punti = [];

            tappe.forEach(function(tappa, i){
                if(!tappa.lat || !tappa.lon) return;
                var marker = L.marker([tappa.lat, tappa.lon], {
                    icon: L.divIcon({
                        className: 'custom-pin',
                        html: '<div>'+(i+1)+'</div>',
                    iconSize: [36, 36],
                    popupAnchor: [0, -18]
                    })
                }).addTo(map).bindPopup('<b>'+ (tappa.nomeSafe || '') +'</b><br>'+ (tappa.descrizioneSafe || ''), { maxWidth: 250 });
                punti.push([tappa.lat, tappa.lon]);
            });

            if (punti.length) {
                L.polyline(punti, {color: '#0c5764', dashArray: '5, 10'}).addTo(map);
                map.fitBounds(punti);
            }

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
                    location.reload();
                });
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }
}
