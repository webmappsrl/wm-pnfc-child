<?php

add_shortcode('wm_grid_poi', 'wm_grid_poi');

function wm_grid_poi($atts)
{

    if (!is_admin()) {

        if (defined('ICL_LANGUAGE_CODE')) {
            $language = ICL_LANGUAGE_CODE;
        } else {
            $language = 'it';
        }

        extract(shortcode_atts(array(
            'poi_type' => 'huts',
            'quantity' => -1,
            'ids' => ''
        ), $atts));
        $ids_array = array();
        if ($ids) {
            if ($language == 'en') {
                $idsarray = explode(',', $ids);
                foreach ($idsarray as $id) {
                    $post_type = get_post_type($id);
                    $post_default_language_id = apply_filters('wpml_object_id', $id, $post_type, FALSE, $language);
                    array_push($ids_array, $post_default_language_id);
                }
            } else {
                $ids_array = explode(',', $ids);
            }
            $posts = get_posts(array('post_type' => 'page', 'post__in' => $ids_array, 'numberposts' => -1));
            usort($posts, function ($a, $b) {
                return strnatcasecmp($a->post_title, $b->post_title);
            });
            $quantity = count($posts);
        } else {
            $activity = strtolower($activity);

            $activities_url = "https://geohub.webmapp.it/api/app/elbrus/1/taxonomies/track_activity_$activity_mapped.json";
            $posts = json_decode(file_get_contents($activities_url), TRUE);
            if (is_array($posts)) {
                usort($posts, function ($a, $b) use ($language) {
                    return strnatcasecmp($a['name'][$language], $b['name'][$language]);
                });
            }
            if ($quantity == -1 || $quantity > count($posts)) {
                $quantity = count($posts);
            }
        }

        ob_start();
        ?><div class="wm-grid-track-item-container">
            <?php
                for ($i = 0; $i < $quantity; $i++) {
                    $post = $posts[$i];
                    $hideClass = '';
                    if ($ids) {
                        $post_url = esc_url(get_permalink($post->ID));
                        $image_url = get_the_post_thumbnail_url($post->ID);
                        $name =  $post->post_title;
                        $excerpt =  wp_filter_nohtml_kses(wp_trim_excerpt(preg_replace('#\[[^\]]+\]#', '', $post->post_content), $post->ID));
                        $hideClass = 'hidesection';
                        //TODO: trovare un modo migliore per discrimnare alcune activity
                        if (
                            strpos($post_url, "/a-cavallo/") || strpos($post_url, "/in-carrozza/") || strpos($post_url, "/in-canoa/") ||
                            strpos($post_url, "/tours-on-horseback/") || strpos($post_url, "/tours-on-chariot/") || strpos($post_url, "/tours-with-canadian-canoes/")
                        ) {
                            $excerpt = '';
                        }
                    } else {
                        $post_url = esc_url(get_permalink(get_page_by_title($post['name'][$language])));
                        $image_url = $post['image']['sizes']['400x200'];
                        if (array_key_exists('name', $post) && is_array($post['name']) && array_key_exists($language, $post['name'])) {
                            $name = $post['name'][$language];
                        } else {
                            $name = '';
                        }

                        if (array_key_exists('excerpt', $post) && is_array($post['excerpt']) && array_key_exists($language, $post['excerpt'])) {
                            $excerpt = $post['excerpt'][$language];
                        } else {
                            $excerpt = '';
                        }

                        if (array_key_exists('difficulty', $post) && is_array($post['difficulty']) && array_key_exists($language, $post['difficulty'])) {
                            $difficulty = $post['difficulty'][$language];
                        } else {
                            $difficulty = '';
                        }
                        if (array_key_exists('distance', $post)) {
                            $distance = $post['distance'];
                        } else {
                            $distance = '';
                        }
                    }

                ?>

                <div class="wm-grid-track-item">
                    <a href="<?= $post_url ?>" class="wm-grid-track-link">
                        <div class="wm-grid-track-intro" style="background-image:url(<?= $image_url ?>);">
                            <div class="wm-grid-track-overlay"></div>
                        </div>
                        <div class="wm-grid-track-info">
                            <div class="wm-grid-track-title"><?= $name ?></div>
                            <div class="wm-grid-track-info-detail">
                                <div class="wm-grid-track-difficulty">
                                    <?php if ($hideClass) {
                                            echo __('Discover more', 'wm-child-maremma');
                                        } else {
                                            echo __('Difficulty', 'wm-child-maremma') . ' <span>' . $difficulty . '</span> | ';
                                        } ?>
                                </div>
                                <div class="wm-grid-track-distance <?= $hideClass ?>">
                                    <?= __('KM', 'wm-child-maremma') . ' ' . $distance ?>
                                </div>

                                <div class="wm-grid-track-link-icon"><i class="far fa-arrow-right"></i></div>
                            </div>
                        </div>
                        <div class="wm-grid-track-excerpt"><?= $excerpt ?></div>
                    </a>
                </div>

            <?php
                }
            ?>
        </div><?php
                echo ob_get_clean();
            } else {
                return;
            }
        }
