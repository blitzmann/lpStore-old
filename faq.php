<?php require 'head.php'; ?>
 <div id='content-header'><h2>FAQ</h2></div>
    <div class='container-fluid'>
    <div class='row-fluid'>
<h3>What do the colored indictors mean?</h3>
<p>The colored indicators are there to act as a guide on how fresh the pricing data is. As <span class='project'>lpStore</span> relies solely on the EMDR network for pricing; this data may or may not be up to date. At this time, <span class='project'>lpStore</span> is not collecting historic market data for items, opting to just collect information on what's currently on the market. As <span class='project'>lpStore</span> does not collect historic data, it would be easy for someone to manipulate the market to the point where the actual value of the item is skewed, even if the market data is less than 24 hours old. If possible, double check the market history for the item. <b>Note:</b> # represents how old the data is in hours.</p>
<dl id='faq-dl'>
    <dt><span class='label label-default'>#</span> No pricing available</dt>
        <dd>This means that <span class='project'>lpStore</span> doesn't have a price on this item. This is caused by very low-volume items on the market (read: a few items are sold per year). There are a few items on the market for which there just isn't any supply/demand for, and so the sell/buy orders screen just sits empty for who knows how long. Potential ISK can be made here as you can set the market price, but be wary of the demand here.</dd>
    <dt><span class='label label-important'>#</span> Data is more than 72 hours old</dt>
        <dd>The last time this item was cached was over 72 hours ago. Again, this is usually caused by item which aren't viewed frequently and thus don't have their market data uploaded to the EMDR network.</dd>
    <dt><span class='label label-warning'>#</span> Data is 24-72 hours old</dt>
        <dd>Same as above, but within a shorter time frame.</dd>
    <dt><span class='label label-success'>#</span> Data is less than 24 hours old</dt>
        <dd>It's usually safe to believe the pricing information on these items, however always be cautious.</dd>
    <dt><span class='label label-info'>#</span> BPC - calculations are different!</dt>
        <dd>A blue indicator simply means that the item is a BPC which are not available on the market and hence do not have any market data. The calculations for these items are different (see below), so please take that into account.</dd>        
</dl>
<h3>How is pricing calculated</h3>
<p>tl;dr: <span class='project'>lpStore</span> takes the 5th percentile of the total volume and averages the price.</p>
<p>Before going into this, I feel it is necessary to note where the original algorithm comes from. Originally having planned on using EVE Central for market data, I decided to subscribe to the EMDR feed after a discussion with fellow developer <strong>Steve Ronuken</strong>. He provided his <a href='https://github.com/fuzzysteve/EMDR-Consumer/blob/master/sell.pl'>Perl script</a> for subscribing to the feed, and I ported it to Python. I started with a 1:1 port to python, however I have since been expanding upon it and will continue to do so in the future. Credit should go to Steve for how the prices are pull and stored.</p>
<p>Market data is pushed down onto the <span class='project'>lpStore</span> server. We throw out the history data (which I hope to incorporate in the future), and parse through the market orders. The total volume is calculated and stored, as well as the timestamp the report was generated. The EMDR script them simulated a 5% purchase of the total volume, and averages out the cost it took to do so. This value is then stored and this is the value that is used in calculations. Taking the 5th percentile helps to avoid outliers - that one guy that puts his sell order at 20% lower than the rest of the market. In this situation, he would be a small part of that 5%, and the average hopefully won't be affected by much.
<h3>How is ISK/LP calculated</h3>
<p>ISK/LP is calculated by simply taking the total cost (initial cost + cost of required items), subtracting that from value of the item in question (taking into account the quantity), and dividing by the amount of LP it costs.</p>
<h4>Blueprint Copies</h4>
<p>Due to their nature, blueprint copies are calculated slightly differently. The item that the blueprint manufactures substitutes all pricing information for the offer. The total cost also includes the materials needed to manufacture the item, however manufacturing materials are not yet affected by player skill (future feature). Otherwise, blueprint copies are calculated much the same as all other offers.
<h3>What is Total Volume</h3>
<p>Total Volume is currently the total quantity, or volume, of items for sale on the market. This is handy to guage demand / market absorbtion of the item. If an item has a total volume of 2, that means there are only 2 of that item being sold. It's generally recommended to stick with items with high volume and are known to move (Datacores, Navy Cap Boosters, Ammo), however if you know what you're doing you can find a niche in an item with little movement and potentially make much more.</p>
</div>
</div>
<?php include 'foot.php'; ?>
