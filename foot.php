</div>
</div>
<div id='footer'>
&copy; 2013 by Sable Blitzmann, Ryan Holmes | <a href='https://github.com/blitzmann/lpStore'>gitHub:</a> <?php echo shell_exec("git log -1 --pretty=format:'%h' --abbrev-commit"); ?> | <a href='http://blitzmann.it.cx/lpStore/about.php'>All Eve Related Materials are Property Of CCP Games</a> |  
<?php echo "perf: ".round(memory_get_usage()/1024/1024,2)."MBc / ".round(memory_get_peak_usage()/1024/1024,2)."MBp"; $time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$finish = $time;
$total_time = round(($finish - $start), 4);
echo '; '.$total_time.'s'; ?>

</div>
</div>
</body>
</html>
