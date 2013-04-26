<?php

class lpOffers {
    private $DB;
    public  $offerID;
	public  $EMDR;
	
	public $array = array(
		'properties' => array(),
		'requiredItems' => array(),
		'manufacturing' => null,
	);	
    
    function __construct ($DB, $EMDR, $initCorp = false) {
	    $this->DB      = $DB;
		$this->emdr    = $EMDR;
        $this->corpID  = $initCorp;
		/*
		if (corp) {
			fetch required items for all offers.
		}*/
    }
	
		
	
}

?>	