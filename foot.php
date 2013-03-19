</div>
</div>
<div id='footer'>
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
