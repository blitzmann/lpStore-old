<?php
require 'config.php';

$nav = array(
    'index.php' => array('home', 'LP Stores'),
	'about.php' => array('question-sign', 'About'),
	'faq.php'   => array('pencil', 'FAQ'),
	'mktScan.php' => array('barcode', 'Market Scanner'));

$page = basename($_SERVER['PHP_SELF']);
?>
<html>
<head>
    <title>lpStore - The place for up-to-date LP-ISK conversion</title>
	<link href="style/bootstrap.min.css" rel="stylesheet" />
	<link href="style/jquery-ui.min.css" rel="stylesheet" />
    <link href="style/lpStore.css" rel="stylesheet" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans+Condensed:300,700|Open+Sans' rel='stylesheet' type='text/css'>
    <link href="style/ColVis.css" rel="stylesheet" />


	<link href="datatables/media/css/jquery.dataTables.css" rel="stylesheet" />

    <script src="js/jquery-1.9.0.js"></script>
	<script src="js/jquery-ui-1.10.0.custom.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/lpStore.js"></script>

    <script src="js/jquery.dataTables.min.js"></script> 
    <script src="js/jquery.dataTables.ColVis.min.js"></script> 
    <script src="js/jquery.dataTables.FixedHeader.min.js"></script> 


</head>
<body>
<div id="header">
    <h1><a href="index.php">lpStore</a></h1>  
</div>
<div id='navigation'>
    <div id='sidebar-search'>
        <form action='index.php' method='get'>
        <input id='lpStore_search' type="text" placeholder="Corp Search..." />
        <button class='tip-right' type='submit'><i class='icon-search icon-white'></i></button>
        <input type='hidden' name='corpID' id='autoCorpID' value='' />
        </form>
    </div>    
    <ul style='display: block;'>
            <?php
            foreach ($nav AS $file => $label) {
                echo "
            <li".($page == $file ? " class='active'" : null)."><a href='".$file."'><i class='icon icon-".$label[0]."'></i><span>".$label[1]."</span></a></li>"; } 
            ?>
    </ul>
</div>
<div id='content'>