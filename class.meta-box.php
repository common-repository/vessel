<?php
/**
 * Created by PhpStorm.
 * User: Aaron Allen
 * Date: 5/19/2018
 * Time: 8:45 PM
 */

abstract class VesselCampaignsMetaBox {
	const jsonMetaKey = '_vessel_json_key';
	const campaignIdMetaKey = '_vessel_campaign_id';
	const jsonRequestTime = '_vessel_json_request_time';
	const belowContent = '_vessel_below_content';
	const aboveContent = '_vessel_above_content';
	const layoutKey = '_vessel_layout';
	const fullLayout = 'full';
	const sidebarLayout = 'sidebar';

	public static function init() {
		add_action('add_meta_boxes', array('VesselCampaignsMetaBox', 'addBoxes'));
		add_action('save_post_' . VesselCampaignPost::POST_TYPE, array('VesselCampaignsMetaBox', 'savePostData'));
        add_filter('get_user_option_meta-box-order_' . VesselCampaignPost::POST_TYPE, array('VesselCampaignsMetaBox', 'boxOrder'));
		add_filter('title_save_pre', array('VesselCampaignsMetaBox', 'setTitle'));
		add_filter('content_save_pre', array('VesselCampaignsMetaBox', 'setContent'));
		//add_filter('excerpt_save_pre', [self::class, 'setExcerpt']);

		// remove the layout metabox
		add_action('init', function() {
			remove_post_type_support(VesselCampaignPost::POST_TYPE, 'primer-layouts');
		}, 12);
	}

	public static function addBoxes() {
		add_meta_box(
            'vessel_campaigns',
            __('Campaign', 'vessel'),
            array('VesselCampaignsMetaBox', 'getCampaignsHtml'),
            VesselCampaignPost::POST_TYPE,
            'normal',
            'high'
        );

		add_meta_box(
            'vessel_campaigns_above_editor',
            __('Above Content', 'vessel'),
            array('VesselCampaignsMetaBox', 'getAboveEditor'),
			VesselCampaignPost::POST_TYPE,
            'normal',
            'high'
        );

		add_meta_box(
			'vessel_campaigns_below_editor',
			__('Below Content', 'vessel'),
			array('VesselCampaignsMetaBox', 'getBelowEditor'),
			VesselCampaignPost::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
            'vessel_campaigns_layout',
            __('Layout', 'vessel'),
            array('VesselCampaignsMetaBox', 'getLayout'),
            VesselCampaignPost::POST_TYPE,
            'side',
            'high'
        );

	}

	/**
     * Print the html for the layout selector
	 * @param $post
	 */
	public static function getLayout($post) {
	    $value = get_post_meta($post->ID, self::layoutKey, true) ?: self::sidebarLayout;
        //wp_nonce_field('vessel_get_layout');
	    ?>
        <div class="form-group">
            <div>
                <input type="radio" name="vessel_layout" value="<?= self::sidebarLayout ?>" id="vessel_sidebar" <?php esc_html(checked(self::sidebarLayout, $value)) ?>>
                <label class="control-label" for="vessel_sidebar">Include the sidebar</label>
            </div>
            <div>
                <input type="radio" name="vessel_layout" value="<?= self::fullLayout ?>" id="vessel_full" <?php esc_html(checked(self::fullLayout, $value)) ?>>
                <label class="control-label" for="vessel_full">Use full page width</label>
            </div>
        </div>
        <?php
    }

	/**
     * Print the html for the above content editor
	 * @param $post
	 */
	public static function getAboveEditor($post) {
        //wp_nonce_field('vessel_get_above_content');
	    $value = get_post_meta($post->ID, self::aboveContent, true);
	    wp_editor($value, 'vessel_above_content', array('textarea_rows' => 7));
    }

	/**
     * Print the html for the below editor
	 * @param $post
	 */
    public static function getBelowEditor($post) {
        //wp_nonce_field('vessel_get_below_content');
	    $value = get_post_meta($post->ID, self::belowContent, true);
	    wp_editor($value, 'vessel_below_content', array('textarea_rows' => 7));
    }

	/**
     * Print the html for the campaign selector
	 * @param $post
	 */
	public static function getCampaignsHtml($post) {
	    $opts = get_option(VesselAdmin::OPTION_NAME);
	    if ($opts && $apiKey = $opts[ VesselAdmin::API_KEY ]) {
	        // get a list of the user's campaigns
	        $apiUrl = VESSEL_API . "deliver/$apiKey/campaigns";
	        
	        $response = wp_remote_get($apiUrl, array('timeout' => 10));

	        if (!$response || $response instanceof \WP_Error) {
	            ?>
                Could not connect to the server! Please try again later...
                <?php
                return;
            }
            $campaigns = json_decode($response['body']);

	        $value = get_post_meta($post->ID, self::campaignIdMetaKey, true);

	        ?>
            <div class="form-group">
                <?php //wp_nonce_field('vessel_choose_campaign'); ?>
                <label class="control-label" for="vessel_campaign_id">Choose a campaign</label>
                <select name="vessel_campaign_id" id="vessel_campaign_id" class="form-control">
                    <option value="">Select one...</option>
		            <?php foreach($campaigns as $campaign): ?>
                        <option value="<?= esc_html($campaign->id) ?>" <?php esc_html(selected($campaign->id, $value, true)) ?>><?= esc_html($campaign->title) ?></option>
		            <?php endforeach; ?>
                </select>

                <p class="description">
		            <?php esc_html_e( 'Note: You must first create your campaign in the VAPI dashboard and then select it here.', 'vessel') ?>
                </p>
            </div>
            <?php
        } else {
	        ?>
            <p>You must enter your API key into the Vessel Settings page.</p>
            <?php
        }
    }

	public static function boxOrder($order) {
	    return array(
            'normal' => 'vessel_campaigns_above_editor,vessel_campaigns,vessel_campaigns_below_editor'
        );
    }

    private static $json = '';
	/** @var object  */
	private static $jsonParsed = null;

	private static function fetchJson() {
		// download the json for this campaign

        //$apiKey = $options[VesselAdmin::API_KEY];
        if( isset( $_POST['vessel_campaign_id'] )) {
        $url = esc_url_raw( wp_unslash( VESSEL_API . "deliver/json/{$_POST['vessel_campaign_id']}" ));
        }
        else {
            $url = '';
        }

		$response = wp_remote_get($url, array('timeout' => 10));

		if ($response instanceof WP_Error || empty($response['body'])) {
			// something went wrong...
			add_action('admin_notices', function() {
				?>
                <div class="notice-error">
                    <p>The campaign could not be saved because a server error occurred.</p>
                </div>
				<?php
			});
		} else {
            $json = $response['body'];
            self::$json = $json;
            self::$jsonParsed = json_decode($json);
        }
    }

	/**
     * Gets the campaign json from VAPI and also sets the title of
     * the post object.
     *
	 * @param $title
	 *
	 * @return string
	 */
    public static function setTitle($title) {
        //check_admin_referer('vessel_choose_campaign');
	    if (array_key_exists('vessel_campaign_id', $_POST)) {
		    $title = 'Vessel Map';

		    if (empty(self::$json)) {
		        self::fetchJson();
            }

		    if (property_exists(self::$jsonParsed, 'title')) {
			    $title = self::$jsonParsed->title;
		    }

		    return $title;
	    }

	    return $title;
    }

    public static function setContent($content) {
        //check_admin_referer('vessel_choose_campaign');
	    if (array_key_exists('vessel_campaign_id', $_POST)) {

		    $content = VesselCampaignPost::POST_FLAG;

		    return $content;
	    }

	    return $content;
    }

	/**
     * Sets the excerpt field for the post. Uses the description if available
     * otherwise it will try to get the text from the above and below content.
     *
	 * @param $excerpt
	 *
	 * @return string
	 */
    public static function setExcerpt($excerpt) {
        //check_admin_referer('vessel_choose_campaign');
        if (array_key_exists('vessel_campaign_id', $_POST)) {
	        if (empty(self::$json)) {
		        self::fetchJson();
	        }

            $content = '';
            // try taking the excerpt from the above or below content
            //check_admin_referer('vessel_get_above_content');
            if (array_key_exists('vessel_above_content', $_POST)) {
                if (isset($_POST['vessel_above_content'])) {
                    //check_admin_referer('vessel_get_above_content');
                    sanitize_meta(self::aboveContent, $_POST['vessel_above_content'], VesselCampaignPost::POST_TYPE);
                    $content = wp_unslash( $_POST['vessel_above_content'] );
                }
            }
            //check_admin_referer('vessel_get_below_content');
            if (array_key_exists('vessel_below_content', $_POST)) {
                if(isset($_POST['vessel_below_content'])) {
                    //check_admin_referer('vessel_get_below_content');
                    sanitize_meta(self::belowContent, $_POST['vessel_below_content'], VesselCampaignPost::POST_TYPE);
                    $content .= ' ' . wp_unslash($_POST['vessel_below_content'] );
                }
            }

            // if not above/below content, fallback to using the campaign description.
	        if (empty($content) && property_exists(self::$jsonParsed, 'description') && !empty(self::$jsonParsed->description)) {
	            $content = self::$jsonParsed->description;
	        }

            $content = strip_tags($content);
            // limit excerpt to 25 words
            $words = explode(' ', $content, 25);

            $truncate = false;
            if (count($words) >= 25) {
                array_pop($words);
                $truncate = true;
            }

            if (!empty($words)) {
                $excerpt = implode(' ', $words);

                if ($truncate) {
                    $excerpt .= '...';
                }
            } else {
                // give it some dummy text so that if a theme tries to pull
                // the excerpt, it won't fall back to the content field.
                $excerpt = '   ';
            }

        }

        return $excerpt;
    }

	/**
     * Uses the json to set the post's meta data
     *
	 * @param $postId
	 */
	public static function savePostData($postId) {
        //check_admin_referer('vessel_choose_campaign');
		if (array_key_exists('vessel_campaign_id', $_POST)) {

		    $json = self::$json;

            // set the meta data
            update_post_meta(
                $postId,
                self::jsonMetaKey,
                sanitize_meta(self::jsonMetaKey, $json, VesselCampaignPost::POST_TYPE)
            );

            $updateTime = empty($json) ? 0 : time();
            update_post_meta(
                $postId,
                self::jsonRequestTime,
                sanitize_meta(self::jsonRequestTime, $updateTime, VesselCampaignPost::POST_TYPE)
            );
            if(isset($_POST['vessel_campaign_id'])) {
                //check_admin_referer('vessel_choose_campaign');
                update_post_meta(
                    $postId,
                    self::campaignIdMetaKey,
                    sanitize_meta(self::campaignIdMetaKey, wp_unslash($_POST['vessel_campaign_id']), VesselCampaignPost::POST_TYPE)
                );
            }
        }
        //check_admin_referer('vessel_get_layout');
		if (array_key_exists('vessel_layout', $_POST)) {
            if(isset($_POST['vessel_layout'])) {
                //check_admin_referer('vessel_get_layout');
                update_post_meta(
                    $postId,
                    self::layoutKey,
                    sanitize_meta( self::layoutKey, wp_unslash($_POST['vessel_layout']),
                        VesselCampaignPost::POST_TYPE )
                );
            }
		}

        //check_admin_referer('vessel_get_above_content');
        if (array_key_exists('vessel_above_content', $_POST)) {
            if(isset($_POST['vessel_above_content'])) {
                //check_admin_referer('vessel_get_above_content');
                update_post_meta(
                    $postId,
                    self::aboveContent,
                    sanitize_meta(self::aboveContent, wp_unslash($_POST['vessel_above_content']), VesselCampaignPost::POST_TYPE)
                );
            }
        }

        //check_admin_referer('vessel_get_below_content');
        if (array_key_exists('vessel_below_content', $_POST)) {
            if(isset($_POST['vessel_below_content'])) {
                //check_admin_referer('vessel_get_below_content');
                update_post_meta(
                    $postId,
                    self::belowContent,
                    sanitize_meta(self::belowContent, wp_unslash($_POST['vessel_below_content']), VesselCampaignPost::POST_TYPE)
                );
            }
        }
	}
}