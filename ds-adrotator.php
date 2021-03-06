<?php
/*
Plugin Name: DS Ad Rotator
Plugin URI: http://www.ds.ee
Description: This plugin is small banner ad management system for WordPress.
Version: 0.8
Author: Jevgeni Kazantsev
Author URI: http://www.ds.ee
*/


if ( is_admin() ) {
    include_once (WP_PLUGIN_DIR . '/ds-adrotator/back.php');
    $ds_adrotator = new DSAdRotator;
    if ( isset($ds_adrotator) ) {
    	register_activation_hook( __FILE__, array( &$ds_adrotator, 'install' ) );
    	register_deactivation_hook( __FILE__, array( &$ds_adrotator, 'uninstall' ) );
    }
} else {
	function ds_show_banners($group_id, $banner_template = false) {
		global $wpdb;
		$ds_adbanners_folder = '/wp-content/banners/';
		$ds_adtables = array(
				$wpdb->prefix . "dsrotator_banners",
				$wpdb->prefix . "dsrotator_groups",
		);
		$banners_number = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `".$ds_adtables[0]."` WHERE `group` = '$group_id'"));
    	$now = current_time('timestamp');
    	$active_banner = " AND `active` = 'yes' AND '$now' >= `startshow` AND '$now' <= `endshow`";
    	$group = $wpdb->get_row($wpdb->prepare("SELECT * FROM `" . $ds_adtables[1]."` WHERE `id` = '$group_id'"));
    	$banners = $wpdb->get_results($wpdb->prepare("SELECT * FROM `" . $ds_adtables[0]."` WHERE `group` = '$group_id'".$active_banner." ORDER BY RAND() LIMIT $group->columns"));
		foreach ($banners as $banner) {
	    		$banner_type = end(explode(".", $banner->banner));
	    		$banner_file = $ds_adbanners_folder.$banner->banner;
	    		$banner_url = get_site_url() . $ds_adbanners_folder.$banner->banner;
	    		$banner_output = $banner_template;
	    		$swf_link = "&clickTAG=".$banner->link;
	    		if ( $banner->width != '0' ) {
	    			$width = $banner->width;
	    		} else {
	    			$width = $group->width;
	    		}
	    		if ( $banner->height != '0' ) {
	    			$height = $banner->height;
	    		} else {
	    			$height = $group->height;
	    		}
	    		if ($banner_type == "swf" || $banner_type == "SWF") {
	    			if(function_exists('wp_swfobject_echo')) {
	    				wp_swfobject_echo($banner_url, $width, $height, $swf_link);
	    			}
	    		} elseif ($banner_template) {
	    			$banner_output = str_replace('%link%', $banner->link, $banner_output);
	    			$banner_output = str_replace('%width%', $width, $banner_output);
	    			$banner_output = str_replace('%height%', $height, $banner_output);
	    			$banner_output = str_replace('%image%', $banner_url, $banner_output);
	    			$banner_output = str_replace('%local_image%', $banner_file, $banner_output);
	    			echo $banner_output;
	    		} else { ?>
	    			<a href="<?php echo $banner->link; ?>"><img style="width : <?php echo $width;?>px; height: <?php echo $height;?>px;" src="<?php echo $banner_url; ?>" /></a>
	    			<?php
	    		}
	    	}
	}
}


?>
