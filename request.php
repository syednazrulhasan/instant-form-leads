<?php


// Replace with your own verify token
$verify_token = '123456';
 
// Check if the request is a GET request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Retrieve the parameters sent by Facebook
    $mode = isset($_GET['hub_mode']) ? $_GET['hub_mode'] : '';
    $challenge = isset($_GET['hub_challenge']) ? $_GET['hub_challenge'] : '';
    $verify_token_received = isset($_GET['hub_verify_token']) ? $_GET['hub_verify_token'] : '';
 
    // Verify the token matches what you expect
    if ($mode === 'subscribe' && $verify_token === $verify_token_received) {
        // Return the challenge code to complete the verification
        echo $challenge;
    } else {
        // If the verification fails
        echo 'Verification failed.';
    }
} else {
    // Handle other methods if needed
    echo 'Invalid request method.';
}


// Code to Read Leads Below

// Define the log file path
$log_file = 'webhook_log.txt';
 
// Open the log file in append mode
$log = fopen($log_file, 'a');
 
// Get the raw POST data
$raw_post_data = file_get_contents('php://input');
 
// Get additional information about the request
$request_headers = getallheaders();
 
$input_data = json_decode($raw_post_data, true);
$leadgen_id = $input_data['entry'][0]['changes'][0]['value']['leadgen_id'];  // Extract the Leadgen ID from webhook data
 
 
 
// Prepare the log entry
/*$log_entry = "------ " . date("Y-m-d H:i:s") . " ------\n";
$log_entry .= "Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
$log_entry .= "Request Headers:\n";
foreach ($request_headers as $header => $value) {
    $log_entry .= "$header: $value\n";
}*/
$log_entry = "------ " . date("Y-m-d H:i:s") . " ------\n";
$log_entry .= "Raw POST Data:$raw_post_data\n";
 
fwrite($log, $log_entry);
fclose($log);
 
// Optionally, send a response to indicate successful receipt of the data
http_response_code(200); // HTTP 200 OK
echo "Webhook received successfully.";
 
// Your Facebook App Credentials
$app_id         = '573232928556496';
$app_secret     = 'c961704609b4a4e72c8474518683f2d8';
$access_token   = 'EAAIJWjEUhdABO97oH267rcNmsiLRlVU77jMWzznMeZAp5ZBE7e1mVJTlJGauYZCluUjPDnogcH5GGjdo870ZCRughE4EY1XU1zMiSGqKY51dCQqipZBlPFtfS9pLBax7siHtthVnN0cBKRzFcAG5HfN7SMA799HZAXKij302ssG5N4mMeONo8wTv1mckPy';
 
 
 
 
 
 
// Function to check if the access token is still valid
function isAccessTokenValid($access_token, $app_id, $app_secret) {
    // URL to check the token validity
    $url = "https://graph.facebook.com/v17.0/debug_token?input_token={$access_token}&access_token={$app_id}|{$app_secret}";
 
    // Initialize cURL session
    $ch = curl_init();
 
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 
    // Execute the cURL request and get the response
    $response = curl_exec($ch);
 
    // Check for errors in the cURL request
    if(curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
        return false;
    }
 
    // Close the cURL session
    curl_close($ch);
 
    // Decode the JSON response
    $data = json_decode($response, true);
 
    // Check if the token is valid
    if (isset($data['data']) && isset($data['data']['is_valid']) && $data['data']['is_valid']) {
        return true;  // Token is valid
    } else {
        return false; // Token is not valid
    }
}
 
// Function to get a long-lived access token if the current one is expired
function refreshAccessToken($access_token, $app_id, $app_secret) {
    // If the token is valid, return it without refreshing
    if (isAccessTokenValid($access_token, $app_id, $app_secret)) {
        return $access_token;
    }
 
    // URL to refresh the access token
    $url = "https://graph.facebook.com/v23.0/oauth/access_token?grant_type=fb_exchange_token&client_id={$app_id}&client_secret={$app_secret}&fb_exchange_token={$access_token}";
 
    // Initialize cURL session
    $ch = curl_init();
 
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 
    // Execute the cURL request and get the response
    $response = curl_exec($ch);
 
    // Check for errors in the cURL request
    if(curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
        return null;
    }
 
    // Close the cURL session
    curl_close($ch);
 
    // Decode the JSON response
    $data = json_decode($response, true);
 
    // Check if the response contains a new access token
    if (isset($data['access_token'])) {
        return $data['access_token']; // Return the new long-lived access token
    } else {
        echo "Error refreshing access token.";
        return null;
    }
}
 
// If the access token is expired, refresh it
if ($access_token) {
    $access_token = refreshAccessToken($access_token, $app_id, $app_secret);
    if (!$access_token) {
        exit('Failed to refresh access token.');
    }
}
 
 
 
 
 
// Graph API URL to fetch the specific lead data
$graph_url = "https://graph.facebook.com/v17.0/{$leadgen_id}?access_token={$access_token}";
 
// Initialize cURL session
$ch = curl_init();
 
// Set cURL options
curl_setopt($ch, CURLOPT_URL, $graph_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 
// Execute the cURL request and get the response
$response = curl_exec($ch);
 
// Check for errors in the cURL request
if(curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
 
// Close the cURL session
curl_close($ch);
 
// Decode the JSON response
$data = json_decode($response, true);
 
// Prepare the final data with lead details and log it
$final_data = [];
 
if (isset($data['id'])) {
    $final_data['lead_id'] = $data['id'];
    $final_data['created_time'] = $data['created_time'];
 
    // Loop through the field responses (e.g., name, email, etc.)
    if (isset($data['field_data'])) {
        $fields = [];
        foreach ($data['field_data'] as $field) {
            $fields[$field['name']] = $field['values'][0];  // key-value pair
        }
        $final_data['fields'] = $fields;
 
        // Log the key-value pairs to the log file
        $log_entry = "\n------ " . date("Y-m-d H:i:s") . " ------\n";
        $log_entry .= "Lead ID: " . $final_data['lead_id'] . "\n";
        $log_entry .= "Created Time: " . $final_data['created_time'] . "\n";
        $log_entry .= "Form Fields:\n";
 
 
        foreach ($fields as $key => $value) {
            $log_entry .= "$key: $value\n";
 
        }
        $log_entry .= "-----------------------------------------\n";
        // Open log file again to append the detailed log
        $log_file = 'webhook_log.txt';
        $log = fopen($log_file, 'a');
        fwrite($log, $log_entry);
        fclose($log);
    }
 
} else {
    $final_data['error'] = "No leads found or error in fetching data.";
}
 
// Output the final JSON response
header('Content-Type: application/json');
echo json_encode($final_data, JSON_PRETTY_PRINT);
?>