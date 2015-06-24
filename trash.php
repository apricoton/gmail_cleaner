<?php
require_once 'vendor/autoload.php';

use \Util as u;

/**
 * see also : https://developers.google.com/gmail/api/quickstart/php
 */

define('APPLICATION_NAME',   'Gmail Cleaner Trash');
define('CREDENTIALS_PATH',   'gmail_cleaner.json');
define('CLIENT_SECRET_PATH', 'client_secret.json');
define('MAX_RESULTS',        100);
define('SCOPES',             implode(' ',[Google_Service_Gmail::MAIL_GOOGLE_COM]));

if (count($argv) < 2) {
    print_usage();
    exit;
}

$query_string = $argv[1];
$target_email = $argv[2];

$params = [
    'maxResults' => MAX_RESULTS,
    'q'          => $query_string,
];

$client = getClient();
$gmail = new Google_Service_Gmail($client);

$page_token = null;
$messages = array();
$pagenum = 1;
do {
    try {
        u::pl('Start: Page ' . $pagenum);
        if ($page_token) {
            $params['pageToken'] = $page_token;
        }
        
        $result = $gmail->users_messages->listUsersMessages($target_email, $params);
        if ($result->getMessages()) {
            $messages = array_merge($messages, $result->getMessages());
            $page_token = $result->getNextPageToken();
        }
        u::pl(count($messages) . ' mails');
        $pagenum++;
    } catch (Exception $e) {
        u::pl($e->getMessage());
    }
} while ($page_token);

u::p('Delete ' . count($messages) . ' mails(y/N) : ');
$ask = trim(fgets(STDIN));

if (strtolower($ask) != 'y') {
    exit;
}

foreach ($messages as $message) {
    $message_params = [
        'format' => 'minimal',
    ];
    try {
        $message = $gmail->users_messages->get($target_email, $message->getId(), $message_params);
        u::pl($message->getSnippet());
    } catch (Exception $e) {
        u::pl($e->getMessage());
    }
}
exit;




function print_usage() {
    u::pl('Usage: php ' . basename(__FILE__) . ' SEARCH_STRING YOUR@EMAIL.ADDRESS');
}

function expandHomeDirectory($path) {
    $homeDirectory = getenv('HOME');
    if (empty($homeDirectory)) {
        $homeDirectory = getenv("HOMEDRIVE") . getenv("HOMEPATH");
    }
    return str_replace('~', realpath($homeDirectory), $path);
}

function getClient() {
    $client = new Google_Client();
    $client->setApplicationName(APPLICATION_NAME);
    $client->setScopes(SCOPES);
    $client->setAuthConfigFile(CLIENT_SECRET_PATH);
    $client->setAccessType('offline');
    
    // Load previously authorized credentials from a file.
    $credentials_path = expandHomeDirectory(CREDENTIALS_PATH);
    
    if (file_exists($credentials_path)) {
        $access_token = file_get_contents($credentials_path);
    } else {
        // Request authorization from the user.
        $auth_url = $client->createAuthUrl();
        u::pl("Open the following link in your browser:\n" . $auth_url);
        u::pl('');
        u::pl('Enter verification code: ');
        $auth_code = trim(fgets(STDIN));
        
        // Exchange authorization code for an access token.
        $access_token = $client->authenticate($auth_code);
        
        // Store the credentials to disk.
        if(!file_exists(dirname($credentials_path))) {
            mkdir(dirname($credentials_path), 0700, true);
        }
        
        file_put_contents($credentials_path, $access_token);
        u::pl('Credentials saved to ' . $credentials_path);
    }
    
    $client->setAccessToken($access_token);
    
    // Refresh the token if it's expired.
    if ($client->isAccessTokenExpired()) {
        $client->refreshToken($client->getRefreshToken());
        file_put_contents($credentials_path, $client->getAccessToken());
    }
    return $client;
}
