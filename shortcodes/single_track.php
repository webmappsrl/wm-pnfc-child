<?php
if (!is_admin()) {
	add_shortcode('wm_single_track', 'wm_single_track_pnfc');
}

function wm_single_track_pnfc($atts)
{
	if (defined('ICL_LANGUAGE_CODE')) {
		$language = ICL_LANGUAGE_CODE;
	} else {
		$language = 'it';
	}
	extract(shortcode_atts(array(
		'track_id' => '',
	), $atts));

	$single_track_base_url = get_option('track_url');
	$geojson_url = $single_track_base_url . $track_id;

	$track = json_decode(file_get_contents($geojson_url), true);
	$track = $track['properties'];
	// echo '<pre>';
	// print_r($track);
	// echo '</pre>';

	$description = null;
	$excerpt = null;
	$title = null;
	$featured_image = null;
	$gallery = [];
	$gpx = null;
	$distance = null;
	$ele_min = null;
	$ele_max = null;
	$duration_forward = null;
	$difficulty = null;

	if ($track) {
		$description = $track['description'][$language] ?? null;
		$excerpt = $track['excerpt'][$language] ?? null;
		$title = $track['name'][$language] ?? null;
		$featured_image_url = $track['feature_image']['url'] ?? get_stylesheet_directory_uri() . '/assets/images/background.jpg';
		$featured_image = $track['feature_image']['sizes']['1440x500'] ?? $featured_image_url;
		$gallery = $track['image_gallery'] ?? [];
		$gpx = $track['gpx_url'] ?? null;
		$distance = $track['distance'] ?? null;
		$ele_min = $track['ele_min'] ?? null;
		$ele_max = $track['ele_max'] ?? null;
		$duration_forward = $track['duration_forward'] ?? null;
		$difficulty = $track['difficulty'] ?? null;
	}
	ob_start();
?>

	<section class="l-section wpb_row height_small with_img with_overlay wm_header_section">
		<div class="l-section-img loaded wm-header-image" style="background-image: url(<?= $featured_image ?>);background-repeat: no-repeat;">
		</div>
		<div class="l-section-h i-cf wm_header_wrapper">
		</div>
	</section>

	<div class="wm_body_section">
		<div class="wm_body_map_wrapper">
			<?php if ($title) { ?>
				<h1 class="align_left wm_header_title">
					<?= $title ?>
				</h1>
			<?php } ?>
			<?php if ($excerpt) { ?>
				<p class="wm_excerpt"><?php echo wp_kses_post($excerpt); ?></p>
			<?php } ?>
			<div class="wm_body_map">
				<div class="wm_info_poi">
					<?php
					if ($language == 'en') {
						$difficulty_label = 'Difficulty:';
					} else {
						$difficulty_label = 'Difficoltà:';
					}
					$info_parts = [];
					if (!empty($difficulty)) {
						$info_parts[] = '<span class="wm_difficulty_info">' . esc_html($difficulty_label) . ' ' . esc_html($difficulty) . '</span>';
					}
					if (!empty($distance)) {
						$info_parts[] = '<span class="wm_distance_info"><span class="fa fa-route"></span> ' . esc_html($distance) . ' km</span>';
					}
					if (!empty($ele_min)) {
						$info_parts[] = '<span class="wm_ele_min_info"><span class="fa fa-arrow-alt-circle-down"></span> ' . esc_html($ele_min) . ' m</span>';
					}
					if (!empty($ele_max)) {
						$info_parts[] = '<span class="wm_ele_max_info"><span class="fa fa-arrow-alt-circle-up"></span> ' . esc_html($ele_max) . ' m</span>';
					}
					if (!empty($duration_forward)) {
						$info_parts[] = '<span class="wm_duration"><span class="fa fa-clock"></span> ' . esc_html($duration_forward) . '</span>';
					}
					echo implode(' - ', $info_parts);
					?>
				</div>
				<?php
				if (!empty($gpx)) {
					echo do_shortcode("[leaflet-map min_zoom='1' max_zoom='16']");
					echo do_shortcode("[leaflet-gpx src='{$gpx}']");
				}
				?>
				<div class="wm_body_download">
					<?php if (!empty($gpx)) : ?>
						<a class="icon_atleft" href="<?= esc_url($gpx); ?>" target="_blank" rel="noopener noreferrer">
							<i class="fa fa-download"></i>
							<?= __('Download GPX', 'wm-child') ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<?php if ($description) { ?>
			<div class="wm_body_description">
				<?php echo $description; ?>
			</div>
		<?php } ?>

		<div class="wm_body_gallery">
			<?php if (is_array($gallery) && !empty($gallery)) : ?>
				<div class="swiper-container">
					<div class="swiper-wrapper">
						<?php foreach ($gallery as $image) : ?>
							<div class="swiper-slide">
								<?php
								$size_order = ['400x200', '1440x500', '335x250', '250x150'];
								$img_url = '';
								foreach ($size_order as $size) {
									if (isset($image['sizes'][$size])) {
										$img_url = esc_url($image['sizes'][$size]);
										break;
									}
								}
								if ($img_url) : ?>
									<img src="<?= $img_url ?>" alt="" loading="lazy">
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
					<div class="swiper-pagination"></div>
					<div class="swiper-button-prev"></div>
					<div class="swiper-button-next"></div>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<script>
		document.addEventListener('DOMContentLoaded', function() {
			var swiper = new Swiper('.swiper-container', {
				slidesPerView: 1,
				spaceBetween: 10,
				breakpoints: {
					768: {
						slidesPerView: 3,
						spaceBetween: 20
					},
				},
				freeMode: true,
				loop: true,
				pagination: {
					el: '.swiper-pagination',
					clickable: true,
				},
				navigation: {
					nextEl: '.swiper-button-next',
					prevEl: '.swiper-button-prev',
				},
			});
		});
	</script>
<?php

	return ob_get_clean();
}
?>