<?php 

// only relevant to pro users who need to upgrade
$plugin_active = ( function_exists('is_plugin_active') && is_plugin_active('easy-faqs-pro/easy-faqs-pro.php') );
$registered_name = get_option('easy_faqs_registered_name');

if ( isValidFAQKey() && !$plugin_active && !empty($registered_name) ) {
	add_action('init', 'easy_faqs_init_automatic_updater');	
	//easy_faqs_init_automatic_updater();
}
				
//only run this on admin screens to prevent calling home on every pageload
function easy_faqs_init_automatic_updater()
{
	if( is_admin() ) {
		$consent_given = get_option('_easy_faqs_upgrade_consented', '');
		if ( !empty ($consent_given) ) {
			$package_url = easy_faqs_get_upgrade_package_url();
			$is_plugin_install_page = !empty( $_GET['page'] ) && ($_GET['page'] == 'easy-faqs-install-plugins');
			if ( !empty( $package_url ) ) {
				require_once( "tgmpa/class-tgm-plugin-activation.php" );
				add_action( 'tgmpa_register', 'easy_faqs_register_required_plugins' );
			} else if ( $consent_given && $is_plugin_install_page ) {
				// oh no, we have consent but no package. that means we couldn't reach the server,
				// but we're trying to go to the install page. so redirect to the install error page instead
				wp_redirect( admin_url('admin.php?page=easy_faqs_pro_error_page') );					
				exit();
			}
		}
	}
}

function easy_faqs_register_interstitial_page() 
{
	add_submenu_page( 
		'plugins',
		__('Privacy Notice'),
		__('Privacy Notice'),
		'manage_options',
		'easy_faqs_pro_privacy_notice',
		'easy_faqs_render_privacy_notice_page'
	);	
	
	add_submenu_page( 
		'plugins',
		__('Error'),
		__('Error'),
		'manage_options',
		'easy_faqs_pro_error_page',
		'easy_faqs_render_error_page'
	);
}
add_action( 'admin_menu', 'easy_faqs_register_interstitial_page' );

function easy_faqs_render_error_page()
{
	$members_url = 'https://goldplugins.com/members/?utm_source=easy_faqs_free_plugin&utm_campaign=pro_install_error&utm_banner=download_via_members_portal';
	$error_msg = '<p>' . __('We will not be able to automatically install Easy FAQs Pro. Please visit the')
				 . sprintf( ' <a href="%s">%s</a> ', $members_url, __('Members Portal') )
				 .  __('to download the plugin or contact support.')
				 . '</p>';
?>
	<h1><?php _e('Error'); ?></h1>
	<?php echo $error_msg; ?>
<?php
}

function easy_faqs_render_privacy_notice_page()
{
	$package_url = easy_faqs_get_upgrade_package_url();
	if ( !empty($_GET['consent']) ) {
		update_option( '_easy_faqs_upgrade_consented', current_time('timestamp') );
	}
	
	$consent_given = get_option('_easy_faqs_upgrade_consented', '');
	if ( !empty($consent_given) ) {
		printf('<script type="text/javascript">window.location = "%s";</script>', admin_url('admin.php?page=easy-faqs-install-plugins'));
		die();
	}
	
	$privacy_notice = '<p>In order to install Easy FAQs Pro, we must contact the Gold Plugins server. We will send only your API key and the URL of this website, in order to verify your license.</p>';
	$privacy_notice .= '<p>We respect your privacy and handle your data carefully. You can view our full <a href="https://goldplugins.com/privacy-policy/?utm_source=easy_faqs_free_plugin&utm_campaign=view_privacy_policy">Privacy Policy on our website</a>.</p>';	
	$privacy_notice .= sprintf( '<p><button class="button button-primary">%s</button></p>',
							    __('Verify License &amp; Continue') . ' &raquo' );
?>
	<h1><?php _e('Privacy Notice'); ?></h1>
	<form method="post" action="<?php echo add_query_arg('consent', '1'); ?>">
	<?php
		echo $privacy_notice;
	?>
	</form>
<?php
}

function easy_faqs_get_upgrade_package_url()
{
	$package_url = get_transient('_easy_faqs_upgrade_package_url');
	if ( empty($package_url) ) {
		$package_url = easy_faqs_get_upgrade_package_url_from_server();
		set_transient('_easy_faqs_upgrade_package_url', $package_url, 3600); // 1 hr
	}
	return !empty($package_url)
		   ? $package_url
		   : '';
}
function easy_faqs_get_upgrade_package_url_from_server()
{
	$api_url = 'https://goldplugins.com/';
	$email = get_option('easy_faqs_registered_name');
	$api_key = get_option('easy_faqs_registered_key');	

	$response = wp_remote_post( $api_url, array(
		'method'      => 'POST',
		'timeout'     => 5,//turned down from 20 due to repeated reports of live site slowdowns
		'redirection' => 5,
		'httpversion' => '1.0',
		'blocking'    => true,
		'headers'     => array(),
		'body'        => array(
			'gp_edd_action' => 'get_upgrade_package',
			'gp_edd_site_url' => home_url(),
			'gp_edd_license' => $api_key,
			'gp_edd_product_id' => 7002,
			'gp_edd_email' => $email,
		),
		'verify_ssl' => false,
		'cookies'     => array()
		)
	);
	
	if ( !is_wp_error( $response ) ) {
		$response = !empty($response['body'])
					? json_decode($response['body'])
					: array();
		if ( !empty($response) && !empty($response->package_url) ) {
			return $response->package_url;
		}
	}
	
	// unknown error
	return '';
}

function easy_faqs_register_required_plugins()
{
	/*
	 * Array of plugin arrays. Required keys are name and slug.
	 * If the source is NOT from the .org repo, then source is also required.
	 */	 
	$package_url = easy_faqs_get_upgrade_package_url();
	if ( empty($package_url) ) {
		return;
	}
	
	$plugins = array(
		array(
			'name'         => 'Easy FAQs Pro', // The plugin name.
			'slug'         => 'easy-faqs-pro', // The plugin slug (typically the folder name).
			'source'       => $package_url,
			'required'     => true, // If false, the plugin is only 'recommended' instead of required.
			'external_url' => 'https://goldplugins.com/downloads/easy-faqs-pro/?utm_source=easy_faqs_free_plugin&utm_campaign=install_pro&utm_banner=plugin_info_link', // If set, overrides default API URL and points to an external URL.
		)
	);

	/*
	 * Array of configuration settings. Amend each line as needed.
	 *
	 * TGMPA will start providing localized text strings soon. If you already have translations of our standard
	 * strings available, please help us make TGMPA even better by giving us access to these translations or by
	 * sending in a pull-request with .po file(s) with the translations.
	 *
	 * Only uncomment the strings in the config array if you want to customize the strings.
	 */
	$config = array(
		'id'           => 'easy-faqs',                 // Unique ID for hashing notices for multiple instances of TGMPA.
		'default_path' => '',                      // Default absolute path to bundled plugins.
		'menu'         => 'easy-faqs-install-plugins', // Menu slug.
		'parent_slug'  => 'easy-faqs-settings',            // Parent menu slug.
		'capability'   => 'manage_options',    // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
		'has_notices'  => false,                    // Show admin notices or not.
		'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
		'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
		'is_automatic' => true,                   // Automatically activate plugins after installation or not.
		'message'      => '',                      // Message to output right before the plugins table.
		'strings'      => array(
			'page_title' => __('Install') . ' Easy FAQs Pro',
			'menu_title' => __('Install') . ' Pro Plugin',
		)
	);
	tgmpa( $plugins, $config );
}

function easy_faqs_tgmpa_change_source_name($table_data)
{
	foreach($table_data as $index => $plugin)
	{
		if ($plugin['slug'] == 'easy-faqs-pro') {
			$table_data[$index]['source'] = '<a href="https://goldplugins.com/?utm_source=easy_faqs_free&utm_campaign=upgrade_to_pro&utm_banner=plugin_source_link" target="_blank">Gold Plugins</a>';
		}
	}
	return $table_data;	
}

add_filter('tgmpa_table_data_items', 'easy_faqs_tgmpa_change_source_name');