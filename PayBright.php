<?php
/**
 * Plugin Name: PayBright Payment Gateway
 * Plugin URI: https://paybright.com/
 * Description: PayBright Payment Gateway - Woocommerce Payment Method.
 * Version: 2.0.1
 * Author: PayBright
 * Author URI: https://paybright.com/en/company
 * Developer: Gurpreet Dhadda, Mahima Doda, Sanuja, Dilna Anto, Clyde Grey, Prasad Kamat, Victor Velasquez, Houssem Gharssallah
 * Developer URI: https://paybright.com/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * PayBright Plugin is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free Software Foundation,
 * either version 2 of the License or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See
 * the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 * You can contact us at info@paybright.com
 *
 * Copyright (c) 2020, Health Smart Financial Services Inc.
 *
 * @class WC_Gateway_Paybright
 * @package Paybright
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Required functions and classes.
 */
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	include_once 'woo-includes/class-woothemes-plugin-updater.php';
}

// Include the main WooCommerce class.
if ( ! class_exists( 'WooCommerce_Gateway_Paybright', false ) ) {
	include_once dirname( __FILE__ ) . '/class-woocommerce-gateway-paybright.php';
}

/**
 * Returns Paybright.
 */
function paybright() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid.
	return WooCommerce_Gateway_Paybright::get_instance();
}

/**
 * Load Paybright.
 */
$GLOBALS['wc_paybright_loader'] = paybright();
