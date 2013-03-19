<?php include 'head.php'; ?>
 <div id='content-header'><h1>FAQ</h1></div><div>
    <div class='container-fluid'>
    <div class='row-fluid'>
<h3>What do the colored indictors mean?</h3>
<p>The colored indicators are there to act as a guide on how fresh the pricing data is. As <strong>lpStore</strong> relies soley on the EMDR network for pricing; this data may or may not be up to date. At this time, <strong>lpStore</strong> is not collecting historic market data for items, opting to just collect information on what's currently on the market. As <strong>lpStore</strong> does not collect historic data, it would be easy for someone to manipulate the market to the point where the actual value of the item is skewed, even if the market data is less than 24 hours old. If possible, double check the market history in Jita</p>
<dl id='faq-dl'>
    <dt><span class='label label-default'>&nbsp;</span> No pricing available</dt>
        <dd>This means that <strong>lpStore</strong> doesn't have a price on this item. This is caused by very low-volume items on the market (read: a few items are sold per year). There are a few items on the market for which there just isn't any supply/demand for, and so the sell/buy orders screen just sits empty for who knows how long. Potential ISK can be made here as you can set the market price, but be wary of the demand here.</dd>
    <dt><span class='label label-important'>&nbsp;</span> Data is more than 72 hours old</dt>
        <dd>The last time this item was cached was over 72 hours ago. Again, this is usually caused by item which aren't viewed frequently and thus don't have their market data uploaded to the EMDR network.</dd>
    <dt><span class='label label-warning'>&nbsp;</span> Data is 24-72 hours old</dt>
        <dd>Same as above, but within a shorter time frame.</dd>
    <dt><span class='label label-success'>&nbsp;</span> Data is less than 24 hours old</dt>
        <dd>It's usually safe to believe the pricing information on these items, however always be cautious.</dd>
    <dt><span class='label label-info'>&nbsp;</span> BPC - calculations are different!</dt>
        <dd>A blue indicator simply means that the item is a BPC which are not available on the market and hence do not have any market data. The calculations for these items are different (see below), so please take that into account.</dd>        
</dl>
<h3>How is pricing calculated</h3>
<p>tl;dr: <strong>lpStore</strong> takes the 5th percentile of the total volume and averages the price.</p>
<p>Before going into this, I feel it is neccessary to note where I got the idea from. Originally having planned on using EVE Central for market data, I decided to subscribe to the EMDR feed after a discussion with fellow developer <strong>Steve Ronuken</strong>. He provided his <a href='https://github.com/fuzzysteve/EMDR-Consumer/blob/master/sell.pl'>Perl script</a> for subscribing to the feed, and I ported it to Python. Currently, it's more or less a 1:1 port, however I do plan on expanding on it in the future. Credit should go to <strong>Steve Ronuken</strong> for how the prices are pull and stored.</p>
<p>Market data is pushed down onto the <strong>lpStore</strong> server. We throw out the history data (which I hope to incorporate in the future), and throw out buy orders (we are only interested in sell orders). The total volume is calculated and stored, as well as the timestamp the report was generated. <strong>lpStore</strong> then virtually "buys" 5% of the total volume, and averages out the cost it took to do so. This value is then stored, and this is the value that is used in calculations. Taking the 5th percentile helps to avoid outliers - that one guy that puts his sell order at 20% lower than the rest of the market. In this situation, he would be a small part of that 5%, and the average hopefully won't be affected by much.
<h3>How is ISK/LP calculated</h3>
<p>ISK/LP is calculated by simply taking the total cost for the item, subtracting that from value of the item in question (taking into account the quantity), and dividing my the amount of LP is costs</p>
<math xmlns='http://www.w3.org/1998/Math/MathML'>
 <mfrac>
  <mrow>
   <mrow>
    <mi>sellValue</mi>
    <mo>*</mo>
    <mi>quantity</mi>
   </mrow>
   <mo>-</mo>
   <mrow>
    <mo>(</mo>
    <mrow>
     <mi>iskCost</mi>
     <mo>+</mo>
     <mi>reqitemsCost</mi>
    </mrow>
    <mo>)</mo>
   </mrow>
  </mrow>
  <mi>lpCost</mi>
 </mfrac>
</math>
where <tt>reqitemsCost</tt> is the amount of ISK needed to buy all required items
<h5>Blueprint Copies</h5>
<p>Due to their nature, blueprint copy offers are more difficult to calculate for as they are not sold on the market. <strong>lpStore</strong> finds what the blueprint makes (and how many runs) and uses that as the <tt>sellValue</tt> and <tt>quantity</tt>, everything else is calculated the same. To get a better representation of ISK/LP, <strong>lpStore</strong> also uses the cost of the materials; however, we do not apply possible skills that would reduct costs at this time.
<h3>What is Total Volume</h3>
<p>Total Volume is currently the total quantity, or volume, of items for sale on the market in The Forge region. This is handy to guage demand / market absorbtion of the item. If an item has a total volume of 2, that means there are only 2 of that item being sold. It's generally recommended to stick with items with high volumen and are known to move (Datacores, Navy Cap Boosters, Ammo), however if you know what you're doing you can find a niche in an item with little movement and potentially make much more.</p>
</div>
<?php include 'foot.php'; ?>
