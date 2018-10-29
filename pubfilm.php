<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

use voku\db\DB;
require_once 'vendor/autoload.php';
include_once 'cloudflare.php';

$db = DB::getInstance('mysql', 'movie', '', 'movies');

$mc = JMathai\PhpMultiCurl\MultiCurl::getInstance();

$url = 'http://pubfilmonline.net';
$c = curl_init();
curl_setopt($c, CURLOPT_URL, $url);
curl_setopt($c, CURLOPT_REFERER, 'http://pubfilmonline.net');
curl_setopt($c, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; CPU iPhone OS 5_0 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Version/5.1 Mobile/9A334 Safari/7534.48.3');

curl_setopt($c, CURLOPT_ENCODING, 'gzip');
curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($c, CURLOPT_RETURNTRANSFER, true);

curl_setopt($c, CURLOPT_COOKIEJAR, dirname(__FILE__).'/cookie.txt');
curl_setopt($c, CURLOPT_COOKIEFILE, dirname(__FILE__).'/cookie.txt');
$result = curl_exec($c);

$cf = new CloudflareSolver($url, $result);
if($cf->isValid()) {
    echo 'Waiting for ' . $cf->getTimeout()/1000 . ' seconds...' . "\r\n";
    usleep($cf->getTimeout() * 1000);
    curl_setopt($c, CURLOPT_URL, $cf->getSolvedUrl());
    $result = curl_exec($c);
}

curl_close($c);


$urls = [];
for ($i=1; $i<=140; $i++) { // 139(7.06.17)

    if ($i == 1) {
        $urls[] = 'http://pubfilmonline.net/movies/';
    } else {
        $urls[] = 'http://pubfilmonline.net/movies/page/' . $i . '/';
    }

}
$threads = 50;
$urls_pack = [];
for($i=0; $i<count($urls); $i=$i+$threads) {
    $urls_pack[] = array_slice($urls, $i, $threads, TRUE);
}

$urls_p = [];
$page = [];

foreach($urls_pack as $pack) { // 3

    foreach ($pack as $k => $url) { // 60

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        curl_setopt($ch, CURLOPT_REFERER, 'http://pubfilmonline.net/');
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; CPU iPhone OS 5_0 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Version/5.1 Mobile/9A334 Safari/7534.48.3');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__) . '/cookie.txt');
        $page[$k] = $mc->addCurl($ch);


    }

    foreach ($page as $p) { // 3*60

        preg_match_all("/<article id=\"post-[0-9]+\" class=\"item movies\">(.*)<\/article>/Us", $p->response, $result_array);
        foreach ($result_array[1] as $article) { // 3*60*30
            preg_match("/<a href=\"(.*)\"><img src=\".*\" alt=\"(.*)\"><\/a>/s", $article, $article_array);
            $urls_p[] = $article_array[1]; //  123
        }

    }
    $page = [];

}

$urls_pack = [];
for($i=0; $i<count($urls_p); $i=$i+$threads) {
    $urls_pack[] = array_slice($urls_p, $i, $threads, TRUE);
}



$page = [];
foreach($urls_pack as $pack) {

    foreach ($pack as $k => $url) {

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        curl_setopt($ch, CURLOPT_REFERER, 'http://pubfilmonline.net/');
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; CPU iPhone OS 5_0 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Version/5.1 Mobile/9A334 Safari/7534.48.3');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__) . '/cookie.txt');
        $page[$k] = $mc->addCurl($ch);


    }

    foreach ($page as $p) {

        if($p->code == '503') { continue; }

        $item = [];

        $result = $p->response;
        if(preg_match("/sources: {\"error\":\"You don't have permission to access this video.\"}/s", $result)==1 || preg_match("/sources: {\"error\":\"This video doesn't exist.\"}/s", $result)==1){
            $item['real_url'] = 'no video';
        } else {
            preg_match("/sources: \[{\"file\":\"(.*)\",\"type\":\"/Usi", $result, $url_array);
            $item['real_url'] = str_replace('\\', '', @$url_array[1]);
        }

        preg_match("/<input type=\"hidden\" name=\"idpost\" value=\"([0-9]+)\">/s", $result, $article_id_array);
        $item['id'] = @$article_id_array[1];

        preg_match("/<input type=\"hidden\" name=\"title\" value=\"(.*)\">/sU", $result, $article_array);
        $item['name'] = html_entity_decode(@$article_array[1]);

        preg_match("/(\(\d{4}\))/", @$item['name'], $name_array);
        $item['tmp_year'] = str_replace(array('(', ')'), '', @$name_array[1]);
        $item['clear_name'] = trim(str_replace(@$name_array[1], '', @$item['name']));

        if(@$item['id'] != null) {
            $insertArray = ['id' => @$item['id']];
            if (@$item['clear_name'] != null) {
                $insertArray['name'] = $item['clear_name'];
            }
            if (@$item['real_url'] != null) {
                $insertArray['url'] = $item['real_url'];
            }
            if (@$item['tmp_year'] != null) {
                $insertArray['tmp_year'] = $item['tmp_year'];
            }
            $db->insert('pubfilmonline', $insertArray);
        }



    }
    $page = [];

}




echo $mc->getSequence()->renderAscii();


exit;


$cu = curl_init();
curl_setopt($cu, CURLOPT_URL, 'http://www.omdbapi.com/?t="'.urlencode($item['clear_name']).'"&y="'.$item['tmp_year'].'"&apikey=');
curl_setopt($cu, CURLOPT_REFERER, 'http://www.omdbapi.com/');
curl_setopt($cu, CURLOPT_RETURNTRANSFER, true);
curl_setopt($cu, CURLOPT_HEADER, false);
$result = curl_exec($cu);
curl_close($cu);

$imdb = json_decode($result, true);

$item['genre'] = @$imdb['Genre'];
$item['real_year'] = @$imdb['Year'];
$item['poster'] = @$imdb['Poster'];
$item['rating'] = @$imdb['imdbRating'];

if(@$item['genre'] != null) { $insertArray['genre'] = $item['genre']; }
if(@$item['real_year'] != null) { $insertArray['year'] = $item['real_year']; }
if(@$item['poster'] != null) { $insertArray['poster'] = $item['poster']; }
if(@$item['rating'] != null) { $insertArray['rating'] = $item['rating']; }

// TODO imdb update to db
//////////////////
exit;

$threads = 250;

$urls_pack = [];
for($i=0; $i<count($urls); $i=$i+$threads) {
    $urls_pack[] = array_slice($urls, $i, $threads, TRUE);
}
//print_r($urls_pack); exit();

$urls_p = [];

foreach($urls_pack as $pack) {
    $mh = curl_multi_init();
    foreach ($pack as $key => $value) {
        $ch[$key] = curl_init($value);
        curl_setopt($ch[$key], CURLOPT_HEADER, false);
        curl_setopt($ch[$key], CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch[$key], CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch[$key], CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch[$key], CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch[$key], CURLOPT_ENCODING, "gzip");
        curl_setopt($ch[$key], CURLOPT_REFERER, 'http://pubfilmonline.net/');
        curl_setopt($ch[$key], CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; CPU iPhone OS 5_0 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Version/5.1 Mobile/9A334 Safari/7534.48.3');
        curl_setopt($ch[$key], CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch[$key], CURLOPT_TIMEOUT, 0);
        curl_setopt($ch[$key], CURLOPT_MAXREDIRS, 10);
        //curl_setopt($ch[$key], CURLOPT_COOKIEJAR, dirname(__FILE__).'/cookie.txt');
        curl_setopt($ch[$key], CURLOPT_COOKIEFILE, dirname(__FILE__).'/cookie.txt');

        curl_multi_add_handle($mh, $ch[$key]);
    }

    do {
        curl_multi_exec($mh, $running);
        $info = curl_multi_info_read($mh);
        if (false !== $info) {
            //var_dump($info);
        }
        curl_multi_select($mh);
        usleep(100);
    } while ($running > 0);

    foreach ($pack as $key => $value) {

        if(curl_getinfo($ch[$key], CURLINFO_HTTP_CODE)=='404') { continue; }

        $result = curl_multi_getcontent($ch[$key]);

        preg_match_all("/<article id=\"post-[0-9]+\" class=\"item movies\">(.*)<\/article>/Us", $result, $result_array);
        foreach ($result_array[1] as $kk => $article){
            preg_match("/<a href=\"(.*)\"><img src=\".*\" alt=\"(.*)\"><\/a>/s", $article, $article_array);
            $urls_p[] = $article_array[1];
        }

        curl_multi_remove_handle($mh, $ch[$key]);
    }

    curl_multi_close($mh);
}
//print_r($urls_p); exit();

$urls_pack = [];
for($i=0; $i<count($urls_p); $i=$i+$threads) {
    $urls_pack[] = array_slice($urls_p, $i, $threads, TRUE);
}

$urls_p = [];

foreach($urls_pack as $pack) {
    $mh = curl_multi_init();
    foreach ($pack as $key => $value) {
        $ch[$key] = curl_init($value);
        curl_setopt($ch[$key], CURLOPT_HEADER, false);
        curl_setopt($ch[$key], CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch[$key], CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch[$key], CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch[$key], CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch[$key], CURLOPT_ENCODING, "gzip");
        curl_setopt($ch[$key], CURLOPT_REFERER, 'http://pubfilmonline.net/');
        curl_setopt($ch[$key], CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; CPU iPhone OS 5_0 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Version/5.1 Mobile/9A334 Safari/7534.48.3');
        curl_setopt($ch[$key], CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch[$key], CURLOPT_TIMEOUT, 0);
        curl_setopt($ch[$key], CURLOPT_MAXREDIRS, 10);
        //curl_setopt($ch[$key], CURLOPT_COOKIEJAR, dirname(__FILE__).'/cookie.txt');
        curl_setopt($ch[$key], CURLOPT_COOKIEFILE, dirname(__FILE__).'/cookie.txt');

        curl_multi_add_handle($mh, $ch[$key]);
    }

    do {
        curl_multi_exec($mh, $running);
        $info = curl_multi_info_read($mh);
        if (false !== $info) {
            //var_dump($info);
        }
        curl_multi_select($mh);
        usleep(100);
    } while ($running > 0);

    foreach ($pack as $key => $value) {

        if(curl_getinfo($ch[$key], CURLINFO_HTTP_CODE)=='404') { continue; }

        $result = curl_multi_getcontent($ch[$key]);


        $item = [];

        if(preg_match("/sources: {\"error\":\"You don't have permission to access this video.\"}/s", $result)==1){
            $item['real_url'] = 'no video';
        } else {
            preg_match_all("/sources: \[{\"file\":\"(.*)\",\"type\":\"/Usi", $result, $url_array);
            $item['real_url'] = str_replace('\\', '', @$url_array[1][0]);
        }

        preg_match_all("/<input type=\"hidden\" name=\"idpost\" value=\"([0-9]+)\">/s", $result, $article_id_array);
        $item['id'] = $article_id_array[1][0];

        preg_match_all("/<input type=\"hidden\" name=\"title\" value=\"(.*)\">/sU", $result, $article_array);
        $item['name'] = html_entity_decode(@$article_array[1][0]);

        preg_match("/(\(\d{4}\))/", $item['name'], $name_array);
        $item['year'] = str_replace(array('(', ')'), '', $name_array[1]);
        $item['clear_name'] = trim(str_replace($name_array[1], '', $item['name']));

        $cu = curl_init();
        curl_setopt($cu, CURLOPT_URL, 'http://www.omdbapi.com/?t="'.urlencode($item['clear_name']).'"&y="'.$item['year'].'"&apikey=');
        curl_setopt($cu, CURLOPT_REFERER, 'http://www.omdbapi.com/');
        curl_setopt($cu, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cu, CURLOPT_HEADER, false);
        $result = curl_exec($cu);
        curl_close($cu);

        $imdb = json_decode($result, true);

        $item['genre'] = @$imdb['Genre'];
        $item['real_year'] = @$imdb['Year'];
        $item['poster'] = @$imdb['Poster'];
        $item['rating'] = @$imdb['imdbRating'];

        $insertArray = ['id' => @$item['id']];
        if(@$item['clear_name'] != null) { $insertArray['name'] = $item['clear_name']; }
        if(@$item['real_url'] != null) { $insertArray['url'] = $item['real_url']; }
        if(@$item['genre'] != null) { $insertArray['genre'] = $item['genre']; }
        if(@$item['real_year'] != null) { $insertArray['year'] = $item['real_year']; }
        if(@$item['poster'] != null) { $insertArray['poster'] = $item['poster']; }
        if(@$item['rating'] != null) { $insertArray['rating'] = $item['rating']; }
        $db->insert('pubfilmonline', $insertArray);

        curl_multi_remove_handle($mh, $ch[$key]);
    }

    curl_multi_close($mh);
}
