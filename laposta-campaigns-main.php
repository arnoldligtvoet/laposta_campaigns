<?php
/**
 * Plugin Name: Laposta Campaigns
 * Plugin URI: https://github.com/arnoldligtvoet/laposta_campaigns
 * Description: A plugin to show your Laposta campaigns on your Wordpress site
 * Version: 0.1
 * Author: Arnold Ligtvoet
 * Author URI: https://github.com/arnoldligtvoet/laposta_campaigns
 * License: GPL3
 */


// Add a menu item for the plugin
function laposta_campaigns_add_admin_menu() {
	add_menu_page(
    	'Laposta Campaigns', // Page title
    	'Laposta Campaigns',      	// Menu title
    	'manage_options',      	// Capability
    	'laposta-campaigns',      	// Menu slug
    	'laposta_campaigns_settings_page' // Callback function
	);
}
add_action('admin_menu', 'laposta_campaigns_add_admin_menu');

// Hook to add settings link on plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'laposta_campaigns_settings_link');

function laposta_campaigns_settings_link($links) {
    $settings_link = '<a href="admin.php?page=laposta-campaigns">Settings</a>';
    array_unshift($links, $settings_link); // Add at the beginning
    return $links;
}

function laposta_campaigns_settings_page() {
	include 'templates/admin-page.php';
}

function get_laposta_campaigns() {

	$api_key = get_option('laposta_campaigns_api_key', 'put your api key here');
	$selected_lists = explode(",", get_option('laposta_campaigns_selected_lists'));

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://api.laposta.org/v2/campaign");
	curl_setopt($ch, CURLOPT_USERPWD, $api_key . ":");  
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);
	curl_close($ch);

	$count = 0;   // result counter
	$limit = 10;  // max results
	$html = "";
	
	if(!curl_errno($ch)){
		$data = json_decode($response, true);

		if (isset($data['error'])) {
    		$html = $data['error']['message'] ?? null;
		} else {
			$html .= "<div class=\"display_archive\">";

			foreach ($data['data'] as $item) {

				// Stop if limit reached
				if ($count >= $limit) {
					break;
				}
				$campaign = $item['campaign'];

				// Must have delivery_ended
				if (empty($campaign['delivery_ended'])) {
					continue;
				}

				// Get list ids from campaign (keys of the object)
				$campaign_lists = array_keys($campaign['list_ids']);

				// Check for intersection
				$hasMatch = count(array_intersect($campaign_lists, $selected_lists)) > 0;

					if (!$hasMatch) {
					continue;
				}

				// Format date dd/mm/yyyy
				$date = date('d/m/Y', strtotime($campaign['delivery_ended']));

				$subject = htmlspecialchars($campaign['subject']);
				$link = htmlspecialchars($campaign['web']);

				$html .= "<div class=\"campaign\">{$date} — <a href=\"{$link}\" target=\"_blank\">{$subject}</a></div>\n";

				$count++; // increment
			} 
			$html .=  "</div>";

			if ($count < 1) {
				$html = "No published campaigns could be found.";
			}
		}		
	}
	return $html;
}

add_shortcode( 'show_laposta_campaigns', 'get_laposta_campaigns');

?>