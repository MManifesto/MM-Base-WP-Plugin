<?php
/*
Plugin Name: MM File List
Plugin URI: http://mediamanifesto.com
Description: Plugin to list files in a given directory
Version: 1
Author: Adam Bissonnette
Author URI: http://www.mediamanifesto.com
*/

include_once('inc/functions.php');

class MM_FileList
{
	var $_settings;
	var $_plugin_slug = "mm_fl_";
	var $_plugin_name = "MM File List";
    var $_options_pagename = 'mm_options';
    var $_versionnum = 0.1;
    var $location_folder;
	var $menu_page;
	
	function MM_FileList()
	{
		return $this->__construct();
	}
	
    function __construct()
    {
        $this->_settings = get_option($_plugin_slug . 'settings') ? get_option($_plugin_slug . 'settings') : array();
		$this->location_folder = trailingslashit(WP_PLUGIN_URL) . dirname( plugin_basename(__FILE__) );
        $this->_set_standart_values();

        add_action( 'admin_menu', array(&$this, 'create_menu_link') );
        add_shortcode( 'MMFileList', array(&$this, 'ListFiles') );
    }
    
    
    static function mm_install() {
		global $wpdb;
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		
		$sql = "";
		
		/* We don't need no Javascript... errrrrrr SQL :< Stuff Right?? */
				
		dbDelta($sql);
		
		add_option($_plugin_slug . "versionnum", $_versionnum);
	}
    
    function add_settings_link($links) {
		$settings = '<a href="' .
					admin_url(sprintf("options-general.php?page=%s", $_options_pagename)) .
					'">' . __('Settings') . '</a>';
		array_unshift( $links, $settings );
		return $links;
	}
	
	function create_menu_link()
    {
        $this->menu_page = add_options_page($_plugin_name . 'Options', $this->_plugin_name . ' Plugin',
        'manage_options',$this->_options_pagename, array(&$this, 'build_settings_page'));
        add_action( "admin_print_scripts-{$this->menu_page}", array(&$this, 'plugin_page_js') );
        add_action("admin_head-{$this->menu_page}", array(&$this, 'plugin_page_css'));
		add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'), 10, 2);
    }

    function build_settings_page()
    {
        if (!$this->check_user_capability()) {
            wp_die( __('You do not have sufficient permissions to access this page.') );
        }

        if (isset($_REQUEST['saved'])) {if ( $_REQUEST['saved'] ) echo '<div id="message" class="updated fade"><p><strong>'. $_plugin_name .' settings saved.</strong></p></div>';}
		if ( isset($_POST[$_plugin_slug . 'settings_saved']) )
            $this->_save_settings_todb($_POST);
        
		include_once('mm_options.php');
    }

    function plugin_js()
	{
		//No included js - wp_enqueue_script('formtools', $this->location_folder . '/js/formtools.js');
	}
	
	function plugin_css()
	{
		//No page css
	}

    function plugin_page_js()
    {
    	wp_enqueue_script('bootstrap', $this->location_folder . '/js/bootstrap.min.js');
    	wp_enqueue_script('plugin', $this->location_folder . '/js/plugin.js');
    	wp_enqueue_script('formtools', $this->location_folder . '/js/formtools.js');
    }

    function plugin_page_css()
    {
?>
         <link rel="stylesheet" href="<?php echo $this->location_folder; ?>/css/bootstrap.min.css" type="text/css" />
<?php
    }

    function check_user_capability()
    {
        if ( is_super_admin() || current_user_can('manage_options') ) return true;

        return false;
    }

    function get_option($setting)
    {
        return $this->_settings[$setting];
    } 
	
	function mm_save()
	{
		if ($this->check_user_capability())
		{
			switch($_REQUEST['fn']){
				case 'settings':
					$data_back = $_POST['settings'];
					
					$values = array(
						$_plugin_slug . 'list_format' => $data_back['list_format'],
						$_plugin_slug . 'item_format' => $data_back['item_format'],		
					);
					
					$this->_save_settings_todb($values);
				break;
				default:
					//Derp
				break;
			}
		}

		die;
	}
	
	function _save_settings_todb($form_settings = '')
	{
		if ( $form_settings <> '' ) {
			unset($form_settings[$_plugin_slug . 'settings_saved']);

			$this->_settings = $form_settings;

			#set standart values in case we have empty fields
			$this->_set_standart_values();
		}

		update_option($_plugin_slug . 'settings', $this->_settings);
	}

	function _set_standart_values()
	{
		global $shortname; 

		$standart_values = array(
			$_plugin_slug . 'list_format' => '',
			$_plugin_slug . 'item_format' => '',
		);

		foreach ($standart_values as $key => $value){
			if ( !array_key_exists( $key, $this->_settings ) )
				$this->_settings[$key] = '';
		}

		foreach ($this->_settings as $key => $value) {
			if ( $value == '' ) $this->_settings[$key] = $standart_values[$key];
		}
	}
	
	function ListFiles($atts)
	{	
		extract( shortcode_atts( array(
		'folder' => '',
		'format' => 'li',
		'types' => 'pdf,doc'
		), $atts ) );
		
		$baseDir = wp_upload_dir();
		$dir = $baseDir['path'] . $folder;
		$outputDir = $baseDir['url'] . $folder;
		
		$typesToList = explode(",", $types);
		$files = scandir($dir);
		$list = array();
		
		foreach($files as $file)
		{
			$path_parts = pathinfo($file);
			$extension = $path_parts['extension'];
			
			if($file != '.' && $file != '..' && in_array($extension, $typesToList))
			{		 
				if(!is_dir($dir.'/'.$file))
				{
					$list[$file] = $outputDir . '/' . $file;
				} 
			}
		}
        
        $output = "";
        
        switch($format){
        	case 'li':
        		return $this->_MakeHtmlList($list);
        	break;
        	case 'comma':
        	default:
        		$output = implode(",", $list);
 			break;
        }
        
        return $output;
    }

	function _MakeHtmlList($list)
	{
		//These templates could be set as editable / saveable options
		$listTemplate = '<ul class="mm-dir-list">%s</ul>';
		$listItemTemplate = '<li><a href="%s">%s</a></li>';
		
		if ($this->_settings[$_plugin_slug . 'list_format'] != '')
		{
			$listTemplate = $this->_settings[$_plugin_slug . 'list_format'];
		}
		
		if ($this->_settings[$_plugin_slug . 'item_format'] != '')
		{
			$listItemTemplate = $this->_settings[$_plugin_slug . 'item_format'];
		}
		
		$items = "";
		
		foreach ($list as $item => $value) //in this case item == filename, value == path
		{
			$items .= sprintf($listItemTemplate, $value, $item);
		}
		
		return sprintf($listTemplate, $items);
	}

} // end MM_ProductManager class

register_activation_hook(__FILE__,array('MM_FileList', 'mm_install'));

add_action( 'init', 'MM_FileList_Init', 5 );
function MM_FileList_Init()
{
    global $MM_FileList;
    $MM_FileList = new MM_FileList();
}
?>