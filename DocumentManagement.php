<?php
/**
 * Document Management Plugin
 *
 * @package   csv-import
 * @author    Team Ajency <wordpress@ajency.in>
 * @license   GPL-2.0+
 * @link      http://ajency.in
 * @copyright 9-22-2014 Ajency.in
 */

/**
 * Document Management class.
 *
 * @package DocumentManagement
 * @author  Team Ajency <wordpress@ajency.in>
 */
class DocumentManagement{
	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   0.1.0
	 *
	 * @var     string
	 */
	protected $version = "0.1.0";

	/**
	 * Unique identifier for your plugin.
	 *
	 * Use this value (not the variable name) as the text domain when internationalizing strings of text. It should
	 * match the Text Domain file header in the main plugin file.
	 *
	 * @since    0.1.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = "document-management";

	/**
	 * Plugin prefix.
	 *
	 * @since    0.1.0
	 *
	 * @var      string
	 */
	protected static $plugin_prefix = "ajdm";
        
	/**
	 * Instance of this class.
	 *
	 * @since    0.1.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    0.1.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = '';

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     0.1.0
	 */
	private function __construct() {

		// Load plugin text domain
		add_action("init", array($this, "load_plugin_textdomain"));
                
                // hook function to register plugin defined and theme defined document types
                // custom added
                add_action("init", array($this, "register_doc_types"));

                // hook function to add categories to attachments
                // custom added
                add_action("init", array($this, "add_categories_to_attachments"));
                
                // hook function to add document types dropdown list on adding categories for attachments
                // custom added
                add_action("folder_add_form_fields", array($this, "add_document_types_dropdown"),10,1);
 
                // hook function to add document types dropdown list on adding categories for attachments
                // custom added
                add_action("folder_edit_form_fields", array($this, "editmode_document_types_dropdown"),10,1);
                
                // hook to save document type on create for a taxonomy
                // custom added
                add_action("create_folder", array($this, "save_taxonomy_document_type"),10,2);
                
                // hook to save document type on create for a taxonomy
                // custom added
                add_action("edited_folder", array($this, "save_taxonomy_document_type"),10,2);
                
		// Add the options page and menu item.
                // custom added
		add_action("admin_menu", array($this, "add_plugin_admin_menu"));        

		// Load admin style sheet and JavaScript.
		add_action("admin_enqueue_scripts", array($this, "enqueue_admin_styles"));
		add_action("admin_enqueue_scripts", array($this, "enqueue_admin_scripts"));

		// Load public-facing style sheet and JavaScript.
		add_action("wp_enqueue_scripts", array($this, "enqueue_styles"));
		add_action("wp_enqueue_scripts", array($this, "enqueue_scripts"));
                
		// Define custom functionality. Read more about actions and filters: http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		add_action("TODO", array($this, "action_method_name"));
		add_filter("TODO", array($this, "filter_method_name"));
                
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     0.1.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn"t been set, set it now.
		if (null == self::$instance) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
         * custom code logic for table creation on plugin activation
         * 
	 * @since    0.1.0
	 *
	 * @param    boolean $network_wide    True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
	 */
	public static function activate($network_wide) {
            // create plugin document uploads directory
            $content_dir = WP_CONTENT_DIR;
            $plugin_documents_dir = $content_dir.DIRECTORY_SEPARATOR.'ajdm_uploads';
            if(!file_exists($plugin_documents_dir))
                mkdir($plugin_documents_dir,0755);
            
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    0.1.0
	 *
	 * @param    boolean $network_wide    True if WPMU superadmin uses "Network Deactivate" action, false if WPMU is disabled or plugin is deactivated on an individual blog.
	 */
	public static function deactivate($network_wide) {
		// TODO: Define deactivation functionality here
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    0.1.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters("plugin_locale", get_locale(), $domain);

		load_textdomain($domain, WP_LANG_DIR . "/" . $domain . "/" . $domain . "-" . $locale . ".mo");
		load_plugin_textdomain($domain, false, dirname(plugin_basename(__FILE__)) . "/lang/");
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     0.1.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		if (!isset($this->plugin_screen_hook_suffix)) {
			return;
		}

		$screen = get_current_screen();
		if ($screen->id == $this->plugin_screen_hook_suffix) {
			wp_enqueue_style($this->plugin_slug . "-admin-styles", plugins_url("css/admin.css", __FILE__), array(),
				$this->version);
		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     0.1.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		if (!isset($this->plugin_screen_hook_suffix)) {
			return;
		}

		$screen = get_current_screen();
		if ($screen->id == $this->plugin_screen_hook_suffix ) {
			wp_enqueue_script($this->plugin_slug . "-admin-script", plugins_url("js/document-management-admin.js", __FILE__),
				array("jquery"), $this->version);
		}

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    0.1.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style($this->plugin_slug . "-plugin-styles", plugins_url("css/public.css", __FILE__), array(),
			$this->version);
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    0.1.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script($this->plugin_slug . "-plugin-script", plugins_url("js/document-management.js", __FILE__), array("jquery"),
			$this->version);
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    0.1.0
	 */
	public function add_plugin_admin_menu() {
		$this->plugin_screen_hook_suffix = add_plugins_page(__("Document Management - Administration", $this->plugin_slug),
			__("Document Management", $this->plugin_slug), "read", $this->plugin_slug, array($this, "display_plugin_admin_page"));
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    0.1.0
	 */
	public function display_plugin_admin_page() {
		include_once("views/admin.php");
	}

	/**
	 * NOTE:  Actions are points in the execution of a page or process
	 *        lifecycle that WordPress fires.
	 *
	 *        WordPress Actions: http://codex.wordpress.org/Plugin_API#Actions
	 *        Action Reference:  http://codex.wordpress.org/Plugin_API/Action_Reference
	 *
	 * @since    0.1.0
	 */
	public function action_method_name() {
		// TODO: Define your action hook callback here
	}

	/**
	 * NOTE:  Filters are points of execution in which WordPress modifies data
	 *        before saving it or sending it to the browser.
	 *
	 *        WordPress Filters: http://codex.wordpress.org/Plugin_API#Filters
	 *        Filter Reference:  http://codex.wordpress.org/Plugin_API/Filter_Reference
	 *
	 * @since    0.1.0
	 */
	public function filter_method_name() {
		// TODO: Define your filter hook callback here
	}
        
        /*
         * function to register the document types
         * custom added function
         * 
         * @since    0.1.0
         * 
         */        
        public function register_doc_types(){
            
            register_document_types('users');
        }  
        
        /*
         * function to add folder as custom taxonomy to attachments
         * 
         * @since    0.1.0
         */
        public function add_categories_to_attachments(){
            
            $labels = array(
            'name'              => 'Folders',
            'singular_name'     => 'Folder',
            'search_items'      => 'Search Folders',
            'all_items'         => 'All Folders',
            'parent_item'       => 'Parent Folder',
            'parent_item_colon' => 'Parent Folder:',
            'edit_item'         => 'Edit Folder',
            'update_item'       => 'Update Folder',
            'add_new_item'      => 'Add New Folder',
            'new_item_name'     => 'New Folder Name',
            'menu_name'         => 'Folders',
            );

            $args = array(
                'labels' => $labels,
                'hierarchical' => true,
                'query_var' => 'true',
                'rewrite' => 'true',
                'show_admin_column' => 'true',
            );

            register_taxonomy( 'folder', 'attachment', $args );
        }
        
        /*
         * function to add document types dropdown on add custom taxonomy 'folder'
         * @param string $taxonomy ie folder
         * 
         * hook to action {$taxonomy}_add_form_fields
         * @since    0.1.0
         */
        public function add_document_types_dropdown($taxonomy){
            
            global $ajdm_doctypes;

            if($_REQUEST['post_type'] == 'attachment'){ ?>

               <div class="form-field form-required">
               <label for="term_meta[document-type]"><?php echo 'Document Type'; ?></label>
               <select name="term_meta[document-type]" id="document-type" aria-required="true">
                   <option value=""></option>
                   <?php
                   foreach($ajdm_doctypes as $doctype){ ?>
                        <option value="<?php echo $doctype;?>"><?php echo $doctype;?> </option>
                   <?php }
                   ?>       
               </select>
               <p><?php _e('The Document type to be associated to a Folder.'); ?></p>
               </div> 

            <?php    
            }
        }
 
        /*
         * function to display document types dropdown on edit custom taxonomy 'folder'
         * @param object $term 
         * 
         * hook to action {$taxonomy}_edit_form_fields
         * @since    0.1.0
         */        
        public function editmode_document_types_dropdown($term){
            
            global $ajdm_doctypes;

            if($_REQUEST['post_type'] == 'attachment'){ 
                $t_id = $term->term_id;
                $term_doc_type = get_option( self::$plugin_prefix."_folder_".$t_id );
                ?>
               
               <tr class="form-field form-required">
                   <th scope="row" valign="top"><label for="term_meta[document-type]"><?php echo 'Document Type'; ?></label></th>
               <td><select name="term_meta[document-type]" id="document-type" aria-required="true">
                   <option value=""></option>
                   <?php
                   foreach($ajdm_doctypes as $doctype){ ?>
                        <option value="<?php echo $doctype;?>" <?php if($term_doc_type == $doctype){echo 'selected';} ?>>
                            <?php echo $doctype;?> 
                        </option>
                   <?php }
                   ?>       
               </select>
                   
               <p class="description"><?php _e('The Document type to be associated to a Folder.'); ?></p>
               </td>
               </tr> 

            <?php    
            }            
        }    

         /*
         * function to save a document type for a taxonomy 'folder'
         * @param int $term_id 
         * @param int $tt_id 
         * 
         * hook to actions create_{taxonomy},edited_{taxonomy}
         * @since    0.1.0
         */         
        public function save_taxonomy_document_type( $term_id ,$tt_id){
            
            if ( isset( $_POST['term_meta'] ) ) {
                $cat_keys = array_keys( $_POST['term_meta'] );
                
                foreach ( $cat_keys as $key ) {
                    if ( isset ( $_POST['term_meta'][$key] ) && $_POST['term_meta'][$key] != '' && $key == 'document-type') {
                             // Save the document type for the term in option for a termid.
                            update_option( self::$plugin_prefix."_folder_".$term_id , $_POST['term_meta'][$key] );
                    }
                }

            } 
        }
        
}