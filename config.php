<?php

// config.php - handles all initial configuration, does not produce any output

ob_start("ob_gzhandler");

include 'DB.php';
$DB = new DB(parse_ini_file('/home/http/private/db-eve.ini'));

$regions = json_decode(file_get_contents('emdr/regions.json'),true);
$emdrVersion = 1;

$defaultPrefs = array(
    'region'     => 10000002, 
    'marketMode' => 'sell'
);

$nav = array(
    'index.php' => array('home', 'LP Stores'),
	'about.php' => array('question-sign', 'About'),
	'faq.php'   => array('pencil', 'FAQ'),
	'mktScan.php' => array('barcode', 'Market Scanner'),
	'pref.php' => array('cog', 'Preferences'));

$projectStyle = '<strong>%s</strong>'; // how to style project names
    
// END USER CONFIGURATION

$time = explode(' ', microtime());
$start = $time[1] + $time[0];

$redis = new Redis();
$redis->connect('localhost', 6379) or die ("Could not connect to Redis server");

$page = basename($_SERVER['PHP_SELF']);

function testRegionInput($input) {
    global $defaultPrefs, $regions;
    if (isset($regions[$input])) {
        return (int)$input; }
    return $defaultPrefs['region'];
}    

function testMarketModeInput($input) {
    global $defaultPrefs;
    if ($input == 'sell' || $input == 'buy') {
        return $input; }
    return $defaultPrefs['marketMode'];
}    

$filterArgs = array(
    'region'    => array(
                'filter' => FILTER_CALLBACK,
                'options'=>'testRegionInput'),
    'marketMode' => array(
                'filter' => FILTER_CALLBACK,
                'options'=>'testMarketModeInput'),
);

if (isset($_COOKIE['preferences'])){
	$prefs = filter_var_array(unserialize($_COOKIE['preferences']), $filterArgs); }
else {
	$prefs = $defaultPrefs; }

?>