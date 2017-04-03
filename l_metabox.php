<?php
/*
Plugin Name: Custom Metabox
Plugin URI: http://www.localhostph.com/
Description: Custom Metabox v 1.3 plugin enbales you to create metabox and metabox fields from the dashboard.
Author: Norbert Feria
Version: 1.3.2
Author URI: ttp://www.localhostph.com/

*/

if(!defined('ABSPATH')) exit;

include('l_metabox_list_table.php');
include('l_metabox_fieldlist_table.php');

$lcmb = new lc_metabox(__FILE__);

class lc_metabox {
	
	var $tblname = 'custom_metabox';
	var $fields_tblname = 'custom_metabox_fields';
	var $tblid = 'unik';
	var $fldtblid = 'mb_unik';
	var $metainherit = FALSE;
	
	public function __construct($file) { 
		register_activation_hook( $file, array($this,'_activate') );
		register_deactivation_hook( $file,array($this,'_deactivate') );
		
		add_action('admin_menu', array($this,'_menu_item') );
		add_action('admin_init', array($this,'lcmb_data_meta') );
		
		add_action( 'save_post', array($this,'save_fields'),10 ); 
		
		add_action( 'admin_enqueue_scripts', array($this,'_load_admin_enqueue') );		
	}
	
	function _load_admin_enqueue()
	{
	    wp_enqueue_script('jquery-ui-datepicker');
		wp_enqueue_style('jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
	}

	function get_tblname($main = TRUE){
		global $wpdb;
		if ($main){
			$table_name = $wpdb->prefix . $this->tblname;
		}else{
			$table_name = $wpdb->prefix . $this->fields_tblname;
		}
		return $table_name;
	}#function
	
	function _activate(){
		if(!current_user_can('activate_plugins' )) return;
		   
		global $wpdb;
		$table_name = $this->get_tblname();
		if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {		
			$sql = "CREATE TABLE `".$table_name."` (
			  `unik` bigint(10) NOT NULL AUTO_INCREMENT,
			  `mb_name` varchar(40) CHARACTER SET utf8 NOT NULL,
			  `mb_id` varchar(40) CHARACTER SET utf8 NOT NULL,
			  `mb_type` varchar(40) CHARACTER SET utf8 NOT NULL,
			  `mb_context` varchar(40) CHARACTER SET utf8 NOT NULL,
			  `mb_priority` varchar(40) CHARACTER SET utf8 NOT NULL,
			  `enabled` INT( 1 ) NOT NULL DEFAULT '0',
			  `mb_description` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
			  PRIMARY KEY (`unik`)
			)";
		}#if no table
		
		$fld_table_name = $this->get_tblname(FALSE);
		if($wpdb->get_var("SHOW TABLES LIKE '$fld_table_name'") != $fld_table_name) {		
			$sql_fld = "CREATE TABLE `".$fld_table_name."` (
			  `unik` bigint(10) NOT NULL AUTO_INCREMENT,
			  `fld_label` varchar(40) CHARACTER SET utf8 NOT NULL,
			  `fld_name` varchar(40) CHARACTER SET utf8 NOT NULL,
			  `fld_inputtype` varchar(40) CHARACTER SET utf8 NOT NULL,
			  `mb_unik` bigint(10) NOT NULL,
			  `enabled` INT( 1 ) NOT NULL DEFAULT '0',
			  `fld_description` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
			  `post_type` VARCHAR( 40 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
			  `dropdownoptions` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
			  PRIMARY KEY (`unik`)
			)";
		}#if no table
		#echo $sql_fld;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		dbDelta( $sql_fld );

	}#function activate
		
	function _deactivate() {
		# data will be cleared on uninstall instead of deactivate
	}#function deactivate
	
	function _menu_item(){
		add_submenu_page("edit.php", "", "Custom Metabox", "manage_options", "manage-metabox", array($this,'_admin_page'));
	}#function

	function _admin_page(){
		echo  '<div class="wrap"><h2>Manage Custom Metabox
		<a class="add-new-h2" href="?page='.$_REQUEST['page'].'">View Custom metabox List</a>
		<a class="add-new-h2" href="?page='. $_REQUEST['page'].'&action=lcmb_add">Add New Custom Metabox</a>
		</h2>
		<BR>';
		if(isset($_GET["action"]) && !isset($_GET["action2"])){
			$this->{$_GET["action"]}();
		}else{
			$this->lcmb_metabox_list();
		}
		echo '</div>';
	}#function
	
	function lcmb_savemetabox(){
		if(isset($_POST['addsetting_nonce'])){
			if(wp_verify_nonce($_POST['addsetting_nonce'],'addsetting_nonce')){
				global $wpdb;
				$table_name = $this->get_tblname();
				$sql = "INSERT INTO ".$table_name." 
				(mb_name,mb_id,mb_type,mb_context,mb_priority,mb_description) VALUES 
				('".$_POST['mb_name']."','".$_POST['mb_id']."','".$_POST['mb_type']."','".$_POST['mb_context']."','".$_POST['mb_priority']."',
				'".$_POST['mb_description']."');";
				$wpdb->query($sql);
				$sdstr = "Custom Metabox successfully saved.";
				echo $sdstr;
			}
		}
		$this->lcmb_metabox_list();
	}#save sidebar
	
	function lcmb_add(){
		$sdstr = '';
		$sdstr .= '<a class="add-new-h2" href="?page='.$_REQUEST['page'].'">View Custom Metabox List</a>';
		$sdstr .= '<BR><BR><div class="form-wrap">';
		$sdstr .= '<form id="contact-settings" method="POST" action="?page='.$_REQUEST['page'].'&action=lcmb_savemetabox">';
		
		$sdstr .= '<div class="form-field">
		<label for="mb_name">Name :</label>
		<input type="text" name="mb_name" id="mb_name" value="" size=40>
		<p>Name of your metabox</p>
		</div>
		';
	
		$sdstr .='
		<div class="form-field">
		<label for="mb_description">Description</label>
		<textarea id="mb_description" class="widefat" name="mb_description"></textarea>
		<p>Enter the description for new custom metabox. </p>
		</div>
		';
		
		$sdstr .='
		<div class="form-field">
		<label for="mb_type">Type</label>
		<select id="mb_type" name="mb_type">';
		$post_types=get_post_types('', 'objects');
		 foreach ($post_types as $post_type):
        	$sdstr .='<option value="'.esc_attr($post_type->name).'">'.esc_html($post_type->label).'</option>';
        endforeach;
		$sdstr .='</select>		
		</div>
		';
		
		$sdstr .='
		<div class="form-field">
		<label for="mb_context">Context</label>
		<select id="mb_context" name="mb_context">
		<option value="normal">Normal</option>
		<option value="advanced">Advanced</option>
		<option value="side">Side</option>
		</select>		
		</div>
		';
		
		$sdstr .='
		<div class="form-field">
		<label for="mb_priority">Priority</label>
		<select id="mb_priority" name="mb_priority">
		<option value="high">High</option>
		<option value="default">default</option>
		<option value="low">Low</option>
		</select>		
		</div>
		';
			
		$sdstr .= '<input type="hidden" name="mb_id" id="mb_id" value="">';	
		$sdstr .= '<input type="hidden" name="addsetting_nonce" id="addsetting_nonce" value="'.wp_create_nonce('addsetting_nonce').'">';	
		$sdstr .= '<BR><input class="button button-primary" type="submit" value="Save">';
		$sdstr .= '</form></div>';
		$sdstr .= '<BR><BR>';
		$sdstr .= '<a class="add-new-h2" href="?page='.$_REQUEST['page'].'">View Custom Metabox List</a>';
		$sdstr .= $this->lcmb_form_javascript();
		echo $sdstr;
	}#function lcmb_add
	
	function lcmb_form_javascript(){
		$sdstr = '';
		$sdstr .='
			<script>
				jQuery(document).ready(function() {

					jQuery( "#mb_name" ).change(function() {
						var Text = jQuery(this).val();
						Text = Text.replace(/[^a-zA-Z 0-9-]+/g,\'\');
						Text = Text.toLowerCase();
						Text = Text.replace(/ /g, \'\');
						jQuery("#mb_id").val(Text+"_meta");
					});
				});
			</script>
		';
		return $sdstr;
	}
	
	function lcmb_updatemetabox(){
		if(isset($_POST['updatesetting_nonce'])){
			if(wp_verify_nonce($_POST['updatesetting_nonce'],'updatesetting_nonce')){
				global $wpdb;
				$table_name = $this->get_tblname();
				$sql = "UPDATE ".$table_name." SET mb_name = '".$_POST["mb_name"]."', 
				mb_id = '".$_POST["mb_id"]."',
				mb_type = '".$_POST["mb_type"]."',
				mb_context = '".$_POST["mb_context"]."',
				mb_priority = '".$_POST["mb_priority"]."',
				mb_description = '".$_POST["mb_description"]."',
				enabled  = '".$_POST["enabled"]."'
				WHERE ".$this->tblid." = ".$_POST[$this->tblid]."";
				$wpdb->query($sql);
				$sdstr = "Custom Metabox successfully updated.";
				echo $sdstr;
			}
		}
		$this->lcmb_metabox_list();
	}#update sidebar
	
	
	function lcmb_statusupdate(){
		global $wpdb;
		$table_name = $this->get_tblname();
		if(isset($_GET['stype'])){
			$new_status = $_GET['stype'] ? 0 : 1;
			$sql = "UPDATE ".$table_name." SET enabled = ".$new_status." WHERE ".$this->tblid." = ".$_GET["id"]."";
			$wpdb->query($sql);
			$sdstr = "Custom Metabox type status updated.";
			echo $sdstr;
		}
		$this->lcmb_metabox_list();
	}#function lcmb_statusupdate
	
	function lcmb_get_metabox($unik){
		global $wpdb;
		
		$table_name = $this->get_tblname();
		$sql = "SELECT * FROM ".$table_name." 
			WHERE ".$table_name.".".$this->tblid." = ".$unik;
		$metabox_row = $wpdb->get_row($sql);
		return $metabox_row;
	}
	
	function lcmb_edit(){
		
		$metabox_row = $this->lcmb_get_metabox($_GET['id']);
		$sdstr = '';
		$sdstr .= '<a class="add-new-h2" href="?page='.$_REQUEST['page'].'">View Custom metabox List</a>';
		$sdstr .= '<BR><BR><div class="form-wrap">';
		$sdstr .= '<form id="contact-settings" method="POST" action="?page='.$_REQUEST['page'].'&action=lcmb_updatemetabox">';
		
		$sdstr .= '<div class="form-field">
		<label for="mb_name">Name :</label>
		<input type="text" name="mb_name" id="mb_name" value="'.$metabox_row->mb_name.'" size=40>
		<p>Name of your metabox</p>
		</div>
		';
	
		$sdstr .='
		<div class="form-field">
		<label for="mb_description">Description</label>
		<textarea id="mb_description" class="widefat" name="mb_description">'.$metabox_row->mb_description.'</textarea>
		<p>Enter the description for new custom metabox. </p>
		</div>
		';
		
		$sdstr .='
		<div class="form-field">
		<label for="mb_type">Type</label>
		<select id="mb_type" name="mb_type">';
		$post_types=get_post_types('', 'objects');
		 foreach ($post_types as $post_type):
			 $selected = ($metabox_row->mb_type == $post_type->name) ? 'selected' : '' ;
        	$sdstr .='<option '.$selected.' value="'.esc_attr($post_type->name).'">'.esc_html($post_type->label).'</option>';
        endforeach;
		$sdstr .='</select>		
		</div>
		';
		
		$sdstr .='
		<div class="form-field">
		<label for="mb_context">Context</label>
		<select id="mb_context" name="mb_context">';
		$selected = ($metabox_row->mb_context == 'normal') ? 'selected' : '' ;
		$sdstr .='<option '.$selected.' value="normal">Normal</option>';
		$selected = ($metabox_row->mb_context == 'advanced') ? 'selected' : '' ;
		$sdstr .='<option '.$selected.' value="advanced">Advanced</option>';
		$selected = ($metabox_row->mb_context == 'side') ? 'selected' : '' ;
		$sdstr .='<option '.$selected.' value="side">Side</option>';
		$sdstr .='</select>	
		</div>
		';
		
		$sdstr .='
		<div class="form-field">
		<label for="mb_priority">Priority</label>
		<select id="mb_priority" name="mb_priority">';
		$selected = ($metabox_row->mb_priority == 'high') ? 'selected' : '' ;
		$sdstr .='<option '.$selected.' value="high">High</option>';
		$selected = ($metabox_row->mb_priority == 'default') ? 'selected' : '' ;
		$sdstr .='<option '.$selected.' value="default">default</option>';
		$selected = ($metabox_row->mb_priority == 'low') ? 'selected' : '' ;
		$sdstr .='<option '.$selected.' value="low">Low</option>';
		$sdstr .='</select>		
		</div>
		';
		
		$sdstr .='
		<div class="form-field">
		<label for="enabled">Enable</label>
		<select name="enabled" id="enabled" class="widefat">';
		$selected = $metabox_row->enabled ? 'selected' : '';
		$sdstr .= '<option value=0>No</option>';
		$sdstr .= '<option value=1 '.$selected.'>Yes</option>';
		$sdstr .= '</select>
		<p>disabled metaboxes will not load on your edit forms but will maintain the fields associated with it along with its corresponding data.</p>
		</div>
		';
		
		$sdstr .= '<input type="hidden" name="mb_id" id="mb_id" value="'.$metabox_row->mb_id.'">';	
		
		$sdstr .= '<input type="hidden" name="updatesetting_nonce" id="updatesetting_nonce" value="'.wp_create_nonce('updatesetting_nonce').'">';	
		$sdstr .= '<input type="hidden" name="'.$this->tblid.'" id="'.$this->tblid.'" value="'.$metabox_row->unik.'">';	
		$sdstr .= '<BR><input class="button button-primary" type="submit" value="Save">';
		$sdstr .= '</form></div>';
		$sdstr .= '<BR><BR>';
		$sdstr .= '<a class="add-new-h2" href="?page='.$_REQUEST['page'].'">View Custom Metabox List</a>';
		$sdstr .= $this->lcmb_form_javascript();
		echo $sdstr;
	}#edit sidebar
	
	function lcmb_delete_confirmed(){
		global $wpdb;
		$table_name = $this->get_tblname();
		
		#$sql = "SELECT * FROM ".$table_name." WHERE ".$this->tblid."=".$_GET["id"];
		#$custom_metabox = $wpdb->get_row($sql);

		#$this->lcmb_delete_cpts($custom_posttype->cpt_slug);
		#$this->lcmb_delete_taxonomy($custom_posttype->taxonomy_urlprefix);
			
		$wpdb->query( $wpdb->prepare( 
							"DELETE FROM ".$table_name."
							 WHERE unik = %d",
								$_GET["id"]));
		$sdstr = "Metabox successfully deleted.";
		echo $sdstr;
		$this->lcmb_metabox_list();
	}#function
	
	function lcmb_delete(){
		$sdstr = '';
		$sdstr .=  'This action will delete your metabox,<BR>
		  all fields on associated with the box,<BR>
		  and all values entered and saved into those fields, <BR><BR>';
		$sdstr .=  'Are you sure you want to continue deleting the Metabox?<BR><BR>';
		
		$sdstr .= '<a class="add-new-h2" href="?page='.$_REQUEST['page'].'&action=lcmb_delete_confirmed&id='.$_GET['id'].'">Yes, Delete my metabox and all its data</a><BR><BR>';
		$sdstr .= '<a class="add-new-h2" href="?page='.$_REQUEST['page'].'">Cancel and Go back to List View</a>';
		echo $sdstr;
		
	}#function lcmb_delete
	
	function lcmb_metabox_list(){
		$table = new lcmb_metabox_list_table($this->tblname,$this->tblid);
		$table->prepare_items();
		
		echo '<form id="metabox-table" method="GET">
			<input type="hidden" name="page" value="'.$_REQUEST['page'].'"/>';
			
		$table->display();
		
		echo '</form>';
		
	}#function

	function lcmb_data_meta() {
		global $wpdb;
		
		$table_name = $this->get_tblname();
		$sql = "SELECT * FROM ".$table_name." 
			WHERE ".$table_name.".enabled = 1";
		$custom_metaboxes = $wpdb->get_results($sql);
		foreach($custom_metaboxes as $custom_metabox){
			add_meta_box($custom_metabox->mb_id,$custom_metabox->mb_name,array($this,'lcmb_data_metabox'),$custom_metabox->mb_type,$custom_metabox->mb_context,$custom_metabox->mb_priority,array( 'unik' => $custom_metabox->unik));	
		}

	} #lcmb_data_meta
	
	function save_fields($post_id)
	{
		if( !isset( $_POST['meta_box_nonce'] ) || !wp_verify_nonce( $_POST['meta_box_nonce'], 'custom_meta_box_nonce' ) )
		{
			return;
		}
	   	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
	   	}

		if ( 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) )
			{
				return $post_id; 
			
		  	} else {
				if ( ! current_user_can( 'edit_post', $post_id ) )
					return $post_id;
			}
		}

		#$boxid = $_POST['boxid'];
		foreach($_POST['boxid'] as $boxid)
		{
			$fields = $this->lcmb_get_boxfields($boxid);
			  	foreach($fields as $field)
			  	{
				  	$fld_value = $_POST[$field->fld_name];	
				  	update_post_meta( $post_id, $field->fld_name, $fld_value );
				}
		}
	}
	
	function lcmb_data_metabox($post,$args){
		$sdstr = '';
		wp_nonce_field( 'custom_meta_box_nonce', 'meta_box_nonce' );
		$fields = $this->lcmb_get_boxfields($args['args']['unik']);
		$sdstr .= $this->hidden_box_details_field($args['args']['unik']);
		foreach($fields as $field){
			if($field->fld_inputtype == "text"){
				$sdstr .= $this->text_field($post->ID, $field->fld_name, $field->fld_label);
			}
			if($field->fld_inputtype == "textarea"){
				$sdstr .= $this->textarea_field($post->ID, $field->fld_name, $field->fld_label);		
			}
			if($field->fld_inputtype == "dropdown"){
				$sdstr .= $this->dropdown($post->ID, $field->fld_name, $field->fld_label,$field->dropdownoptions);		
			}
			if($field->fld_inputtype == "dropdownyn"){
				$sdstr .= $this->dropdownyn($post->ID, $field->fld_name, $field->fld_label);		
			}
			if($field->fld_inputtype == "DateField"){
				$sdstr .= $this->date_field($post->ID, $field->fld_name, $field->fld_label);
			}

		}#foreach fields
		#$sdstr .= file_field($post->ID, 'MentorCompanyLogo', "Company Logo");
		echo $sdstr;
	}#funciton
	
	function textarea_field($postid, $field, $label){
		$sdstr = '';
		$meta_value = get_post_meta( $postid, $field, true );
		$sdstr .= '<p class="description">';
		$sdstr .= $label;
		$sdstr .= '</p>';
		$sdstr .= '<textarea class="widefat" id="'.$field.'" name="'.$field.'">'.$meta_value.'</textarea>';	
		return $sdstr;
	}

	function dropdownyn($postid, $field, $label){
		$sdstr = '';
		$meta_value = get_post_meta( $postid, $field, true );
		$sdstr .= '<p class="description">';
		$sdstr .= $label;
		$sdstr .= '</p>';
		$sdstr .= '<select id="'.$field.'" name="'.$field.'">';
		$selected = ($meta_value == 1) ? 'selected' : '' ;
		$sdstr .='<option '.$selected.' value="1">Yes</option>';
		$selected = ($meta_value == 0) ? 'selected' : '' ;
		$sdstr .='<option '.$selected.' value="0">No</option>';
		$sdstr .='</select>';

		return $sdstr;
	}

	function dropdown($postid, $field, $label,$options){
		$sdstr = '';
		$meta_value = get_post_meta( $postid, $field, true );
		$optionvalues = explode(",",$options);
		$sdstr .= '<p class="description">';
		$sdstr .= $label;
		$sdstr .= '</p>';
		$sdstr .= '<select id="'.$field.'" name="'.$field.'">';
		foreach($optionvalues as $value)
		{
			$selected = ($meta_value == $value) ? 'selected' : '' ;
			$sdstr .='<option '.$selected.' value="'.$value.'">'.$value.'</option>';
		}
		$sdstr .='</select>';

		return $sdstr;
	}
	
	function hidden_box_details_field($unik)
	{
		$sdstr = '';
		#$meta_value = get_post_meta( $postid, $field, true );
		$sdstr .= '<input type="hidden" id="boxid" name="boxid[]"  value="'.$unik.'" size="50">';	
		return $sdstr;
	}
	
	function date_field($postid, $field, $label)
	{
		$sdstr = '';
		$meta_value = get_post_meta( $postid, $field, true );
		$sdstr .= '<p class="description">';
		$sdstr .= $label;
		$sdstr .= '</p>';
		$sdstr .= '<input type="date" id="'.$field.'" name="'.$field.'" value="'.$meta_value.'" class="datepicker" />';
		$sdstr .= '<script>
					jQuery(document).ready(function(){
						jQuery(".datepicker").datepicker({ dateFormat: "yy-mm-dd" });	
					});
					</script>';
		return $sdstr;
	}

	function text_field($postid, $field, $label)
	{
		$sdstr = '';
		$meta_value = get_post_meta( $postid, $field, true );
		$sdstr .= '<p class="description">';
		$sdstr .= $label;
		$sdstr .= '</p>';
		$sdstr .= '<input type="text" id="'.$field.'" name="'.$field.'"  value="'.$meta_value.'" size="50">';	
		return $sdstr;
	}
	
		
	function lcmb_get_boxfields($boxid)
	{
		global $wpdb;
		
		$table_name = $this->get_tblname(FALSE);
		$sql = "SELECT * FROM ".$table_name." 
			WHERE ".$table_name.".mb_unik = ".$boxid." and ".$table_name.".enabled=1";
		$fields = $wpdb->get_results($sql);
		return $fields;
	}
	
		

	function lcmb_add_field()
	{
		$metabox_row = $this->lcmb_get_metabox($_GET['parentid']);
		
		echo  '<div class="wrap"><h3>Add New '.$metabox_row->mb_name.' Metabox Field<BR><BR>
		<a class="add-new-h2" href="?page='.$_REQUEST['page'].'&action=lcmb_managefields&parentid='.$_GET['parentid'].'">View '.$metabox_row->mb_name.' Field List</a>
		</h3>';
		
		$sdstr .= '<BR><div class="form-wrap">';
		$sdstr .= '<form id="contact-settings" method="POST" action="?page='.$_REQUEST['page'].'&action=lcmb_savenew_field&parentid='.$_GET['parentid'].'">';
		
		$sdstr .= '<div class="form-field">
		<label for="fld_label">Label :</label>
		<input type="text" name="fld_label" id="fld_label" value="" size=40>
		<p>Label for your field</p>
		</div>
		';
		
		$sdstr .= '<div class="form-field">
		<label for="fld_name">Fieldname :</label>
		<input type="text" name="fld_name" id="fld_name" value="" size=40>
		<p>Alpa characters only.</p>
		</div>
		';
	
		$sdstr .='
		<div class="form-field">
		<label for="fld_description">Description</label>
		<textarea id="fld_description" class="widefat" name="fld_description"></textarea>
		<p>Enter the description for new custom field. </p>
		</div>
		';
				
		$sdstr .='
		<div class="form-field">
		<label for="fld_inputtype">Type</label>
		<select id="fld_inputtype" name="fld_inputtype">
		<option value="text">Text</option>
		<option value="textarea">TextArea</option>
		<option value="dropdown">Dropdown</option>
		<option value="dropdownyn">Yes/no Dropdown</option>
		<option value="DateField">Date Field</option>
		</select>		
		</div>
		';
		

		$sdstr .='
		<div class="form-field" id="dropdownextra" style="display:none;">
		<label for="dropdownoptions">Dropdown Options</label>
		<input type="text" name="dropdownoptions" id="dropdownoptions" value="" size=40>
		<p>used for dropdown field (separate options with comma[,])</p>
		</div>
		';
			
		$sdstr .= '<input type="hidden" name="mb_unik" id="mb_unik" value="'.$_GET['parentid'].'">';	
		$sdstr .= '<input type="hidden" name="addsetting_nonce" id="addsetting_nonce" value="'.wp_create_nonce('addsetting_nonce').'">';	
		$sdstr .= '<BR><input class="button button-primary" type="submit" value="Save">';
		$sdstr .= '</form></div>';
		$sdstr .= '<BR><BR>';
		$sdstr .= $this->lcmb_fieldform_javascript();
		$sdstr .= '</div>';
		echo $sdstr;
	}
	
	function lcmb_fieldform_javascript(){
		$sdstr = '';
		$sdstr .= '<script>
				jQuery(document).ready(function() {

					if(jQuery("#fld_inputtype").val() == "dropdown")
					{
						jQuery("#dropdownextra").show();
					}
					
					jQuery("#fld_name").bind("keypress", function (event) {
						var keyCode = event.keyCode || event.which
						if (keyCode == 8 || (keyCode >= 35 && keyCode <= 40) || keyCode==46) 
						{ 
							return;
						}
						var regex = new RegExp("^[a-zA-Z]+$");
						var key = String.fromCharCode(!event.charCode ? event.which : event.charCode);
						if (!regex.test(key)) 
						{
							event.preventDefault();
							return false;
						}
					});
					
					jQuery("#fld_inputtype").change(function() {
					  if(jQuery("#fld_inputtype").val() == "dropdown"){
					    jQuery("#dropdownextra").show();
					  }

					});
				});
			</script>';
		return $sdstr;
	}#function
	
	function lcmb_get_metafield($unik){
		global $wpdb;
		
		$table_name = $this->get_tblname(FALSE);
		$sql = "SELECT * FROM ".$table_name." 
			WHERE ".$table_name.".".$this->tblid." = ".$unik;
		#echo $sql;
		$metabox_row = $wpdb->get_row($sql);
		return $metabox_row;
	}
	
	function lcmb_edit_field(){
		global $wpdb;
		#$fld_table_name = $this->get_tblname(FALSE);
		
		$metabox_row = $this->lcmb_get_metabox($_GET['parentid']);
		echo  '<div class="wrap"><h3>Edit '.$metabox_row->mb_name.' Metabox Field<BR><BR>
		<a class="add-new-h2" href="?page='.$_REQUEST['page'].'&action=lcmb_managefields&parentid='.$_GET['parentid'].'">View '.$metabox_row->mb_name.' Field List</a>
		</h3>';
		
		$fieldrow = $this->lcmb_get_metafield($_GET['id']);
		
		$sdstr .= '<BR><div class="form-wrap">';
		$sdstr .= '<form id="contact-settings" method="POST" action="?page='.$_REQUEST['page'].'&action=lcmb_update_field&parentid='.$_GET['parentid'].'">';
		
		$sdstr .= '<div class="form-field">
		<label for="fld_label">Label :</label>
		<input type="text" name="fld_label" id="fld_label" value="'.$fieldrow->fld_label.'" size=40>
		<p>Label for your field</p>
		</div>
		';
		
		$sdstr .= '<div class="form-field">
		<label for="fld_name">Fieldname :</label>
		<input type="text" name="fld_name" id="fld_name" value="'.$fieldrow->fld_name.'" size=40>
		<p>Alpa characters only.</p>
		</div>
		';
	
		$sdstr .='
		<div class="form-field">
		<label for="fld_description">Description</label>
		<textarea id="fld_description" class="widefat" name="fld_description">'.$fieldrow->fld_description.'</textarea>
		<p>Enter the description for new custom field. </p>
		</div>
		';
				
		$sdstr .='
		<div class="form-field">
		<label for="fld_inputtype">Type</label>
		<select id="fld_inputtype" name="fld_inputtype">';
		$selected = ($fieldrow->fld_inputtype == 'text') ? 'selected' : '' ;
		$sdstr .='<option '.$selected.' value="text">Text</option>';
		$selected = ($fieldrow->fld_inputtype == 'textarea') ? 'selected' : '' ;
		$sdstr .='<option '.$selected.' value="textarea">TextArea</option>';
		$selected = ($fieldrow->fld_inputtype == 'dropdown') ? 'selected' : '' ;
		$sdstr .='<option '.$selected.' value="dropdown">Dropdown</option>';	
		$selected = ($fieldrow->fld_inputtype == 'dropdownyn') ? 'selected' : '' ;
		$sdstr .='<option '.$selected.' value="dropdownyn">yes/no Dropdown</option>';
		$selected = ($fieldrow->fld_inputtype == 'DateField') ? 'selected' : '' ;
		$sdstr .='<option '.$selected.' value="DateField">Date Field</option>';
		$sdstr .='</select>		
		</div>';

		$sdstr .='
		<div class="form-field" id="dropdownextra" style="display:none;">
		<label for="dropdownoptions">Dropdown Options</label>
		<input type="text" name="dropdownoptions" id="dropdownoptions" value="'.$fieldrow->dropdownoptions.'" size=40>
		<p>used for dropdown field (separate options with comma[,])</p>
		</div>
		';
		
		$sdstr .= '<input type="hidden" name="mb_unik" id="mb_unik" value="'.$fieldrow->mb_unik.'">';	
		$sdstr .= '<input type="hidden" name="unik" id="unik" value="'.$fieldrow->unik.'">';	
		$sdstr .= '<input type="hidden" name="updatefield_nonce" id="updatefield_nonce" value="'.wp_create_nonce('updatefield_nonce').'">';	
		$sdstr .= '<BR><input class="button button-primary" type="submit" value="Update">';
		$sdstr .= '</form></div>';
		$sdstr .= '<BR><BR>';
		$sdstr .= $this->lcmb_fieldform_javascript();
		echo $sdstr;
	}#function
	
	function lcmb_savenew_field(){
		$metabox_row = $this->lcmb_get_metabox($_GET['parentid']);
		
		echo  '<div class="wrap"><h3>'.$metabox_row->mb_name.' Metabox Fields<BR><BR>
		<a class="add-new-h2" href="?page='.$_REQUEST['page'].'">Back View Metaboxes List</a>
		<a class="add-new-h2" href="?page='. $_REQUEST['page'].'&action=lcmb_add_field&parentid='.$metabox_row->unik.'">Add New Field</a>
		</h3>
		<BR>';
		
		if(isset($_POST['addsetting_nonce'])){
			if(wp_verify_nonce($_POST['addsetting_nonce'],'addsetting_nonce')){
				global $wpdb;
				$table_name = $this->get_tblname(FALSE);
				$sql = "INSERT INTO ".$table_name." 
				(fld_label,fld_name,fld_inputtype,fld_description,mb_unik,post_type,dropdownoptions) VALUES 
				('".$_POST['fld_label']."','".$_POST['fld_name']."','".$_POST['fld_inputtype']."','".$_POST['fld_description']."',".$_POST['mb_unik'].",'".$_POST['fld_post_type']."','".$_POST['dropdownoptions']."');";
				$wpdb->query($sql);
				$sdstr = "Metabox field successfully saved.<BR><BR>";
				echo $sdstr;
			}
		}
		$this->lcmb_metabox_field_list();
		
		echo '</div>';
	}#function
	
	function lcmb_update_field(){
		$metabox_row = $this->lcmb_get_metabox($_GET['parentid']);
		
		echo  '<div class="wrap"><h3>'.$metabox_row->mb_name.' Metabox Fields<BR><BR>
		<a class="add-new-h2" href="?page='.$_REQUEST['page'].'">Back View Metaboxes List</a>
		<a class="add-new-h2" href="?page='. $_REQUEST['page'].'&action=lcmb_add_field&parentid='.$metabox_row->unik.'">Add New Field</a>
		</h3>
		<BR>';
		
		if(isset($_POST['updatefield_nonce'])){
			if(wp_verify_nonce($_POST['updatefield_nonce'],'updatefield_nonce')){
				global $wpdb;
				
				$fld_table_name = $this->get_tblname(FALSE);
				$sql = "UPDATE ".$fld_table_name." SET fld_label = '".$_POST["fld_label"]."', 
				fld_name = '".$_POST["fld_name"]."',
				fld_inputtype = '".$_POST["fld_inputtype"]."',
				fld_description = '".$_POST["fld_description"]."',
				mb_unik = ".$_POST["mb_unik"].",
				post_type = '".$_POST["fld_post_type"]."',
				dropdownoptions = '".$_POST["dropdownoptions"]."' 
				WHERE ".$this->tblid." = ".$_POST[$this->tblid].";";
				$wpdb->show_errors();
				$wpdb->query($sql);
				$sdstr = "|Custom field successfully updated.<BR><BR>";
				echo $sdstr;
			}
		}
		
		$this->lcmb_metabox_field_list();
		
		echo '</div>';
	}#function
	
	function lcmb_delete_field(){
		$sdstr .=  'This action will delete field,<BR>
		  all values saved into the field.<BR><BR>';
		$sdstr .=  'Are you sure you want to continue deleting the Field?<BR><BR>';
		
		$sdstr .= '<a class="add-new-h2" href="?page='.$_REQUEST['page'].'&action=lcmb_delete_field_confirmed&id='.$_GET['id'].'&parentid='.$_GET['parentid'].'">Yes, Delete my field and all its data</a><BR><BR>';
		$sdstr .= '<a class="add-new-h2" href="?page='.$_REQUEST['page'].'&action=lcmb_managefields&parentid='.$_GET['parentid'].'">Cancel and Go back to List View</a>';
		echo $sdstr;
	}#function
	
	function lcmb_delete_field_confirmed(){
		$metabox_row = $this->lcmb_get_metabox($_GET['parentid']);
		
		echo  '<div class="wrap"><h3>'.$metabox_row->mb_name.' Metabox Fields<BR><BR>
		<a class="add-new-h2" href="?page='.$_REQUEST['page'].'">Back View Metaboxes List</a>
		<a class="add-new-h2" href="?page='. $_REQUEST['page'].'&action=lcmb_add_field&parentid='.$metabox_row->unik.'">Add New Field</a>
		</h3>
		<BR>';
		
		global $wpdb;
		$table_name = $this->get_tblname(FALSE);
		
		$fieldrow = $this->lcmb_get_metafield($_GET['id']);
		$test = $wpdb->get_results( "SELECT meta_key FROM wp_postmeta where meta_key='".$fieldrow->fld_name."'" );
		if (($wpdb->num_rows)>0) {
				$wpdb->query( $wpdb->prepare( 
							"DELETE FROM wp_postmeta
							 WHERE meta_key = %d",
								$fieldrow->fld_name));
		}

		$wpdb->query( $wpdb->prepare( 
							"DELETE FROM ".$table_name."
							 WHERE unik = %d",
								$_GET["id"]));
		$sdstr = "Field successfully deleted.<BR><BR>";
		echo $sdstr;
		
		$this->lcmb_metabox_field_list();
		
		echo '</div>';
	}#function
	
	function lcmb_field_statusupdate(){
		$metabox_row = $this->lcmb_get_metabox($_GET['parentid']);
		
		echo  '<div class="wrap"><h3>'.$metabox_row->mb_name.' Metabox Fields<BR><BR>
		<a class="add-new-h2" href="?page='.$_REQUEST['page'].'">Back View Metaboxes List</a>
		<a class="add-new-h2" href="?page='. $_REQUEST['page'].'&action=lcmb_add_field&parentid='.$metabox_row->unik.'">Add New Field</a>
		</h3>
		<BR>';
		
		global $wpdb;
		$fld_table_name = $this->get_tblname(FALSE);
		if(isset($_GET['stype'])){
			$new_status = $_GET['stype'] ? 0 : 1;
			$sql = "UPDATE ".$fld_table_name." SET enabled = ".$new_status." WHERE ".$this->tblid." = ".$_GET["id"]."";
			$wpdb->query($sql);
			$sdstr = "field status updated.<BR><BR>";
			echo $sdstr;
		}
		$this->lcmb_metabox_field_list();		
		
		echo '</div>';
		
	}#function update status of field

	function lcmb_metabox_field_list(){
		
		$fldtable = new lcmb_metabox_fieldlist_table($this->fields_tblname,$this->fldtblid,$_GET['parentid'],$this->tblname);
		$fldtable->prepare_items();
		
		echo '<form id="metaboxfield-table" method="GET">
			<input type="hidden" name="page" value="'.$_REQUEST['page'].'"/>
			<input type="hidden" name="parentid" value="'.$_GET['id'].'"/>';
		$fldtable->display();
		
		echo '</form>';
	}#function
	
	function lcmb_managefields(){
		$metabox_row = $this->lcmb_get_metabox($_GET['parentid']);
		
		echo  '<div class="wrap"><h3>'.$metabox_row->mb_name.' Metabox Fields<BR><BR>
		<a class="add-new-h2" href="?page='.$_REQUEST['page'].'">Back View Metaboxes List</a>
		<a class="add-new-h2" href="?page='. $_REQUEST['page'].'&action=lcmb_add_field&parentid='.$metabox_row->unik.'">Add New Field</a>
		</h3>
		<BR>';
		if( (isset($_GET["action"]) && !isset($_GET["action2"])) && $_GET['action'] == 'lcmb_managefields' ){
			$this->lcmb_metabox_field_list();
		}
		echo '</div>';
	}#function manage fields
	
}#class