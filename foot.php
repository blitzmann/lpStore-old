<div id='footer'>
&copy; 2013 by Sable Blitzmann, Ryan Holmes | 
<a href='https://github.com/blitzmann/lpStore'>gitHub:</a> <?php echo `git log -1 --pretty=format:'%h' --abbrev-commit`; ?> | 
Eve Related Materials are <a href='http://blitzmann.it.cx/lpStore/about.php'>Property Of CCP Games</a> |  
<?php 
$time = explode(' ', microtime());
$finish = $time[1] + $time[0];
$total_time = round(($finish - $start), 4);
echo $total_time.'s'; 
?>

</div>
</div>
</body>
</html>
