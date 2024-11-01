<?php namespace VotingPhoto\Frontend;

use Premmerce\SDK\V2\FileManager\FileManager;
use VotingPhoto\VotingPhotoPlugin;

/**
 * Class Frontend
 *
 * @package VotingPhoto\Frontend
 */
class Frontend {

	/**
	 * @var FileManager
	 */
	private $fileManager;
	private $frontendData;

	public function __construct( FileManager $fileManager ) {

	    $ajaxUrl = admin_url('admin-ajax.php');
        $this->frontendData = '
        /* <![CDATA[ */
        var photo_contest_options  = {
        "votingAjaxUrl":"'.$ajaxUrl.'"
        }; /* ]]> */
        ';

		$this->fileManager = $fileManager;
        $this->registerActions();
    }

    public function registerActions(){
        add_filter('post_gallery', array($this, 'addGalleryVotes'), 10, 3);
        add_action('wp_head', array($this, 'addInlineStyles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueueScripts'));
        add_action('wp_footer', array($this, 'enqueueAdaptiveStyles'));
    }

     public function addInlineStyles(){

    $iconType = get_theme_mod('like_icon_type', 'heart_red');

    switch ($iconType){
        case 'heart_red':
            $iconActiveUrl = $this->fileManager->locateAsset('frontend/img/icon-heart-red.png');
            $iconNotActiveUrl = $this->fileManager->locateAsset('frontend/img/icon-heart-stroke.png');
            break;
        case 'heart_white':
            $iconActiveUrl = $this->fileManager->locateAsset('frontend/img/icon-heart-white.png');
            $iconNotActiveUrl = $this->fileManager->locateAsset('frontend/img/icon-heart-stroke-white.png');
            break;
        case 'like_red':
            $iconActiveUrl = $this->fileManager->locateAsset('frontend/img/icon-like-red.png');
            $iconNotActiveUrl = $this->fileManager->locateAsset('frontend/img/icon-like-stroke.png');
            break;
        case 'like_white':
            $iconActiveUrl = $this->fileManager->locateAsset('frontend/img/Icon-like-white.png');
            $iconNotActiveUrl = $this->fileManager->locateAsset('frontend/img/icon-like-stroke-white.png');
            break;

    }


    $styles = "
<style>
.contest-img{
position: relative;
}
.gallery-voting{
position:absolute;
color:white;
bottom:10px;
right:10px;
background-color: rgba( 0, 0, 0, 0.6);
z-index: 1000;
padding: 5px;
border-radius: 5px;
font-size: 15px;
}
.gallery-voting:hover{
    cursor: pointer;
}
.gallery-voting img{
display: inline-block;
vertical-align: middle;
}
.voting-active{
display: inline-block;
vertical-align: middle;
background: transparent url({$iconActiveUrl}) no-repeat left;
background-size: 25px 25px;
min-height: 25px;
min-width: 25px;
}
.not-voting{
display: inline-block;
vertical-align: middle;
background: transparent url({$iconNotActiveUrl}) no-repeat left;
background-size: 25px 25px;
min-height: 25px;
min-width: 25px;
}
</style>";

    echo $styles;
}


    public function enqueueScripts()
    {

        wp_enqueue_script(
            'voting-for-photo',
            $this->fileManager->locateAsset('frontend/js/voting-for-photo.js'),
            array('jquery'),
            VotingPhotoPlugin::VERSION,
            true
        );

        wp_add_inline_script( 'voting-for-photo', $this->frontendData, 'before' );


    }

    public function enqueueAdaptiveStyles(){


        if(get_theme_mod('adaptive_gallery',0)){
            wp_enqueue_style(
                'wff-styles',
                $this->fileManager->locateAsset('frontend/css/gallery-adaptive.css'),
                array(),
                VotingPhotoPlugin::VERSION
            );
        }


    }


    public function addGalleryVotes($output, $attr, $instance){

        if(!isset($attr['voting_enable']) || !$attr['voting_enable']){
            return '';
        }

        $post = get_post();

        $html5 = current_theme_supports( 'html5', 'gallery' );
        $atts = shortcode_atts( array(
            'order'      => 'ASC',
            'orderby'    => 'menu_order ID',
            'id'         => $post ? $post->ID : 0,
            'itemtag'    => $html5 ? 'figure'     : 'dl',
            'icontag'    => $html5 ? 'div'        : 'dt',
            'captiontag' => $html5 ? 'figcaption' : 'dd',
            'columns'    => 3,
            'size'       => 'thumbnail',
            'include'    => '',
            'exclude'    => '',
            'link'       => '',
            'voting_count' => 1
        ), $attr, 'gallery' );

        $id = intval( $atts['id'] );

        if ( ! empty( $atts['include'] ) ) {
            $_attachments = get_posts( array( 'include' => $atts['include'], 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $atts['order'], 'orderby' => $atts['orderby'] ) );

            $attachments = array();
            foreach ( $_attachments as $key => $val ) {
                $attachments[$val->ID] = $_attachments[$key];
            }
        } elseif ( ! empty( $atts['exclude'] ) ) {
            $attachments = get_children( array( 'post_parent' => $id, 'exclude' => $atts['exclude'], 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $atts['order'], 'orderby' => $atts['orderby'] ) );
        } else {
            $attachments = get_children( array( 'post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $atts['order'], 'orderby' => $atts['orderby'] ) );
        }

        if ( empty( $attachments ) ) {
            return '';
        }

        if ( is_feed() ) {
            $output = "\n";
            foreach ( $attachments as $att_id => $attachment ) {
                $output .= wp_get_attachment_link( $att_id, $atts['size'], true ) . "\n";
            }
            return $output;
        }

        $itemtag = tag_escape( $atts['itemtag'] );
        $captiontag = tag_escape( $atts['captiontag'] );
        $icontag = tag_escape( $atts['icontag'] );
        $valid_tags = wp_kses_allowed_html( 'post' );
        if ( ! isset( $valid_tags[ $itemtag ] ) ) {
            $itemtag = 'dl';
        }
        if ( ! isset( $valid_tags[ $captiontag ] ) ) {
            $captiontag = 'dd';
        }
        if ( ! isset( $valid_tags[ $icontag ] ) ) {
            $icontag = 'dt';
        }

        $columns = intval( $atts['columns'] );
        $itemwidth = $columns > 0 ? floor(100/$columns) : 100;
        $float = is_rtl() ? 'right' : 'left';

        $selector = "gallery-{$instance}";

        $gallery_style = '';

        /**
         * Filters whether to print default gallery styles.
         *
         * @since 3.1.0
         *
         * @param bool $print Whether to print default gallery styles.
         *                    Defaults to false if the theme supports HTML5 galleries.
         *                    Otherwise, defaults to true.
         */
        if ( apply_filters( 'use_default_gallery_style', ! $html5 ) ) {
            $gallery_style = "
		<style type='text/css'>
			#{$selector} {
				margin: auto;
			}
			#{$selector} .gallery-item {
				float: {$float};
				margin-top: 10px;
				text-align: center;
				width: {$itemwidth}%;
			}
			#{$selector} img {
				border: 2px solid #cfcfcf;
			}
			#{$selector} .gallery-caption {
				margin-left: 0;
			}
			/* see gallery_shortcode() in wp-includes/media.php */
		</style>\n\t\t";
        }

        $size_class = sanitize_html_class( $atts['size'] );
        $gallery_div = "<div id='$selector' class='gallery galleryid-{$id} gallery-columns-{$columns} gallery-size-{$size_class} like_icon_type' data-id='{$id}-{$instance}' data-voting-count='{$atts['voting_count']}'>";

        /**
         * Filters the default gallery shortcode CSS styles.
         *
         * @since 2.5.0
         *
         * @param string $gallery_style Default CSS styles and opening HTML div container
         *                              for the gallery shortcode output.
         */
        $output = apply_filters( 'gallery_style', $gallery_style . $gallery_div );

        $i = 0;
        foreach ( $attachments as $id => $attachment ) {

            $attr = ( trim( $attachment->post_excerpt ) ) ? array( 'aria-describedby' => "$selector-$id" ) : '';
            if ( ! empty( $atts['link'] ) && 'file' === $atts['link'] ) {
                $image_output = wp_get_attachment_link( $id, $atts['size'], false, false, false, $attr );
            } elseif ( ! empty( $atts['link'] ) && 'none' === $atts['link'] ) {
                $image_output = wp_get_attachment_image( $id, $atts['size'], false, $attr );
            } else {
                $image_output = wp_get_attachment_link( $id, $atts['size'], true, false, false, $attr );
            }
            $image_meta  = wp_get_attachment_metadata( $id );


            $orientation = '';
            if ( isset( $image_meta['height'], $image_meta['width'] ) ) {
                $orientation = ( $image_meta['height'] > $image_meta['width'] ) ? 'portrait' : 'landscape';
            }
            $output .= "<{$itemtag} class='gallery-item'>";

            $image = wp_get_attachment_image_src($id, $atts['size'], false);

            $width = $image[1];
            $output .= "
			<{$icontag} class='gallery-icon {$orientation}'><div class='contest-img' style='max-width: {$width}px;'>";

            $output .= $image_output;


            global $wpdb;
            $countquery = "SELECT COUNT(`id`) FROM `" . $wpdb->prefix . "galleryvotes` WHERE `attachment_id` = '" . $attachment->ID . "'";
            $count = $wpdb->get_var($countquery);
            if (empty($count)) {
                $count = 0;
            }

            $output .= "<span class='gallery-voting' data-id='{$attachment->ID}'>";

            $vote_count_same = (empty($_COOKIE['gallery_voting_same_' . $attachment->ID])) ? 0 : $_COOKIE['gallery_voting_same_' . $attachment->ID];
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $vote_count_same_query = "SELECT COUNT(*) FROM `" . $wpdb -> prefix . "galleryvotes` WHERE `ip_address` = '" . $ip_address . "' AND `attachment_id` = '" . $attachment->ID . "'";
            $vote_count_same_ip = $wpdb -> get_var($vote_count_same_query);

            if(!empty($vote_count_same) || !empty($vote_count_same_ip)){
                $output .= "<span id='voting-icon-{$attachment->ID}' class='voting-active'></span> ";
            }else{
                $output .= "<span id='voting-icon-{$attachment->ID}' class='not-voting'></span> ";
            }


            $output .= "<span id='gallery-voting-count-{$attachment->ID}'>{$count}</span>               
                        </span>";

            $output .= "</div></{$icontag}>";

            if ( $captiontag && trim($attachment->post_excerpt) ) {
                $output .= "
				<{$captiontag} class='wp-caption-text gallery-caption' id='$selector-$id'>
				" . wptexturize($attachment->post_excerpt) . "
				</{$captiontag}>";
            }

            $output .= "</{$itemtag}>";
            if ( ! $html5 && $columns > 0 && ++$i % $columns == 0 ) {
                $output .= '<br style="clear: both" />';
            }
        }

        if ( ! $html5 && $columns > 0 && $i % $columns !== 0 ) {
            $output .= "
			<br style='clear: both' />";
        }

        $output .= "
		</div>\n";

        return $output;

    }

}