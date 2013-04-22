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
            <div id='content-header'><h2>".$name." <small>".$regions[$regionID]." - ".ucfirst($marketMode)." Orders</small></h2></div>
                <div class='container-fluid'>
                <div class='row-fluid'>
                    <table class='table table-bordered table-condensed table-striped' id='lpOffers'>
                        <thead><tr><th>LP Offer</th><th>LP Cost</th><th>ISK Cost</th><th>Required Items</th><th>Total Costs</th><th>Profit</th><th>Total Vol</th><th>ISK/LP</th></thead>
                        <tbody>";
                
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
            if ($price = $emdr->get($offer['typeID'])) {
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

				if ($rprice = $emdr->get($reqItem['typeID'])) {
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
                if ($price = $emdr->get($manTypeID)) {
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

                    if ($rprice = $emdr->get($reqItem['typeID'])) {
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
                    data-content='".($fresh[0] !== 'default' ? "Reported: ".round($timeDiff)."h ago<br />Price: ".number_format($price['orders'][$marketMode][0], 2) : null)."' 
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
        <noscript>
            <div class='alert'> 
                <button type='button' class='close' data-dismiss='alert'>&times;</button>
                <strong>Warning!</strong> This site is best used with JavaScript enabled! Please <a href='http://www.enable-javascript.com/'>enable it via your browser settings</a>.
            </div>
        </noscript>
        <div class='span8'>
            <p>Please select the desired corporation to the right to browse their store. Green backgrounds indicate corporations that have had their LP Store Offers verified, and thus should represent data found in-game. Please note that there is no guarentee of the data - the LP Stores may have missing, incomplete, or additional data that does not correctly represent the actual data found in-game. This is because <span class='project'>lpStore</span> operates on user-collected data, much of which is outdated and needs to be verified. It's currently in the process of being verified, but this is an ongoing process. Up-to-date information out the backend data can be found <a href='https://forums.eveonline.com/default.aspx?g=posts&t=197115'>at this EVE-ONLINE forum thread.</a></p>
        </div>
        <div class='span2 offset1'>
            <form style='' id='corpForm' name='corpForm' action='index.php'  method='get'>
            <select class='large' style='width: 100%;' name='corpID'>";
                $results = $DB->qa('
                    SELECT a.*, b.itemName 
                    FROM lpStore a 
                    INNER JOIN invUniqueNames b ON (a.corporationID = b.itemID AND b.groupID = 2) 
                    GROUP BY a.corporationID 
                    ORDER BY b.itemName ASC', array());
            
                foreach ($results AS $corp){
                    echo "
                <option".
                (in_array($corp['corporationID'], $verified) ? " style='background-color:lightgreen !important;'" : null ).
                ($corp['corporationID'] == $prefs['defaultCorp'] ? " selected" : null).
                " value='".$corp['corporationID']."'>".$corp['itemName']."</option>";
                }
            echo "
            </select>
            <button type='submit' class='btn btn-block btn-primary'>Go!</button>
           </form>
        </div>
    </div>

    <div class='row-fluid'>
        <div class='span12'>
            <ul class='lpStats'>
                <li><strong>".$totalCorps."</strong> LP Stores</li>
                <li><strong>".round(($totalVerified / $totalCorps) * 100, 0)."%</strong> Stores Verified</li>
                <li><strong>".$largest[0]['cnt']."</strong> Largest Store</li>
                <li><strong>".$smallest[0]['cnt']."</strong> Smallest Store</li>
            </ul>
        </div>
    </div>"; 
}

echo "</div>";
include 'foot.php';

?>