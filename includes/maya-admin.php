<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require_once 'vendor/autoload.php';

// Import necessary classes from the library
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\Output\QRImage;

// Add the admin options page
add_action( "admin_menu", "maya_plugin_menu_func" );
function maya_plugin_menu_func() {
   add_menu_page(
      "Maya API",                // Page title
      "Maya API",                // Menu title
      "manage_options",          // Minimum capability (manage_options is an easy way to target administrators)
      "maya-admin",              // Menu slug
      "maya_plugin_options",     // Callback that prints the markup
      'dashicons-admin-generic', // Icon
      100                        // Position
   );
}

// Print the markup for the page
function maya_plugin_options() {
   if ( !current_user_can( "manage_options" ) )  {
      wp_die( __( "You do not have sufficient permissions to access this page." ) );
   }

   $client_id = get_option("client_id");
   $client_secret = get_option("client_secret");

   if ($client_id && $client_secret) {
      echo "<h1>Maya API</h1>" .
      "<h2>Current Maya API Info</h2>" .
      "<p>Maya API key: {$client_id}</p>" .
      "<p>Maya API secret: ***</p>" . 
      "<br />";
   }
   ?>
   <form method="post" action="<?php echo admin_url( 'admin-post.php'); ?>">

      <input type="hidden" name="action" value="handle_api_auth" />

      <h3><?php _e("Add new Maya API Info", "maya-api"); ?></h3>
      <p>
      <label><?php _e("Maya Key:", "maya-api"); ?></label>
      <input class="" type="text" name="client_id" value="<?php echo get_option('client_id'); ?>" />
      </p>

      <p>
      <label><?php _e("Maya Secret:", "maya-api"); ?></label>
      <input class="" type="password" name="client_secret" value="<?php echo get_option('client_secret'); ?>" />
      </p>

      <input class="button button-primary" type="submit" value="<?php _e("Save", "maya-api"); ?>" />

   </form>
   <?php
}

add_action( "admin_post_handle_api_auth", "maya_plugin_handle_api_auth" );
function maya_plugin_handle_api_auth() {
   if ( isset($_POST["client_id"]) && isset($_POST["client_secret"]) ) {
      update_option("client_id", $_POST["client_id"], TRUE );
      update_option("client_secret", $_POST["client_secret"], TRUE);
   }

   $client_id = get_option("client_id");
   $client_secret = get_option("client_secret");

   if ($client_id && $client_secret) {
      header("Location: " . admin_url( 'admin.php?page=maya-admin' ) );
   } else {
      echo "Something went wrong! Please enter a valid client id and secret.";
      header("Location: " . admin_url( 'admin.php?page=maya-admin' ) );
   }
}

// Add a submenu under the custom menu
function maya_plugin_submenu_page() {
   add_submenu_page(
      'maya-admin',          // Parent menu slug
      'Register eSim',       // Page title
      'Register eSim',       // Menu title
      'manage_options',      // Capability required to access the submenu item
      'maya-register-esim',  // Submenu slug
      'maya_register_esim'   // Callback function to display the page content
  );

   add_submenu_page(
       'maya-admin',          // Parent menu slug
       'Register Plans',      // Page title
       'Register Plans',      // Menu title
       'manage_options',      // Capability required to access the submenu item
       'maya-register-plans', // Submenu slug
       'maya_register_plans'  // Callback function to display the page content
   );

  add_submenu_page(
      'maya-admin',          // Parent menu slug
      'eSim Details',        // Page title
      'eSim Details',        // Menu title
      'manage_options',      // Capability required to access the submenu item
      'maya-esim-details',   // Submenu slug
      'get_esim_details'     // Callback function to display the page content
  );

   add_submenu_page(
      'maya-admin',          // Parent menu slug
      'Current Plans',       // Page title
      'Current Plans',       // Menu title
      'manage_options',      // Capability required to access the submenu item
      'maya-current-plans',  // Submenu slug
      'maya_get_current_plans'   // Callback function to display the page content
   );
}

add_action('admin_menu', 'maya_plugin_submenu_page');

function maya_get_current_plans() {
   $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : get_current_user_id(); // Get the selected user's ID or the current user's ID
   $esim = get_user_meta($user_id, 'esim_uid', true);

   // Display the select dropdown list
   $users = get_users();
   ?>
   <br />
   <form method="post">
      <label for="user_id">Select User:</label>
      <select name="user_id" id="user_id">
         <?php foreach ($users as $user) { ?>
            <option value="<?php echo $user->ID; ?>" <?php selected($user_id, $user->ID); ?>><?php echo $user->display_name; ?></option>
         <?php } ?>
      </select>
      <input class="button button-primary" type="submit" value="Submit">
   </form>
   <?php

   if (!$esim) {
      echo "<p>No eSIM registered for user <b>{$user_id}</b></p>";
      return;
   }

   $resp = client("GET", "esim/{$esim}/plans", null);

   if (empty($resp)) {
      echo '<p>No data</p>';
      return;
   }

   if ($resp == false) {
      echo "<p>No eSIM registered for user <b>{$user_id}</b></p>";
      return;
   }

   $resp = json_decode($resp->getContents(), true);
   if ($resp['status'] == 404) {
      echo "<p>No eSIM registered for user <b>{$user_id}</b></p>";
      return;
   }

   $plans = $resp['plans'];

   foreach($plans as $plan) {
      foreach($plan as $key => $value) {
         if ($key == 'countries_enabled') {
            $countires = null;
            foreach ($value as $country) {
               $countires .= $country . ', ';
            }
            echo "<p>{$key}: {$countires}</p>";
         } else {
            echo "<p>{$key}: {$value}</p>";
         }
      }
      echo '<hr />';
   }

   return;
}

function maya_register_plans() {
   $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : get_current_user_id(); // Get the selected user's ID or the current user's ID

   echo "<h1>Register Plans</h1>";
   // we have to handle here the form submission
   if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['plan_type']) && isset($_POST['user_id'])) {
      $esim = get_user_meta($user_id, 'esim_uid', true);

      $plan_id = $_POST['plan_type'];
      
      $user_data = get_userdata($user_id);
      $username = $user_data->user_login;
      
      if (!$esim) {
         echo "<p>No eSIM registered for user <b>{$username}</b></p>";
         return;
      }

      $resp = client("POST", "esim/{$esim}/plan/{$plan_id}", $_POST);

      if (empty($resp)) {
          return 'No data';
      }

      if ($resp == false) {
         echo "<p>No eSIM registered for user <b>{$user_id}</b></p>";
         return;
      }

      $resp = json_decode($resp->getContents(), true);

      echo "<p>Plan registered</p>";
      return;
   }

   $response = client("GET", "account/plan-types", null)->getContents();
   $response_data = json_decode($response, true);
   $plan_types = $response_data['plan_types'];

   // Display the select dropdown list
   $users = get_users();

   ?>
   <form method="post">
      <input type="hidden" name="action" value="register_plan" />
      <p>
         <label for="user_id">Select User:</label>
         <select name="user_id" id="user_id">
            <?php foreach ($users as $user) { ?>
               <option value="<?php echo $user->ID; ?>" <?php selected($user_id, $user->ID); ?>><?php echo $user->display_name; ?></option>
            <?php } ?>
         </select>
      </p>
      <p>
         <label for="plan_type"><?php _e("Select Plan Type:", "maya-api"); ?></label>
         <select name="plan_type" id="plan_type">
            <?php foreach ($plan_types as $plan_type) { ?>
               <option value="<?php echo $plan_type['uid']; ?>"><?php echo $plan_type['name']; ?></option>
            <?php } ?>
         </select>
      </p>
      <input class="button button-primary" type="submit" value="<?php _e("Register Plan", "maya-api"); ?>" />
   </form>
   <?php
}

function maya_register_esim() {
   if ($_SERVER["REQUEST_METHOD"] == "POST") {
      $region = $_POST['region'];
      $tag = $_POST['tag'];
      $user_id = $_POST['user_id'];

      $resp = client("POST", "esim", ['region' => $region, 'tag' => $tag]);

      if (empty($resp)) {
         return 'No data';
      }

      if ($resp == false) {
         echo "<p>No eSIM registered for user <b>{$user_id}</b></p>";
         return;
      }

      $resp = json_decode($resp->getContents(), true);
      $esim = $resp['esim']['iccid'];

      echo "<h1>New eSIM created:</h1>" .
      "<p>eSIM: {$esim}</p>";
      update_user_meta($user_id, 'esim_uid', $esim);
      return;
  } else {
   // Display the select dropdown list
   $users = get_users();
   $user_id = get_current_user_id(); // Get the current user's ID
   ?>
      <br>
      <h3>Register new eSIM</h3>
      <form method="post">
         <p>
            <label><?php _e("Region:", "maya-api"); ?></label>
            <input class="" type="text" name="region">
         </p>
         <p>
            <label><?php _e("Tag:", "maya-api"); ?></label>
            <input class="" type="text" name="tag">
         </p>
         <p>
            <label for="user_id">Select User:</label>
            <select name="user_id" id="user_id">
               <?php foreach ($users as $user) { ?>
                  <option value="<?php echo $user->ID; ?>" <?php selected($user_id, $user->ID); ?>><?php echo $user->display_name; ?></option>
               <?php } ?>
            </select>
         </p>
         <input class="button button-primary" type="submit" value="<?php _e("Submit", "maya-api"); ?>">
      </form>
   <?php
  }
}

function add_custom_user_profile_fields($contactmethods) {
   $contactmethods['esim_uid'] = 'eSIM UID';

   return $contactmethods;
}
add_filter('user_contactmethods', 'add_custom_user_profile_fields');

function get_esim_details() {
   $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : get_current_user_id(); // Get the selected user's ID or the current user's ID
   $esim = get_user_meta($user_id, 'esim_uid', true);

   // Display the select dropdown list
   $users = get_users();
   ?>
   <br />
   <form method="post">
      <label for="user_id">Select User:</label>
      <select name="user_id" id="user_id">
         <?php foreach ($users as $user) { ?>
            <option value="<?php echo $user->ID; ?>" <?php selected($user_id, $user->ID); ?>><?php echo $user->display_name; ?></option>
         <?php } ?>
      </select>
      <input class="button button-primary" type="submit" value="Submit">
   </form>
   <?php

   if (!$esim) {
      echo "<p>No eSIM registered for user <b>{$user_id}</b></p>";
      return;
   }

   $resp = client("GET", "esim/{$esim}", null);

   if (empty($resp)) {
      return 'No data';
   }

   if ($resp == false) {
      echo "<p>No eSIM registered for user <b>{$user_id}</b></p>";
      return;
   }

   $resp = json_decode($resp->getContents(), true);
   if ($resp['status'] == 404) {
      echo "<p>No eSIM registered for user <b>{$user_id}</b></p>";
      return;
   }

   $esim = $resp['esim'];

   foreach($esim as $key => $value) {
      echo "<p>{$key}: {$value}</p>";
   }

   echo '<img src="'.(new QRCode)->render($esim['activation_code']).'" alt="QR Code" />';

   return;
}
