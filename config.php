<?php

ob_start("ob_gzhandler");

include 'DB.php';
$DB = new DB(parse_ini_file('/home/http/private/db-eve.ini'));

$regions = json_decode(file_get_contents('inc/regions.json'),true);

$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$start = $time;

$memcache = new Memcache;
$memcache->connect('localhost', 11211) or die ("Could not connect to Memcache server");

?>