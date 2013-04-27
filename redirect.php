<?php
require_once 'config.php';
if ($corpID = filter_input(INPUT_POST, 'corpID', FILTER_VALIDATE_INT)) { 
    header("Location: ".BASE_PATH."corp/".$corpID);
    exit;
}
header("Location: ".BASE_PATH); // default: send to index