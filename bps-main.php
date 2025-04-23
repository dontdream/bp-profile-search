<?php
/*
Plugin Name: BP Profile Search
Plugin URI: https://dontdream.it/bp-profile-search/
Description: Member search and member directories for BuddyPress and the BuddyBoss Platform.
Version: 5.8.3
Author: Andrea Tarantini
Author URI: https://dontdream.it/
Text Domain: bp-profile-search
*/

define ('BPS_VERSION', '5.8.3');
define ('BPS_PLUGIN_BASENAME', plugin_basename (__FILE__));

add_action ('admin_notices', 'bps_no_buddypress');
function bps_no_buddypress ()
{
?>
	<div class="notice notice-error is-dismissible">
		<p><?php _e('BP Profile Search requires BuddyPress or the BuddyBoss Platform.', 'bp-profile-search'); ?></p>
	</div>
<?php
}

add_action ('bp_include', 'bps_buddypress');
function bps_buddypress ()
{
	remove_action ('admin_notices', 'bps_no_buddypress');
	include 'bps-start.php';
}

add_action ('in_plugin_update_message-'. BPS_PLUGIN_BASENAME, 'bps_update_message', 10, 2);
function bps_update_message ($plugin_data, $response)
{
}

function bps_platform ()
{
	static $platform;
	if (isset ($platform))  return $platform;

	include_once ABSPATH. 'wp-admin/includes/plugin.php';
	$platform = is_plugin_active ('buddyboss-platform/bp-loader.php')? 'buddyboss': 'buddypress';

	return $platform;
}

function bps_parser ()
{
	if (function_exists ('bp_core_get_query_parser'))
		return bp_core_get_query_parser ();
	return 'undefined';
}
