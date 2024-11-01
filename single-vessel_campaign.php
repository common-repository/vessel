<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since 1.0
 * @version 1.0
 */

//$venueData = get_post_meta(get_the_ID(), VesselCampaignsMetaBox::jsonMetaKey, true);
the_post();

$json = $post->vessel_json;

$aboveContent = get_post_meta(get_the_ID(), VesselCampaignsMetaBox::aboveContent, true);
$belowContent = get_post_meta(get_the_ID(), VesselCampaignsMetaBox::belowContent, true);
$layout = get_post_meta(get_the_ID(), VesselCampaignsMetaBox::layoutKey, true);

function addClassIfSidebar($class) {
    global $layout;
    if ($layout === VesselCampaignsMetaBox::sidebarLayout) return "class=\"$class\"";
    return '';
}

get_header(); ?>

<?php
the_content();

// If comments are open or we have at least one comment, load up the comment template.
if ( comments_open() || get_comments_number() ) {
    comments_template();
}

if ( is_singular( VesselCampaignPost::POST_TYPE ) ) {
    // Previous/next post navigation.
    the_post_navigation( array(
        'next_text' => '<span class="meta-nav" aria-hidden="true">' . __( 'Next', 'twentysixteen' ) . '</span> ' .
                       '<span class="screen-reader-text">' . __( 'Next post:', 'twentysixteen' ) . '</span> ' .
                       '<span class="post-title">%title</span>',
        'prev_text' => '<span class="meta-nav" aria-hidden="true">' . __( 'Previous', 'twentysixteen' ) . '</span> ' .
                       '<span class="screen-reader-text">' . __( 'Previous post:', 'twentysixteen' ) . '</span> ' .
                       '<span class="post-title">%title</span>',
    ) );
}

?>

<?php get_footer(); ?>
