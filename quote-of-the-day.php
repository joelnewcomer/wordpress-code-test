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
	
	function call_qod_api($method, $url, $data = false,$api_key=null) {
	    $curl = curl_init();
	
	    switch ($method) {
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
		// Get the saved transient containing quote of the day
		$qod_result = get_transient( 'qod' );
		if(empty( $qod_result) ) {
			// Get the JSON data for the quote of the day and decode it.
			$qod_json = call_qod_api("GET","https://quotes.rest/qod",false,null);
			$qod_result = json_decode($qod_json);
			set_transient( 'qod', $qod_result, HOUR_IN_SECONDS );
		}
		
		// Set up the variables
		$quote = $qod_result->contents->quotes[0]->quote;
		$url = $qod_result->contents->quotes[0]->permalink;
		$author = $qod_result->contents->quotes[0]->author;
		$tags = $qod_result->contents->quotes[0]->tags;
		
		$qod = '<div class="kanopi-qod"><h4>Quote of the Day</h4><blockquote>' . wpautop($qod_result->contents->quotes[0]->quote);
		$qod .= '<footer>';
		$qod .= '<cite><a href="' . $url . '" target="_blank">&mdash; ' . $author . '</a></cite>';
		$qod .= '<div class="qod-tags">';
		foreach ($tags as $tag) {
			$qod .= '<span class="qod-tag">' . $tag . '</span>';
		}
		$qod .= '</div> <!-- qod-tags -->';
		$qod .= '</footer>';
		$qod .= '</blockquote>';
		$qod .= '<span style="z-index:50;font-size:0.9em; font-weight: bold;">
      <img src="https://theysaidso.com/branding/theysaidso.png" height="20" width="20" alt="theysaidso.com"/>
      <a href="https://theysaidso.com" title="Powered by quotes from theysaidso.com" style="color: #ccc; margin-left: 4px; vertical-align: middle;">
        They Said So®
      </a>
</span>';
		$qod .= '</div><!-- kanopi-qod -->';
		// $qod .= '<pre>';
		// $qod .= print_r($qod_result, true);
    	// $qod .= '</pre>';
		return $qod;
	}
	add_filter( 'the_content', 'add_quote_of_the_day' );
	
	//Register settings
	function qod_options_add(){
    	register_setting( 'qod_options_group', 'qod_options_group' );
	}
	
	function qod_register_options_page() {
		add_options_page('Quote of the Day', 'Quote of the Day', 'manage_options', 'quote_of_the_day', 'qod_options_page');
	}
	
	//Grant access to options page 
	add_action( 'admin_init', 'qod_options_add' );
	add_action('admin_menu', 'qod_register_options_page');
	
	function qod_options_page() { ?>
		<div>
			<h2>Quote of the Day Options</h2>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'qod_options_group' );
				$options = get_option( 'qod_options_group' );
					// Get the saved transient containing quote of the day
		$qod_cats_result = get_transient( 'qod_cats' );
		if(empty( $qod_cats_result) ) {
			// Get the JSON data for the quote of the day and decode it.
			$qod_cats_json = call_qod_api("GET","https://quotes.rest/qod/categories",false,null);
			$qod_cats_result = json_decode($qod_cats_json);
			set_transient( 'qod_cats', $qod_cats_result, HOUR_IN_SECONDS );
		}
		$qod_cats = $qod_cats_result->contents->categories;

		?>			
			<table>
			<tr valign="top">
			<td>
				<label for="qod_cat">Choose a  category:</label>

<select name="qod_options_group[qod_cat]" id="qod_options_group[qod_cat]">
	<?php foreach ($qod_cats as $key => $value) {
		$selected = '';
		if ($key == $options['qod_cat']) {
			$selected = ' selected';
		}
		echo '<option value="' . $key . '"' . $selected . '>' . $value . '</option>';
	} ?>
</select>
			</td>
			</tr>
			</table>
			<?php  submit_button(); ?>
			</form>
			</div>
			<?php
	}

	$plugin = new Quote_Day();
	$plugin->run();

}
run_quote_of_the_day();
