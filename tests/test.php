<?php

if($out = trim(`php ../SingleFile.php srctop.php target.php`))
	die("FAILURE: \n$out\n");

if($out = trim(`diff ref_target.php target.php`))
	die("FAILURE: ref_target.php and target.php are different\n$out\n");

if($out = trim(`php target.php > target.out`))
	die("FAILURE: target.php doesn't run correctly\n$out\n");

if($out = trim(`diff ref_target.out target.out`))
	die("FAILURE: ref_target.out and target.out are different\n$out\n");

echo "Pass.\n";
	
	

