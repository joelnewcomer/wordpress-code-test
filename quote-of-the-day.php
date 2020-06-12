<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://kanopistudios.com
 * @since             1.0.0
 * @package           Quote_Day
 *
 * @wordpress-plugin
 * Plugin Name:       Quote of the Day
 * Plugin URI:        http://kanopistudios.com/quote-of-the-day/
 * Description:       Get the quote of the day using the public API from They Said So
 * Version:           1.0.0
 * Author:            Joel Newcomer
 * Author URI:        http://joelnewcomer.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:      quote-of-the-day
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'QUOTE_DAY_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-quote-of-the-day-activator.php
 */
function activate_quote_of_the_day() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-quote-of-the-day-activator.php';
	Quote_Day_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-quote-of-the-day-deactivator.php
 */
function deactivate_quote_of_the_day() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-quote-of-the-day-deactivator.php';
	Quote_Day_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_quote_of_the_day' );
register_deactivation_hook( __FILE__, 'deactivate_quote_of_the_day' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-quote-of-the-day.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_quote_of_the_day() {
	
	function call_api($method, $url, $data = false,$api_key=null) {
	    $curl = curl_init();
	
	    switch ($method)
	    {
		case "POST":
		    curl_setopt($curl, CURLOPT_POST, 1);
	
		    if ($data)
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		    break;
		case "PUT":
		    curl_setopt($curl, CURLOPT_PUT, 1);
		    break;
		default:
		    if ($data)
			$url = sprintf("%s?%s", $url, http_build_query($data));
	    }
	
	    $headers = [
		'Content-Type: application/json'
		];
	    if ( !empty($api_key))
		$headers[] = 'X-TheySaidSo-Api-Secret: '. $api_key;
	
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	
	    $result = curl_exec($curl);
	
	    curl_close($curl);
	
	    return $result;
	}

	
	function add_quote_of_the_day( $content ) {
		$qod_json = call_api("GET","https://quotes.rest/qod?category=funny",false,null);
		$qod_result = json_decode($qod_json);
		
		$qod = '<blockquote><h2>Quote of the Day</h2>' . wpautop($qod_result->contents->quotes[0]->quote) . '</blockquote>';
		$qod .= '<pre>';
		$qod .= print_r($qod_result, true);
    	$qod .= '</pre>';
    	if ( function_exists('get_field') ) {
			$content .= $qod;
    	}
		return $content;
	}
	add_filter( 'the_content', 'add_quote_of_the_day' );

	$plugin = new Quote_Day();
	$plugin->run();

}
run_quote_of_the_day();
