<?php

// check user capabilities
if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

// check if we are updating settings
if (isset($_POST)) {
	
	if (isset($_POST['api_key'])) {
		update_option('laposta_campaigns_api_key', sanitize_text_field($_POST['api_key']));
			
		if (!empty($_POST['lists'])) {
			// user selected lists or lists were selected already
			update_option('laposta_campaigns_selected_lists', sanitize_text_field(implode(",", $_POST['lists'])));
		} else {
			// lists do not exist or are empty
			update_option('laposta_campaigns_selected_lists', "");
		}
		echo '<div class="updated"><p>Settings saved!</p></div>';
	}
}

// read values from db
$api_key = get_option('laposta_campaigns_api_key', 'put your api key here');
$selected_lists = explode(",", get_option('laposta_campaigns_selected_lists'));

//Get the active tab from the $_GET param
$default_tab = null;
$tab = isset($_GET['tab']) ? $_GET['tab'] : $default_tab;

?>
	<!-- Our admin page content should all be inside .wrap -->
	<div class="wrap">
		<!-- Print the page title -->
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<!-- Here are our tabs -->
		<nav class="nav-tab-wrapper">
			<a href="?page=laposta-campaigns" class="nav-tab <?php if($tab===null):?>nav-tab-active<?php endif; ?>">Settings</a>
			<a href="?page=laposta-campaigns&tab=integrations" class="nav-tab <?php if($tab==='integrations'):?>nav-tab-active<?php endif; ?>">Integration</a>
		</nav>
		<div class="tab-content">
		<?php switch($tab) :
			case 'integrations':
				echo '<h4>Example data:</h4>';
				echo get_laposta_campaigns();

				?>
				<h4>Shortcode<h4>
				Use shortcode <b>[show_laposta_campaigns]</b> to show the sent campaigns in a page or post
				<?php
				break;
			default:
				?>
				<form method="POST">
					<br>
					<h4>Enter your API key for connecting to Laposta below</h4>
					<label for="footer_text">API key:</label>
					<input type="password" id="api_key" name="api_key" value="<?php echo esc_attr($api_key); ?>" style="width: 100%; max-width: 400px;">
					<br>
					Create and manage your API keys at <a href="https://app.laposta.nl/config/c.connect/s.api/" target="_blank">the Laposta page</a>
					<?php

					if ($api_key != "put your api key here") {
						// call Laposta API to get all lists 
						$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL, "https://api.laposta.org/v2/list");
						curl_setopt($ch, CURLOPT_USERPWD, $api_key . ":");  
						curl_setopt($ch, CURLOPT_TIMEOUT, 30);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						$response = curl_exec($ch);
						curl_close($ch);
						if (($response) && (!curl_errno($ch))) {
						
							$data = json_decode($response, true);

							if (!empty($data['data'])) {
								?> <h4>Select the lists you want to include</h4> <?php
								foreach ($data['data'] as $item) {
									$list = $item['list'];
									echo '<label>';
									if (in_array(htmlspecialchars($list['list_id']), $selected_lists)) {
										// if the list was selected before, reselect it
										echo '<input type="checkbox" name="lists[]" value="' . htmlspecialchars($list['list_id']) . '" checked> ';
									} else {
										// list new or not previously selected
										echo '<input type="checkbox" name="lists[]" value="' . htmlspecialchars($list['list_id']) . '"> ';
									}
									echo htmlspecialchars($list['name']);
									echo '</label><br>';
								}
							}
						}
					}

					?>
					<br><br>
					<input type="submit" value="Save Settings" class="button button-primary">
				</form>
			<?php
			break;
    		endswitch; ?>
    	</div>
  	</div>

<?php 

?>