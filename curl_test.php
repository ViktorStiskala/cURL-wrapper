<?php

/**
 * cURL Test bootstrap file.
 *
 * @copyright  Copyright (c) 2009 Filip Procházka
 * @package    Vdsc
 */


require_once dirname(__FILE__) . '/libs/Curl.php';

// register wrapper safe for file manipulation
// SafeStream::register();


function proxy(&$test)
{
// 	$test->addProxy('192.168.1.160', 3128);
}


if( true ){ // test 1: get
	$test = new Curl("http://curl.kdyby.org/prevodnik.asm.zdrojak");
// 	$test = new Curl("http://iskladka.cz/iCopy/downloadBalancer.php?file=1222561395_obava_bojov+cz.avi&ticket=pc1660-1265493063.25");


	echo "<hr>test 1: get ... init ok<hr>", "<h2>Setup:</h2>";

	proxy($test); // for debbuging at school
	var_dump($test);


	$response =  $test->get();


	echo "<h2>Headers:</h2>";
	var_dump($response->getHeaders());

	echo "<h2>Response:</h2>";
	var_dump($response->getResponse());
}


if( true ){ // test 2: post
	$test = new Curl("http://curl.kdyby.org/dump_post.php");

	echo "<hr>test 2: post ... init ok<hr>", "<h2>Setup:</h2>";

	proxy($test); // for debbuging at school
	var_dump($test);

	$response =  $test->post(array(
		'var1' => 'Lorem ipsum dot sit amet',
		'var2' => 0,
		'var3' => 23,
		'var4' => True,
		'var5' => False,
	));


	echo "<h2>Headers:</h2>";
	var_dump($response->getHeaders());

	echo "<h2>Response:</h2>";
	var_dump($response->getResponse());
}



if( true ){ // test 3: download
	$test = new Curl("http://curl.kdyby.org/prevodnik.asm.zdrojak");

	echo "<hr>test 3: download ... init ok<hr>", "<h2>Setup:</h2>";

	proxy($test); // for debbuging at school
	var_dump($test);


	$test->setDownloadFolder(realpath('./download'));

	$response =  $test->download();


	echo "<h2>Headers:</h2>";
	var_dump($response->getHeaders());

	echo "<h2>Response:</h2>";
	$fp = $response->openFile();
	var_dump(fread($fp, $response->getHeader('Content-Length')));
	fclose($fp);
}




