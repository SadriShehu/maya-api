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

require_once 'vendor/autoload.php';
 
function client() {
    $client = new GuzzleHttp\Client();
    $res = $client->request('GET', 'https://dummyjson.com/products/categories');
    $dummy = $res->getBody();

    return $dummy;
}

function maya_func( $atts ) {
    $dummy = client()->getContents();
    $dummy = json_decode($dummy, true);

    if (empty($dummy)) {
        return 'No data';
    }

    $content = '';
    foreach ($dummy as $value) {
        $content .= '<p>' . $value . '</p>';
    }

    return $content;
}

add_shortcode( "maya", "maya_func" );

