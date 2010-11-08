<?php
/*
Plugin Name: DeepSystems Ad Rotator
Plugin URI: http://www.ds.ee
Description: This is a really great plugin that extends WordPress.
Version: 0.6
Author: Jevgeni Kazantsev
Author URI: http://www.ds.ee
*/

//error_reporting(E_ALL);

if ( is_admin() ) {
    include_once ABSPATH . 'wp-content/plugins/ds-adrotator/back.php';
    $ds_adrotator = new DSAdRotator;
    if ( isset($ds_adrotator) ) {
    	register_activation_hook( __FILE__, array( &$ds_adrotator, 'install' ) );
    	register_deactivation_hook( __FILE__, array( &$ds_adrotator, 'uninstall' ) );
    }
} else {
    include_once ABSPATH . 'wp-content/plugins/ds-adrotator/front.php';
    $ds_adrotator = new DSAdRotator;
}
function show_banners($group) {
	
	$ds_adrotator->show_banners($group);
	
}
?>