<?php

include 'DB.php';
$DB = new DB(parse_ini_file('/home/http/private/db-eve.ini'));

$verified = array();
//Corps that have been verified:
foreach ($DB->qa("SELECT * FROM `lpVerified`", array()) AS $corp) {
    $verified[$corp['corporationID']] = $corp['verification']; }

$factionName = array (
    500001 => 'Caldari State',
    500002 => 'Minmatar Republic',
    500003 => 'Amarr Empire',
    500004 => 'Gallente Federation'
);    
    
$factions = array (
    500001 => array(),
    500002 => array(),
    500003 => array(),
    500004 => array()
);
?>

<html>
<head>
    <title>lpStore - The place for up-to-date LP-ISK conversion</title>
    
	<link href="style/bootstrap.min.css" rel="stylesheet" />
	<link href="style/jquery-ui.min.css" rel="stylesheet" />
	<link href="datatables/media/css/jquery.dataTables.css" rel="stylesheet" />

    <script src="js/jquery-1.9.0.js"></script>
	<script src="js/jquery-ui-1.10.0.custom.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="tablesorter/jquery.tablesorter.js"></script> 
        <script src="datatables/media/js/jquery.dataTables.js"></script> 


</head>
<body>

<div class="container-fluid">
  <div class="row-fluid">
  <div class='span2'>&nbsp;</div>
<?php
    
foreach ($DB->qa("
    SELECT a.*, b.factionID, c.itemName, count(*) AS num
    FROM `lpStore` a 
    INNER JOIN crpNPCCorporations b ON (b.corporationID = a.corporationID) 
    INNER JOIN invUniqueNames c ON (a.corporationID = c.itemID AND c.groupID = 2)
    GROUP BY a.corporationID 
    ORDER BY c.itemName ASC", array()) AS $corp){
    if (!array_key_exists($corp['factionID'], $factions)){
        continue; }
        
    $faction[$corp['factionID']][] = "<li".(array_key_exists($corp['corporationID'], $verified) ? ($verified[$corp['corporationID']] == 1 ? " style='background-color:lightgreen !important;'" : " style='background-color:#C4C4FF !important;'" ) : null )."><a target='_blank' href='debug/index.php?corp=".$corp['corporationID']."'>".$corp['itemName']." (".$corp['num'].")</a></li>";
}

foreach ($faction AS $id => $corps) {
    echo " <div class='span2'><h4>".$factionName[$id]." (".count($corps).")</h4><ul>";
    foreach ($corps AS $corp) {
        echo $corp; }
    echo "</div>";
}
    
$corps = array(
    500001 => array (
        'military' => array(
            
        ),
        'industry' => array(
            
        ),
        'exploration' => array(
            
        )
    ),
    500002 => array (
        'military' => array(
            
        ),
        'industry' => array(
            
        ),
        'exploration' => array(
            
        )
    ),
    500003 => array (
        'military' => array(
            
        ),
        'industry' => array(
            
        ),
        'exploration' => array(
            
        )
    ),
    500004 => array (
        'military' => array(
            
        ),
        'industry' => array(
            
        ),
        'exploration' => array(
            
        )
    )
);

?>