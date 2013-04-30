<?php
$title = 'Preferences';
require_once 'config.php';
$ack = '';

if (isset($_POST['region'])){
    if (isset($_POST['reset'])) {
        $prefs = filter_var_array($defaultPrefs, $filterArgs); }
    else {
        $prefs = filter_input_array(INPUT_POST, $filterArgs); }

	if (setcookie('preferences', serialize($prefs), time()+60*60*24*30)) {
        $ack = "<div class='alert alert-success'><strong>Success!</strong> Your preferences have been saved!</div>"; }
    else {
        $ack = "<div class='alert alert-danger'><strong>Error!</strong> There was an error saving your preferences.</div>"; }
}
 
require_once 'head.php'; 

?>
<div id='content-header'><h2>Preferences</h2></div>
<div class='container-fluid'>
<div class='row-fluid'>
    <?php echo $ack; ?>

    <form action='<?php echo $_SERVER['PHP_SELF']; ?>' method='post'>
    
    <fieldset>
        <legend>Default Region</legend>

        <p>Please select the default region you would like to use for market data</p>
        <select name='region'>
        <?php
        foreach ($regions AS $id => $name){
            echo "  <option value='".$id."'".($id == $prefs['region'] ? " selected" : null).">".$name."</option>\n"; }
        ?>
        </select>
    </fieldset>
    <fieldset>
        <legend>Default Corporation</legend>

        <p>Please select the default corporation you would like to be pre-selected in the corporation dropdown on the homepage.</p>
        <select name='defaultCorp'>
        <?php
        foreach ($DB->qa('
                    SELECT a.*, b.itemName 
                    FROM lpStore a 
                    INNER JOIN invUniqueNames b ON (a.corporationID = b.itemID AND b.groupID = 2) 
                    GROUP BY a.corporationID 
                    ORDER BY b.itemName ASC', array()) AS $corp){
            echo "  <option value='".$corp['corporationID']."'".($corp['corporationID'] == $prefs['defaultCorp'] ? " selected" : null).">".$corp['itemName']."</option>\n"; 
        }
        ?>
        </select>
        </select>
    </fieldset>
    <fieldset>
        <legend>Default Market Mode</legend>
        
        <label class="radio">
        <input name='marketMode' value='sell' type='radio'<?php echo ($prefs['marketMode'] == 'sell' ? " checked " : null) ?>/> <strong>Sell Orders</strong>: 
            Will use the average of the lowest 5% <strong>sell orders</strong> for pricing information. Useful if you are planning on putting up sell orders and have good market skills; usuallt results in the best margin.
        </label>
        <label class="radio">
        <input name='marketMode' value='buy' type='radio'<?php echo ($prefs['marketMode'] == 'buy' ? " checked " : null) ?>/> <strong>Buy Orders</strong>: 
            Will use the average of the highest 5% <strong>buy orders</strong> for pricing information. Useful if you want to just offload your goods at a trade hub (selling to buy order). <b>Note:</b> Required items and materials for blueprint manufacturing will still use sell orders for their calculations.
        </label>
    </fieldset>
    <div class="form-actions">
        <button type='submit' class='btn btn-primary'>Submit</button>
        <button type='submit' class='btn' name='reset'>Reset to Defaults</button>
    </div>
	</form>
</div>
</div>
<?php include 'foot.php'; ?>