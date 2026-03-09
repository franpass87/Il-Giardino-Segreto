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
        #mappa-viaggio-<?php echo (int) $postId; ?> { width: 100%; height: 500px; max-width: 100%; }
        @media (max-width: 768px) { #mappa-viaggio-<?php echo (int) $postId; ?> { height: 350px; } }
        .custom-pin{ display:flex; justify-content:center; align-items:center; font-weight:bold; background-color:#0c5764; color:#fff;
            border-radius:50%; width:36px; height:36px; text-align:center; line-height:36px; font-size:14px; }
        </style>
        <div id="mappa-viaggio-<?php echo (int) $postId; ?>"></div>
        <script>
        document.addEventListener("DOMContentLoaded", function(){
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
        });
        </script>
        <?php
        return ob_get_clean();
    }
}
