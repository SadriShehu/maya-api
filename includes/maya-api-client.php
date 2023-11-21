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
    $res = null;
    try {
        $res = $client->request($method, "https://api.maya.net/connectivity/v1/$path", [
            'headers' => $headers,
            'json' => $data,
        ]);
    } catch (GuzzleHttp\Exception\RequestException $e) {
        // Handle request exception
        echo 'Request Exception: ' . $e->getMessage();
        return false;
    } catch (GuzzleHttp\Exception\ConnectException $e) {
        // Handle connection exception
        echo 'Connection Exception: ' . $e->getMessage();
        return false;
    } catch (GuzzleHttp\Exception\ClientException $e) {
        // Handle client exception
        echo 'Client Exception: ' . $e->getMessage();
        return false;
    } catch (GuzzleHttp\Exception\ServerException $e) {
        // Handle server exception
        echo 'Server Exception: ' . $e->getMessage();
        return false;
    } catch (Exception $e) {
        // Handle other exceptions
        echo 'Exception: ' . $e->getMessage();
        return false;
    }

    switch ($res->getStatusCode()) {
    case 404:
        return 404;
        break;
    case 500:
        return 500;
        break;
    case 200:
        $resp = $res->getBody();
        return $resp;
        break;
    case 201:
        $resp = $res->getBody();
        return $resp;
        break;
    default:
        return 500;
        break;
    }
}

function maya_func() {
    echo '<h1>Maya API</h1>';
}

add_shortcode( "maya", "maya_func" );
