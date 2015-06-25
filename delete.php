<?php
require_once 'vendor/autoload.php';

use \Util as u;

/**
 * see also :
 *    https://github.com/apricoton/gmail_cleaner/
 *    https://developers.google.com/gmail/api/quickstart/php
 **/

define('APPLICATION_NAME',   'Gmail Cleaner Delete');
define('CREDENTIALS_PATH',   'gmail_cleaner.json');
define('CLIENT_SECRET_PATH', 'client_secret.json');
define('MAX_RESULTS',        100);
define('SCOPES',             implode(' ',[Google_Service_Gmail::MAIL_GOOGLE_COM]));

if (count($argv) <= 1) {
    print_usage();
    exit;
}

$queries = [];
foreach ($argv as $num => $option) {
    if ($num == 1) {
        $target_email = $option;
    }
    if ($num >= 2) {
        $queries[] = $option;
    }
}

$query_string = implode(' ', $queries);

$params = [
    'maxResults' => MAX_RESULTS,
    'q'          => $query_string,
];

$client = getClient();
$gmail = new Google_Service_Gmail($client);

$page_token = null;
$threads = array();
$pagenum = 1;
do {
    try {
        u::pl('Start: Page ' . $pagenum);
        if ($page_token) {
            $params['pageToken'] = $page_token;
        }
        
        $result = $gmail->users_threads->listUsersThreads($target_email, $params);
        if ($result->getThreads()) {
            $threads = array_merge($threads, $result->getThreads());
            $page_token = $result->getNextPageToken();
        }
        u::pl(count($threads) . ' threads');
        $pagenum++;
    } catch (Exception $e) {
        u::pl($e->getMessage());
    }
} while ($page_token);

if (!count($threads)) {
    u::pl('0 threads found.');
    exit;
}

u::pl('Delete ' . count($threads) . ' threads?');
u::pl('y: Yes delete');
u::pl('d: Dry run');
u::pl('N: exit');
u::p('(y/d/N) : ');
$ask = strtolower(trim(fgets(STDIN)));

if ($ask != 'y' && $ask != 'd') {
    exit;
}

$i = 1;
$total = count($threads);
if ($ask == 'd') {
    foreach ($threads as $thread) {
        // Dry Run
        try {
            $data = $gmail->users_threads->get($target_email, $thread->getId());
            u::pl('(' . $i . '/' . $total . ') Delete(Dry run) : '. $data->getId());
        } catch (Exception $e) {
            u::pl($e->getMessage());
        }
        $i++;
    }
} else {
    foreach ($threads as $thread) {
        // Delete
        try {
            $gmail->users_threads->delete($target_email, $thread->getId());
            u::pl('(' . $i . '/' . $total . ') Delete : '. $thread->getId());
        } catch (Exception $e) {
            u::pl($e->getMessage());
        }
        $i++;
    }
}
exit;


function print_usage()
{
    u::pl('Usage: php ' . basename(__FILE__) . ' YOUR@EMAIL.ADDRESS QUERIES...');
}

function expandHomeDirectory($path)
{
    $homeDirectory = getenv('HOME');
    if (empty($homeDirectory)) {
        $homeDirectory = getenv("HOMEDRIVE") . getenv("HOMEPATH");
    }
    return str_replace('~', realpath($homeDirectory), $path);
}

function getClient()
{
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
