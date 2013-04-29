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
<div>
<a href="http://apache.org">
  <img src="<?php echo BASE_PATH; ?>img/badge-apache-80x15.png"
    width="80" height="15" border="0" alt="Powered by Apache"
    title="Powered by Apache 2.2" />
</a>
<a href="http://mariadb.org">
  <img src="<?php echo BASE_PATH; ?>img/badge-mariadb-80x15.png"
    width="80" height="15" border="0" alt="Powered by MariaDB"
    title="Powered by MariaDB" />
</a>
<a href="http://php.net">
  <img src="<?php echo BASE_PATH; ?>img/badge-php-80x15.png"
    width="80" height="15" border="0" alt="Powered by PHP"
    title="Powered by PHP" />
</a>
<a href="http://redis.io">
  <img src="<?php echo BASE_PATH; ?>img/badge-redis-80x15.png"
    width="80" height="15" border="0" alt="Powered by Redis"
    title="Powered by Redis" />
</a>
</div>
</div>
</body>
</html>
<?php exit; ?>
