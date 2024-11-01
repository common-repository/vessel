<?php

/**
 * Plugin Name: Vessel
 * Plugin URI: https://wzgd-central.com/
 * Description: Vessel seamlessly integrates a visually-rich, map-driven media experience into your WP Post/Listicle and shows analytics to increase engagement rate.
 * Author: Vessel
 * Version: 1.0.5
 * Author URI: https://www.vesselapp.co
 */

if (!function_exists('add_action')) {
	echo "Do not call me directly...";
	exit();
}

define("VESSEL__PLUGIN_DIR", plugin_dir_path(__FILE__));
define("VESSEL_VER", '1.0.5');

//check environment for host url
if (!defined("VESSEL_HOST")){
	define("VESSEL_HOST", 'https://wzgd-central.com');
}

define("VESSEL_API", VESSEL_HOST.'/api/');


require_once VESSEL__PLUGIN_DIR . 'class.campaign-post.php';
require_once VESSEL__PLUGIN_DIR . 'class.meta-box.php';
require_once VESSEL__PLUGIN_DIR . 'class.admin.php';
require_once VESSEL__PLUGIN_DIR . 'class.short-code.php';
require_once VESSEL__PLUGIN_DIR . 'class.gutenberg-block.php';

add_action('init', array('VesselCampaignPost', 'init'), 10);
add_action('init', array('VesselShortCode', 'init'));
add_action('init', array('VesselGutenbergBlock', 'init'));

register_activation_hook(__FILE__, array('VesselCampaignPost', 'installPrefix'));
register_activation_hook(__FILE__, array('VesselAdmin', 'activationHook'));

if (is_admin()) {
	add_action('admin_init', array('VesselAdmin', 'redirectToInfoPage'));
	add_action('init', array('VesselCampaignsMetaBox', 'init'));
	add_action('init', array('VesselAdmin', 'init'));
}