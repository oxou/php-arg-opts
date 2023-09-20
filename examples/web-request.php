<?php

// Copyleft Nurudin Imsirovic <github.com/oxou>
//
// This is a sample CLI utility to make a web request.
// It uses arg-opts library to handle the arguments.
//
// Created: 2023-09-20 03:35 AM
// Updated: 2023-09-20 05:02 AM

// Inform the user they don't have cURL installed.
if (!extension_loaded("curl")) {
    echo "You need the curl extension.\n";
    echo "https://www.php.net/manual/en/curl.installation.php\n";
    exit(1);
}

require "../lib.php";

$cli_name = "web-request";

function cli_usage($exit = -1) {
    global $cli_name;
    echo "$cli_name usage:\n";
    echo "  --hostname        Example: google.com\n";
    echo "  --port            Port number (example: 8080)\n";
    echo "  --path            Path name (example: /?q=test)\n";
    echo "  --request         Request type (example: POST)\n";
    echo "  --post-fields     Post parameters\n";
    echo "  --use-http        Set scheme to 'http' (insecure)\n";
    echo "  --user-agent      Browser User Agent\n";
    echo "  --log-file        Log output to a file\n";
    echo "  --get-header      Get response headers\n";
    echo "  --get-info        Get cURL information\n";
    echo "  --http-header     Specify file to use HTTP Request Headers from\n";
    echo "  --get-user-agent  Print the default User Agent and quit\n";

    if ($exit > -1)
        exit($exit);
}

$args = arg_opts($argv);

// Print help if no arguments given
if (sizeof($args) == 0) {
    cli_usage(1);
}

// Alias function for strlen and trim
function empty2($v) {
    if (is_array($v))
        return sizeof($v) == 0;
    return strlen(trim((string) $v)) == 0;
}

// Argument fixation (string)
function arg_fix_str(&$arg, $default_value) {
    if (empty2($arg))
        $arg = $default_value;
}

// Argument fixation (int)
function arg_fix_int(&$arg, $default_value) {
    if (!ctype_digit((string) $arg))
        $arg = $default_value;
}

$default_ua = "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/117.0";

$hostname       = $args["hostname"]       ?? null;
$port           = $args["port"]           ?? 443;
$path           = $args["path"]           ?? "/";
$request        = $args["request"]        ?? "GET";
$post_fields    = $args["post-fields"]    ?? null;
$use_http       = $args["use-http"]       ?? false;
$user_agent     = $args["user-agent"]     ?? $default_ua;
$log_file       = $args["log-file"]       ?? null;
$get_header     = $args["get-header"]     ?? false;
$get_info       = $args["get-info"]       ?? false;
$http_header    = $args["http-header"]    ?? null;
$get_user_agent = $args["get-user-agent"] ?? false;


// Here we check that the parameter is set
// at all as we don't care about its value

// Sanitize specific arguments so we can fail early.
arg_fix_str($hostname,       null);
arg_fix_int($port,           443);
arg_fix_str($path,           '/');
arg_fix_str($request,        "GET");
arg_fix_str($post_fields,    null);
arg_fix_int($use_http,       false);
arg_fix_str($user_agent,     $default_ua);
arg_fix_str($log_file,       null);
arg_fix_int($get_header,     false);
arg_fix_int($get_info,       false);
arg_fix_str($http_header,    null);
arg_fix_int($get_user_agent, false);

if ($get_user_agent) {
    echo $default_ua;
    exit(0);
}

// Check that the required arguments are here.
if (is_null($hostname) || strlen($hostname) == 0) {
    echo "Please specify the hostname\n\n";
    cli_usage(1);
}

// Try and load HTTP Request Headers from a file
$http_header_array = [];
$http_header_ok = 1;

if ($http_header === null)
    $http_header_ok = 0;

// Resource exists
if ($http_header_ok && !file_exists($http_header))
    $http_header_ok = 0;

// Resource is not a file
if ($http_header_ok && !is_file($http_header)) {
    echo "$cli_tool: Tried to load $http_header but it's not a file\n";
    $http_header_ok = 0;
}

// Resource is not readable
if ($http_header_ok && !is_readable($http_header)) {
    echo "$cli_tool: Tried to load $http_header but is not readable\n";
    $http_header_ok = 0;
}

// Resource is empty
if ($http_header_ok && filesize($http_header) == 0) {
    echo "$cli_tool: Tried to load $http_header but it's empty\n";
    $http_header_ok = 0;
}

if ($http_header_ok) {
    // All checks for HTTP Request Headers have passed,
    // load the file and construct an array of headers.
    $http_header_array = file_get_contents($http_header);
    // Convert CRLF line endings to LF
    $http_header_array = str_replace(
        ["\r\n", "\r"],
        ["\n", ''],
        $http_header_array
    );
    $http_header_array = explode("\n", $http_header_array);
}

// If the checks fail, we make default HTTP Request Headers
// based on the properties we've given to the tool.
if (!$http_header_ok) {
    $http_header = [
        "Host: $hostname",
        "User-Agent: $user_agent",
        "Connection: close"
    ];
}

$scheme = $use_http ? "http" : "https";

// Fix path if it doesn't begin with /
if ($path[0] != '/')
    $path = '/' . $path;

$curl_handle = curl_init();
curl_setopt_array($curl_handle, array(
    CURLOPT_URL            => $scheme . "://" . $hostname . $path,
    CURLOPT_PORT           => $port,
    CURLOPT_HEADER         => (bool) $get_header,
    CURLOPT_HTTPHEADER     => $http_header_array,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_USERAGENT      => $user_agent,
    CURLOPT_RETURNTRANSFER => true
));

if ($post_fields !== null)
    curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $post_fields);

$exec = curl_exec($curl_handle);
$info = curl_getinfo($curl_handle);
curl_close($curl_handle);

if ($get_info) {
    print_r($info);
    exit(0);
} else {
    echo $exec;
    exit(0);
}
