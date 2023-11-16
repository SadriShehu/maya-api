<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require_once 'vendor/autoload.php';

function client($method, $path, $data) {
    if (!get_option('client_id') || !get_option('client_secret')) {
        return;
    }

    if (!$method || !$path) {
        return;
    }

    $username = get_option('client_id');
    $password = get_option('client_secret');
    $base64Credentials = base64_encode("$username:$password");

    $headers = [];
    if ($method == 'GET') {
        $headers = [
            'Accept' => 'application/json',
            'Authorization' => "Basic $base64Credentials",
        ];
    }

    if ($method == 'POST') {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => "Basic $base64Credentials",
        ];
    }

    $client = new GuzzleHttp\Client();
    $res = $client->request($method, "https://api.maya.net/connectivity/v1/$path", [
        'headers' => $headers,
        'json' => $data,
    ]);
    $resp = $res->getBody();

    return $resp;
}

function maya_func() {
    echo '<h1>Maya API</h1>';
}

add_shortcode( "maya", "maya_func" );
