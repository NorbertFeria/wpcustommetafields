<?php
if(!defined('ABSPATH')) exit;

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}
class lcmb_metabox_fieldlist_table extends WP_List_Table{
	
	var $tbl_name;
	var $fldtbl_name;
	var $tbl_unikid;
    var $fldtbl_unikid = 'unik';
	var $parentid;
	
    function __construct($fldtblname,$fldid,$parentid,$tblname){
		
		$this->fldtbl_name = $fldtblname;
		$this->tbl_name = $tblname;
		$this->tbl_unikid = $fldid;
		$this->parentid= $parentid;
		
        global $status, $page;
        parent::__construct(array(
            'singular' => 'Metabox',
            'plural' => 'Metaboxes',
        ));
    }#construct

    function column_default($item, $column_name){
        return $item[$column_name];
    }#function

    function column_fld_label($item){
		$status = $item['enabled'] ? 'Disable' : 'enable';
        $actions = array(
            'edit' => sprintf('<a href="?page=%s&action=lcmb_edit_field&id=%s&parentid=%s">%s</a>',  $_REQUEST['page'],$item[$this->fldtbl_unikid],$_GET['parentid'], __('Edit', 'lcmb_table')),
            'delete' => sprintf('<a href="?page=%s&action=lcmb_delete_field&id=%s&parentid=%s">%s</a>', $_REQUEST['page'], $item[$this->fldtbl_unikid],$_GET['parentid'], __('Delete', 'lcmb_table')),		
			$status => sprintf('<a href="?page=%s&action=lcmb_field_statusupdate&stype=%s&id=%s&parentid=%s">%s</a>', $_REQUEST['page'],$item['enabled'], $item[$this->fldtbl_unikid],$_GET['parentid'], __($status, 'lcmb_table')),					
        );
        return sprintf('%s %s',$item['fld_label'],$this->row_actions($actions));
    }#function
	
    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',
            $item[$this->tbl_unikid]
        );
    }#function
	
	function column_enabled($item){
        return '<a href="?page='.$_REQUEST['page'].'&action=lcmb_field_statusupdate&stype='.$item['enabled'].'&id='.$item[$this->fldtbl_unikid].'&parentid='.$_GET['parentid'].'"><img src="' . plugins_url( 'images/icon-'.$item['enabled'].'.png' , __FILE__ ) . '" ></a>';
    }#function
	

    function get_columns(){
        $columns = array(
            'cb' => '<input type="checkbox"/>',
            'fld_label' => __('Name', 'lcmb_table'),
			'enabled' => __('Status', 'lcmb_table'),
			'fld_inputtype' => __('Type', 'lcmb_table'),
			'fld_description' => __('Description', 'lcmb_table')
        );
        return $columns;
    }#function

    function get_sortable_columns(){
        $sortable_columns = array(
            'fld_name' => array('fld_name', false),
			'fld_inputtype' => array('fld_inputtype', false)
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
        $table_name = $wpdb->prefix . $this->fldtbl_name;
        if ('bulkdelete' === $this->current_action()) {
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
            if (is_array($ids)) $ids = implode(',', $ids);
            if (!empty($ids)) {
				echo '<BR>Custom Metabox field successfully Deleted.<BR><BR>';
                $wpdb->query("DELETE FROM $table_name WHERE ".$this->fldtbl_unikid." IN($ids)");
            }
        }
    }#function

    function prepare_items(){
        global $wpdb;
        $table_name = $wpdb->prefix . $this->fldtbl_name;
        $per_page = 10; 
        
		$columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
		
        $this->_column_headers = array($columns, $hidden, $sortable);
        
		$this->process_bulk_action();
		
        $total_items = $wpdb->get_var("SELECT COUNT(".$this->tbl_unikid.") FROM $table_name");
		
		$paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
		
		$offset = isset($_REQUEST['paged']) ? ($per_page * (intval($_REQUEST['paged'])-1)) : 0;
		
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'fld_name';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'asc';		
        $this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE ".$this->tbl_unikid." = ".$this->parentid." ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $offset), ARRAY_A);
			
        $this->set_pagination_args(array(
            'total_items' => $total_items, 
            'per_page' => $per_page, 
            'total_pages' => ceil($total_items / $per_page) 
        ));
    }
	
}#class
?>