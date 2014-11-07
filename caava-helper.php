<?php
/**
 * @package   CaavaHelper
 * @author    Brandon Lavigne <brandon@caavadesign.com>
 * @license   GPL-2.0+
 * @link      http://caavadesign.com
 * @copyright 2013 Caava Design
 *
 * Plugin Name: Caava Helper 2
 * Plugin URI: http://caavadesign.com
 * Description: A series of developer facing functionality created to optimize or enhance a WordPress site.
 * Version: 2.0
 * Author: Brandon Lavigne
 * Author URI: http://caavadesign.com
 * Text Domain: caava-helper-locale
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /lang
 *
 * Copyright 2013  Brandon Lavigne  (email : brandon@caavadesign.com)
 *
 * 	This program is free software; you can redistribute it and/or modify
 * 	it under the terms of the GNU General Public License, version 2, as
 * 	published by the Free Software Foundation.
 *
 * 	This program is distributed in the hope that it will be useful,
 * 	but WITHOUT ANY WARRANTY; without even the implied warranty of
 * 	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * 	GNU General Public License for more details.
 *
 * 	You should have received a copy of the GNU General Public License
 * 	along with this program; if not, write to the Free Software
 * 	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once( plugin_dir_path( __FILE__ ) . 'class-caava-common.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-caava-helper.php' );

// Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
register_activation_hook( __FILE__, array( 'CaavaHelper', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'CaavaHelper', 'deactivate' ) );
register_uninstall_hook(__FILE__, array( 'CaavaHelper', 'uninstall' ) );

CaavaHelper::get_instance();
