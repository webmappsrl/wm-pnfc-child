<?php
if (!is_admin()) {
	add_shortcode('wm_single_poi', 'wm_single_poi_pnfc');
}

function wm_single_poi_pnfc($atts)
{
	if (defined('ICL_LANGUAGE_CODE')) {
		$language = ICL_LANGUAGE_CODE;
	} else {
		$language = 'it';
	}

	extract(shortcode_atts(array(
		'poi_id' => '',
	), $atts));

	$single_poi_base_url = get_option('poi_url');
	$geojson_url = $single_poi_base_url . $poi_id;

	$response = wp_remote_get($geojson_url);
	if (is_wp_error($response)) {
		return 'Errore nel recuperare i dati del POI.';
	}

	$poi_data = wp_remote_retrieve_body($response);
	$poi = json_decode($poi_data, true);
	// echo '<pre>';
	// print_r($poi);
	// echo '</pre>';
	if (!$poi || !isset($poi['properties'])) {
		return 'Failed to load POI data.';
	}

	$poi_properties = $poi['properties'];
	$geometry = $poi['geometry'];

	if (!empty($geometry) && $geometry['type'] == 'Point' && !empty($geometry['coordinates'])) {
		$longitude = $geometry['coordinates'][0];
		$latitude = $geometry['coordinates'][1];
	} else {
		return 'POI coordinates not found.';
	}

	$featured_image = $poi_properties['feature_image']['sizes']['1440x500'] ?? '';
	$title = $poi_properties['name'][$language] ?? '';
	$description = $poi_properties['description'][$language] ?? '';
	$excerpt = $poi_properties['excerpt'][$language] ?? '';
	$gallery = $poi_properties['image_gallery'] ?? [];
	$addr_street = $poi_properties['addr_street'] ?? '';
	$addr_postcode = $poi_properties['addr_postcode'] ?? '';
	$addr_locality = $poi_properties['addr_locality'] ?? '';

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
			<h1 class="align_left wm_header_title">
				<?= $title ?>
			</h1>
			<?php if ($excerpt) { ?>
				<p class="wm_excerpt"><?php echo wp_kses_post($excerpt); ?></p>
			<?php } ?>
			<div class="wm_body_map">
				<?php
				echo do_shortcode("[leaflet-map lat=$latitude lng=$longitude zoom=16]");;
				echo do_shortcode('[leaflet-marker lat=$latitude lng=$longitude]');
				echo do_shortcode("[leaflet-marker lat=$latitude lng=$longitude]{$title}[/leaflet-marker]");
				?>
			</div>
			<?php if (!empty($addr_street) || !empty($addr_postcode) || !empty($addr_locality)) {
				echo '<div class="wm_address_info">';
				echo '<span class="fa fa-map-marker-alt"></span> ';
				echo '<span>';
				if (!empty($addr_street)) {
					echo wp_kses_post($addr_street) . ', ';
				}
				if (!empty($addr_postcode)) {
					echo wp_kses_post($addr_postcode) . ' ';
				}
				if (!empty($addr_locality)) {
					echo wp_kses_post($addr_locality);
				}
				echo '</span>';
				echo '</div>';
			} ?>
		</div>

		<?php if ($description) { ?>
			<div class="wm_body_description">
				<?php echo wp_kses_post($description); ?>
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