<?php

function main() {
	global $argv;
	if($argv[2])
		inlineify($argv[1],$argv[2]);
	else if($argv[1])
		echo inlineify($argv[1],'return');
	else
		echo "usage: \n singlefile <src> <destination ";
	print "\n";
}

/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~ */

function tok_str($t) { return is_array($t) ? $t[1] : $t; }
function tok_type($t) { return is_array($t) ? $t[0] : $t; }
function tok_name($t) { return is_array($t) ? token_name($t[0]) : $t; }

function tok_rep($t) {
	$tokstr = trim($tokstr = tok_str($t)) ? $tokstr : '(whitespc)';
	$tokname = tok_name($t);
	return "[TOKEN: $tokstr ($tokname)]";
}

function tok_is() {
	$args = func_get_args();
	$t = array_shift($args);
	$type = tok_type($t);
	foreach($args as $is)
		if($type === $is) 
			return true;
	return false;
}

function inlineify($src,$dest,$strip_t_open_and_close=false) {
	_log("\n-- CALL: inlineify($src,$dest,$strip_t_open_and_close) --");
	if(!($srcstr = get_file_str($src)))
		return null;

	$tokens = token_get_all($srcstr);
	$pTokStrAcc = array(); //processed token string accumulator

	$token = current($tokens);	

	if($strip_t_open_and_close) {
		_log("\nconsume through first open...");
		consume_through_t_open($tokens);
		$token = current($tokens);
		_log("\nconsumed through first open.");
	} else
		_log("\ndon't consume through first open");

	while($token) {
		_log("\n",tok_rep($token));
		if(is_array($token)) {
			if($token[0] == T_CLOSE) {
				$pTokStrAcc[] = handle_close($tokens,$strip_t_open_and_close);
			} else if(tok_is($token,T_INCLUDE,T_REQUIRE))
				$pTokStrAcc[] = include_as_inline_str($tokens);
			else
				$pTokStrAcc[] = $token[1];
		} else //is_string($token) 
			$pTokStrAcc[] = $token;
		$token = next($tokens);
	}
	$rv = implode(null,$pTokStrAcc);	
	_log("\n-- RETURN: inlineify($src) --");
	return ($dest == 'return') ? $rv : file_put_contents($dest,$rv);
}

function consume_through_t_open(&$tokens) {
	_log("\n-- CALL: consume_through_t_open(\$tokens) --");
	$t = current($tokens);
	while(tok_is($t,T_INLINE_HTML)) {
		_log("\n",tok_rep($t));
		$t = next($tokens);
	}
	_log("\nconsumed_through: ",tok_rep($t));
	$t = next($tokens);
	_log("\nnow at (pre-return): ",tok_rep($t));
	_log("\n-- RETURN: consume_through_t_open() --");
}

function handle_close(&$tokens,$strip_t_open_and_close) {
	_log("\n-- CALL: handle_close(\$tokens,\$strip_t_open_and_close=$strip_t_open_and_close) --");
	$t = current($tokens);
	$rv = array($t[1]);
	$t = next($tokens);

	while(tok_is($t,T_INLINE_HTML)) {
		_log("\n",tok_rep($t));
		$rv[] = $t[1];
		$t = next($tokens);
	}

	if(!$t && $strip_t_open_and_close) {
		_log("\nend of token stream, strip true, return null");
		$rv = null;
	} else {
		_log("\strip: $strip_t_open_and_close, token: $t, return accumulated tokens ");
		$rv[] = $t[1];
		$rv = implode(null,$rv);
	}
	_log("\n-- RETURN: handle_close() --");
	return $rv;
}

function include_as_inline_str(&$tokens) {
	_log("\n--CALL: include_as_inline_str(\$tokens)--");
	$t = next($tokens); 

	while(tok_is($t,'(',T_WHITESPACE)) {
		_log("\n",tok_rep($t));
		$t = next($tokens); 
	}
	_log("\n",tok_rep($t));
	if(tok_is($t,T_CONSTANT_ENCAPSED_STRING)) 
		$filename = substr($t[1],1,-1);
	else
		die('fatal error: Badly formed include, expecting T_STRING of some sort');

	$t = next($tokens);
	while(tok_is($t,')',T_WHITESPACE)) {
		_log("\n",tok_rep($t));
		$t = next($tokens);
	}
	if($t != ';') 
		die('fatal error: Badly formed include, expecting closing ) or T_WHITESPACE followed by terminating ;');

	_log("\nfilename: $filename");
	if($filestr = inlineify($filename,'return',true)) 
		$rv = "/*# include('$filename') #*/\n$filestr\n/*# end include $filename #*/\n";
	else
		$rv = null;
	_log("\n--RETURN: include_as_inline_str()--");
	return $rv;
}

function init_PATH() {
	global $PATH;
	$PATH = array();

	$path = explode(':',get_include_path());
	foreach($path as $p) {
		if($p != '.') 
			$PATH[$p] = 1;
	}
}
init_PATH();

function get_file_str($filename) {
	_log("\n--CALL: get_file_str($filename)--");
	global $PATH;
	$srcstr = null;

	if(file_exists($filename)) {
		_log("\n\t$filename visible from cwd");
		$srcstr = file_get_contents($filename);
		if(basename($filename) != $filename) {
			$PATH[dirname($filename)] = 1;
			_log("\n\t",dirname($filename)," added to PATH");
		}
	} else {
		foreach(array_keys($PATH) as $path) {
			$f = "$path/$filename";
			_log("\n\t checking $path for $filename ($f)");
			if(file_exists($f)) {
				$srcstr = file_get_contents($f);
				_log("\n\t FOUND!");
				break;
			}	
			_log("\n\t nope.");
		}
	}

	if(trim($srcstr) == '') 
		$srcstr = null;
	_log("\n--RETURN: get_file_str() = ",strlen($srcstr)," bytes");
	return $srcstr;
}

function _log() {
	//$args = func_get_args();
	//echo implode(null,$args);
}


main();

