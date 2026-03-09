<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Admin;

class MapMetabox
{
    public function register(): void
    {
        add_action('add_meta_boxes', [$this, 'addMetabox']);
        add_action('save_post_product', [$this, 'save']);
    }

    public function addMetabox(): void
    {
        add_meta_box(
            'mappa_tappe_meta',
            'Mappa del Viaggio',
            [$this, 'render'],
            'product',
            'normal',
            'default'
        );
    }

    public function render(\WP_Post $post): void
    {
        $paese = get_post_meta($post->ID, '_mappa_paese', true);
        $tappe = get_post_meta($post->ID, '_mappa_tappe', true);
        if (!is_array($tappe)) {
            $tappe = [];
        }

        wp_nonce_field('salva_mappa_tappe', 'mappa_tappe_nonce');

        echo '<p><label>Paese: <input type="text" name="mappa_paese" value="' . esc_attr($paese) . '" style="width: 100%;"></label></p>';
        echo '<div id="tappe-container">';

        foreach ($tappe as $i => $tappa) {
            echo '<div class="tappa-item" style="border:1px solid #ccc; padding:10px; margin-bottom:10px; position:relative;">';
            echo '<div style="display:flex; gap:8px; align-items:center; justify-content:space-between; margin-bottom:6px;">';
            echo '<label style="flex:1;">Nome località: <input type="text" name="mappa_tappe[' . (int) $i . '][nome]" value="' . esc_attr($tappa['nome'] ?? '') . '" style="width:80%;"> </label>';
            echo '<div style="display:flex; gap:6px; white-space:nowrap;">';
            echo '<button type="button" class="button trova-coord">Trova coordinate</button>';
            echo '<button type="button" class="button button-link-delete rimuovi-tappa" style="color:#b32d2e;">Rimuovi tappa</button>';
            echo '</div></div>';
            echo '<div style="display:flex; gap:10px; margin-bottom:8px;">';
            echo '<label style="flex:1;">Latitudine: <input type="text" name="mappa_tappe[' . (int) $i . '][lat]" value="' . esc_attr($tappa['lat'] ?? '') . '" style="width:100%;"></label>';
            echo '<label style="flex:1;">Longitudine: <input type="text" name="mappa_tappe[' . (int) $i . '][lon]" value="' . esc_attr($tappa['lon'] ?? '') . '" style="width:100%;"></label>';
            echo '</div>';
            echo '<label>Descrizione: <textarea name="mappa_tappe[' . (int) $i . '][descrizione]" rows="2" style="width:100%;">' . esc_textarea($tappa['descrizione'] ?? '') . '</textarea></label>';
            echo '</div>';
        }

        echo '</div>';
        echo '<button type="button" id="aggiungi-tappa" class="button">+ Aggiungi Tappa</button>';
        echo '<p style="margin-top:15px;font-style:italic;color:#555;">Shortcode per questa mappa: <code>[mappa_viaggio id="' . (int) $post->ID . '"]</code></p>';
        $this->renderScript();
    }

    public function save(int $postId): void
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!isset($_POST['mappa_tappe_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mappa_tappe_nonce'])), 'salva_mappa_tappe')) {
            return;
        }

        $paese = isset($_POST['mappa_paese']) ? sanitize_text_field(wp_unslash($_POST['mappa_paese'])) : '';
        update_post_meta($postId, '_mappa_paese', $paese);

        if (isset($_POST['mappa_tappe']) && is_array($_POST['mappa_tappe'])) {
            $raw = wp_unslash($_POST['mappa_tappe']);
            $pulite = [];
            foreach ($raw as $t) {
                $nome = isset($t['nome']) ? sanitize_text_field($t['nome']) : '';
                $lat = isset($t['lat']) ? $this->sanitizeCoord($t['lat'], -90, 90) : '';
                $lon = isset($t['lon']) ? $this->sanitizeCoord($t['lon'], -180, 180) : '';
                $desc = isset($t['descrizione']) ? sanitize_textarea_field($t['descrizione']) : '';
                if ($nome !== '') {
                    $pulite[] = [
                        'nome' => $nome,
                        'lat' => $lat,
                        'lon' => $lon,
                        'descrizione' => $desc,
                    ];
                }
            }
            if (!empty($pulite)) {
                update_post_meta($postId, '_mappa_tappe', array_values($pulite));
            } else {
                delete_post_meta($postId, '_mappa_tappe');
            }
        } else {
            delete_post_meta($postId, '_mappa_tappe');
        }
    }

    private function sanitizeCoord(mixed $val, float $min, float $max): string
    {
        $v = is_numeric($val) ? (float) $val : 0.0;
        $v = max($min, min($max, $v));
        return $v !== 0.0 || $val === '0' || $val === 0 ? (string) round($v, 6) : '';
    }

    private function renderScript(): void
    {
        ?>
        <script>
        jQuery(document).ready(function($){
            var $container = $('#tappe-container');

            function reindexTappe(){
                $container.find('.tappa-item').each(function(idx){
                    $(this).find('input, textarea').each(function(){
                        var name = $(this).attr('name');
                        if(!name) return;
                        name = name.replace(/mappa_tappe\[\d+\]/, 'mappa_tappe['+idx+']');
                        $(this).attr('name', name);
                    });
                });
            }

            $('#aggiungi-tappa').on('click', function(){
                var idx = $container.find('.tappa-item').length;
                var html = ''
                + '<div class="tappa-item" style="border:1px solid #ccc; padding:10px; margin-bottom:10px; position:relative;">'
                + '  <div style="display:flex; gap:8px; align-items:center; justify-content:space-between; margin-bottom:6px;">'
                + '    <label style="flex:1;">Nome località: <input type="text" name="mappa_tappe['+idx+'][nome]" style="width:80%;"></label>'
                + '    <div style="display:flex; gap:6px; white-space:nowrap;">'
                + '      <button type="button" class="button trova-coord">Trova coordinate</button>'
                + '      <button type="button" class="button button-link-delete rimuovi-tappa" style="color:#b32d2e;">Rimuovi tappa</button>'
                + '    </div>'
                + '  </div>'
                + '  <div style="display:flex; gap:10px; margin-bottom:8px;">'
                + '    <label style="flex:1;">Latitudine: <input type="text" name="mappa_tappe['+idx+'][lat]" style="width:100%;"></label>'
                + '    <label style="flex:1;">Longitudine: <input type="text" name="mappa_tappe['+idx+'][lon]" style="width:100%;"></label>'
                + '  </div>'
                + '  <label>Descrizione: <textarea name="mappa_tappe['+idx+'][descrizione]" rows="2" style="width:100%;"></textarea></label>'
                + '</div>';
                $container.append(html);
                reindexTappe();
            });

            $(document).on('click', '.rimuovi-tappa', function(e){
                e.preventDefault();
                if(!confirm('Eliminare questa tappa?')) return;
                $(this).closest('.tappa-item').remove();
                reindexTappe();
            });

            $(document).on('click', '.trova-coord', function(e){
                e.preventDefault();
                var btn = $(this);
                var wrapper = btn.closest('.tappa-item');
                var $nome = wrapper.find('input[name*="[nome]"]');
                var $lat  = wrapper.find('input[name*="[lat]"]');
                var $lon  = wrapper.find('input[name*="[lon]"]');
                var $msg = wrapper.find('.igs-geo-feedback');
                if(!$msg.length) $msg = $('<span class="igs-geo-feedback" style="margin-left:8px;font-size:13px;"></span>').insertAfter(btn);
                var nomeLocalita = ($nome.val() || '').trim();
                if(!nomeLocalita){ alert('Inserisci il nome della località'); return; }

                btn.prop('disabled', true);
                $msg.removeClass('success error').text('Ricerca in corso...').show();
                var url = "https://nominatim.openstreetmap.org/search?format=json&q=" + encodeURIComponent(nomeLocalita);
                fetch(url, { headers: {'Accept':'application/json'} })
                .then(function(r){ return r.json(); })
                .then(function(data){
                    if(Array.isArray(data) && data.length > 0){
                        $lat.val(data[0].lat);
                        $lon.val(data[0].lon);
                        $msg.addClass('success').css('color','#00a32a').text('Coordinate trovate');
                    } else {
                        $msg.addClass('error').css('color','#b32d2e').text('Località non trovata');
                    }
                    setTimeout(function(){ $msg.fadeOut(); }, 2500);
                })
                .catch(function(){
                    $msg.addClass('error').css('color','#b32d2e').text('Errore nella ricerca');
                    setTimeout(function(){ $msg.fadeOut(); }, 2500);
                })
                .finally(function(){ btn.prop('disabled', false); });
            });
        });
        </script>
        <?php
    }
}
