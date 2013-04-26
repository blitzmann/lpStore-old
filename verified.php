<?php
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

<div id='content-header'><h2>Verified Corporations</h2></div>
    <div class='container-fluid'>
    <div class='row-fluid'>
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
foreach (array_chunk($factions, 4, true) AS $factionChunk) {
    echo "<div class='row-fluid'><div class='span2'>&nbsp;</div>";
        foreach ($factionChunk AS $id => $corpArray) {

        echo "\n\t\t<div class='span2'><h4>".$names[$id]."</h4><ul style='list-style:none;'>";
        foreach ($corpArray AS $id => $num) {
           echo  "<li".(array_key_exists($id, $verified) ? ($verified[$id] == 1 ? " style='background-color:lightgreen !important;'" : " style='background-color:#C4C4FF !important;'" ) : null ).">".$names[$id]." (".$num.")</li>"; 
        }
        echo "</div>\n";
    }
    echo "</div>";
}
    echo "</div>";
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
</div></div>

<?php include 'foot.php'; ?>
