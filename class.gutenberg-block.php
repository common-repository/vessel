<?php

abstract class VesselGutenbergBlock {
	public static function init() {
		// register the gutenberg block for WP version >= 5
		if (function_exists('register_block_type')) {
			wp_register_script(
				'vessel-gutenberg-block-script',
				plugins_url('js/vessel-gutenberg-block.js', __FILE__),
				array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-compose', 'jquery' ),
				VESSEL_VER
			);

			register_block_type( 'vessel/gutenberg-campaign-block', array(
				'editor_script' => 'vessel-gutenberg-block-script',
				'render_callback' => array('VesselShortCode', 'shortCode'),
				'attributes' => array (
					'id' => array('type' => 'string'),
					'trigger' => array('type' => 'string')
				)
			));
		}
	}
}