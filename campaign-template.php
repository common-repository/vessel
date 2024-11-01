<?php
/**
 * The template for displaying vessel campaigns
 *
 * @package Vessel
 */

$json = $post->vessel_json;

$aboveContent = get_post_meta($post->ID, VesselCampaignsMetaBox::aboveContent, true);
$belowContent = get_post_meta($post->ID, VesselCampaignsMetaBox::belowContent, true);
$campaignId   = get_post_meta($post->ID, VesselCampaignsMetaBox::campaignIdMetaKey, true);

// if on the homepage, should only use sidebar layout
if (!is_home()) {
    $layout = get_post_meta($post->ID, VesselCampaignsMetaBox::layoutKey, true);
} else {
    $layout = VesselCampaignsMetaBox::sidebarLayout;
}


// place a flag so we know this content is for a vessel campaign
$elementId = "vessel-campaign-" . $post->ID;
?>

<div id="<?= esc_html($elementId) ?>">

    <?= esc_html(apply_filters('the_content', $aboveContent)) ?>

    <?php if (isset($campaignId)): ?>
        <a href="#" class="open-full-screen">Enter Full Screen View</a>

        <div class="map-wrapper <?= $layout === VesselCampaignsMetaBox::sidebarLayout ? 'use-margins' : '' ?>">
            <div class="vessel-moment-viewer-tracer"></div>

            <div class="vessel-sidebar-wrapper use-margins">
                <div class="vessel-menu-tab"></div>

                <div class="vessel-sidebar">
                    <a href="#" class="exit-full-screen">Exit Full Screen</a>
                    <h2 class="vessel-campaign-title"></h2>

                    <div class="vessel-tags-wrapper"></div>

                    <ol class="vessel-sidebar-list">
                    </ol>

                    <a href="#" class="more-info-button">MORE INFO</a>
                </div>

                <div class="vessel-info-container">
                </div>
            </div>

            <div class="vessel-powered-by <?= $layout === VesselCampaignsMetaBox::sidebarLayout ? 'use-margins' : '' ?>">
                <a href="https://vesselapp.co" target="_blank">
                <div class = "mobile-hide">
                    <span>made with </span><span class="vessel-heart">&#x2764;</span><span> by</span>
                </div>
                <img src="<?= esc_url(VESSEL_HOST . 'images/vessel-logo.svg') ?>">
                </a>
            </div>

            <div class="vessel-moment-viewer">
                <div class="title-bar">
                    <span class="close-viewer-button"></span>
                    <span class="venue-title">Venue Name</span>
                </div>

                <div class="vessel-viewer-wrapper">
                    <div class="moment-owl-carousel owl-carousel owl-theme viewer"></div>

                    <div class="vessel-moment-progress-bar">
                        <div class="vessel-progress-fill"></div>
                    </div>

                    <img class="vessel-nav-left" src="<?= esc_url(VESSEL_HOST . 'images/leftarrow.svg') ?>" width="15">
                    <img class="vessel-nav-right" src="<?= esc_html(VESSEL_HOST . 'images/rightarrow.svg') ?>" width="15">
                </div>

                <div class="vessel-button-container">
                    <div class="action-button-container">
                        <a href="#" target="_blank" class="action-button-1">Action 1</a>
                        <a href="#" target="_blank" class="action-button-2">Action 2</a>
                    </div>
                    <div class="vessel-viewer-gradient"></div>
                    <div class="viewer-button-bar">
                        <button class="vessel-call-button">CALL</button>
                        <button class="vessel-directions-button">DIRECTIONS</button>
                        <button class="vessel-map-button">MAP</button>
                    </div>
                </div>
            </div>

            <div class="vessel-mobile-venue-carousel-wrapper">
                <div class="venue-nav nav-left">
                    <span class="vessel-venue-nav-left">&#10094;</span>
                </div>
                <div class="vessel-mobile-venue-carousel owl-carousel"></div>
                <div class="venue-nav nav-right">
                    <span class="vessel-venue-nav-right">&#10095;</span>
                </div>
            </div>
        </div>

        <div class="vessel-map-spacer <?= $layout === VesselCampaignsMetaBox::sidebarLayout ? 'use-margins' : '' ?>"></div>

        <a href="#" class="open-full-screen">Enter Full Screen View</a>

        <script>
            VesselApp(
                '<?= esc_html($elementId) ?>',
                <?= esc_html($campaignId) ?>,
                '<?= esc_url(plugin_dir_url(__FILE__) . 'images/') ?>',
                <?= ($layout === VesselCampaignsMetaBox::sidebarLayout) ? 'true' : 'false' ?>,
                false,
            )
        </script>

    <?php endif; ?>

    <?= esc_html(apply_filters('the_content', $belowContent)) ?>

</div>

