<?php
	
if ( $this->db->has_cap( 'collation' ) ) {
		if ( ! empty( $this->db->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET " . $this->db->charset;
		}
		if ( ! empty( $this->db->collate ) ) {
			$charset_collate .= "COLLATE " . $this->db->collate;
		}
}

if( ! $this->is_table_exists( $this->tables[0] ) ) {
	$add1 = "CREATE TABLE " . $this->tables[0] . " (
		`id` mediumint(8) unsigned NOT NULL auto_increment,
			  `title` longtext NOT NULL,
			  `thetime` int(15) NOT NULL default '0',
			  `width` int(15) NOT NULL default '0',
			  `height` int(15) NOT NULL default '0',
			  `author` varchar(60) NOT NULL default '',
			  `active` varchar(4) NOT NULL default 'yes',
			  `startshow` int(15) NOT NULL default '0',
			  `endshow` int(15) NOT NULL default '0',
			  `group` int(15) NOT NULL default '1',
			  `banner` varchar(255) NOT NULL,
			  `link` longtext NOT NULL,
	  	PRIMARY KEY  (`id`)
	) " . $charset_collate;
	if( $this->db->query( $this->db->prepare( $add1 ) ) !== true ) {
		echo '<div class="updated"><h3>WARNING! There was an error with MySQL! One or more queries failed. This means the database has not been created or only partly.</h3></div>';
	}
} 

if( ! $this->is_table_exists( $this->tables[1] ) ) {
	$add2 = "CREATE TABLE " . $this->tables[1] . " (
		`id` mediumint(8) unsigned NOT NULL auto_increment,
		`name` varchar(255) NOT NULL,
		`columns` tinyint(2) NOT NULL default '1',
		`width` varchar(6) NOT NULL,
		`height` varchar(6) NOT NULL,
		PRIMARY KEY  (`id`)
	) " . $charset_collate;
	if( $this->db->query( $this->db->prepare( $add2 ) ) !== true ) {
		echo '<div class="updated"><h3>WARNING! There was an error with MySQL! One or more queries failed. This means the database has not been created or only partly.</h3></div>';
	}
}
if(!is_dir(ABSPATH . $this->banners_folder)) {
		mkdir(ABSPATH . $this->banners_folder, 0755);
}
?>