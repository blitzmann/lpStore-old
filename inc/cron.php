<?php

// this would be so much easier once I migrate to unique offers


require dirname(__FILE__).'/../config.php';

// we only track data from The Forge region
$emdr->regionID = 10000002;

// todo: check to see if we can connect to EMDR (makes sure EMDR-py is running)

$marketMode = 'sell';
$time = time();

$req  = array();
$done = array();
$i = 0;

foreach ($DB->qa('
            SELECT      a.typeID, a.quantity, b.typeName, a.offerID
            FROM        lpOfferRequirements a
            INNER JOIN  invTypes b ON (b.typeID = a.typeID)
            INNER JOIN  lpOffers c ON (c.offerID = a.offerID)', array()) AS $item) {  
                $req[$item['offerID']][] = $item; }
$DB->beginTransaction(); 

foreach ($DB->qa('SELECT a.*, b.typeName FROM lpOffers a INNER JOIN  invTypes b ON (a.typeID = b.typeID)', array()) AS $offer) {

    // collect unique info - unneeded anymore?
    $reqArray = array();
    $array = array();
    if (isset($req[$offer['offerID']])){
        foreach ($req[$offer['offerID']] AS $item) {
            $reqArray[$item['typeID']] = $item['quantity']; }
        ksort($reqArray);
    }
    array_push($array, $offer['typeID'], $offer['quantity'], $offer['lpCost'], $offer['iskCost'], $reqArray);
    $md5 = md5(json_encode($array));
    
    if (!array_key_exists($md5, $done)) {
        
    
        // if this particular offer has not been processed yet, do it and add it.

        // calculations are the exact same as the main page, with useless info stripped
        
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
        
        // set required items
        if (isset($req[$offer['offerID']])){
            $reqItems = $reqContainer[$offer['offerID']];}
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
            }
        }
        
        // Blueprints are special fucking buterflies
        if (strstr($offer['typeName'], " Blueprint")) {
            $bpc       = true;
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
        }     
           
        if (!$cached) {
            $lp2isk = 0;
            $profit = 0; }
        else {
            $profit = ($price['orders'][$marketMode][0]*$offer['quantity'] - $totalCost);
            $lp2isk = $profit / $offer['lpCost']; 
        }
         
        $done[$md5] = $lp2isk;
        
    }
     
    $DB->ea("INSERT INTO lpTracking (offerID, time, lp2isk) VALUES (?, NOW(), ?)", array($offer['offerID'], $done[$md5]));
    
    print "$offer[offerID]\n";
}
$DB->commit();
print "Yay!";

?>


