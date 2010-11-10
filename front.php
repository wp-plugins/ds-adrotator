<?php
#---------------------------------------------------
# class DSAdRotator (front-end)
#---------------------------------------------------

class DSAdRotator {

    private $db = false;
    private $tables;
	private $banners_folder;
	
    function __construct() {
        global $wpdb;
        $this->db = &$wpdb;
        $this->banners_folder = 'wp-content/banners/';
        $this->tables = array(
				$this->db->prefix . "dsrotator_banners",
				$this->db->prefix . "dsrotator_groups",
		);
       // wp_enqueue_script('swfobject', ABSPATH . 'wp-content/plugins/ds-adrotator/swfobject.js');
       // wp_enqueue_script('myscript', ABSPATH . 'wp-content/plugins/ds-adrotator/myscript.js');
    }
    public function show_banners($group_id) {
    	$banners_number = $this->db->get_var($this->db->prepare("SELECT COUNT(*) FROM `".$this->tables[0]."` WHERE `group` = '$group_id'"));
    	$now = current_time('timestamp');
    	$active_banner = " AND `active` = 'yes' AND '$now' >= `startshow` AND '$now' <= `endshow`";
    	$group = $this->db->get_row($this->db->prepare("SELECT * FROM `" . $this->tables[1]."` WHERE `id` = '$group_id'"));
    	$banners = $this->db->get_results($this->db->prepare("SELECT * FROM `" . $this->tables[0]."` WHERE `group` = '$group_id'".$active_banner." ORDER BY RAND() LIMIT $group->columns"));
    	foreach ($banners as $banner) {
		echo "<div class='banner-content'>";
    		$banner_type = end(explode(".", $banner->banner));
    		$banner_file = $this->banners_folder.$banner->banner;
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
    			wp_swfobject_echo($banner_file, $width, $height, $swf_link);
    		} else { ?>
    			<a href="<?php echo $banner->link; ?>"><img style="width : <?php echo $width;?>px; height: <?php echo $height;?>px;" src="<?php echo $banner_file; ?>" /></a>
		<?php echo "</div>"; ?>
    			<?php
    		}
    	}
    }
}
?>
