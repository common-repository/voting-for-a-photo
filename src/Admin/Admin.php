<?php namespace VotingPhoto\Admin;

use Premmerce\SDK\V2\FileManager\FileManager;

/**
 * Class Admin
 *
 * @package VotingPhoto\Admin
 */
class Admin {

	/**
	 * @var FileManager
	 */
	private $fileManager;

	private $error;

	/**
	 * Admin constructor.
	 *
	 * Register menu items and handlers
	 *
	 * @param FileManager $fileManager
	 */
	public function __construct( FileManager $fileManager ) {
		$this->fileManager = $fileManager;
        $this->registerActions();
    }

    public function registerActions(){
        add_action('admin_enqueue_scripts', array($this, 'enqueueScripts'));
        add_action('print_media_templates', array($this, 'addMediaElements'));
        add_filter('post_gallery', array($this, 'addGalleryVotes'), 10, 3);
        add_action('wp_ajax_calculate_votes',  array($this, 'addVOte'), 10, 1);
        add_action('wp_ajax_nopriv_calculate_votes',  array($this, 'addVOte'), 10, 1);
        add_filter('plugin_action_links_voting-for-a-photo/voting-for-a-photo.php', array($this, 'PluginActionLinks'));

    }

    public function enqueueScripts()
    {

    }



    public function addMediaElements(){
        ?>
        <script type="text/html" id="tmpl-custom-gallery-setting">
            <div style="clear: both;"></div>
            <h2><?= __('Voting Settings', 'voting-for-photo'); ?></h2>

            <label class="setting">
                <span><?= __('Enable voting', 'voting-for-photo'); ?></span>
                <input type="checkbox" data-setting="voting_enable">
            </label>

            <label class="setting">
                <span><?php _e('Number of votes per user', 'voting-for-photo'); ?></span>
                <input type="number" value="" data-setting="voting_count" style="float:left;" min="1">
            </label>

        </script>

        <script>

            jQuery(document).ready(function()
            {
                _.extend(wp.media.gallery.defaults, {
                    voting_enable: false,
                    voting_count: "1"
                });

                wp.media.view.Settings.Gallery = wp.media.view.Settings.Gallery.extend({
                    template: function(view){
                        return wp.media.template('gallery-settings')(view)
                            + wp.media.template('custom-gallery-setting')(view);
                    },
                    // this is function copies from WP core /wp-includes/js/media-views.js?ver=4.6.1
                    update: function( key ) {
                        var value = this.model.get( key ),
                            $setting = this.$('[data-setting="' + key + '"]'),
                            $buttons, $value;

                        // Bail if we didn't find a matching setting.
                        if ( ! $setting.length ) {
                            return;
                        }

                        // Attempt to determine how the setting is rendered and update
                        // the selected value.

                        // Handle dropdowns.
                        if ( $setting.is('select') ) {
                            $value = $setting.find('[value="' + value + '"]');

                            if ( $value.length ) {
                                $setting.find('option').prop( 'selected', false );
                                $value.prop( 'selected', true );
                            } else {
                                // If we can't find the desired value, record what *is* selected.
                                this.model.set( key, $setting.find(':selected').val() );
                            }

                            // Handle button groups.
                        } else if ( $setting.hasClass('button-group') ) {
                            $buttons = $setting.find('button').removeClass('active');
                            $buttons.filter( '[value="' + value + '"]' ).addClass('active');

                            // Handle text inputs and textareas.
                        } else if ( $setting.is('input[type="text"], textarea') ) {
                            if ( ! $setting.is(':focus') ) {
                                $setting.val( value );
                            }
                            // Handle checkboxes.
                        } else if ( $setting.is('input[type="checkbox"]') ) {
                            $setting.prop( 'checked', !! value && 'false' !== value );
                        }
                        // HERE the only modification I made
                        else {
                            $setting.val( value ); // treat any other input type same as text inputs
                        }
                        // end of that modification
                    },
                });

            });

        </script>
        <?php
    }


    public function addVote() {
        global $wpdb;

        $ip_address = $_SERVER['REMOTE_ADDR'];
        if(isset($_POST['voting_count'])){
            $votingCount = $_POST['voting_count'];
        }else{
            $votingCount = 1;
        }

        $tracking = 'ipaddress';

        $error = false;
        $success = false;

        if (!empty($_POST)) {
            if (!empty($_POST['attachment_id']) && !empty($_POST['gallery_id'])) {
                $attachment_id = $_POST['attachment_id'];
                $gallery_id = $_POST['gallery_id'];

               $this->checkCookieAndIp($gallery_id, $attachment_id, $votingCount, $ip_address);

            } else {
                $this->error = __('No photo was specified','voting-for-photo');
            }
        } else {
            $this->error = __('No data was posted','voting-for-photo');
        }

        $countquery = "SELECT COUNT(`id`) FROM `" . $wpdb -> prefix . "galleryvotes` WHERE `attachment_id` = '" . $attachment_id . "'";
        $count = $wpdb -> get_var($countquery);

        if($this->checkVoteCount($attachment_id,$ip_address)){
            $iconClass = 'voting-active';
        }else{
            $iconClass = 'not-voting';
        }



        if (empty($this->error)) {
            $data = array(
                'success'		=>	true,
                'count'			=> $count,
                'class'        => $iconClass
            );
        } else {
            $data = array(
                'success'		=>	false,
                'error'			=>	$this->error,
                'count'			=>	$count,
                'class'        =>  $iconClass
            );
        }

        header("Content-Type: application/json");

        wp_die(json_encode($data));
    }

    protected function checkVoteCount($attachment_id, $ip_address){
	    global $wpdb;

        $voteCount = (empty($_COOKIE['gallery_voting_same_' . $attachment_id])) ? 0 : $_COOKIE['gallery_voting_same_' . $attachment_id];
        if(empty($voteCount)){
            $voteCountQuery = "SELECT COUNT(*) FROM `" . $wpdb -> prefix . "galleryvotes` WHERE `ip_address` = '" . $ip_address . "' AND `attachment_id` = '" . $attachment_id . "'";
            $voteCount = $wpdb -> get_var($voteCountQuery);
        }

        if(empty($voteCount)){
            return false;
        }

	    return $voteCount;
    }

    protected function checkIp($gallery_id, $attachment_id, $votingCount, $ip_address){

        global $wpdb;

        $votecountquery = "SELECT COUNT(`id`) FROM " . $wpdb -> prefix . "galleryvotes WHERE `ip_address` = '" . $ip_address . "' AND `gallery_id` = '" . $gallery_id . "'";
        $votecount = $wpdb -> get_var($votecountquery);

        if (empty($votecount) || $votecount < $votingCount) {
            //same vote?
            $votecountsamequery = "SELECT COUNT(*) FROM `" . $wpdb -> prefix . "galleryvotes` WHERE `ip_address` = '" . $ip_address . "' AND `attachment_id` = '" . $attachment_id . "'";
            $votecountsame = $wpdb -> get_var($votecountsamequery);

            if (empty($votecountsame)) {

                $insert = $wpdb->insert(
                    $wpdb -> prefix .'galleryvotes',
                    array( 'ip_address' =>  $ip_address,
                        'gallery_id' => $gallery_id ,
                    'attachment_id' => $attachment_id,
                     'rating' => 1,
                     'created' => date("Y-m-d H:i:s"),
                     'modified' => date("Y-m-d H:i:s"),
                    ),
                    array( '%s','%s','%d', '%d','%s', '%s' )
                );

                if (!$insert) {
                    $this->error = __("Database could not be updated",'voting-for-photo');
                    return false;
                }else{
                    setcookie('gallery_voting_all_' . $gallery_id, ($votecount + 1), (time() + 60 * 60 * 24 * 30),'/');
                    setcookie('gallery_voting_same_' . $attachment_id, ($votecountsame + 1), (time() + 60 * 60 * 24 * 30),'/');
                    setcookie('gallery_voting_id_'. $attachment_id, $wpdb->insert_id , (time() + 60 * 60 * 24 * 30),'/');
                }
            } else {
                $query = "DELETE FROM `" . $wpdb -> prefix . "galleryvotes` WHERE `ip_address` = '" . $ip_address . "' AND `attachment_id` = '" . $attachment_id . "'";

                if (!$wpdb -> query($query)) {
                    $this->error = __("Database could not be updated",'voting-for-photo');
                    return false;
                }
            }
        } else {
            $votecountsamequery = "SELECT COUNT(*) FROM `" . $wpdb -> prefix . "galleryvotes` WHERE `ip_address` = '" . $ip_address . "' AND `attachment_id` = '" . $attachment_id . "'";
            $votecountsame = $wpdb -> get_var($votecountsamequery);
            if (empty($votecountsame)) {
                $this->error = sprintf(__("You have already voted %s times", 'voting-for-photo'), $votingCount);
                return false;
            }else{
                $query = "DELETE FROM `" . $wpdb -> prefix . "galleryvotes` WHERE `ip_address` = '" . $ip_address . "' AND `attachment_id` = '" . $attachment_id . "'";

                if (!$wpdb -> query($query)) {
                    $this->error = __("Database could not be updated",'voting-for-photo');
                    return false;
                }
            }
        }

        return true;
    }

    protected function checkCookie($gallery_id, $attachment_id, $votingCount, $ip_address){

        global $wpdb;

        $votecount = (empty($_COOKIE['gallery_voting_all_' . $gallery_id])) ? 0 : $_COOKIE['gallery_voting_all_' . $gallery_id];
        $votecountsame = (empty($_COOKIE['gallery_voting_same_' . $attachment_id])) ? 0 : $_COOKIE['gallery_voting_same_' . $attachment_id];
        $insertId = (empty($_COOKIE['gallery_voting_id_'. $attachment_id])) ? 0 : $_COOKIE['gallery_voting_id_'. $attachment_id];

        if (empty($votecount) || $votecount < $votingCount) {

            if (empty($votecountsame)) {

                $insert = $wpdb->insert(
                    $wpdb -> prefix .'galleryvotes',
                    array( 'ip_address' =>  $ip_address,
                        'gallery_id' => $gallery_id ,
                        'attachment_id' => $attachment_id,
                        'rating' => 1,
                        'created' => date("Y-m-d H:i:s"),
                        'modified' => date("Y-m-d H:i:s"),
                    ),
                    array( '%s','%s','%d', '%d','%s', '%s' )
                );

                if ($insert) {
                    setcookie('gallery_voting_all_' . $gallery_id, ($votecount + 1), (time() + 60 * 60 * 24 * 30),'/');
                    setcookie('gallery_voting_same_' . $attachment_id, ($votecountsame + 1), (time() + 60 * 60 * 24 * 30),'/');
                    setcookie('gallery_voting_id_'. $attachment_id, $wpdb->insert_id , (time() + 60 * 60 * 24 * 30),'/');
                } else {
                    $this->error = "Database could not be updated";
                    return false;
                }

            } else {

                $query = "DELETE FROM `" . $wpdb -> prefix . "galleryvotes` WHERE `id` = '" . $insertId . "'";

                $cookie_name = 'gallery_voting_id_'. $attachment_id;
                unset($_COOKIE[$cookie_name]);
                setcookie($cookie_name, '', (time() - 3600),'/');
                $cookie_name = 'gallery_voting_same_'. $attachment_id;
                unset($_COOKIE[$cookie_name]);
                setcookie($cookie_name, '', (time() - 3600),'/');
                setcookie('gallery_voting_all_' . $gallery_id, ($votecount - 1), (time() + 60 * 60 * 24 * 30),'/');

                if (!$wpdb -> query($query)) {
                    $this->error = __("Database could not be updated",'voting-for-photo');
                    return false;
                }
            }
        } else {

            if (empty($votecountsame)) {
                $this->error = sprintf(__("You have already voted %s times", 'voting-for-photo'), $votingCount);
                return false;
            }else{
                $query = "DELETE FROM `" . $wpdb -> prefix . "galleryvotes` WHERE `id` = '" . $insertId . "'";

                $cookie_name = 'gallery_voting_id_'. $attachment_id;
                unset($_COOKIE[$cookie_name]);
                setcookie($cookie_name, '', (time() - 3600),'/');
                $cookie_name = 'gallery_voting_same_'. $attachment_id;
                unset($_COOKIE[$cookie_name]);
                setcookie($cookie_name, '', (time() - 3600),'/');
                setcookie('gallery_voting_all_' . $gallery_id, ($votecount - 1), (time() + 60 * 60 * 24 * 30),'/');

                if (!$wpdb -> query($query)) {
                    $this->error = __("Database could not be updated",'voting-for-photo');
                    return false;
                }
            }
        }
        return true;
    }

    protected function checkCookieAndIp($gallery_id, $attachment_id, $votingCount, $ip_address)
    {
        if(!isset($_COOKIE['gallery_voting_all_' . $gallery_id]) || !isset($_COOKIE['gallery_voting_same_' . $attachment_id])){

            $this->checkIp($gallery_id, $attachment_id, $votingCount, $ip_address);

        }else{
            $this->checkCookie($gallery_id, $attachment_id, $votingCount, $ip_address);
        }
    }

    public function PluginActionLinks($links)
    {
        $action_links = array(
            'settings' => '<a href="' .
                wp_kses(esc_url(add_query_arg(array(
                    'autofocus' => array(
                        'section' => 'gallery_settings',
                    ),
                    'url' => home_url(),
                ), admin_url('customize.php'))), array(
                    'a' => array(
                        'href' => array(),
                        'title' => array(),
                    ),
                ))

                . '" aria-label="' . esc_attr__('View Voting for a Photo settings', 'voting-for-photo') . '">' . esc_html__('Settings', 'voting-for-photo') . '</a>',
        );

        return array_merge($action_links, $links);
    }




}