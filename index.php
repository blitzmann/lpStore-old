<?php 

require 'head.php'; 

$verified = array();
//Corps that have been verified:
foreach ($DB->qa("SELECT * FROM `lpVerified`", array()) AS $corp) {
    $verified[] = $corp['corporationID']; }

// TODO: split all this off into a class/seperate function (return standardized json output)?

if (isset($_GET['corpID'])) {
    try {
        $corpID     = filter_input(INPUT_GET, 'corpID', FILTER_VALIDATE_INT);
        $regionID   = $prefs['region'];
		$marketMode = $prefs['marketMode'];

        if (!$regionID || !array_key_exists($regionID, $regions)) {
            $regionID = 10000002; } //default to Jita
            
        $name = $DB->q1('SELECT itemName FROM invUniqueNames WHERE itemID = ? AND groupID = 2', array($corpID));
       
        if ($name == false) {
            throw new Exception('Corporation ID does not exist within database.'); }
        
        $offers = $DB->qa('
            SELECT      a.*, b.typeName  
            FROM        lpStore a
            INNER JOIN  invTypes b ON (a.typeID = b.typeID)
            WHERE       a.corporationID = ?
            ORDER BY    a.`lpCost`, a.iskCost, b.typeName', array($corpID));
            
        echo "
            <div id='content-header'><h2>".$name." <small>".$regions[$regionID]." - ".ucfirst($marketMode)." Orders</small></h2></div><div>
                <div class='container-fluid'>
                <div class='row-fluid'>
                    <table class='table table-bordered table-condensed table-striped' id='lpOffers'>
                        <colgroup>
                            <col span='4' class='lpData' />
                            <col span='4' class='lpCalculated' />
                        </colgroup>
                        <thead> 
                        <tr><th>LP Offer</th><th>LP Cost</th><th>ISK Cost</th><th>Required Items</th><th>Total Costs</th><th>Profit</th><th>Total Vol</th><th>LP/ISK</th></thead><tbody> ";
                
        /*
            Get required items for all of store's offers. This prevents costly 
            loop later (in main offer loop), which shaved off about 1 sec 
            exec time for the large LP Stores
        */
        $reqContainer = array();
        foreach ($DB->qa('
            SELECT      a.typeID, a.quantity, b.typeName, a.parentID
            FROM        lpRequiredItems a
            INNER JOIN  invTypes b ON (b.typeID = a.typeID)
            INNER JOIN  lpStore c ON (c.storeID = a.parentID)
            WHERE       c.corporationID = ?', array($corpID)) AS $item) {  
                $reqContainer[$item['parentID']][] = $item; }                
        
        foreach ($offers AS $offer){
            $totalCost = $offer['iskCost'];
            $req       = array(); // array that holds name of reuired items
            $cached    = false;   // flag
            $bpc       = false;   // flag
            
            // get pricing info on item
            if ($price = $memcache->get('emdr-'.$emdrVersion.'-'.$regionID.'-'.$offer['typeID'])) {
                $cached   = true;
                $price    = json_decode($price, true);
                $timeDiff = (time() - $price['orders']['generatedAt'])/60/60; // time difference in hours
            }

            if ($cached) {
                if (round($timeDiff) >= 100) {
                    $label = ">99"; }
                else if ($timeDiff < 1) {
                    $label = "<1"; }
                else {
                    $label = round($timeDiff); }
                
                if ($timeDiff > 72) {
                    $fresh = array('important', 'Price data is over 72 hours old'); }
                else if ($timeDiff > 24) {
                    $fresh = array('warning', 'Price data is over 24 hours old'); }
                else {
                    $fresh = array('success', 'Price data is under 24 hours old'); }
            }
            else {
                $label = "N/A";
                $fresh = array('default', 'Price has not yet been cached'); }
            
            // set required items
            if (isset($reqContainer[$offer['storeID']])){
                $reqItems = $reqContainer[$offer['storeID']];}
            else {
                $reqItems = array(); }

            $manReqItems = array();
                        
            // Get pricing info for required items
            // todo: should we parse pricing data for required items with no cache of their own?
            foreach ($reqItems AS $reqItem) {
                if ($reqItem['quantity'] <= 0) {
                    continue; }

				if ($rprice = $memcache->get('emdr-'.$emdrVersion.'-'.$regionID.'-'.$reqItem['typeID'])) {
					$rprice = json_decode($rprice, true);
					$totalCost = $totalCost + ($rprice['orders']['sell'][0] * $reqItem['quantity']);
					array_push($req, $reqItem['quantity']." x ".$reqItem['typeName']); 
				}
            }
            
            // Blueprints are special fucking buterflies
            if (strstr($offer['typeName'], " Blueprint")) {
                $bpc       = true;
                $name      = "1 x ".$offer['typeName']." Copy (".$offer['quantity']." run".($offer['quantity'] > 1 ? "s" : null).")"; 
                $label     = 'BP';
                $fresh     = array('info', 'Calculating with manufactured item');
                $manTypeID = $DB->q1(' 
                    SELECT      ProductTypeID  
                    FROM        invBlueprintTypes
                    WHERE       blueprintTypeID = ?', array($offer['typeID']));
                
                // set pricing info as the manufactured item
                if ($price = $memcache->get('emdr-'.$emdrVersion.'-'.$regionID.'-'.$manTypeID)) {
                    $price  = json_decode($price, true); 
                    $cached = true;
                }
                
                // Here we merge bill of materials for blueprints (remembering to multiple qnt with # of BPC runs)
                $manReqItems = array_merge(
                    // Get minerals needed
                    $DB->qa('
                        SELECT t.typeID,
                               t.typeName,
                               ROUND(greatest(0,sum(t.quantity)) * (1 + (b.wasteFactor / 100))) * ? AS quantity
                        FROM
                          (SELECT invTypes.typeid typeID,
                                  invTypes.typeName typeName,
                                  quantity
                           FROM invTypes,
                                invTypeMaterials,
                                invBlueprintTypes
                           WHERE invTypeMaterials.materialTypeID=invTypes.typeID
                            AND invBlueprintTypes.productTypeID = invTypeMaterials.typeID

                             AND invTypeMaterials.TypeID=?
                           UNION 
                           SELECT invTypes.typeid typeid,
                                        invTypes.typeName name,
                                        invTypeMaterials.quantity*r.quantity*-1 quantity
                           FROM invTypes,
                                invTypeMaterials,
                                ramTypeRequirements r,
                                invBlueprintTypes bt
                           WHERE invTypeMaterials.materialTypeID=invTypes.typeID
                             AND invTypeMaterials.TypeID =r.requiredTypeID
                             AND r.typeID = bt.blueprintTypeID
                             AND r.activityID = 1
                             AND bt.productTypeID=?
                             AND r.recycle=1) t
                        INNER JOIN invBlueprintTypes b ON (b.productTypeID = ?)

                        GROUP BY t.typeid,
                                 t.typeName', array($offer['quantity'], $manTypeID, $manTypeID, $manTypeID)),
                    // Get extra items needed
                    $DB->qa('
                        SELECT t.typeID AS    typeID,
                            t.typeName AS     typeName,
                            (r.quantity * ?) AS quantity
                        FROM ramTypeRequirements r,
                            invTypes t,
                            invBlueprintTypes bt,
                            invGroups g
                        WHERE r.requiredTypeID = t.typeID
                        AND r.typeID = bt.blueprintTypeID
                        AND r.activityID = 1
                        AND bt.productTypeID = ?
                        AND g.categoryID != 16
                        AND t.groupID = g.groupID', array($offer['quantity'], $manTypeID))); // append material needs to req items      
                
				foreach ($manReqItems AS $reqItem) {
                    if ($reqItem['quantity'] <= 0) {
                        continue; }

                    if ($rprice = $memcache->get('emdr-'.$emdrVersion.'-'.$regionID.'-'.$reqItem['typeID'])) {
						$rprice = json_decode($rprice, true);
						$totalCost = $totalCost + ($rprice['orders']['sell'][0] * $reqItem['quantity']);
					}
				}
                // one day this will display them all, but for now, just note that materials are needed...
				array_push($req, "Manufacturing Materials");
            }     
            else {
                $name = $offer['quantity']." x ".$offer['typeName']; }
               
            if (!$cached) {
                $lp2isk = 'N/A';
                $profit = 0; }
            else {
                $profit = ($price['orders'][$marketMode][0]*$offer['quantity'] - $totalCost);
                $lp2isk = $profit / $offer['lpCost']; 
            }

            echo "
            <tr id='lp-$offer[typeID]'>
                <td><span class='label label-".$fresh[0]." pop lp-label' 
                    data-content='".($fresh[0] !== 'default' ? "Reported: ".round($timeDiff)."h ago<br />Price: ".number_format($price['orders']['sell'][0], 2) : null)."' 
                    rel='popover' 
                    data-placement='right' 
                    data-original-title='".$fresh[1]."' 
                    data-trigger='hover'>".$label."</span> ".$name."</td>
                <td>".number_format($offer['lpCost'])."</td>
                <td>".number_format($offer['iskCost'])."</td>
                <td>".implode("<br />", $req)."&nbsp;</td>
                <td>".number_format($totalCost)."</td>
                <td>".number_format($profit)."</td>
                <td>".$price['orders']['sell'][1]."</td>
                <td>".(is_numeric($lp2isk)? number_format($lp2isk) : $lp2isk)."</td>
            </tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
		echo "<h3>Error</h3>
        <p>".$e->getMessage()."</p>";
	}	
}
else if (isset($_GET['storeID'])) {

    try {
        $storeID = filter_input(INPUT_GET, 'storeID', FILTER_VALIDATE_INT);
        $name = $DB->q1('SELECT itemName FROM invUniqueNames WHERE itemID = ? AND groupID = 2', array($corpID));

        $offer = $DB->qa('
            SELECT      a.*, b.typeName  
            FROM        lpStore a
            INNER JOIN  invTypes b ON (a.typeID = b.typeID)
            WHERE       a.storeID = ?
            LIMIT 0, 1', array($storeID));
        
        if ($offer == false) {
            throw new Exception('Corporation ID does not exist within database.'); }
        
        $reqItems = $DB->qa('
                SELECT      a.typeID, a.quantity, b.typeName
                FROM        lpRequiredItems a
                INNER JOIN  invTypes b ON (b.typeID = a.typeID)
                WHERE       a.parentID = ?', array($offer['storeID']));

        $manReqItems = array();
            
        echo "<div id='content-header'><h1>$offer[typeName]</h1></div><div id='breadcrumb'></div>
    <div class='container-fluid'>
    <div class='row-fluid'></div><table><tr><th>Qty</th><th>Item</th><th>Price</th><th>Subtotal</th></tr>";            
        // Blueprints are special fucking buterflies
        if (strstr($offer['typeName'], " Blueprint")) {
            $manItem   = str_replace(' Blueprint', '', $offer['typeName']); // Get item that the BPC makes
            $manTypeID = $DB->q1(' 
                SELECT      typeID  
                FROM        invTypes
                WHERE       typeName LIKE ?', array($manItem));
            
            // set pricing info as the manufactured item
            if (!($price = $memcache->get('emdr-'.$emdrVersion.'-'.$regionID.'-'.$manTypeID))) {
                $price = json_encode(array('orders' => array('generatedAt' => 0, 'sell'=>array(0,0), 'buy'=>array(0,0)))); }
            $price = json_decode($price, true);
            
            // Here we merge bill of materials for blueprints (remembering to multiple qnt with # of BPC runs)
            $manReqItems = array_merge(
                // Get minerals needed
                $DB->qa('
                    SELECT t.typeID,
                           t.typeName,
                           ROUND(greatest(0,sum(t.quantity)) * (1 + (b.wasteFactor / 100))) * ? AS quantity
                    FROM
                      (SELECT invTypes.typeid typeID,
                              invTypes.typeName typeName,
                              quantity
                       FROM invTypes,
                            invTypeMaterials,
                            invBlueprintTypes
                       WHERE invTypeMaterials.materialTypeID=invTypes.typeID
                        AND invBlueprintTypes.productTypeID = invTypeMaterials.typeID

                         AND invTypeMaterials.TypeID=?
                       UNION 
                       SELECT invTypes.typeid typeid,
                                    invTypes.typeName name,
                                    invTypeMaterials.quantity*r.quantity*-1 quantity
                       FROM invTypes,
                            invTypeMaterials,
                            ramTypeRequirements r,
                            invBlueprintTypes bt
                       WHERE invTypeMaterials.materialTypeID=invTypes.typeID
                         AND invTypeMaterials.TypeID =r.requiredTypeID
                         AND r.typeID = bt.blueprintTypeID
                         AND r.activityID = 1
                         AND bt.productTypeID=?
                         AND r.recycle=1) t
                    INNER JOIN invBlueprintTypes b ON (b.productTypeID = ?)

                    GROUP BY t.typeid,
                             t.typeName', array($offer['quantity'], $manTypeID, $manTypeID, $manTypeID)),
                // Get extra items needed
                $DB->qa('
                    SELECT t.typeID AS    typeID,
                        t.typeName AS     typeName,
                        (r.quantity * ?) AS quantity
                    FROM ramTypeRequirements r,
                        invTypes t,
                        invBlueprintTypes bt,
                        invGroups g
                    WHERE r.requiredTypeID = t.typeID
                    AND r.typeID = bt.blueprintTypeID
                    AND r.activityID = 1
                    AND bt.productTypeID = ?
                    AND g.categoryID != 16
                    AND t.groupID = g.groupID', array($offer['quantity'], $manTypeID))); // append material needs to req items      
            
           foreach ($manReqItems AS $reqItem) {
                if ($reqItem['quantity'] <= 0) {
                    continue; }

                if (!($rprice = $memcache->get('emdr-'.$emdrVersion.'-'.$regionID.'-'.$reqItem['typeID']))) {
                    $rprice = json_encode(array(0,0,0,0)); }

                $rprice = json_decode($rprice);
                $totalCost = $totalCost + ($rprice[0] * $reqItem['quantity']);
            }
            array_push($req, "Manufacturing Materials");
        }     
        else {
            $name = $offer['quantity']." x ".$offer['typeName']; }
    } catch (Exception $e) {
		echo "<h3>Error</h3>
        <p>".$e->getMessage()."</p>";
	}	
}
else if (isset($_GET['lpTypeID'])){

	$name = $DB->q1('SELECT typeName FROM invTypes WHERE typeID = ?', array($_GET['lpTypeID']));
	$offers = $DB->qa('
		SELECT a . * , b.itemName AS corpName, `reqItems`
		FROM  `lpStore` a
		INNER JOIN invUniqueNames b ON ( a.corporationID = b.itemID AND b.groupID =2 ) 
		LEFT JOIN
		(
		  SELECT  z.parentID,
			 GROUP_CONCAT( CONCAT( z.quantity,  " x ", y.typeName ) ) AS  `reqItems`
		  FROM lpRequiredItems z
		  INNER JOIN invTypes y ON (z.typeID = y.typeID)
		  GROUP BY z.parentID
		) c ON ( a.storeID = c.parentID )
		WHERE a.typeID = ?
		ORDER BY  `a`.`lpCost`, corpName ASC
		', array($_GET['lpTypeID']));
		
	echo "<h3>$name</h3><table class='table table-bordered table-condensed table-striped'>
	<tr><th>Corporation</th><th>LP Cost</th><th>ISK Cost</th><th>Required Items</th>";
    if (!($price = $memcache->get('emdr-'.$emdrVersion.'-'.$regionID.'-'.$_GET['lpTypeID']))) {
            $price = json_encode(array('orders' => array('generatedAt' => 0, 'sell'=>array(0,0), 'buy'=>array(0,0)))); }
    $price = json_decode($price, true);

	foreach ($offers AS $offer){
        
        
		echo "<tr>
        <td><small>(<a href='#' class='remove-row'>remove</a>)</small> $offer[corpName]<small>  (sID: $offer[storeID])</small>".(in_array($offer['corporationID'], $verified) ? "<span style='float:right;color:green;'><b>Verified</b></span>" : null)."</td>
        <td>".number_format($offer['lpCost'])."</td>
        <td>".number_format($offer['iskCost'])."</td>
        <td>".implode("<br />", explode(',', $offer['reqItems']))."</td>
        </tr>";
	}
	echo "</table>";
}
else {
    $totalCorps = $DB->q1("SELECT COUNT( DISTINCT corporationID )  FROM `lpStore`", array());
    $largest    = $DB->qa("SELECT COUNT(typeID) AS cnt, b.itemName FROM `lpStore` a INNER JOIN invUniqueNames b ON ( a.corporationID = b.itemID AND b.groupID =2 ) GROUP BY a.corporationID ORDER BY cnt DESC LIMIT 0,1", array());
    $smallest   = $DB->qa("SELECT COUNT(typeID) AS cnt, b.itemName FROM `lpStore` a INNER JOIN invUniqueNames b ON ( a.corporationID = b.itemID AND b.groupID =2 ) GROUP BY a.corporationID ORDER BY cnt ASC LIMIT 0,1", array());

    $totalVerified = count($verified);
	echo "
    <div id='content-header'><h2>Welcome to lpStore!</h2></div>
    <div id='breadcrumb'></div>
    <div class='container-fluid'>
    <div class='row-fluid'>
        <noscript><div class='alert'> 
            <button type='button' class='close' data-dismiss='alert'>&times;</button>
            <strong>Warning!</strong> This site is best used with JavaScript enabled! Please <a href='http://www.enable-javascript.com/'>enable it via your browser settings</a>.
        </div></noscript>
    <p>Please select the desired corporation to the left to browse their store. Please note that there is no guarentee of data - the LP Store may have missing, incomplete, or additional data that does not correctly represent the actual data. This is because <strong>lpStore</strong> operates on user-collected data, and much of it was outdated before I got to it. I'm currently in the process of manually verifying the LP Stores throughout the game, however, it is time consuming, and will take many months before I verify them all. Up-to-date information out the backend data can be found <a href='https://forums.eveonline.com/default.aspx?g=posts&t=197115'>at this EVE-ONLINE forum thread.</a></p>
    <ul>
    <li><a href='verified.php'>Verified</a> $totalVerified / $totalCorps corps (".round(($totalVerified / $totalCorps) * 100, 2)."%)</li>
    <li>Largest LP Store: ".$largest[0]['itemName']." (".$largest[0]['cnt']." LP Offers)</li>
    <li>Smallest LP Store: ".$smallest[0]['itemName']." (".$smallest[0]['cnt']." LP Offers)</li>
    </ul>
    ";?>
    <form style='' class="form-horizontal" id="corpForm" name="corpForm" action="index.php"  method="get">

        <select class='xlarge' name='corpID'>
        <?php
            $results = $DB->qa('
                SELECT a.*, b.itemName 
                FROM lpStore a 
                INNER JOIN invUniqueNames b ON (a.corporationID = b.itemID AND b.groupID = 2) 
                GROUP BY a.corporationID 
                ORDER BY b.itemName ASC', array());
        
            foreach ($results AS $corp){
                echo "<option ".(in_array($corp['corporationID'], $verified) ? " style='background-color:lightgreen !important;'" : null )." value='".$corp['corporationID']."'>".$corp['itemName']."</option>";
            }
        ?>
        </select>

        <input class="btn btn-mini btn-primary" type="submit" value="Go!" />
	</form>
    <?php 
}

echo "</div>";
include 'foot.php';

?>