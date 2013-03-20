<?php include 'head.php'; ?>
 <div id='content-header'><h1>About</h1></div><div>
    <div class='container-fluid'>
    <div class='row-fluid'>

<h3>lpStore</h3>
<p><strong>lpStore</strong> is a web application useful in calculating the value of LP Store offers. It is written by <strong>Sable Blitzmann</strong> in PHP with a MySQL backend and using EMDR as a price data feed. The code is open-source at <a href='https://github.com/blitzmann/lpStore' target='_blank'>GitHub</a></p>
<h4>History</h4>
<p>I was first introduced to Factional Warfare when my corp joined it after a particularly horrible null sec campaign. I immediately saw the value in automatically calculating ISK/LP and went to work on <strong>lpStore</strong>'s prototype. It was a simple application, with a script set to periodically run to update prices from EVE Central. I used Ellatha's site and manually copy and pasted the LP store offers for my faction - I didn't bother with other stores. Development eventually halted as my corp disbanded and absorbed into our old alliance's main corp and we moved back to null and away from FW.</p>
<p>The null sec thing eventually faded away again, and many of us jumped right back into FW. I was interested in continuing where I left off; however I wanted to go bigger and on a much larger scale. I no longer wanted to provide data for my FW faction and for my friends only, but for all LP Store's in-game and the rest of the EVE community.</p>
<h4>Release</h4>
<p><strong>lpStore</strong> has not been released yet.</p>
<h3>lpDatabase</h3> 
<p><strong>lpDatabase</strong> is a completely separate project that <strong>lpStore</strong> is built upon. <strong>lpDatabase</strong> is simply the SQL database used for <strong>lpStore</strong>, and it is currently being maintained separately due to the scope of the project.</p>
<h4>History</h4>
<p>Unfortunately, CCP does not release LP Store data along with their static database dumps (I've yet to see a reason why). And so work set out collecting the data that I needed to upstart this project. I tried to search for this data online; unfortunately, it would seem that all the LP Store Database sites are outdated and proprietary (do not offer their SQL data to third parties). I finally came across usable data; thanks to Zanto Snix and his Google Doc (link), I was able to download a CSV file of the database. His work looked like a scrape from Ellatha's LP Store site, with more than enough errors, but it seemed to be more or less complete and it was definitely a start. From there, I went through a long and boring process of detecting and correcting most of the errors and typos (more details on how I achieved this can be found <a href='http://www.reddit.com/r/Eve/comments/17mgu4/isklp_calculator_for_the_various_lp_stores/c875wqv'>in this reddit comment</a>) before finally converting it to useable SQL data.</p>
<h4>Release</h4>
<p>I released the first version of the <strong>lpDatabase</strong> to the <a href='https://forums.eveonline.com/default.aspx?g=posts&find=unread&t=197115'>EVE Online Technology Lab forum</a>. <strong>lpDatabase</strong> is not considered complete until all LP Stores in EVE have had their contents verified for accuracy. As the underlying data seems to come from a site scrape of Ellatha, which itself is outdated and prone to errors, accuracy cannot be guaranteed.
</p>
<h3>Credits</h3>
<dl>
    <dt>Steve Ronuken</dt>
    <dd>Introducing me to EMDR and offering a sample of his EMDR script, Steve Ronuken is also extremely helpful on the forums. Not to mention he created the first LP Store using <strong>lpDatabase</strong> data. Visit his website for his LP Store and other useful applications: <a href='http://www.fuzzwork.co.uk/' target='_blank'>Fuzzwork Enterprises</a>.</dd>
    <dt>Zanto Snix</dt>
    <dd>Zanto compiled LP Store data into a <a href='https://forums.eveonline.com/default.aspx?g=posts&m=2172364#post2172364' target='_blank'>Google Docs document</a>, which gave me a great starting point for <strong>lpDatabase</strong>.
    <dt>Ellatha and Chruker.dk</dt>
    <dd>As outdated as they may be, <a href='http://www.ellatha.com/eve/LP_Stores.asp' target='_blank'>Ellatha's LP Store</a> and <a href='http://games.chruker.dk/eve_online/' target='_blank'>chruker.dk</a> were still valuable tools that I used while putting <strong>lpDatabase</strong> together.
    <dt>EMDR</dt>
    <dd><a href='https://eve-market-data-relay.readthedocs.org/en/latest/' target='_blank'>EMDR</a> feeds <strong>lpStore</strong> pricing data realtime as it's uploaded onto the network. It's probably the most inovative EVE project I've seen.</dd>
</dl>
<h3>Service</h3>
<p><strong>lpStore</strong> is hosted on my personal home server at the time being. Therefore, it is susceptible to outages, restarts, and general downtime. I cannot guarantee service. I often work on my server which tends to break things. If anyone is interested in allowing me to host <strong>lpStore</strong> on their servers, please contact me.</p>
<h3>Copyright Notice</h3>
<p>EVE Online, the EVE logo, EVE and all associated logos and designs are the intellectual property of CCP hf. All artwork, screenshots, characters, vehicles, storylines, world facts or other recognizable features of the intellectual property relating to these trademarks are likewise the intellectual property of CCP hf. EVE Online and the EVE logo are the registered trademarks of CCP hf. All rights are reserved worldwide. All other trademarks are the property of their respective owners. CCP hf. has granted permission to <strong>lpStore</strong> to use EVE Online and all associated logos and designs for promotional and information purposes on its website but does not endorse, and is not in any way affiliated with, <strong>lpStore</strong>. CCP is in no way responsible for the content on or functioning of this website, nor can it be liable for any damage arising from the use of this website.</p>
</div>

<?php include 'foot.php'; ?>