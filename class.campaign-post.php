<?php
/**
 * Created by PhpStorm.
 * User: Aaron Allen
 * Date: 5/18/2018
 * Time: 5:42 PM
 */

abstract class VesselCampaignPost {
	const POST_TYPE = 'vessel_campaign';
	// the 'content' of a campaign post will be set to this string.
	const POST_FLAG = '!!!VESSEL-CAMPAIGN!!!';

	public static function init() {
		// register the custom post type
		$postType = register_post_type(self::POST_TYPE,
			array(
				'labels' => array(
					'name' => __('Vessel Campaigns'),
					'singular_name' => __('Vessel Campaign'),
                    'add_new_item' => __('Add New Campaign'),
                    'edit_item' => __('Edit Campaign'),
                    'new_item' => __('New Campaign'),
                    'view_item' => __('View Campaign'),
                    'view_items' => __('View Campaigns'),
                    'search_items' => __('Search Campaigns')
				),
				'public' => false, // hides the menu item
				'has_archive' => true,
				'rewrite' => array('slug' => 'vessel'),
				'supports' => array('author', 'comments', 'tag', 'thumbnail', 'excerpt'),
                'menu_icon' => plugin_dir_url(__FILE__) . 'images/wzgd.png',
                'taxonomies' => array('category', 'post_tag'),
                'capability_type' => 'post'
			)
		);

		add_action('pre_get_posts', array('VesselCampaignPost', 'addCustomPostTypes'));
		//add_action('loop_start', [self::class, 'loopStart']);
		add_filter('single_template', array('VesselCampaignPost', 'getTemplate'));
        add_filter('the_content', array('VesselCampaignPost', 'contentFilter'));
        add_filter('post_row_actions', array('VesselCampaignPost', 'addRowAction'));
		//add_filter('wp_get_attachment_image_src', [self::class, 'getFeaturedImage'], 10, 4);
		add_action('wp_enqueue_scripts', array('VesselCampaignPost', 'embedScripts'));

		add_filter('acf/location/rule_match/post_type', array('VesselCampaignPost', 'addToSimpleMagTheme'), 15, 3 );
	}

	public static function addToSimpleMagTheme($match, $rule, $options) {
		if ($rule['param'] === 'post_type'
		    && $rule['operator'] === '=='
			&& $rule['value'] === 'post') {
			return true;
		}

		return $match;
	}

	/**
	 * Runs only on plugin activation
	 */
	public static function installPrefix() {
		// trigger our function that registers the custom post type
		self::init();

		// clear the permalinks after the post type has been registered
		flush_rewrite_rules();


    }

	/**
     * Detect if the content is for a vessel campaign
     * render the template if so.
     *
	 * @param $content
	 *
	 * @return string
	 */
	public static function contentFilter($content) {
		global $post;
		if (empty($content)) return '';

		if (!$post || $post->post_type !== VesselCampaignPost::POST_TYPE) return $content;

		// check for the flag
        if (substr_compare(ltrim($content, "<p> \r\n"), self::POST_FLAG, 0, strlen(self::POST_FLAG)) === 0) {
            // need to check json age
            //self::requestPostJson($post);
            //self::getJson($post);

			ob_start();
            include('campaign-template.php');
            $template = ob_get_clean();

            // return the rendered template
            return $template;
        }

        return $content;
    }

	/**
	 * Display our custom posts on the home page
	 *
	 * @param \WP_Query $query
	 *
	 */
	public static function addCustomPostTypes(WP_Query $query) {
		$metaKey = $query->get('meta_key');

		if ((!is_admin() && !$query->is_single) || $query->is_feed())  /*&& ($query->is_feed() || is_home())*/ {
			$postTypes = $query->get('post_type');
			if (empty($postTypes)) {
				$query->set('post_type', array('post', 'page', self::POST_TYPE));
				return;
			}
			if (is_array($postTypes)) {
				$postTypes[] = self::POST_TYPE;
				$query->set('post_type', $postTypes);
			} else if (
				in_array($metaKey,
					array('featured_post_add', 'category_slider_add', 'homepage_slider_add'))
				&& is_string($postTypes)
			) {
				// this is for the 'SimpleMag' theme.
				$query->set('post_type', array($postTypes, self::POST_TYPE));
			}
		}
	}

	/**
	 * Add a link to edit page in vessel dashboard to the post table
	 *
	 * @param $actions
	 *
	 * @return array
	 */
	public static function addRowAction( $actions ) {
		$post = get_post();
		if ($post->post_type !== self::POST_TYPE) return $actions;

		$id = (string)get_post_meta($post->ID, VesselCampaignsMetaBox::campaignIdMetaKey, true);

		if (isset($id)) {
			$url = VESSEL_HOST . "/campaign-editor/$id";
			$actions['vessel-edit'] = "<a href='$url' target='_blank'>Edit in Vessel Dash</a>";
		}

		return $actions;
	}

	/**
	 * Use a custom template
	 *
	 * @param $template
	 *
	 * @return string
	 */
	public static function getTemplate($template) {
		global $post;

		if ($post->post_type === self::POST_TYPE && $template !== locate_template('single-vessel_campaign.php')) {
			$layout = get_post_meta($post->ID, VesselCampaignsMetaBox::layoutKey, true);
			// we only use the custom template for full page layout
		    if ($layout !== VesselCampaignsMetaBox::fullLayout) return $template;

		    //self::requestPostJson($post);

		    return VESSEL__PLUGIN_DIR . 'single-vessel_campaign.php';
		}

		return $template;
	}

	/**
	 * @deprecated
	 *
     * Inserts the url of the featured image for campaigns that have one
     * or should use any image that was set in the WP dashboard
     *
	 * @param $img
	 * @param $attachment_id
	 * @param $size
	 * @param $icon
	 *
	 * @return array
	 */
	public static function getFeaturedImage($img, $attachment_id, $size, $icon) {
	    $post = get_post();

	    if ($post && $post->post_type == self::POST_TYPE) {
		    $_wp_additional_image_sizes = wp_get_additional_image_sizes();

		    if (isset($_wp_additional_image_sizes[$size]['width'])) {
			    $width = $_wp_additional_image_sizes[$size]['width'];
		    } else {
			    $width = $_wp_additional_image_sizes[$size][0];
		    }

	        // check if the post has a WP thumbnail associated with it
            if ($img && $img[1] === $width) {
                return $img;
            }

	        if (!property_exists($post, 'vessel_json')) {
	            $json = get_post_meta($post->ID, VesselCampaignsMetaBox::jsonMetaKey, true);

	            $post->vessel_json = json_decode($json);
            }

	        // get the image source of the featured image for this campaign
	        $imgSrc = $post->vessel_json->mediaPath . $post->vessel_json->id . '/' . $post->vessel_json->featured_image;

            $img = array(
                $imgSrc,
                $width,
                '',
                1
            );
        }

        return $img;
    }

	/**
     * Will check a timestamp to see if the campaign json needs to be retrieved
     * if it does, a request will be made to VAPI and the post will be updated.
     *
	 * @param \WP_Post $post
	 */
	private static function requestPostJson(WP_Post $post) {
	    $lastRequestTime = get_post_meta($post->ID, VesselCampaignsMetaBox::jsonRequestTime, true);

	    $curTime = time();
	    if ($curTime - $lastRequestTime <= 60 * 60 * 24) {
	        // the json doesn't need to be requested
	        return;
        }

	    $options = get_option('vapi_options');
	    if ($options) {
	        $apiKey = $options['vessel_api_key'];

	        $campaignId = get_post_meta($post->ID, VesselCampaignsMetaBox::campaignIdMetaKey, true);
	        $url = VESSEL_API . "deliver/json/$campaignId";

	        $response = wp_remote_get($url, array('timeout' => 10));

	        if ($response instanceof WP_Error || empty($response['body'])) {
		        update_post_meta($post->ID, VesselCampaignsMetaBox::jsonRequestTime, 0);
	            return;
            }

            $json = $response['body'];
	        if (boolval($json)) {
                update_post_meta(
                    $post->ID,
                    VesselCampaignsMetaBox::jsonMetaKey,
                    sanitize_meta(VesselCampaignsMetaBox::jsonMetaKey, $json, VesselCampaignPost::POST_TYPE)
                );
                update_post_meta($post->ID, VesselCampaignsMetaBox::jsonRequestTime, $curTime);
                $post->vessel_json = json_decode($json);

                // change the title if needed
                if ($post->post_title !== $post->vessel_json->title) {
                    wp_update_post(array(
                        'ID' => $post->ID,
                        'post_title' => $post->vessel_json->title
                        //'post_content' => $post->vessel_json->description
                    ));
                }
            }
        }
    }

	/**
     * Gets the json as a parsed array. Monkey patches it to the post object.
     *
	 * @param \WP_Post $post
	 *
	 * @return object
	 */
    private static function getJson(WP_Post $post) {
        if ($post->post_type !== self::POST_TYPE) return null;

	    if (!property_exists($post, 'vessel_json') || empty($post->vessel_json)) {
	        $json = get_post_meta($post->ID, VesselCampaignsMetaBox::jsonMetaKey, true);

	        $post->vessel_json = json_decode($json);
        }

        return $post->vessel_json;
    }

	/**
     * Set the json object on the post at the start of the loop
     *
	 * @param \WP_Query $query
	 */
    public static function loopStart(WP_Query $query) {
        $post = $query->post;

        if ($post->post_type === self::POST_TYPE) {
            self::getJson($post);
        }
    }

	/**
	 * Embed a script when our post type is viewed
     * or when viewing the homepage
	 */
	public static function embedScripts() {
		if (is_singular(self::POST_TYPE) || is_home()) {
			wp_enqueue_script('mapbox_gl_script', 'https://api.mapbox.com/mapbox-gl-js/v1.0.0/mapbox-gl.js');

			if (!wp_script_is('vessel_campaign_script')) {
				wp_enqueue_script('vessel_campaign_script', VESSEL_HOST.'/api/deliver/js', array('jquery'), VESSEL_VER);
			}

			wp_enqueue_script('owl-carousel_script', plugins_url('js/owl-carousel/owl.carousel.min.js', __FILE__), array('jquery'));

			// include the stylesheet
            wp_enqueue_style('mapbox_gl_style', 'https://api.mapbox.com/mapbox-gl-js/v1.0.0/mapbox-gl.css');
			wp_enqueue_style('vessel_campaign_style', VESSEL_HOST.'/static/css/vessel.css', array(), VESSEL_VER);
			wp_enqueue_style('owl-carousel_carousel', plugins_url('js/owl-carousel/assets/owl.carousel.css', __FILE__));
			wp_enqueue_style('owl-carousel_theme', plugins_url('js/owl-carousel/assets/owl.theme.default.css', __FILE__));
		}
	}
}