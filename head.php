<?php require_once 'config.php'; ?>
<html>
<head>
    <title>lpStore<?php echo (isset($title) ? " - $title" : null); ?></title>
	<link href="style/bootstrap.min.css" rel="stylesheet" />
	<link href="style/jquery-ui.min.css" rel="stylesheet" />
    <link href="style/lpStore.css" rel="stylesheet" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans+Condensed:300,700|Open+Sans' rel='stylesheet' type='text/css'>
    <link href="style/ColVis.css" rel="stylesheet" />


    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
	<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.0/jquery-ui.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/lpStore.js"></script>

    <script src="js/jquery.dataTables.min.js"></script> 
    <script src="js/jquery.dataTables.ColVis.min.js"></script> 
    <script src="js/jquery.dataTables.FixedHeader.min.js"></script> 
</head>
<?php ob_flush(); ?>
<body>
<div id="header">
    <h1><a href="index.php">lpStore</a><small>beta</small></h1>  
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