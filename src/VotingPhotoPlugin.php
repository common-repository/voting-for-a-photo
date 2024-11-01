<?php namespace VotingPhoto;

use Premmerce\SDK\V2\FileManager\FileManager;
use VotingPhoto\Admin\Admin;
use VotingPhoto\Frontend\Frontend;
use VotingPhoto\Admin\Customizer;

/**
 * Class VotingPhotoPlugin
 *
 * @package VotingPhoto
 */
class VotingPhotoPlugin {

    const VERSION = '1.2';

	/**
	 * @var FileManager
	 */
	private $fileManager;

	/**
	 * VotingPhotoPlugin constructor.
	 *
     * @param string $mainFile
	 */
    public function __construct($mainFile) {
        $this->fileManager = new FileManager($mainFile);

        add_action('plugins_loaded', [ $this, 'loadTextDomain' ]);

	}

	/**
	 * Run plugin part
	 */
	public function run() {
		if ( is_admin() ) {
			new Admin( $this->fileManager );
		} else {
			new Frontend( $this->fileManager );
		}
        new Customizer();
	}

    /**
     * Load plugin translations
     */
    public function loadTextDomain()
    {
        $name = $this->fileManager->getPluginName();
        load_plugin_textdomain('voting-for-photo', false, $name . '/languages/');
    }

	/**
	 * Fired when the plugin is activated
	 */
	public function activate() {
		// TODO: Implement activate() method.

        global $wpdb;
        $name = $wpdb -> prefix . 'galleryvotes';
        $query = "SHOW TABLES LIKE '" . $name . "'";
        if (!$wpdb -> get_var($query)) {
            $query = "CREATE TABLE `" . $name . "` (";
            $query .= "`id` INT NOT NULL AUTO_INCREMENT,";
            $query .= "`ip_address` VARCHAR(100) NOT NULL DEFAULT '',";
            $query .= "`gallery_id` VARCHAR(50) NOT NULL DEFAULT '',";
            $query .= "`attachment_id` INT(11) NOT NULL DEFAULT '0',";
            $query .= "`rating` INT(11) NOT NULL DEFAULT '0',";
            $query .= "`created` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',";
            $query .= "`modified` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',";
            $query .= "PRIMARY KEY (`id`)";
            $query .= ") ENGINE=MyISAM AUTO_INCREMENT=1 CHARSET=UTF8 COLLATE=utf8_general_ci;";

            $wpdb -> query($query);
        }


	}

	/**
	 * Fired when the plugin is deactivated
	 */
	public function deactivate() {
		// TODO: Implement deactivate() method.
	}

	/**
	 * Fired during plugin uninstall
	 */
	public static function uninstall() {
		// TODO: Implement uninstall() method.
	}
}