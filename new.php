<?php

include_once 'core.php';
$mc = JMathai\PhpMultiCurl\MultiCurl::getInstance();

//$ch = curl_init('');
$ch = curl_init('');
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
curl_setopt($ch, CURLOPT_REFERER, 'https://google.com/');
curl_setopt($ch, CURLOPT_USERAGENT, $refer);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
curl_setopt($ch, CURLOPT_TIMEOUT, 0);
curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
$page = $mc->addCurl($ch);


print_r($page->code);