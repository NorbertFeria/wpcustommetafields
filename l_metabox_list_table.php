<?php
if(!defined('ABSPATH')) exit;

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class lcmb_metabox_list_table extends WP_List_Table{
	
	var $tbl_name;
	var $tbl_unikid;
	
    function __construct($tblname,$fldid){
		$this->tbl_name = $tblname;
		$this->tbl_unikid = $fldid;
        global $status, $page;
        parent::__construct(array(
            'singular' => 'Metabox',
            'plural' => 'Metaboxes',
        ));
    }#construct

    function column_default($item, $column_name){
        return $item[$column_name];
    }#function

    function column_mb_name($item){
		$status = $item['enabled'] ? 'Disable' : 'enable';
        $actions = array(
            'edit' => sprintf('<a href="?page=%s&action=lcmb_edit&id=%s">%s</a>',  $_REQUEST['page'],$item[$this->tbl_unikid], __('Edit', 'lcmb_table')),
            'delete' => sprintf('<a href="?page=%s&action=lcmb_delete&id=%s">%s</a>', $_REQUEST['page'], $item[$this->tbl_unikid], __('Delete', 'lcmb_table')),		
			'manage' => sprintf('<a href="?page=%s&action=lcmb_managefields&parentid=%s">%s</a>', $_REQUEST['page'], $item[$this->tbl_unikid], __('Manage Fields', 'lcmb_table')),
			$status => sprintf('<a href="?page=%s&action=lcmb_statusupdate&stype=%s&id=%s">%s</a>', $_REQUEST['page'],$item['enabled'], $item[$this->tbl_unikid], __($status, 'lcmb_table')),					
        );
        return sprintf('%s %s',$item['mb_name'],$this->row_actions($actions));
    }#function
	
    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',
            $item[$this->tbl_unikid]
        );
    }#function
	
	function column_enabled($item){
        return '<a href="?page='.$_REQUEST['page'].'&action=lcmb_statusupdate&stype='.$item['enabled'].'&id='.$item[$this->tbl_unikid].'"><img src="' . plugins_url( 'images/icon-'.$item['enabled'].'.png' , __FILE__ ) . '" ></a>';
    }#function
	

    function get_columns(){
        $columns = array(
            'cb' => '<input type="checkbox"/>',
            'mb_name' => __('Name', 'lcmb_table'),
			'enabled' => __('Status', 'lcmb_table'),
			'mb_type' => __('Type', 'lcmb_table'),
			'mb_description' => __('Description', 'lcmb_table')
        );
        return $columns;
    }#function

    function get_sortable_columns(){
        $sortable_columns = array(
            'mb_name' => array('mb_name', false),
			'mb_type' => array('mb_type', false)
        );
        return $sortable_columns;
    }#function

    function get_bulk_actions(){
        $actions = array(
            'bulkdelete' => 'Delete'
        );
        return $actions;
    }

    function process_bulk_action(){
        global $wpdb;
        $table_name = $wpdb->prefix . $this->tbl_name;
        if ('bulkdelete' === $this->current_action()) {
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
            if (is_array($ids)) $ids = implode(',', $ids);
            if (!empty($ids)) {
				echo '<BR>Custom Metabox successfully Deleted.<BR><BR>';
                $wpdb->query("DELETE FROM $table_name WHERE ".$this->tbl_unikid." IN($ids)");
            }
        }
    }#function

    function prepare_items(){
        global $wpdb;
        $table_name = $wpdb->prefix . $this->tbl_name;
        $per_page = 20; 
        
		$columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
		
        $this->_column_headers = array($columns, $hidden, $sortable);
        
		$this->process_bulk_action();
		
        $total_items = $wpdb->get_var("SELECT COUNT(".$this->tbl_unikid.") FROM $table_name");
		
		$paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
		
		$offset = isset($_REQUEST['paged']) ? ($per_page * (intval($_REQUEST['paged'])-1)) : 0;
		
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'mb_name';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'asc';		
        $this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $offset), ARRAY_A);
			
        $this->set_pagination_args(array(
            'total_items' => $total_items, 
            'per_page' => $per_page, 
            'total_pages' => ceil($total_items / $per_page) 
        ));
    }
	
}#class
?>