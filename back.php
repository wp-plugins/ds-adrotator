<?php
#---------------------------------------------------
# class DSAdRotator (back-end)
#---------------------------------------------------


class DSAdRotator {

    private $db = false;
	private $tables;
    private $userdata = false;
	private $banners_folder;
    
    function __construct() {
        global $wpdb, $userdata;
        $this->db = &$wpdb;
        $this->userdata = &$userdata;
        $this->tables = array(
				$this->db->prefix . "dsrotator_banners",
				$this->db->prefix . "dsrotator_groups",
		);
		$this->banners_folder = 'wp-content/banners/';
        $this->actions_add();
    }
    
	private function actions_add() {
		add_action('admin_notices', array(&$this, 'expired_banners'));
		add_action('admin_menu', array(&$this, 'add_admin_pages'));
        if( isset( $_POST['rotator_banner_action'] ) ) {
			add_action('init', array(&$this, 'banner_action') );
        }
	        if( isset( $_POST['rotator_group_action'] ) ) {
			add_action('init', array(&$this, 'group_action') );
        }
        if( isset( $_POST['rotator_group_submit'] ) ) {
			add_action('init', array(&$this, 'insert_group') );
        }
		if( isset( $_POST['rotator_banner_submit'] ) ) {
			add_action('init', array(&$this, 'insert_banner') );
		}
        
	}
	
    public function install() {
    	include_once ABSPATH . 'wp-content/plugins/ds-adrotator/installer.php';
    }
    
    public function uninstall() {
    	include_once ABSPATH . 'wp-content/plugins/ds-adrotator/uninstaller.php';
    }
    
    private function is_table_exists($tablename) {
    	foreach ( $this->db->get_col( "SHOW TABLES", 0 ) as $table ) {
			if ( $table == $tablename ) {
				return true;
			}
		}
		return false;
    }

    function add_admin_pages() {
    	add_object_page( 'DS AdRotator', 'DS AdRotator', -1, __FILE__, array( &$this, 'plugin_manage' ) );
		add_submenu_page( __FILE__, 'Manage Banners', 'Manage Banners', 7, 'dsrotator', array( &$this, 'manage_banners' ) );
		add_submenu_page( __FILE__, 'Add Banner', 'Add Banner', 7, 'dsrotator2', array( &$this, 'add_banner' ) );
		add_submenu_page( __FILE__, 'Manage Groups', 'Manage Groups', 7, 'dsrotator3', array( &$this, 'manage_groups' ) );
		add_submenu_page( __FILE__, 'Add Group', 'Add Group', 7, 'dsrotator4', array( &$this, 'add_group' ) );
    }
    function generateRandomStr($length = 6){
	  $chars = 'abdefhiknrstyzABDEFGHKNQRSTYZ23456789';
	  $numChars = strlen($chars);
	  $string = '';
	  for ($i = 0; $i < $length; $i++) {
	    $string .= substr($chars, rand(1, $numChars) - 1, 1);
	  }
	  return $string;
	}

    function manage_banners() {   	
		$message = $_GET['message'];
		$rotator_db_error = false;
		if(isset($_POST['rotator_order'])) { 
			$order = $_POST['rotator_order']; 
		} else { 
			$order = 'thetime ASC'; 
		}
		if ( ( $this->is_table_exists( $this->tables[0] ) ) && ($this->is_table_exists( $this->tables[1] ) ) ) {
			$rotator_db_error = false;
			$now = current_time('timestamp');
			$in2days = $now + 172800;
			$groups = $this->db->get_results("SELECT * FROM `" . $this->tables[1] . "` ORDER BY `id`");
			$banners = $this->db->get_results("SELECT * FROM `" . $this->tables[0] . "`  ORDER BY ".$order);
		} else {
			$rotator_db_error = true;
		}
		?>
	    <div class="wrap">
	    	<h2><?php _e('Manage Banners'); ?> <a class="button add-new-h2" href="admin.php?page=dsrotator2">Lisa uus</a></h2>
	    	<?php if ($message == 'created') { ?>
				<div id="message" class="updated fade"><p><?php _e('Banner'); ?> <strong>created</strong>.</p></div>
			<?php } else if ($message == 'updated') { ?>
				<div id="message" class="updated fade"><p><?php _e('Banner'); ?> <strong>updated</strong>.</p></div>
			<?php } else if ($message == 'deleted') { ?>
				<div id="message" class="updated fade"><p><?php _e('Banner(s)'); ?> <strong>deleted</strong>.</p></div>
			<?php } else if ($message == 'renew') { ?>
				<div id="message" class="updated fade"><p><?php _e('Banner(s)'); ?> <strong>renewed</strong>.</p></div>
			<?php } else if ($message == 'deactivate') { ?>
				<div id="message" class="updated fade"><p><?php _e('Banner(s)'); ?> <strong>deactivated</strong>.</p></div>
			<?php } else if ($message == 'activate') { ?>
				<div id="message" class="updated fade"><p><?php _e('Banner(s)'); ?> <strong>activated</strong>.</p></div>
			<?php } else if ($message == 'no_access') { ?>
				<div id="message" class="updated fade"><p><?php _e('Action prohibited'); ?>.</p></div>
			<?php } ?>
	    	<form name="banners" method="post" action="admin.php?page=dsrotator">
	    	<input type="hidden" name="rotator_banner_action" value="deactivate" />
	    	<?php
	    	if (function_exists ('wp_nonce_field') ) {
	        	wp_nonce_field('banners_form');
	    	}
	        ?>
	    		<div class="tablenav">
		    		<div class="alignleft action">
		    			<select class="postform" name="rotator_banner_action">
		    				<option selected="selected" value=""><?php _e('Bulk Actions'); ?></option>
					        <option value="deactivate"><?php _e('Deactivate'); ?></option>
					        <option value="activate"><?php _e('Activate'); ?></option>
					        <option value="delete"><?php _e('Delete'); ?></option>
					        <option value="renewmultiple-31536000"><?php _e('Renew for 1 year'); ?></option>
					        <option value="renewmultiple-2592000"><?php _e('Renew for 30 days'); ?></option>
					        <option value="renewmultiple-5184000"><?php _e('Renew for 180 days'); ?></option>
					        <?php if ($groups) { ?>
				        		<option disabled><?php _e('Move selection to category'); ?></option>
								<?php foreach($groups as $group) { ?>
						     		<option value="move-<?php echo $group->id;?>"><?php echo $group->id;?> - <?php echo $group->name;?></option>
				 				<?php } ?>
							<?php } ?>
		    			</select>
		    			<input type="submit" name="submit" onclick="return confirm('Kas Te olete kindel?\n\n\'OK\' to continue, \'Cancel\' to stop.')" id="post-action-submit" value="<?php _e('Rakenda'); ?>" class="button-secondary" />
		    			
		    			Sort by <select name='rotator_order' id='cat' class='postform' >
					        <option value="startshow ASC" <?php if($order == "startshow ASC") { echo 'selected'; } ?>>start date (ascending)</option>
					        <option value="startshow DESC" <?php if($order == "startshow DESC") { echo 'selected'; } ?>>start date (descending)</option>
					        <option value="endshow ASC" <?php if($order == "endshow ASC") { echo 'selected'; } ?>>end date (ascending)</option>
					        <option value="endshow DESC" <?php if($order == "endshow DESC") { echo 'selected'; } ?>>end date (descending)</option>
					        <option value="ID ASC" <?php if($order == "ID ASC") { echo 'selected'; } ?>>ID (ascending)</option>
					        <option value="ID DESC" <?php if($order == "ID DESC") { echo 'selected'; } ?>>ID (descending)</option>
					        <option value="title ASC" <?php if($order == "title ASC") { echo 'selected'; } ?>>title (A-Z)</option>
					        <option value="title DESC" <?php if($order == "title DESC") { echo 'selected'; } ?>>title (Z-A)</option>
					        </select>
						<input type="submit" id="post-query-submit" value="Sort" class="button-secondary" />
					</div>
		    		<br class="clear">
		    	</div>
		    	<table class="widefat" style="margin-top: .5em">
		    		<thead>
		    			<tr>
							<th scope="col" class="check-column">&nbsp;</th>
							<th scope="col" width="2%"><center>ID</center></th>
							<th scope="col">Title</th>
							<th scope="col" width="10%"><center>Show from</center></th>
							<th scope="col" width="10%"><center>Show until</center></th>
							<th scope="col" width="5%"><center>Active</center></th>
							<th scope="col" width="10%"><center>Author</center></th>
							<th scope="col" width="15%">Group</th>
							<th scope="col" width="10%">Date</th>					
	
						</tr>
		    		</thead>
		    		<tfoot>
		    			<tr>
							<th scope="col" class="check-column">&nbsp;</th>
							<th scope="col" width="2%"><center>ID</center></th>
							<th scope="col">Title</th>
							<th scope="col" width="15%"><center>Show from</center></th>
							<th scope="col" width="15%"><center>Show until</center></th>
							<th scope="col" width="5%"><center>Active</center></th>
							<th scope="col" width="10%"><center>Author</center></th>
							<th scope="col" width="15%">Group</th>
							<th scope="col" width="10%">Date</th>	
						</tr>
		    		</tfoot>
		    		<tbody>
		    		<?php if (! $rotator_db_error) {
		    					if ( $banners)  {
			    					foreach ($banners as $banner) {
			    						$expired = $this->db->get_var("SELECT `id` FROM `".$this->tables[0]."` WHERE `id` = '$banner->id' AND `active` = 'no' AND `endshow` <= $now");
										$banner_show = $this->db->get_var("SELECT `id` FROM `" . $this->tables[0] . "` WHERE `id` = '$banner->id'  AND `active` = 'yes' AND '$now' >= `startshow` AND '$now' <= `endshow`");
			    						$group = $this->db->get_row($this->db->prepare("SELECT `name` FROM `" . $this->tables[1] . "` WHERE `id` = '$banner->group'"));	
			    					?>
			    					<tr id="banner-<?php echo $banner->id; ?>" class=" alternate">
										<th scope="row" class="check-column"><input type="checkbox" value="<?php echo $banner->id; ?>" name="bannercheck[]"></th>
										<td><center><?php echo $banner->id; ?></center></td>
										<td><strong>
											<a class="row-title" href="<?php echo get_option('siteurl').'/wp-admin/admin.php?page=dsrotator2&amp;edit_banner='.$banner->id;?>" title="Edit"><?php echo stripslashes(html_entity_decode($banner->title));?></a>
										</strong> </td>
										<td <?php if ( $banner_show ) { echo 'style="color: green;"';} else { if ( $expired ) { echo 'style="color: black;"'; } else if ( ! $banner_show && ! $expired && $banner->active == 'yes'){ echo 'style="color: green;"'; } } ?>><center><?php echo date("d.m.Y", $banner->startshow);?></center></td>
										<td <?php if ( $banner_show ) { echo 'style="color: green;"'; } else { if ( $expired ) { echo 'style="color: red;"'; } else { echo 'style="color: black;"'; } } ?>><center><?php echo date("d.m.Y", $banner->endshow);?></center></td>	
										<td><center><?php if($banner->active == "yes") { echo '<img src="'.get_option('siteurl').'/wp-content/plugins/ds-adrotator/icons/tick.png" title="Active"/>'; } else { echo '<img src="'.get_option('siteurl').'/wp-content/plugins/ds-adrotator/icons/cross.png" title="In-active"/>'; }?></center></td>
										<td><center><?php echo $banner->author; ?></center></td>
										<td><?php echo $group->name; ?></td>
										<td><?php echo date("d.m.Y", $banner->thetime);?></td>	
									</tr>
									<?php }?>
		    					<?php } else {?>
		    								<tr id='no-id'><td scope="row" colspan="7"><em><?php echo _e('Pole bännereid veel!');?>!</em></td></tr>
		    					<?php }?>
		    		<?php } else {?>
		    					<tr id='no-id'><td scope="row" colspan="7"><span style="font-weight: bold; color: #f00;">There was an error locating the main database table for DS AdRotator. Please deactivate and re-activate DS AdRotator from the plugin page!!</span></td></tr>
		    		<?php }?>
		    		</tbody>
		    	</table>
	    	</form>
	    	<br class="clear">
	    </div>
    	<?php
    }
	
	
	function insert_group() {

		$id 		= $_POST['adgroup_id'];
		$name 		= $_POST['adgroup_name'];
		$columns 	= $_POST['adgroup_columns'];
		$width		= $_POST['adgroup_width'];
		$height		= $_POST['adgroup_height'];

		if (strlen($name) != 0) {
			if($id > 0) {
				$postquery = "UPDATE `".$this->tables[1]."` SET `name` = '$name', `columns` = '$columns', `width` = '$width', `height` = '$height' WHERE `id` = '$id'";
				$action = "group_edit";
		} else {
				$postquery = "INSERT INTO `".$this->tables[1]."` (`name`, `columns`, `width`, `height`) VALUES ('$name', '$columns', '$width', '$height')";
				$action = "group_new";
		}
		if( $this->db->query( $this->db->prepare( $postquery ) ) !== FALSE ) {
			$this->rotator_return($action);
			exit;
		} else {
			die(mysql_error());
		}
		} else {
			$this->rotator_return('group_field_error', array($id));
			exit;
		}
	}
	function rotator_return($action, $arg = null) {
		switch($action) {
			// Regular actions
			case "new" :
				wp_redirect('admin.php?page=dsrotator&message=created');
			break;
	
			case "banner_field_error" :
				wp_redirect('admin.php?page=dsrotator2&message=fields_error&edit_banner='.$arg[0]);
			break;
			
			case "update" :
				wp_redirect('admin.php?page=dsrotator&message=updated');
			break;
			
			case "delete" :
				wp_redirect('admin.php?page=dsrotator&message=deleted');
			break;
	
			// Manage Events
	
	
			case "deactivate" :
				wp_redirect('admin.php?page=dsrotator&message=deactivate');
			break;
	
			case "activate" :
				wp_redirect('admin.php?page=dsrotator&message=activate');
			break;
	
			// Groups
			case "group_new" :
				wp_redirect('admin.php?page=dsrotator3&message=created');
			break;
	
			case "group_edit" :
				wp_redirect('admin.php?page=dsrotator3&message=updated');
			break;
	
			case "group_field_error" :
				wp_redirect('admin.php?page=dsrotator4&message=field_error&edit_group='.$arg[0]);
			break;
	
			case "group_delete" :
				wp_redirect('admin.php?page=dsrotator3&message=deleted');
			break;
	
			case "group_delete_banners" :
				wp_redirect('admin.php?page=dsrotator3&message=deleted_banners');
			break;
			
			
	
			// Misc plugin events
			case "no_access" :
				wp_redirect('admin.php?page=dsrotator&message=no_access');
			break;
	
			case "field_error" :
				wp_redirect('admin.php?page=dsrotator2&message=field_error');
			break;
	
			case "error" :
				wp_redirect('admin.php?page=dsrotator&message=error');
			break;
			
			case "file-upload-error" :
				wp_redirect('admin.php?page=dsrotator2&message=file-upload-error');
			break;
			
			case "move-file-error" :
				wp_redirect('admin.php?page=dsrotator2&message=move-file-error');
			break;
		}
	}
	function rotator_delete($id, $what) {
			if($id > 0) {
				if($what == 'banner') {
					$filename = $this->db->get_var("SELECT `banner` FROM `" . $this->tables[0] . "` WHERE `id` = $id");
					if ( $filename && file_exists(ABSPATH .$this->banners_folder.$filename)) {
						unlink(ABSPATH .$this->banners_folder.$filename);
					}
					}  else if ($what == 'bannergroup') {
						$filenames = $this->db->get_results("SELECT `banner` FROM `" . $this->tables[0] . "` WHERE `group` = $id");
						if ($filenames) {
							foreach ($filenames as $filename) {
								if (file_exists(ABSPATH .$this->banners_folder.$filename->banner)) {
									unlink(ABSPATH .$this->banners_folder.$filename->banner);
								}
							}
						}
					} else {
						$this->rotator_return('error');
						exit;
					}
			}
			if($id > 0) {
				if($what == 'banner') {
					if($this->db->query("DELETE FROM `" . $this->tables[0] . "` WHERE `id` = $id") == FALSE) {
						die(mysql_error());
					}
				} else if ($what == 'group') {
					if($this->db->query("DELETE FROM `".$this->tables[1] . "` WHERE `id` = $id") == FALSE) {
						die(mysql_error());
					}
				} else if ($what == 'bannergroup') {
					if($this->db->query("DELETE FROM `".$this->tables[1] . "` WHERE `id` = $id") == FALSE) {
						die(mysql_error());
					}
					if($this->db->query("DELETE FROM `".$this->tables[0] . "` WHERE `group` = $id") == FALSE) {
						die(mysql_error());
					}
				} else {
					$this->rotator_return('error');
					exit;
				}
		}
	}
	function group_action() {
		if ( isset($_POST['submit']) && check_admin_referer('groups_form')  ) {
			if ( !current_user_can('manage_options') ) {
				$this->rotator_return('no_access');
				exit;
			}			
			if(isset($_POST['groupcheck'])) $group_ids = $_POST['groupcheck'];
			$actions = $_POST['rotator_group_action'];
			list($action, $specific) = explode("-", $actions);				
			if($group_ids != '') {
				foreach($group_ids as $group_id) {
					if($action == 'group_delete') {
						$this->rotator_delete($group_id, 'group');
					}
					if($action == 'group_delete_banners') {
						$this->rotator_delete($group_id, 'bannergroup');
					}
				}
			}
			$this->rotator_return($action, array($banner_id));
		}
		
	}
	function banner_action() {

		if ( isset($_POST['submit']) && check_admin_referer('banners_form')  ) {
			if(isset($_POST['bannercheck'])) $banner_ids = $_POST['bannercheck'];
		
			$actions = $_POST['rotator_banner_action'];
			list($action, $specific) = explode("-", $actions);
			
			if ( !current_user_can('manage_options') ) {
				$this->rotator_return('no_access');
				exit;
			}	
			if($banner_ids != '') {
				foreach($banner_ids as $banner_id) {
					if($action == 'deactivate') {
						$this->banner_active($banner_id, 'deactivate');
					}
					if($action == 'activate') {
						$this->banner_active($banner_id, 'activate');
					}
					if($action == 'delete') {
						$this->rotator_delete($banner_id, 'banner');
					}
					if($action == 'renew') {
						$this->banner_renew($banner_id);
					}
					if($action == 'renewmultiple') {
						$this->banner_renew($banner_id, $specific);
					}
					if($action == 'move') {
						$this->banner_move($banner_id, $specific);
					}
			}
				$this->rotator_return($action, array($banner_id));
			}
		}
	}
	function add_group () { 
		$message = $_GET['message'];
		if($_GET['edit_group']) $group_edit_id = $_GET['edit_group'];
		?>
		<div class=wrap>
			<h2><?php if($group_edit_id) {echo _e('Muuda asukoht');} else {echo _e('Lisa uus asukoht');} ?></h2>
			<?php if ($message == 'field_error') { ?>
				<div id="message" class="updated fade"><p><?php echo _e('Palun täitke kõik väljad !')?></p></div>
			<?php } ?>
			<form name="group-form" id="post" method="post" action="">
	 	  	<?php
	        	if (function_exists ('wp_nonce_field') ) {
	            	wp_nonce_field('rotator_group_submit');
	            }
	            if ($group_edit_id) { 
	            	$edit_group = $this->db->get_row($this->db->prepare("SELECT * FROM `" . $this->tables[1] . "` WHERE `id` = '$group_edit_id'")); ?>
	            	<input type="hidden" name="adgroup_id" value="<?php echo $edit_group->id;?>" /> <?php
	            } 
	         ?> 
			<table class="widefat" style="margin-top: .5em">
				<thead>
					<?php
					if( ! $group_edit_id) {  ?>
			  			<tr>
							<th scope="col" colspan="2"><?php _e('Luuake uut asukoht'); ?></th>
						</tr> <?php 
					} else { ?>
						<tr valign="top">
							<th colspan="2" bgcolor="#DDD"><?php _e('Te saate muuta rühma parameetre siin. ID jääb samaks!'); ?></th>
						</tr> <?php 
					} ?>
		  		</thead>
		  		<tbody> 
		  		<?php
		  		if ($group_edit_id) { ?>
		  			<tr>
					    <th scope="row" width="25%">ID:</th>
					    <td><?php echo $edit_group->id;?></td>
				    </tr> <?php 
		  		} ?>
				      	 		
			  		<tr id='group-new'>
						<th scope="row"><?php _e('Name'); ?>:</th>
						<td><input tabindex="1" name="adgroup_name" type="text" class="search-input" size="40" value="<?php if ($group_edit_id) echo $edit_group->name;?>" autocomplete="off" /></td>
					</tr>
					<tr id='group-new'>
						<th scope="row"><?php _e('Columns'); ?>:</th>
						<td>
							<select tabindex="2" name="adgroup_columns"><?php for ($i = 1; $i <= 20; $i++) { ?>
								<option value="<?php echo $i; if ($group_edit_id && $i == $edit_group->columns) echo "selected";?>"><?php echo $i;?></option>
								<?php }?>
							</select>
						</td>
					</tr>
					<tr id='group-new'>
						<th scope="row"><?php _e('Width'); ?>:</th>
						<td><input tabindex="3" name="adgroup_width" type="text" class="search-input" size="5" value="<?php if ($group_edit_id) echo $edit_group->width;?>" autocomplete="off" /></td>
					</tr>
					<tr id='group-new'>
						<th scope="row"><?php _e('Height'); ?>:</th>
						<td><input tabindex="4" name="adgroup_height" type="text" class="search-input" size="5" value="<?php if ($group_edit_id) echo $edit_group->height;?>" autocomplete="off" /></td>
					</tr>
				</tbody>
			</table>
			<br class="clear">
			<input type="submit" name="rotator_group_submit" id="rotator-group-submit" value="<?php _e('Apply'); ?>" class="button-primary" />
			<a href="admin.php?page=dsrotator3" class="button"><?php _e('Cancel'); ?></a>
			</form>
		</div> <?php
	}
	public function manage_groups() { 
		$message = $_GET['message']; ?>
		<div class="wrap">
			<h2><?php _e('Manager Group'); ?> <a class="button add-new-h2" href="admin.php?page=dsrotator4"><?php _e('Add New'); ?></a></h2><?php 
			if ($message == 'created') { ?>
				<div id="message" class="updated fade"><p><?php _e('Group '); ?><strong><?php _e('created'); ?></strong>.</p></div> <?php
			} else if ($message == 'updated') { ?>
				<div id="message" class="updated fade"><p><?php _e('Group '); ?><strong><?php _e('updated'); ?></strong>.</p></div> <?php
			} else if ($message == 'deleted') { ?>
				<div id="message" class="updated fade"><p><?php _e('Group '); ?><strong><?php _e('deleted'); ?></strong>.</p></div> <?php
			} else if ($message == 'deleted_banners') { ?>
				<div id="message" class="updated fade"><p><?php _e('Group '); ?><strong><?php _e('deleted'); ?></strong>.<?php _e('Including all banners that were in that group.'); ?></p></div> <?php
			} ?>
			<form name="grops" method="post" action="">
				<input type="hidden" name="rotator_qroup_action" value="group_delete" />
				<?php
                if (function_exists ('wp_nonce_field') ) {
                    wp_nonce_field('groups_form');
                }
            	?> 
				<div class="tablenav">
					<div class="alignleft actions">
						<select name="rotator_group_action" class="postform">
							<option value=""><?php _e('Bulk Actions'); ?></option>
				        	<option value="group_delete"><?php _e('Delete Group'); ?></option>
				        	<option value="group_delete_banners"> <?php _e('Delete Group including ads'); ?></option>
						</select>
						<input type="submit" onclick="return confirm('Kas Te olete kindel?\n\n\'OK\' to continue, \'Cancel\' to stop.')" name="submit" id="group-action-submit" value="<?php _e('Apply'); ?>" class="button-secondary" />
					</div>
					<br class="clear">
				</div>
				<div class="clear"></div>
				<table class="widefat" style="margin-top: .5em">
	  				<thead>
	  					<tr>
							<th scope="col" class="check-column">&nbsp;</th>
							<th scope="col" width="5%"><center>ID</center></th>
							<th scope="col"><?php _e('Name'); ?></th>
							<th scope="col" width="10%"><center><?php _e('Columns'); ?></center></th>
							<th scope="col" width="10%"><center><?php _e('Width'); ?></center></th>
							<th scope="col" width="10%"><center><?php _e('Height'); ?></center></th>
							<th scope="col" width="10%"><center><?php _e('Banners'); ?></center></th>
						</tr>
	  				</thead>
	  				<tfoot>
	  					<tr>
							<th scope="col" class="check-column">&nbsp;</th>
							<th scope="col" width="5%"><center>ID</center></th>
							<th scope="col"><?php _e('Name'); ?></th>
							<th scope="col" width="10%"><center><?php _e('Columns'); ?></center></th>
							<th scope="col" width="10%"><center><?php _e('Width'); ?></center></th>
							<th scope="col" width="10%"><center><?php _e('Height'); ?></center></th>
							<th scope="col" width="10%"><center><?php _e('Banners'); ?></center></th>
						</tr>
	  				</tfoot>
	  				<tbody>
	  					<?php if ( $this->is_table_exists( $this->tables[1] ) ) {
	  							$groups = $this->db->get_results($this->db->prepare("SELECT * FROM `" . $this->tables[1] . "` ORDER BY `id`"));
		  						if( $groups) {
		  							foreach ( $groups as $group ) {
		  								$banners_in_group = $this->db->get_var($this->db->prepare("SELECT COUNT(*) FROM `" . $this->tables[0] . "` WHERE `group` = '$group->id'")); ?>
					  					<tr id="group-<?php echo $group->id; ?>" class="alternate">
					  						<th scope="row" class="check-column"><input type="checkbox" name="groupcheck[]" value="<?php echo $group->id; ?>"/></th>
					  						<td><center><?php echo $group->id; ?></center></td>
					  						<td><strong>
					  							<a href="<?php echo get_option('siteurl').'/wp-admin/admin.php?page=dsrotator4&amp;edit_group='.$group->id;?>"><?php echo $group->name; ?></a>
					  						</strong>
					  						</td>
					  						<td><center><?php echo $group->columns; ?></center></td>
											<td><center><?php echo $group->width; ?></center></td>
											<td><center><?php echo $group->height; ?></center></td>
											<td><center><?php echo $banners_in_group; ?></center></td>
					  					</tr> <?php 
		  							}
		  						} else { ?>
		  								<tr id='no-groups'>
											<th scope="row" class="check-column">&nbsp;</th>
											<td colspan="3"><em><?php _e('No groups created yet!'); ?></em></td>
										</tr> <?php 
		  						}
	  					}	
									?>
	  				</tbody>
	  			</table>
			</form>
		</div> 
		<?php 
	}

	function  add_banner() { 
		$thetime 	= current_time('timestamp');
		$startshow = $thetime;
		$endshow = $thetime + 31536000;
		$message 	= $_GET['message'];
		$groups = $this->db->get_results($this->db->prepare("SELECT * FROM `" . $this->tables[1] . "` ORDER BY `id`"));
		if($_GET['edit_banner']) {
			$banner_edit_id = $_GET['edit_banner'];
			$edit_banner = $this->db->get_row($this->db->prepare("SELECT * FROM `" . $this->tables[0] . "` WHERE `id` = '$banner_edit_id'"));
			$startshow = $edit_banner->startshow;
			$endshow = $edit_banner->endshow;
		}
		list($sday, $smonth, $syear) = split(" ", gmdate("d m Y", $startshow));
		list($eday, $emonth, $eyear) = split(" ", gmdate("d m Y", $endshow));
		?>
		<div class="wrap">
			<h2><?php if ( $banner_edit_id ) {echo _e('Muuda Banner');} else {echo _e('Lisa Uus Banner');} ?></h2>
			<?php  
			if ($message == 'fields_error') { 
			?>
			<div id="message" class="updated fade"><p><?php echo _e('Mitte kõik väljad täidetud'); ?></p></div>
			<?php } 
			if($groups) { ?>
			<form name="banners" method="post" action="" enctype="multipart/form-data">
				<input type="hidden" name="banner_username" value="<?php echo $this->userdata->display_name;?>" />
		    	<input type="hidden" name="banner_id" value="<?php echo $banner_edit_id;?>" />
			<?php
                if (function_exists ('wp_nonce_field') ) {
                    wp_nonce_field('banners_form');
                }
            	?>            	
				<table class="widefat" style="margin-top: 0.5em;">
					<thead valign="top">
						<tr>
							<?php
							if ( ! $banner_edit_id ) { 
							?>
							<th colspan="4"><?php echo _e('Loo banner, kõik väljad on kohustuslikud!'); ?></th>
							<?php
							} else { 
							?>
							<th colspan="4"><?php echo _e('Muuda banner, kõik väljad on kohustuslikud!'); ?></th>
							<?php 
							}
							?>
						</tr>
					</thead>
					<tbody>
						<tr>
							<th width="25%" scope="row"><?php echo _e('Nimetus'); ?>:</th>
							<td colspan="3">
								<?php
								if ( ! $banner_edit_id) {
								?>
								<input tabindex="1" name="banner_title" type="text" class="search-input" size="40" value="" />
								<?php 
								} else {
								?>
								<input tabindex="1" name="banner_title" type="text" class="search-input" size="40" value="<?php echo $edit_banner->title; ?>" />
								<?php }?>
							</td>
						</tr>
						<tr>
							<th width="25%" scope="row"><?php echo _e('Faili nimi'); ?>:</th>
							<td colspan="3">
								<?php
								if ( ! $banner_edit_id ) { 
								?>
								<input tabindex="2" name="banner_file" type="file" class="text urlfield" size="40" value="" />
								<?php
								} else { 
								?>
								<input tabindex="2" name="banner_file" type="text" class="search-input" size="40" value="<?php echo $edit_banner->banner; ?>"  readonly="readonly" />
								<?php } ?>
							</td>
						</tr>
						<tr>
							<th width="25%" scope="row"><?php echo _e('Asukoht')?>:</th>
							<td colspan="3">
								<select tabindex="3" name='banner_group' id='cat' class='postform'>
								<?php foreach($groups as $group) { ?>
						    		<option value="<?php echo $group->id; ?>" <?php if ( $banner_edit_id ) {if ( $group->id == $edit_banner->group ) echo 'selected';}?>> <?php echo $group->id; ?> - <?php echo $group->name; ?></option>
				    			<?php } ?>
				    			</select>
							</td>
						</tr>
						<tr>
							<th width="25%" scope="row"><?php echo _e('Link'); ?>:</th>
							<td colspan="3">
								<?php
								if ( ! $banner_edit_id ) { 
								?>
								<input tabindex="4" name="banner_link" type="text" class="search-input" size="40" value="" />
								<?php
								} else { 
								?>
								<input tabindex="4" name="banner_link" type="text" class="search-input" size="40" value="<?php echo $edit_banner->link; ?>" />
								<?php } ?>
							</td>
						</tr>
					</tbody>
					<thead>
						<tr>
							<th colspan="4"><?php echo _e('Täpsemad seaded (All olev on valikuline)'); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<th scope="row"><?php echo _e('Laius'); ?>:</th>
							<td>
								<?php
								if ( ! $banner_edit_id ) { 
								?>
								<input tabindex="5" name="banner_width" type="text" class="search-input" size="5" value="" />
								<?php
								} else { 
								?>
								<input tabindex="5" name="banner_width" type="text" class="search-input" size="5" value="<?php echo $edit_banner->width; ?>" />
								<?php } ?>
							</td>
							<th scope="row"><?php echo _e('Kõrgus'); ?>:</th>
							<td>
								<?php
								if ( ! $banner_edit_id ) { 
								?>
								<input tabindex="6" name="banner_height" type="text" class="search-input" size="5" value="" />
								<?php
								} else { 
								?>
								<input tabindex="6" name="banner_height" type="text" class="search-input" size="5" value="<?php echo $edit_banner->height; ?>" />
								<?php } ?>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php echo _e('Näita alates')?>:</th>
							<td>
								<input type="text" value="<?php echo $sday; ?>" maxlength="2" size="4" class="search-input" name="banner_sday" tabindex="7">
								/
								<select tabindex="8" name="banner_smonth">
									<option value="01" <?php if($smonth == "01") { echo 'selected'; } ?>>January</option>
									<option value="02" <?php if($smonth == "02") { echo 'selected'; } ?>>February</option>
									<option value="03" <?php if($smonth == "03") { echo 'selected'; } ?>>March</option>
									<option value="04" <?php if($smonth == "04") { echo 'selected'; } ?>>April</option>
									<option value="05" <?php if($smonth == "05") { echo 'selected'; } ?>>May</option>
									<option value="06" <?php if($smonth == "06") { echo 'selected'; } ?>>June</option>
									<option value="07" <?php if($smonth == "07") { echo 'selected'; } ?>>July</option>
									<option value="08" <?php if($smonth == "08") { echo 'selected'; } ?>>August</option>
									<option value="09" <?php if($smonth == "09") { echo 'selected'; } ?>>September</option>
									<option value="10" <?php if($smonth == "10") { echo 'selected'; } ?>>October</option>
									<option value="11" <?php if($smonth == "11") { echo 'selected'; } ?>>November</option>
									<option value="12" <?php if($smonth == "12") { echo 'selected'; } ?>>December</option>
								</select> /
								<input tabindex="9" name="banner_syear" class="search-input" type="text" size="4" maxlength="4" value="<?php echo $syear;?>" />
							</td>
							<th scope="row"><?php echo _e('Kuni'); ?>:</th>
							<td>
								<input type="text" value="<?php echo $eday; ?>" maxlength="2" size="4" class="search-input" name="banner_eday" tabindex="10">
								/
								<select tabindex="11" name="banner_emonth">
									<option value="01" <?php if($emonth == "01") { echo 'selected'; } ?>>January</option>
									<option value="02" <?php if($emonth == "02") { echo 'selected'; } ?>>February</option>
									<option value="03" <?php if($emonth == "03") { echo 'selected'; } ?>>March</option>
									<option value="04" <?php if($emonth == "04") { echo 'selected'; } ?>>April</option>
									<option value="05" <?php if($emonth == "05") { echo 'selected'; } ?>>May</option>
									<option value="06" <?php if($emonth == "06") { echo 'selected'; } ?>>June</option>
									<option value="07" <?php if($emonth == "07") { echo 'selected'; } ?>>July</option>
									<option value="08" <?php if($emonth == "08") { echo 'selected'; } ?>>August</option>
									<option value="09" <?php if($emonth == "09") { echo 'selected'; } ?>>September</option>
									<option value="10" <?php if($emonth == "10") { echo 'selected'; } ?>>October</option>
									<option value="11" <?php if($emonth == "11") { echo 'selected'; } ?>>November</option>
									<option value="12" <?php if($emonth == "12") { echo 'selected'; } ?>>December</option>
								</select> /
								<input tabindex="12" name="banner_eyear" class="search-input" type="text" size="4" maxlength="4" value="<?php echo $eyear;?>" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php echo _e('Aktiveeri banner')?>:</th>
							<td colspan="3">
								<select tabindex="13" name="banner_active">
									<?php if($edit_banner->active == "no") { ?>
									<option value="no">No! Do not show the banner anywhere.</option>
									<option value="yes">Yes! The banner will be shown at random intervals.</option>
									<?php } else { ?>
									<option value="yes">Yes! The banner will be shown at random intervals.</option>
									<option value="no">No! Do not show the banner anywhere.</option>
									<?php } ?>
								</select>
							</td>
						</tr>
					</tbody>
				</table>
				<input type="submit" name="rotator_banner_submit" class="button-primary" value="<?php echo _e('Rakenda'); ?>" />
				<a href="admin.php?page=dsrotator" class="button"><?php echo _e('Tühista'); ?></a>
			</form>
			<?php 
			} else { ?>
				<table class="form-table">
		    	<thead>
				<tr valign="top">
					<th>Error!</th>
				</tr>
				</thead>

				<tbody>
		      	<tr>
			        <td><?php echo _e('Te peaksite looma vähemalt üks rühm enne lisades bännereid!'); ?><a href="admin.php?page=dsrotator4">Add a group now</a>.</td>
		      	</tr>
		      	</tbody>
			</table>
		<?php } ?>
		</div> <?php
	}
	function insert_banner() { 
		$banner_id 			= $_POST['banner_id'];
		$author 			= $_POST['banner_username'];
		$title	 			= htmlspecialchars(trim($_POST['banner_title'], "\t\n "), ENT_QUOTES);
		$thetime 			= date('U');
		$active 			= $_POST['banner_active'];
		$group				= $_POST['banner_group'];
		$width				= $_POST['banner_width'];
		$height				= $_POST['banner_height'];
		$link				= htmlspecialchars(trim($_POST['banner_link'], "\t\n "), ENT_QUOTES);
		$sday 				= htmlspecialchars(trim($_POST['banner_sday'], "\t\n "), ENT_QUOTES);
		$smonth 			= htmlspecialchars(trim($_POST['banner_smonth'], "\t\n "), ENT_QUOTES);
		$syear 				= htmlspecialchars(trim($_POST['banner_syear'], "\t\n "), ENT_QUOTES);
		$eday 				= htmlspecialchars(trim($_POST['banner_eday'], "\t\n "), ENT_QUOTES);
		$emonth 			= htmlspecialchars(trim($_POST['banner_emonth'], "\t\n "), ENT_QUOTES);
		$eyear 				= htmlspecialchars(trim($_POST['banner_eyear'], "\t\n "), ENT_QUOTES);
		if ( ( strlen($banner_id) == 0 ) && isset( $_FILES['banner_file'] ) ) {
			if ( $_FILES['banner_file']['error'] > 0 ) {
				$this->rotator_return('file-upload-error');
				exit;
			} else {
				$rnd_str = $this->generateRandomStr();
				$banner_path = ABSPATH.$this->banners_folder;
				$banner_path = $banner_path . $rnd_str . basename( $_FILES['banner_file']['name']);
				$banner_filename = $rnd_str . basename( $_FILES['banner_file']['name']);
				if( ! move_uploaded_file($_FILES['banner_file']['tmp_name'], $banner_path)) {
					$this->rotator_return('move-file-error');
					exit;
				} 
			}		
		} else {
			$banner_filename			= $_POST['banner_file'];
		}
		if ( strlen($title) != 0 && strlen($banner_filename) != 0) {
			if(strlen($smonth) == 0) 	$smonth 	= date('m');
			if(strlen($sday) == 0) 		$sday 		= date('d');
			if(strlen($syear) == 0) 	$syear 		= date('Y');
			if(strlen($emonth) == 0) 	$emonth 	= $smonth;
			if(strlen($eday) == 0) 		$eday 		= $sday;
			if(strlen($eyear) == 0) 	$eyear 		= $syear+1;	
				
			$startdate 	= gmmktime($shour, $sminute, 0, $smonth, $sday, $syear);
			$enddate 	= gmmktime($ehour, $eminute, 0, $emonth, $eday, $eyear);
			
			if(strlen($banner_id) != 0) {
				$postquery = "UPDATE `" . $this->tables[0] . "`	SET `title` = '$title', `width` = '$width', `height` = '$height', `active` = '$active', `startshow` = '$startdate', `endshow` = '$enddate', `group` = '$group', `link` = '$link' WHERE `id` = '$banner_id'";
				$action = "update";
			} else {
				$postquery = "INSERT INTO `" . $this->tables[0] . "` (`title`, `thetime`, `width`, `height`, `author`, `active`, `startshow`, `endshow`, `group`, `banner`, `link`) VALUES ('$title', '$thetime', '$width', '$height', '$author', '$active', '$startdate', '$enddate', '$group', '$banner_filename', '$link')";
				$action = "new";
			}
			if($this->db->query( $this->db->prepare( $postquery) ) !== FALSE) {
				$this->rotator_return( $action, array($banner_id) );
				exit;
			} else {
				die('[MySQL error] '.mysql_error());
			}
		} else {
			$this->rotator_return('banner_field_error');
			exit;
		}
	}
	function banner_active($id, $what) {
	
		if($id > 0) {
			if($what == 'deactivate') {
				$this->db->query($this->db->prepare("UPDATE `" . $this->tables[0] . "` SET `active` = 'no' WHERE `id` = '$id'"));
			}
			if ($what == 'activate') {
				$this->db->query($this->db->prepare("UPDATE `" . $this->tables[0] . "` SET `active` = 'yes' WHERE `id` = '$id'"));
			}
		}
	}
	function banner_renew($id, $howlong = 31536000) {

		if($id > 0) {
			$this->db->query($this->db->prepare("UPDATE `" . $this->tables[0] . "` SET `endshow` = `endshow` + '$howlong' WHERE `id` = '$id'"));
		}
	}
	function banner_move($id, $group) {
		if($id > 0) {
			$this->db->query($this->db->prepare("UPDATE `" . $this->tables[0] . "` SET `group` = '$group' WHERE `id` = '$id'"));
		}
	}
	function expired_banners() {
		$now = current_time('timestamp');
		$count_exp = 0;
		
		$expired_banners = $this->db->get_results($this->db->prepare("SELECT `id` FROM `" . $this->tables[0] . "` WHERE `active` = 'yes' AND `endshow` <= $now"));
		if ($expired_banners) {
			foreach ($expired_banners as $banner) {
				$this->banner_active($banner->id, 'deactivate');
				$count_exp += 1;
			}
		}
		
		
		if($count_exp > 0) {
			echo '<div class="error"><p>' . $count_exp . ' banneri(te)l avaldamise kuupäev on. <a href="admin.php?page=dsrotator">Vaata siin</a>!</p></div>';
		}
	}
}
?>
