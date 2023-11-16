<?php
/**
 * Plugin Name: MAYA API
 * description: Add MAYA endpoints
 * Version: 1.0
 * Author: Sadri Shehu
 * License: GPLv2 or later
 * Text Domain: MAYA
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
define('MAYA_API_PLUGIN', plugin_dir_path( __FILE__ ));

include( MAYA_API_PLUGIN . 'includes/maya-api-client.php' );

if ( is_admin() ) {
    include( MAYA_API_PLUGIN . 'includes/maya-admin.php');
}
