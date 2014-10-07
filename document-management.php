<?php
/**
 * Document Management Plugin
 *
 * A simple Document Management Plugin for wordpress
 *
 * @package   document-management
 * @author    Team Ajency <talktous@ajency.in>
 * @license   GPL-2.0+
 * @link      http://ajency.in
 * @copyright 9-22-2014 Ajency.in
 *
 * @wordpress-plugin
 * Plugin Name: Document Management
 * Plugin URI:  http://ajency.in
 * Description: A simple Document Management Plugin for wordpress
 * Version:     0.1.0
 * Author:      Team Ajency
 * Author URI:  http://ajency.in
 * Text Domain: document-management-locale
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /lang
 */

// If this file is called directly, abort.
if (!defined("WPINC")) {
	die;
}

// include the register document types functions file
require_once( plugin_dir_path( __FILE__ ) . '/include/register_document_types.php');

// include the custom plugin functions file
require_once( plugin_dir_path( __FILE__ ) . '/include/functions.php');

require_once(plugin_dir_path(__FILE__) . "DocumentManagement.php");

// Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
register_activation_hook(__FILE__, array("DocumentManagement", "activate"));
register_deactivation_hook(__FILE__, array("DocumentManagement", "deactivate"));

function aj_documentmanagement() {
	return DocumentManagement::get_instance();
}

// add the document management to globals
$GLOBALS['aj_documentmanager'] = aj_documentmanagement();
