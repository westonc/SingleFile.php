SingleFile.php 
==============

Recursively processes and inlines `require` and `include` directives to produce a single PHP file.

Usage
-----

`php SingleFile.php <top level source file> <target file>`

Omitting the target file will write to stdout.

For example, consider the following PHP files (present in the test directory of this project rep):

### srctop.php

	<?php
	
	include('A.php');
	include('dir/B.php');

	A();
	B();
	C();

### A.php

	<?php
	
	function A() {
		echo "A\n";
	}

### dir/B.php

	<?php
	
	require('C.php');
	
	function B() {
		echo "B\n";
	}

### dir/C.php

	<?php
	
	function C() {
		echo "C\n";
	}

Running `php SingleFile.php srctop.php` will produce:

	<?php
	
	/*# include('A.php') #*/
	
	function A() {
		echo "A\n";
	}
	
	/*# end include A.php #*/
	
	/*# include('dir/B.php') #*/
	
	/*# include('C.php') #*/
	
	function C() {
		echo "C\n";
	}
	
	/*# end include C.php #*/
	
	
	function B() {
		echo "B\n";
	}
	
	/*# end include dir/B.php #*/
	
	
	A();
	B();
	C();


