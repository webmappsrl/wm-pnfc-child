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

	// <iframe src="https://geohub.webmapp.it/w/simple/16294" frameborder="0"></iframe>
	$single_track_base_url = get_option('track_url');
	$geojson_url = $single_track_base_url . $track_id;

	$track = json_decode(file_get_contents($geojson_url), true);
	$track = $track['properties'];
	// echo '<pre>';
	// print_r($track);
	// echo '</pre>';
	$excerpt = null;
	if (array_key_exists('excerpt', $track) && array_key_exists($language, $track['excerpt'])) {
		$excerpt = $track['excerpt'][$language];
	}
	$description = $track['description'][$language];
	$title = $track['name'][$language];
	$featured_image = get_stylesheet_directory_uri() . '/assets/images/background.jpg';
	// echo '<pre>';
	// print_r($featured_image);
	// echo '</pre>';
	$featured_image = $track['feature_image']['url'];
	$featured_image = $track['feature_image']['sizes']['1440x500'];
	$gallery = array_key_exists('image_gallery', $track) ? $track['image_gallery'] : null;
	$gpx = $track['gpx_url'];

	$mapping = array();

	ob_start();
?>

	<section class="l-section wpb_row height_small wm_track_breadcrumb_section">
		<div class="l-section-h i-cf">
			<div class="pm-breadcrumb-yoast">
				<div class="wpb_wrapper">
					<?php echo do_shortcode('[wpseo_breadcrumb]'); ?>
				</div>
			</div>
		</div>
	</section>
	<section class="l-section wpb_row height_small with_img with_overlay wm_track_header_section">
		<div class="l-section-img loaded wm-header-image" style="background-image: url(<?= $featured_image ?>);background-repeat: no-repeat;">
		</div>
		<div class="l-section-h i-cf wm_track_header_wrapper">
		</div>
	</section>
	<div class="wm_track_body_section">
		<div class="wm_track_body_map_wrapper">
			<h1 class="align_left wm_track_header_title">
				<?= $title ?>
			</h1>
			<div class="wm_track_body_map">
				<?php
				echo do_shortcode('[wm-embedmaps geojson_url="' . $geojson_url . '" height="500px" lang="' . $language . '" related_poi_click_behaviour="open" show_related_pois="true" fullscreen="true"  hide_taxonomy_filters="true"]');
				?>
			</div>
		</div>
		<?php if ($description) { ?>
			<div class="wm_track_body_description">
				<?php echo $description; ?>
			</div>
		<?php } ?>
		<div class="wm_track_body_gallery">
			<?php if (is_array($gallery) && !empty($gallery)) : ?>
				<!-- Swiper -->
				<div class="swiper-container">
					<div class="swiper-wrapper">
						<?php foreach ($gallery as $image) : ?>
							<div class="swiper-slide">
								<img src="<?= esc_url($image['sizes']['400x200']) ?>" alt="" loading="lazy">
							</div>
						<?php endforeach; ?>
					</div>
					<!-- Add Pagination -->
					<div class="swiper-pagination"></div>
					<!-- Add Navigation -->
					<div class="swiper-button-prev"></div>
					<div class="swiper-button-next"></div>
				</div>
			<?php endif; ?>
		</div>


		<div class="wm_track_body_sidebar_wrapper">
			<div class="wm_track_body_map_details">
				<div class="wm_track_detail">
					<p class="track_sidebar_label">
						<?= __('Tecnical info', 'wm-child-maremma') ?>
					</p>
				</div>
				<?php
				echo do_shortcode('[wm-embedmaps-technical-info feature_id="' . $track_id . '-' . $track_id . '" config="ele_from,ele_to,ele_max,ele_min,distance,duration_forward,ascent,descent,difficulty,scale"]');
				?>
				<div class="wm_track_body_map_elevation">
					<p class="track_sidebar_label">
						<?= __('Elevation chart', 'wm-child-maremma') ?>
					</p>
					<div class="wm-elevation-chart">
						<?php
						echo do_shortcode('[wm-embedmaps-elevation-chart feature_id="' . $track_id . '-' . $track_id . '"]');
						?>
					</div>
				</div>
				<div class="wm_track_body_download">
					<a class="icon_atleft" href="<?= $gpx ?>"><?= __('Download GPX', 'wm-child-maremma') ?></span></a>
				</div>
			</div>
		</div>
	</div>

	<script>
		document.addEventListener('DOMContentLoaded', function() {
			var swiper = new Swiper('.swiper-container', {
				loop: true,
				autoplay: {
					delay: 5000,
				},
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