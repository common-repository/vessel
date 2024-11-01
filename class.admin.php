<?php
/**
 * Created by PhpStorm.
 * User: Aaron Allen
 * Date: 6/15/2018
 * Time: 1:59 AM
 */

abstract class VesselAdmin {
    const OPTION_NAME = 'vapi_options';
    const API_KEY = 'vessel_api_key';
	const REDIRECT_OPTION = 'vessel_plugin_do_activation_redirect';

	private static $isInitialized = false;
	private static $apiKey = '';
	private static $apiVerified = false;

	public static function init() {
		if (self::$isInitialized) return;
		self::$isInitialized = true;		
		self::initHooks();
	}

	public static function activationHook() {
	    add_option(self::REDIRECT_OPTION, true);
    }

	public static function redirectToInfoPage() {;
	    if (get_option(self::REDIRECT_OPTION, false)) {
			//wp_nonce_field('vessel_redirect_InfoPage');
			delete_option(self::REDIRECT_OPTION);
			//check_admin_referer('vessel_redirect_InfoPage');
	        if (!isset($_GET['activate-multi'])) {
	            wp_safe_redirect("admin.php?page=vessel_welcome");
	            exit();
            }
        }
    }

	private static function initHooks() {
		add_action( 'admin_init', array('VesselAdmin', 'addSetting') );
		add_action( 'admin_menu', array('VesselAdmin', 'optionsPage') );
		add_action('wp_ajax_vessel_campaigns', array('VesselAdmin', 'getCampaignsAjax'));
		add_action('wp_dashboard_setup', array('VesselAdmin', 'addDashboardWidget'));
		add_filter( 'admin_footer_text', array('VesselAdmin','customAdminCredits'));
	}

	public static function customAdminCredits($footer_text) {
		$footer_text = __( 'Please rate <strong>Vessel</strong> <a href="https://wordpress.org/support/plugin/vessel/reviews/" target="_blank" rel="noopener">★★★★★</a> on <a href="https://wordpress.org/support/plugin/vessel/reviews/" target="_blank" rel="noopener noreferrer">WordPress.org</a> to help us spread the word. Thank you from the Vessel team!');
		return $footer_text;
	}
	
	// function to get api key from the WP database
	public static function getApiKey(){
		// if it is already set, just return it
		if (!empty(self::$apiKey)) return self::$apiKey;

		// otherwise, checks the database for the apiKey and sets it if it finds it
		$opts = get_option(VesselAdmin::OPTION_NAME);
		if (key_exists(VesselAdmin::API_KEY, $opts)) {
			self::$apiKey = $opts[VesselAdmin::API_KEY];
		}
		return self::$apiKey;
	}

	// returns true if the API key is present
	public static function checkApiKey(){
		return !empty(self::getApiKey());
	}

	// verify if api key given by the user exist in our database
	public static function verifyApiKey(){
		if (self::$apiVerified) return true;
		//only call webservice if we have a key
        if (self::checkApiKey()) {
	    	$userInfo = self::getBasicInfo();
	    	// server error occurred
	    	if ($userInfo instanceof \WP_Error) {
		    	echo "<p>Could not connect to the Vessel server! Please try again later.</p>";
				return false;
	        }	
	    self::$apiVerified = !empty($userInfo);
	    }
		return self::$apiVerified;
	}
		 
	public static function addDashboardWidget(){
		if(!self::verifyApiKey()){
		 	wp_add_dashboard_widget( 'vessel_dashboard_widget', 'Please Connect Vessel', array('VesselAdmin', 'dashboard_widget_function'));
			// place widget on top
			global $wp_meta_boxes;
			$dashboard = $wp_meta_boxes['dashboard']['normal']['core'];
			$vesselWidget = array( 'vessel_dashboard_widget' => $dashboard['vessel_dashboard_widget'] );
			unset( $dashboard['vessel_dashboard_widget'] );
			$sorted_dashboard = array_merge($vesselWidget, $dashboard);
			$wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;
		}
	}

	// add connect vessel widget to the dashboard 
	public static function dashboard_widget_function() {
		$vesselOptsPath = 'admin.php?page=vessel';
		$vesselOptsUrl = admin_url($vesselOptsPath);
		$logoUrl = plugin_dir_url(__FILE__) . 'images/BlackWZGD.svg';
		?>
			<div id = "dashboard" style="text-align:center;">
				<img alt="vessel" width="50px" src="<?= esc_url($logoUrl) ?>">
				<h2>Please Connect Vessel</h2>
				<p>Vessel creates value for advertisers by increasing view time and content engagement. Convert your guides
				into immersive and interactive maps now!</p>
				<a class="button button-primary" href="<?= esc_html($vesselOptsUrl) ?>">Connect Vessel</a>
			</div>
		<?php
	}

	public static function addSetting() {
		register_setting('vapi', self::OPTION_NAME);

		add_settings_section(
			'vessel_section_config',
			__('API Credentials', 'vessel'),
			array('VesselAdmin', 'sectionCallback'),
			'vapi'
		);

		add_settings_field(
			self::API_KEY,
			__( 'API key', 'vessel' ),
			array('VesselAdmin', 'apiKeyCallback'),
			'vapi',
			'vessel_section_config',
			array(
				'label_for' => self::API_KEY,
				'class' => 'vessel_row',
            )
		);
	}

	public static function sectionCallback($args) {
		?>
		<?php if (!self::verifyApiKey()): ?>
			<p style="color:red;">You must authenticate your Vessel account before you can use Vessel on this site.</p>
			<p id="<?= esc_attr( $args['id'] ) ?>">
            <?php esc_html_e( 'Need a Vessel account? ')?>
            <a href="https://wzgd-central.com/create-account"> Secure your API key by creating an account HERE </a>
            </p>
		<?php else: ?>
			<p style="color:green;"> Success! </p>
			<p> You can now add Vessel maps to your posts.</p>
		<?php endif;?>
		<?php
	}

	public static function apiKeyCallback($args) {
		$options = get_option( self::OPTION_NAME );
		if (!empty($options)) {
			$value = array_key_exists($args['label_for'], $options) ? $options[ $args['label_for'] ] : '';			
        } else {
			$value = '';	
		}
		?>
		<input id="<?= esc_attr($args['label_for']) ?>"
		       name="vapi_options[<?= esc_attr( $args['label_for'] ) ?>]"
		       type="password"
			   value="<?= esc_html($value) ?>"/>
		<p class="description">
			<?php esc_html_e( 'A single API key found in your Vessel Account API area.', 'vessel') ?>
		</p>
		<?php
	}

	public static function optionsPage() {
		// add top level menu page
		add_menu_page(
			'Vessel',
			'Vessel Options',
			'manage_options',
			'vessel',
			array('VesselAdmin', 'optionsPageHtml'),
			plugin_dir_url(__FILE__) . 'images/wzgd.png'
		);

		// add info page as a sub menu item
        add_submenu_page(
                'vessel',
                'Welcome to Vessel',
                'Welcome Page',
                'manage_options',
                'vessel_welcome',
            array('VesselAdmin', 'welcomePageHtml')
        );
	}

	public static function welcomePageHtml() {
	    wp_enqueue_style(
	            'vessel_welcome',
                plugins_url('css/vessel-welcome.css', __FILE__),
                array(),
                VESSEL_VER
        );

		$vesselOptspath = 'admin.php?page=vessel';
		$vesselOptsUrl = admin_url($vesselOptspath);
		$logoUrl = plugin_dir_url(__FILE__) . 'images/logo.svg'

	    ?>
        <div>
            <div class="vessel-header card">
                <div class="vessel-logo">
                    <img alt="vessel" width="140px" src="<?= esc_url($logoUrl) ?>">
                    <small>v<?= esc_html(VESSEL_VER) ?></small>
                </div>
                <div class="vessel-header-right">
                    <a href="https://vesselapp.co/blogs/tips-takeaways" target="_blank">Need Help?</a>
                    <a href="https://vesselapp.co/pages/contact-vessel" target="_blank">Send Us Feedback</a>
                </div>
            </div>
            <div>
                <div class="vessel-card">
                    <h1>Welcome to Vessel</h1>
                </div>
                <div class="card vessel-card">
                    <p>Please connect or create a Vessel account to start using the Vessel plugin.</p>
                    <div class="vessel-button-row">
                        <div class="vessel-button-wrapper">
                            <span>don't have an API Key?</span>
                            <a class="button button-primary" href="<?= esc_url(VESSEL_HOST) ?>/create-account" target="_blank">CREATE AN ACCOUNT</a>
                        </div>
                        <div class="vessel-button-wrapper">
                            <span>got your API key?</span>
                            <a class="button button-secondary" href="<?= esc_url($vesselOptsUrl) ?>">Connect your account</a>
                        </div>
                    </div>
                </div>
                <div class="card vessel-card">
                    <h3>Driving Engagement Today!</h3>
                    <p>
                        Vessel is the first platform to enrich and convert your travel guides, city guides, and restaurant lists into visually-rich interactive maps.
                    </p>

                    <iframe width="560" height="315" src="https://www.youtube.com/embed/0TqWBqtKP-E" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
                <div class="card vessel-card">
                    <p>
                        Join hundreds of publishers who are publishing content with Vessel everyday!
                    </p>
                    <div class="vessel-button-row">
                        <div class="vessel-button-wrapper">
                            <span>don't have an API Key?</span>
							<a class="button button-primary" href="<?= esc_url(VESSEL_HOST) ?>/create-account" target="_blank">CREATE AN ACCOUNT</a>
						</div>
                        <div class="vessel-button-wrapper">
                            <span>got your API key?</span>
                            <a class="button button-secondary" href="<?= esc_url($vesselOptsUrl) ?>">Connect your account</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

	public static function optionsPageHtml() {
		//this nonce is causing issues @kanika
		//wp_nonce_field('vessel_set_html');
		//include CSS
		wp_enqueue_style(
			'vessel_option',
			plugins_url('css/vessel-option.css', __FILE__)
		);
		//include script
		wp_enqueue_script(
			'vessel_option_script',
			plugins_url('js/vessel-option.js', __FILE__)
		);
		wp_localize_script(
			'vessel_option_script',
			'serverinfo',
			array('supportData' => self::get_server_data())
		);
		// check user capabilities
		if (!current_user_can('manage_options')) return;

		// add error/update messages
		// wordpress will add the "settings-updated" $_GET parameter to url
		//check_admin_referer('vessel_set_html');
		if (isset($_GET['settings-updated'])) {
			// add settings saved message with the class of "updated"
			add_settings_error('vessel_messages', 'vessel_message', __('Settings Saved', 'vessel'), 'updated');
		}

		// show error/update messages
		settings_errors('vessel_messages');
		$name = '';
		$email= '';
		$business = '';
		$userId = '';
		$disabled = false;

		if (self::verifyApiKey()) {
			$userInfo = self::getBasicInfo();
			$name = $userInfo[0]['username'];
			$email = $userInfo[0]['email'];
			$business = $userInfo[0]['account_name'];
			$userId = $userInfo[0]['id'];
			$disabled = true;
		}
		?>
		<div class="wrap">
            <div class = "options-ui">
            	<div class="tab">
                <button class="tablink" data-id="api" id="vessel-tabs-default">API Credentials</button>
                <button class="tablink" data-id="support">Support</button>
                <button class="tablink" data-id="contact">Contact Us</button>
            </div>
            <div class="tabcontent-ui">
                <div id="api" class="tabcontent">
                    <h1><? esc_html( get_admin_page_title() ) ?></h1>
                    <form action="options.php" method="post">
						<?php
							// output security fields for the registered setting "testing"
							settings_fields('vapi');
							// output settings and their fields
							// (sections are registered for "testing", each field is registered to a specific section)
							do_settings_sections('vapi');
							// output save settings button
							submit_button('Save Settings');
						?>
					</form>
                    </div>
                    <div id="support" class="tabcontent">
                        <h3>Support</h3>
                        <iframe class = "vessel-video" width="560" height="315" src="https://www.youtube.com/embed/0TqWBqtKP-E" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        <div class = "option-links">
                            <h3>Helpful Links</h3>
                            <ul>
                                <li><a href="https://www.vesselapp.co/blogs/tips-takeaways/getting-started-with-vessel">Getting started with WordPress</a></li>
                                <li><a href="https://www.vesselapp.co/blogs/tips-takeaways/the-map-editor">The Map Editor - What you need to know</a></li>
                                <li><a href="https://www.vesselapp.co/blogs/tips-takeaways/instasearch">Acquire Instagram content with InstaSearch</a></li>
                            </ul>
                        </div>
                    </div>
                    <div id="contact" class="tabcontent">
                        <h3>Contact Us</h3>
                        <p>We'd love to hear from you, drop us a line below:</p>
                        <div style = "width:80%;">
                            <div>
                                <input id="username" type="text" class="contact-input" placeholder="Name" required <?php disabled($disabled, true) ?>
									value="<?= esc_html($name) ?>">
                                <input id="email" type="text" class="contact-input" style="float:right;" placeholder="Email" required <?php disabled($disabled, true) ?>
									value="<?= esc_html($email) ?>">
                            </div>
                            <div>
                                <input id="business" type="text" class="contact-website" placeholder="Website or Business Name" required <?php disabled($disabled, true) ?>
									value="<?= esc_html($business) ?>">
                            </div>
                            <div>
                                <textarea id="msg" type="text" class="contact-textarea" placeholder="What's Up?" required></textarea>
                            </div>
                            <button id="btn" class="button button-primary" style="float:right;" data-id=<?= esc_html($userId) ?>>Send</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
		<?php
	}

	/**
    * Get info about the vessel account given the api key.
    * Returns null if there is no API key.
    *
	* @return object|\WP_Error|null
	*/
	public static function getBasicInfo() {
		if (!self::checkApiKey()) {
		   return null;
		}
		$apiUrl = VESSEL_API . "account/" . self::$apiKey . "/info";
		$response = wp_remote_get($apiUrl, array('timeout' => 10));
		if (!$response || $response instanceof \WP_Error) {
			return $response;
		}
		$userInfo = json_decode($response['body'], true);
		return $userInfo;
	}

	/**
     * Retrieve the campaigns for this user's account.
     * returns null if there's no api key set.
     *
	 * @return array|\WP_Error|null
	 */
	public static function getCampaigns() {
		if (!self::checkApiKey()) {
		     return null;
        }
		$apiUrl = VESSEL_API . "deliver/" . self::$apiKey . "/campaigns";

		$response = wp_remote_get($apiUrl, array('timeout' => 10));

		if (!$response || $response instanceof \WP_Error) {
			return $response;
		}

		$campaigns = json_decode($response['body']);

		return $campaigns;
    }

	/**
	 * Admin ajax endpoint for getting user's campaigns
     *
     * @return array|string
	 */
	public static function getCampaignsAjax() {
		$campaigns = self::getCampaigns();

		if ($campaigns instanceof \WP_Error) {
			// server error occurred
			echo "\"<p>Could not connect to the server! Please try again later.</p>\"";
		} else if (is_null($campaigns)) {
			// if no api key, display some info
			$optionsUrl = admin_url('admin.php?page=vessel');
			$response = array(
                "beforeLink" => "You must enter your Vessel API Key in the ",
                "linkUrl" => $optionsUrl,
                "linkText" => "Vessel Options",
                "afterLink" => " page before you can insert a campaign!"
            );

			echo json_encode($response);
		} else if (empty($campaigns)) {
			// no campaigns
			$response = array(
				"beforeLink" => "You don't have any campaigns yet. Head to the ",
				"linkUrl" => VESSEL_HOST,
				"linkText" => "Vessel Dashboard",
				"afterLink" => " to get started."
			);
			echo json_encode($response);
		} else {
			echo json_encode($campaigns);
		}

		wp_die();
	}


	/**
	 * Build array of server information to localize
	 *
	 * @since 1.1.5
	 *
	 * @return array
	 */
	public static function get_server_data() {

		$theme_data = wp_get_theme();
		$theme      = $theme_data->Name . ' ' . $theme_data->Version;
		$plugins        = get_plugins();
		$active_plugins = get_option( 'active_plugins', array() );
		$used_plugins   = "\n";
		foreach ( $plugins as $plugin_path => $plugin ) {
			if ( ! in_array( $plugin_path, $active_plugins ) ) {
				continue;
			}
			$used_plugins .= $plugin['Name'] . ': ' . $plugin['Version'] . "\n";
		}
		if(isset( $_SERVER['SERVER_SOFTWARE'] )) {
			$server = sanitize_text_field( wp_unslash(esc_html( $_SERVER['SERVER_SOFTWARE'] )));
		}
		else {
			$server = '';
		}
		$array = array(
			'Server Info'        => $server,
			'PHP Version'        => function_exists( 'phpversion' ) ? esc_html( phpversion() ) : 'Unable to check.',
			'Error Log Location' => function_exists( 'ini_get' ) ? ini_get( 'error_log' ) : 'Unable to locate.',
			'Default Timezone'   => date_default_timezone_get(),
			'WordPress Home URL' => get_home_url(),
			'WordPress Site URL' => get_site_url(),
			'WordPress Version'  => get_bloginfo( 'version' ),
			'Multisite'          => is_multisite() ? 'Multisite Enabled' : 'Not Multisite',
			'Language'           => get_locale(),
			'Active Theme'       => $theme,
			'Active Plugins'     => $used_plugins,

		);

		return $array;
	}
}