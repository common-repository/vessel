<?php
/**
 * Created by PhpStorm.
 * User: Aaron Allen
 * Date: 10/15/2018
 * Time: 4:47 AM
 */

abstract class VesselShortCode {
	const tag = 'vessel-campaign';

	public static function init() {
		add_shortcode(self::tag, array('VesselShortCode', 'shortCode'));
		add_action('media_buttons', array('VesselShortCode', 'mediaButton'));
		add_action('print_media_templates', array('VesselShortCode', 'printTemplates'));
	}

	public static $scriptIncluded = false;

	/**
	 * Register the short code for inserting a vessel campaign into a regular WP post.
	 * @param array $atts
	 * @param string  $content
	 *
	 * @return null|string
	 */
	public static function shortCode($atts = array(), $content = null) {
		if (!key_exists('id', $atts) || is_admin()) return $content;

		$campaignId = $atts['id'];

		$output = "<div id='vessel-campaign-$campaignId'></div><script></script>";

		// add all scripts
		wp_enqueue_script('mapbox_gl_script', 'https://api.mapbox.com/mapbox-gl-js/v1.0.0/mapbox-gl.js');
		wp_enqueue_script('owl-carousel_script', plugins_url('js/owl-carousel/owl.carousel.min.js', __FILE__), array('jquery'));

		$staticUrl = VESSEL_HOST. '/static/images/';
		// if the script has already been registered by the custom post type, the wp_add_inline_script method doesn't work
		if (!wp_script_is('vessel_campaign_script')) {
		    wp_enqueue_script('vessel_campaign_script', VESSEL_HOST.'/api/deliver/js', array('jquery'), VESSEL_VER);
            wp_add_inline_script('vessel_campaign_script', "VesselApp('vessel-campaign-$campaignId', $campaignId, '$staticUrl', true, true);");
            self::$scriptIncluded = true;
        } else if (!self::$scriptIncluded) {
		    $output .= "<script>VesselApp('vessel-campaign-$campaignId', $campaignId, '$staticUrl', true, true);</script>";
            self::$scriptIncluded = true;
		}

		// include the stylesheet
		wp_enqueue_style('mapbox_gl_style', 'https://api.mapbox.com/mapbox-gl-js/v1.0.0/mapbox-gl.css');
		wp_enqueue_style('vessel_campaign_style', VESSEL_HOST.'/static/css/vessel.css', array(), VESSEL_VER);
		wp_enqueue_style('owl-carousel_carousel', plugins_url('js/owl-carousel/assets/owl.carousel.css', __FILE__));
		wp_enqueue_style('owl-carousel_theme', plugins_url('js/owl-carousel/assets/owl.theme.default.css', __FILE__));

		return $output;
	}

	/**
	 * Display a button in the editor toolbar for adding a campaign.
	 *
	 * @param string $editor_id
	 */
	public static function mediaButton($editor_id = 'content') {
		static $instance = 0;
		$instance++;

		$post = get_post();

		// only show the button if this is not a vessel campaign
		if (is_null($post) || $post->post_type === VesselCampaignPost::POST_TYPE) return;

		$logoUrl = plugin_dir_url(__FILE__) . 'images/wzgd-black.png';
		$img = "<img src='$logoUrl' width='30px' height='30px' style='margin-top: -3px;'>";
		$id_attribute = $instance === 1 ? ' id="vessel-insert-campaign-button"' : '';
		printf( '<button type="button"%s class="button vessel-insert-campaign" data-editor="%s">%s</button>',
			esc_html( $id_attribute ),
			esc_attr( $editor_id ),
			$img . __( 'Add Vessel Campaign' )
		);

		if ($instance === 1) {
			// enqueue the script
			wp_enqueue_script('vessel_shortcode_script', plugins_url('js/vessel-shortcode.js', __FILE__), array('jquery', 'underscore', 'media-editor'), VESSEL_VER);
		}
	}

	/**
	 * Adds the modal template to the DOM
	 */
	public static function printTemplates() {
	    $post = get_post();

	    if (is_null($post) || $post->post_type === VesselCampaignPost::POST_TYPE) return;

		$class = 'media-modal wp-core-ui';
		// get the api key
		$opts = get_option(VesselAdmin::OPTION_NAME);
		$apiKey = null;
		$apiUrl = VESSEL_API;

		if ($opts && array_key_exists(VesselAdmin::API_KEY, $opts)) {
		    $apiKey = $opts[ VesselAdmin::API_KEY ];
        }

        $vesselOptspath = 'admin.php?page=vessel';
		$vesselOptsUrl = admin_url($vesselOptspath);

		$logoUrl = plugin_dir_url(__FILE__) . 'images/wzgd-black.png';
		$img = "<img src='$logoUrl' style='vertical-align: text-bottom;'>";

		// print out the template
		?>
<script type="text/html" id="tmpl-vessel-campaign-modal">
	<div tabindex="0" class="<?= esc_html($class) ?>" style="width: 550px; height: 200px; top: 50%; left: 50%; margin-left: -225px; margin-top: -100px;">
		<button type="button" class="media-modal-close"><span class="media-modal-icon"><span class="screen-reader-text"><?php esc_html_e( 'Close media panel' ); ?></span></span></button>
		<div class="media-modal-content" style="padding: 20px; min-height: unset">
            <?php if (is_null($apiKey) || empty($apiKey)): ?>
                <h1>No API Key!</h1>
                You need to get your API key from the <a href="https://wzgd-central.com/create-account" target="_blank">Vessel Dashboard</a>
                and paste it into the <a href="<?= esc_url($vesselOptsUrl) ?>">Vessel Options</a> page.
            <?php else: ?>
                <h1><?= $img ?> Choose a Campaign:</h1>
                <br/>
                <div style="margin-left: 30px;" id="vessel-selection-wrapper" data-url="<?= esc_url($apiUrl) ?>" data-key="<?= esc_html($apiKey) ?>">
                    <span>Loading...</span>
                </div>
            <?php endif; ?>
        </div>
	</div>
	<div class="media-modal-backdrop"></div>
</script>
		<?php
	}
}