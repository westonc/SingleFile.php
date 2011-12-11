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
