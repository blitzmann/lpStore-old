<?php

require 'config.php';

if (isset($_REQUEST['term'])) {
	$rs = $DB->qa('SELECT typeID, typeName FROM invTypes WHERE typeName LIKE ? ORDER BY typeName ASC limit 0,20', array('%'.$_REQUEST['term'].'%'));
	 
	// loop through each zipcode returned and format the response for jQuery
	$data = array();
	if ($rs && count($rs)) {
		foreach ($rs AS $row) {
			$data[] = array(
				'value' => $row['typeName'],
				'id' => $row['typeID']
			);
		}
	}
	 
	// jQuery wants JSON data
	echo json_encode($data);
	flush();
}
else if (isset($_REQUEST['lpName'])) {
	$rs = $DB->qa('SELECT a . * , b.typeName
FROM  `lpStore` a
INNER JOIN invTypes b ON ( a.typeID = b.typeID ) 
WHERE b.`typeName` LIKE  ?
GROUP BY a.typeID
ORDER BY  `b`.`typeName` ASC 
LIMIT 0 , 20', array('%'.$_REQUEST['lpName'].'%'));
	 
	// loop through each zipcode returned and format the response for jQuery
	$data = array();
	if ($rs && count($rs)) {
		foreach ($rs AS $row) {
			$data[] = array(
				'value' => $row['typeName'],
				'id' => $row['typeID']
			);
		}
	}
	 
	// jQuery wants JSON data
	echo json_encode($data);
	flush();
}
else if (isset($_REQUEST['corpName'])) {
	$rs = $DB->qa('SELECT a.*, b.itemName 
                FROM lpStore a 
                INNER JOIN invUniqueNames b ON (a.corporationID = b.itemID AND b.groupID = 2) 
                WHERE itemName LIKE ?
                GROUP BY a.corporationID 
                ORDER BY b.itemName ASC LIMIT 0,20', array('%'.$_REQUEST['corpName'].'%'));
	 
	// loop through each zipcode returned and format the response for jQuery
	$data = array();
	if ($rs && count($rs)) {
		foreach ($rs AS $row) {
			$data[] = array(
				'value' => $row['itemName'],
				'id' => $row['corporationID']
			);
		}
	}
	 
	// jQuery wants JSON data
	echo json_encode($data);
	flush();
}
else if (isset($_REQUEST['search'])) {
	$rs = $DB->qa('
SELECT a . * , b.typeName
    FROM  `lpStore` a
    INNER JOIN invTypes b ON ( a.typeID = b.typeID ) 
    WHERE b.`typeName` LIKE  ?
    GROUP BY a.typeID
    ORDER BY  `b`.`typeName` ASC 
UNION
LIMIT 0 , 20', array('%'.$_REQUEST['lpName'].'%'));
	 
	// loop through each zipcode returned and format the response for jQuery
	$data = array();
	if ($rs && count($rs)) {
		foreach ($rs AS $row) {
			$data[] = array(
				'value' => $row['typeName'],
				'id' => $row['typeID']
			);
		}
	}
	 
	// jQuery wants JSON data
	echo json_encode($data);
	flush();
}

