<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://adnow.com
 * @since             1.0.2
 * @package           adnow_widget
 *
 * @wordpress-plugin
 * Plugin Name:       Adnow Plugin
 * Plugin URI:        https://adnow.com/wordpress-plugin
 * Description:       Adding a widget to your website Adnow
 * Version:           1.0.2
 * Author:            Adnow
 * Author URI:        https://adnow.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       adnow-widget
 * Domain Path:       /languages
 */

/*  Copyright 2017 Adnow (email: publishers@adnow.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc.
*/


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-adnow-widget-activator.php
 */
function activate_adnow_widget() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-adnow-widget-activator.php';
	adnow_widget_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-adnow-widget-deactivator.php
 */
function deactivate_adnow_widget() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-adnow-widget-deactivator.php';
	adnow_widget_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_adnow_widget' );
register_deactivation_hook( __FILE__, 'deactivate_adnow_widget' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-adnow-widget.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_adnow_widget() {

	$plugin = new adnow_widget();
	$plugin->run();

}
run_adnow_widget();
