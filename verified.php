<?php
$title = 'Corporations';
include 'head.php';

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
    
$factions = array ();
?>

<div id='content-header'><h2>Corporations <small>Legend: 
    <ul class='legend'>
        <li><span>Corp Name - unverified</span></li>
        <li class='vManual'><span>Corp Name - manual verification</span></li>
        <li class='vRelation'><span>Corp Name - relational verification</span></li>
    </ul></small></h2>
</div>
<div class='container-fluid'>
<?php

$names = array();
    
foreach ($DB->qa("
    SELECT a.corporationID, b.factionID, c.itemName AS corpName, d.itemName AS facName, count(*) AS num
    FROM `lpStore` a 
    INNER JOIN crpNPCCorporations b ON (b.corporationID = a.corporationID) 
    INNER JOIN invUniqueNames c ON (a.corporationID = c.itemID AND c.groupID = 2)
    INNER JOIN invUniqueNames d ON (b.factionID = d.itemID)
    GROUP BY a.corporationID 
    ORDER BY c.itemName ASC", array()) AS $corp){
        
    $factions[$corp['factionID']][$corp['corporationID']] = $corp['num'];
    
    if (!array_key_exists($corp['factionID'], $names)){
        $names[$corp['factionID']] = $corp['facName']; }
    if (!array_key_exists($corp['corporationID'], $names)){
        $names[$corp['corporationID']] = $corp['corpName']; }
}
uasort($factions, create_function('$a, $b', 'return bccomp(count($b), count($a));'));

//var_dump($factions);
foreach (array_chunk($factions, 5, true) AS $factionChunk) {
    echo "<div class='row-fluid'><div class='span1'>&nbsp;</div>";
        foreach ($factionChunk AS $id => $corpArray) {

        echo "\n\t\t<div class='span2'><h3>".$names[$id]."</h3><ul class='corpList'>";
        foreach ($corpArray AS $id => $num) {
           echo  "<li".(array_key_exists($id, $verified) ? ($verified[$id] == 1 ? " class='vManual'" : " class='vRelation'" ) : null )."><a href='".BASE_PATH."corp/".$id."'>".$names[$id]."</a></li>"; 
        }
        echo "</div>\n";
    }
    echo "</div>";
}
echo "</div>";

include 'foot.php'; ?>
