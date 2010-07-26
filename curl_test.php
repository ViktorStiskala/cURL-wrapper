<?php

/**
 * cURL Test bootstrap file.
 *
 * @copyright  Copyright (c) 2009 Filip Procházka
 * @package    Vdsc
 */

require_once dirname(__FILE__) . '/Nette/loader.php';
require_once dirname(__FILE__) . '/cURL-wrapper/Curl.php';

use cURL\Curl;

// register wrapper safe for file manipulation
// SafeStream::register();


Nette\Debug::enable();
Nette\Debug::$strictMode = True;

Nette\Environment::loadConfig('config.ini');


function proxy(&$test)
{
// 	$test->addProxy('192.168.1.160', 3128);
}


if( true ){ // test 1: get
	$test = new Curl("http://curl.kdyby.org/prevodnik.asm.zdrojak");
// 	$test = new Curl("http://iskladka.cz/iCopy/downloadBalancer.php?file=1222561395_obava_bojov+cz.avi&ticket=pc1660-1265493063.25");


	echo "<hr>test 1: get ... init ok<hr>", "<h2>Setup:</h2>";

	proxy($test); // for debbuging at school
	dump($test);


	$response =  $test->get();


	echo "<h2>Headers:</h2>";
	dump($response->getHeaders());

	echo "<h2>Response:</h2>", "<pre>";
	var_dump($response->getResponse());
	echo "</pre>";
}


if( true ){ // test 2: post
	$test = new Curl("http://curl.kdyby.org/dump_post.php");

	echo "<hr>test 2: post ... init ok<hr>", "<h2>Setup:</h2>";

	proxy($test); // for debbuging at school
	dump($test);

	$response =  $test->post(array(
		'var1' => 'Lorem ipsum dot sit amet',
		'var2' => 0,
		'var3' => 23,
		'var4' => True,
		'var5' => False,
	));


	echo "<h2>Headers:</h2>";
	dump($response->getHeaders());

	echo "<h2>Response:</h2>", "<pre>";
	var_dump($response->getResponse());
	echo "</pre>";
}



if( true ){ // test 3: download
	$test = new Curl("http://curl.kdyby.org/prevodnik.asm.zdrojak");

	echo "<hr>test 3: download ... init ok<hr>", "<h2>Setup:</h2>";

	proxy($test); // for debbuging at school
	dump($test);


	$test->setDownloadFolder(realpath('./download'));

	$response =  $test->download();


	echo "<h2>Headers:</h2>";
	dump($response->getHeaders());

	echo "<h2>Response:</h2>", "<pre>";
	$fp = $response->openFile();
	var_dump(fread($fp, $response->getHeader('Content-Length')));
	fclose($fp);
	echo "</pre>";
}







// FOLLOW LOCATION URL BUILD TEST:

//$cycles = 1;
//
//foreach( array(
//	"http://www.kdyby.org/download/prevodnik.asm.zdrojak",
//	"www.kdyby.org/download/prevodnik.asm.zdrojak",
//	"kdyby.org/download/prevodnik.asm.zdrojak",
//	"/download/prevodnik.asm.zdrojak",
//	"download/prevodnik.asm.zdrojak",
//	"prevodnik.asm.zdrojak"
//) AS $response_headers['Location'] ){
//
//	foreach( array(
//	    "http://curl.kdyby.org/prevodnik.asm.zdrojak",
//	    "curl.kdyby.org/prevodnik.asm.zdrojak",
//	    "/prevodnik.asm.zdrojak",
//	    "prevodnik.asm.zdrojak"
//	) AS $this_url ){
//
//		try {
//
//			$this_getMethod = 'get';
//
//			echo "<h2>",$response_headers['Location']," vs ",$this_url,"</h2>";
//
//
//			$url = new Nette\Web\Uri($response_headers['Location']);
//
//			$lastUrl = new Nette\Web\Uri(/*$this->url*/$this_url);
//
//			if( empty($url->scheme) ){
//				if( empty($lastUrl->scheme) ){
//					throw new \Exception("Missign URL scheme!");
//				}
//
//				$url->scheme = $lastUrl->scheme;
//			}
//
//			if( empty($url->host) ){
//				if( empty($lastUrl->host) ){
//					throw new \Exception("Missign URL host!");
//				}
//
//				$url->host = $lastUrl->host;
//			}
//
//			if( empty($url->path) ){
//				$url->path = $lastUrl->path;
//			}
//
//
//			$response = dump($this_getMethod, (string)$url, array(), ++$cycles);
//
//		} catch( \Exception $e ){
//			dump("Exception ! ".$e->getMessage());
//		}
//
//		echo "<hr>";
//	}
//}
//
//exit("<hr>shut up");