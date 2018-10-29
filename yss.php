<?php

use voku\db\DB;
include_once 'core.php';

$db = DB::getInstance('localhost', 'movie', '', 'movies'); //mysql
//$db = DB::getInstance('mysql', 'movie', '', 'movies'); //mysql
$mc = JMathai\PhpMultiCurl\MultiCurl::getInstance();

if(@$_GET['go'] == 'ALL') {

    $handle = fopen('code_log.txt', 'w+');

### step 1

    $urls = [];
    for ($i = 0; $i <= 15000; $i += 15) { // 20000

        if ($i == 0) {
            $urls[] = 'https://yss.rocks/';
        } else {
            $urls[] = 'https://yss.rocks/-' . $i . '.html';
        }

    }

    $threads = 50;
    $urls_pack = [];
    for ($i = 0; $i < count($urls); $i = $i + $threads) {
        $urls_pack[] = array_slice($urls, $i, $threads, TRUE);
    }
    unset($urls);

//print_r($urls_pack); exit();
//$urls_pack = []; $urls_pack[0][0] = 'https://yss.rocks/'; // test

    $urls_p = [];

    foreach ($urls_pack as $pack) {

        $page = [];

        foreach ($pack as $k => $url) {

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
            curl_setopt($ch, CURLOPT_REFERER, 'https://yss.rocks/');
            curl_setopt($ch, CURLOPT_USERAGENT, $refer);
            curl_setopt($ch, CURLOPT_PROXY, get_proxy());
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            $page[$k] = $mc->addCurl($ch);

        }

        foreach ($page as $k => $p) {

            $code = $p->code;

            fwrite($handle, date('H:i:s') . ' (' . $code . ') - id:' . $k . '(' . $pack[$k] . ')' . "\r\n");

            if ($code != '200') continue;

            preg_match("/<div class=\"alert alert-danger\">.*<strong>(.*)<\/strong>.*<\/div>/Usm", $p->response, $not_found);
            if (@$not_found[1] == 'No Results Found!') die('end pages!');

            preg_match_all("/<div class=\"item active\">(.*)<\/div>/Us", $p->response, $items);
            if (@$items[1] != null) {
                foreach ($items[1] as $key => $item) {
                    if ($key == 15) continue;
                    preg_match("/<a href=\"(.*)\">/Us", $item, $item_url);
                    preg_match("/<img.*alt=\"(.*)\".*>/Us", $item, $item_clear_name);
                    preg_match("/<kbd>(.*)<\/kbd>/Us", $item, $item_name);
                    if (@$item_url[1] != null & @$item_clear_name[1] != null & @$item_name[1] != null) {

                        $movie = [];
                        $movie['id'] = trim(str_replace(['/', 'tt', '-0.html'], '', $item_url[1]));
                        $movie['name'] = trim(html_entity_decode($item_clear_name[1], ENT_QUOTES));
                        $movie['tmp_year'] = trim(str_replace(['(', ')', $item_clear_name[1]], '', $item_name[1]));
                        $movie['tmp_url'] = trim($item_url[1]);


                        if ($movie['id'] != null) {

                            $sel = $db->select('yss', 'id=' . (int)$movie['id']);

                            if (!$sel->is_empty()) {
                                die('done!'); // die('done!'); // break; //continue;
                            }

                            $insertArray = [
                                'id' => $movie['id'],
                                'name' => $movie['name'],
                                'tmp_year' => $movie['tmp_year'],
                                'tmp_url' => $movie['tmp_url']
                            ];

                            $db->insert('yss', $insertArray);
                            unset($insertArray);

                        }

                        unset($movie);

                    }

                }
            }

        }

        unset($page);

    }

    unset($urls_pack);

    echo $mc->getSequence()->renderAscii();

    fclose($handle);

}

if(@$_GET['go'] == 'ALL2') {

    $handle = fopen('code_log.txt', 'w+');

#### step 2

    $urls_p = [];
    $sel = $db->select('yss', '`hash_one` IS NULL');
    foreach ($sel->fetchAllArray() as $k => $item) {
        $urls_p[$item['id']] = 'https://yss.rocks'.$item['tmp_url'];
    }

    $threads = 250;
    $urls_pack = [];
    for ($i = 0; $i < count($urls_p); $i = $i + $threads) {
        $urls_pack[] = array_slice($urls_p, $i, $threads, TRUE);
    }
    unset($urls_p);

//print_r($urls_pack); exit();
//$urls_pack = []; $urls_pack[0][1508349] = 'https://yss.rocks/tt1508349-0.html'; // test

    foreach ($urls_pack as $pack) {

        $page = [];

        foreach ($pack as $k => $url) {

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
            curl_setopt($ch, CURLOPT_REFERER, 'https://yss.rocks/');
            curl_setopt($ch, CURLOPT_USERAGENT, $refer);
            curl_setopt($ch, CURLOPT_PROXY, get_proxy());
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            $page[$k] = $mc->addCurl($ch);

        }

        foreach ($page as $k => $p) {

            $code = $p->code;
            fwrite($handle, date('H:i:s').' ('.$code.') - id:' . $k . '(' . $pack[$k] . ')' . "\r\n");

            if ($code != '200') continue;

            preg_match("/<input type=\"radio\" class=\"btn btn-success\" name=\"vote\" value=\"(.*)\".*>/Usm", $p->response, $get_url_video);

            if (@$get_url_video[1] != null) {
                $db->update('yss', ['hash_one' => trim($get_url_video[1])], 'id=' . $k);
            }

        }

        unset($page);

    }

    unset($urls_pack);

    echo $mc->getSequence()->renderAscii();

    fclose($handle);

}


if(@$_GET['go'] == 'ALL3') {

    $handle = fopen('code_log.txt', 'w+');

#### step 3

    $hashes = [];
    $sel = $db->select('yss', '`hash_one` IS NOT NULL AND `hash_two` IS NULL');

    foreach ($sel->fetchAllArray() as $k => $item) {
        $hashes[$item['id']] = $item['hash_one'];
    }

    $threads = 300;
    $hash_pack = [];
    for ($i = 0; $i < count($hashes); $i = $i + $threads) {
        $hash_pack[] = array_slice($hashes, $i, $threads, TRUE);
    }
    unset($hashes);

//print_r($hash_pack); exit();
//$hash_pack = []; $hash_pack[0][52303] = '52de56a34d309743015dbaa2330a10423bc64b58d04c444d6ea87e60d9832cfb5c3c36f479a16842fe9e2f2e13fbddb7'; // test

    foreach ($hash_pack as $pack) {

        $page = [];

        foreach ($pack as $k => $hash) {

            $ch = curl_init('https://yss.rocks/getvid.php?v='.$hash);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
            curl_setopt($ch, CURLOPT_REFERER, 'https://yss.rocks/');
            curl_setopt($ch, CURLOPT_USERAGENT, $refer);
            curl_setopt($ch, CURLOPT_PROXY, get_proxy());
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            $page[$k] = $mc->addCurl($ch);

        }

        foreach ($page as $k => $p) {

            $code = $p->code;
            fwrite($handle, date('H:i:s').' ('.$code.') - id:' . $k . '(' . $pack[$k] . ')' . "\r\n");

            if ($code != '200') continue;

            preg_match("/<input type=\"radio\" name=\"vote\" value=\"(.*)\".*>/Usm", $p->response, $get_video);

            if (@$get_video[1] != null) {
                $db->update('yss', ['hash_two' => trim($get_video[1])], 'id=' . $k);
            }

        }

        unset($page);

    }

    unset($hash_pack);

    echo $mc->getSequence()->renderAscii();

    fclose($handle);

}

if(@$_GET['go'] == 'ALL4') {

    $handle = fopen('code_log.txt', 'w+');

#### step 4

    $hashes = [];
    $sel = $db->select('yss', '`hash_two` IS NOT NULL AND `done_url` IS NULL');

    foreach ($sel->fetchAllArray() as $k => $item) {
        $hashes[$item['id']] = $item['hash_two'];
    }

    $threads = 300;
    $hash_pack = [];
    for ($i = 0; $i < count($hashes); $i = $i + $threads) {
        $hash_pack[] = array_slice($hashes, $i, $threads, TRUE);
    }
    unset($hashes);

//print_r($hash_pack); exit();
//$hash_pack = []; $hash_pack[0][1130980] = 'cfd8ea197317182dbd01d2e21a7fcc77101f36dd3383595d96872b24dde11b4f3882c06bbd078d4e8bca768eec3c5e84f1b6f33ae3b56b6154e1283601bdb1580a52598d80682728a098eddb9a27e14041977c8e91c11ea8e858e7219c37aee6fa40a7fc11ea3579af7096e953af90d484e594e618d6ed5abf7217f9de8decfaecf453ccacf1a74593800088de23e30d73d627fc9579a794a4f92cfc94bb3f1016a19fb0fe43f00aff15c4ce659814f53eb285475f6779228de3471246ea0c66005794fa7dfac18bfd16a9fe9698a2a1'; // test

    foreach ($hash_pack as $pack) {

        $page = [];

        foreach ($pack as $k => $hash) {

            $ch = curl_init('https://yss.rocks/getvid.php?v='.$hash);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
            curl_setopt($ch, CURLOPT_REFERER, 'https://yss.rocks/');
            curl_setopt($ch, CURLOPT_USERAGENT, $refer);
            curl_setopt($ch, CURLOPT_PROXY, get_proxy());
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            $page[$k] = $mc->addCurl($ch);

        }

        foreach ($page as $k => $p) {

            $code = $p->code;
            fwrite($handle, date('H:i:s').' ('.$code.') - id:' . $k . '(' . $pack[$k] . ')' . "\r\n");

            if ($code != '200') continue;

            preg_match("/<a href=\"(.*)\">/Usm", $p->response, $done_url);

            if (@$done_url[1] != null) {
                $db->update('yss', ['done_url' => trim($done_url[1])], 'id=' . $k);
            }

        }

        unset($page);

    }

    unset($hash_pack);

    echo $mc->getSequence()->renderAscii();

    fclose($handle);

}

if(@$_GET['go'] == 'ALL_4') {

    $handle = fopen('code_log.txt', 'w+');

#### step _4

    $hashes = [];
    $sel = $db->select('yss', '`hash_two` IS NOT NULL AND `done_url` IS NULL AND `openload_url` IS NULL');

    foreach ($sel->fetchAllArray() as $k => $item) {
        $hashes[$item['id']] = $item['hash_two'];
    }

    $threads = 250;
    $hash_pack = [];
    for ($i = 0; $i < count($hashes); $i = $i + $threads) {
        $hash_pack[] = array_slice($hashes, $i, $threads, TRUE);
    }
    unset($hashes);

//print_r($hash_pack); exit();
//$hash_pack = []; $hash_pack[0][1130980] = 'cfd8ea197317182dbd01d2e21a7fcc77101f36dd3383595d96872b24dde11b4f3882c06bbd078d4e8bca768eec3c5e84f1b6f33ae3b56b6154e1283601bdb1580a52598d80682728a098eddb9a27e14041977c8e91c11ea8e858e7219c37aee6fa40a7fc11ea3579af7096e953af90d484e594e618d6ed5abf7217f9de8decfaecf453ccacf1a74593800088de23e30d73d627fc9579a794a4f92cfc94bb3f1016a19fb0fe43f00aff15c4ce659814f53eb285475f6779228de3471246ea0c66005794fa7dfac18bfd16a9fe9698a2a1'; // test

    foreach ($hash_pack as $pack) {

        $page = [];

        foreach ($pack as $k => $hash) {

            $ch = curl_init('https://yss.rocks/getvid.php?v='.$hash);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
            curl_setopt($ch, CURLOPT_REFERER, 'https://yss.rocks/');
            curl_setopt($ch, CURLOPT_USERAGENT, $refer);
            curl_setopt($ch, CURLOPT_PROXY, get_proxy());
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            $page[$k] = $mc->addCurl($ch);

        }

        foreach ($page as $k => $p) {

            $code = $p->code;
            fwrite($handle, date('H:i:s').' ('.$code.') - id:' . $k . '(' . $pack[$k] . ')' . "\r\n");

            if ($code != '200') continue;

            preg_match("/<iframe.*src=\"(.*)\">/Usm", $p->response, $openload_url);

            if (@$openload_url[1] != null) {
                $db->update('yss', ['openload_url' => trim($openload_url[1])], 'id=' . $k);
            }

        }

        unset($page);

    }

    unset($hash_pack);

    echo $mc->getSequence()->renderAscii();

    fclose($handle);

}

if(@$_GET['go'] == 'ALL_44') {

    $handle = fopen('code_log.txt', 'w+');

#### step _44

    $urls_p = [];
    $sel = $db->select('yss', '`openload_url` IS NOT NULL AND `openload_chk` IS NULL');

    foreach ($sel->fetchAllArray() as $k => $item) {
        $urls_p[$item['id']] = $item['openload_url'];
    }

    $threads = 50;
    $urls_pack = [];
    for ($i = 0; $i < count($urls_p); $i = $i + $threads) {
        $urls_pack[] = array_slice($urls_p, $i, $threads, TRUE);
    }
    unset($urls_p);

//print_r($urls_pack); exit();
//$urls_pack = []; $urls_pack[0][3498820] = 'https://openload.co/embed/bU-kgI-6whE'; // test

    foreach ($urls_pack as $pack) {

        $page = [];

        foreach ($pack as $k => $url) {

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
            curl_setopt($ch, CURLOPT_REFERER, 'https://yss.rocks/');
            curl_setopt($ch, CURLOPT_USERAGENT, $refer);
            curl_setopt($ch, CURLOPT_PROXY, get_proxy());
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            $page[$k] = $mc->addCurl($ch);

        }

        foreach ($page as $k => $p) {

            $code = $p->code;
            fwrite($handle, date('H:i:s').' ('.$code.') - id:' . $k . '(' . $pack[$k] . ')' . "\r\n");

            if ($code != '200') continue;

            preg_match("/<h3>(.*)<\/h3>/Usm", $p->response, $openload_chk);

            if(@$openload_chk[1] == 'We’re Sorry!') {
                $db->update('yss', ['openload_chk' => 404], 'id=' . $k);
            }

        }

        unset($page);

    }

    unset($urls_pack);

    echo $mc->getSequence()->renderAscii();

    fclose($handle);

}

if(@$_GET['go'] == 'IMDB') {

    $handle = fopen('code_log.txt', 'w+');

    $sel = $db->select('yss', '`done_url` IS NOT NULL AND `real_name` IS NULL');
    $links = $sel->getArray();
    $urls = [];
    //print_r($links); exit; // check

    foreach ($links as $link) {
        $urls[$link['id']] = 'http://www.omdbapi.com/?apikey=&i=tt'.str_pad($link['id'], 7, '0', STR_PAD_LEFT);
    }

    $threads = 50;
    $urls_pack = [];
    for ($i = 0; $i < count($urls); $i = $i + $threads) {
        $urls_pack[] = array_slice($urls, $i, $threads, TRUE);
    }
    unset($urls);

    //print_r($urls_pack); exit();
    //$urls_pack = []; $urls_pack[0][287733] = 'http://www.omdbapi.com/?apikey=&i=tt0287733'; // test

    foreach ($urls_pack as $pack) {

        $page = [];

        foreach ($pack as $k => $url) {

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
            curl_setopt($ch, CURLOPT_REFERER, 'http://www.omdbapi.com/');
            curl_setopt($ch, CURLOPT_USERAGENT, $refer);
            //curl_setopt($ch, CURLOPT_PROXY, get_proxy());
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            $page[$k] = $mc->addCurl($ch);

        }

        foreach ($page as $k => $p) {

            $code = $p->code;
            fwrite($handle, date('H:i:s').' ('.$code.') - id:' . $k . '(' . $pack[$k] . ')' . "\r\n");

            if ($code != '200') continue;

            $result = $p->response;

            $imdb = json_decode($result, true);

            if($imdb != null) {
                $db->update('yss', [
                    'real_name' => $imdb['Title'],
                    'real_year' => $imdb['Year'],
                    'genre' => $imdb['Genre'],
                    'poster' => $imdb['Poster'],
                    'rating' => $imdb['imdbRating']
                ], 'id=' . $k);
            }

        }

        unset($page);

    }

    unset($urls_pack);

    echo $mc->getSequence()->renderAscii();

    fclose($handle);

}

if(isset($_GET['q'])) {

    $q = $db->escape(urldecode($_GET['q']));


    $sel = $db->query('SELECT * FROM `yss` WHERE `real_name` like \'%'.$q.'%\'
        GROUP BY `real_name` ORDER BY CASE
        WHEN `real_name` like \''.$q.'%\' THEN 0
        WHEN `real_name` like \'% %'.$q.'% %\' THEN 1
        WHEN `real_name` like \'%'.$q.'\' THEN 2
        ELSE 3 END, `real_name`');
    $item = $sel->get();

    if(isset($item->id)) {

        if (isset($item->done_url)) {

            $ch = curl_init($item->done_url);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
            curl_setopt($ch, CURLOPT_REFERER, 'https://yss.rocks/');
            curl_setopt($ch, CURLOPT_USERAGENT, $refer);
            //curl_setopt($ch, CURLOPT_PROXY, get_proxy()); // TODO very bad proxies (for one check proxy turned off)
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            $page = $mc->addCurl($ch);

            //print_r($page->response); exit;
            if ($page->code == '302') {
                die('redirect error');
            }
            if ($page->code == '0') {
                die('proxy error');
            }


/* // TODO checking off for getting old link each yss don't working
            if ($page->code == '403' or $page->code == '400') {

                unset($page);

                start:

                $ch = curl_init('https://yss.rocks/getvid.php?v=' . $item->hash_two); // TODO if hash_two will not working to add check it
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
                curl_setopt($ch, CURLOPT_REFERER, 'https://yss.rocks/');
                curl_setopt($ch, CURLOPT_USERAGENT, $refer);
                //curl_setopt($ch, CURLOPT_PROXY, get_proxy()); // TODO very bad proxies (for one check proxy turned off)
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
                curl_setopt($ch, CURLOPT_TIMEOUT, 0);
                curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
                $page = $mc->addCurl($ch);

                if ($page->code == '0') goto start;

                preg_match("/<a href=\"(.*)\">/Usm", $page->response, $done_url);


                if (@$done_url[1] != null) {
                    $db->update('yss', ['link' => trim($done_url[1])], 'id=' . $item->id);
                    $item->done_url = $done_url[1];
                } else {

                    $post_array = [];
                    $var = ['link' => $item->hash_two];
                    foreach ($var as $key => $value) {
                        $post_array[] = urlencode($key) . '=' . urlencode($value);
                    }
                    $post_string = implode('&', $post_array);
                    $ch = curl_init('https://yss.rocks/d/plugins/gkpluginsphp.php');
                    curl_setopt($ch, CURLOPT_HEADER, false);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
                    curl_setopt($ch, CURLOPT_REFERER, 'https://yss.rocks/');
                    curl_setopt($ch, CURLOPT_USERAGENT, $refer);
                    //curl_setopt($ch, CURLOPT_PROXY, get_proxy());
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 0);
                    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
                    $page = $mc->addCurl($ch);

                    preg_match("/\[{\"link\":\"(.*)\",/U", $page->response, $done);
                    $dones = str_replace('\\', '', $done[1]);
                    $db->update('yss', ['link' => trim($dones)], 'id=' . $item->id);
                    $item->done_url = $dones;

                }

            }
*/
            echo htmlspecialchars('<movie> <id>' . $item->id . '</id> <name>' . $item->real_name . '</name> <year>' . $item->real_year . '</year> <url>1</url> <img>' . $item->poster . '</img><ytid>' . $item->done_url . '</ytid> <imdbLink>11</imdbLink> <rating>' . $item->rating . '</rating> <storyline>11</storyline><poster>' . $item->poster . '</poster> <genres>' . $item->genre . '</genres> <casts>1</casts><casts_photoes>1</casts_photoes> <photoes/></movie>') . "<br/><br/>";
            unset($item);

        } else {
            die('not found video url');
        }

    } else {
        die('not found in db');
    }

}

if(isset($_GET['qq'])) {

    $sel = $db->query('SELECT `real_name`, `done_url` from `yss` ORDER BY `real_year` DESC');
    $items = $sel->getAll();

    foreach ($items as $item) {
        if (isset($item->done_url)) {
            echo htmlspecialchars('<name>' . $item->real_name . '</name>') . '<br/>'. "\r\n";
        }
    }

}

if(@$_GET['go'] == 'proxy') {

    $proxy = get_proxy();

    $ch = curl_init('http://ipinfo.info/');
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
    curl_setopt($ch, CURLOPT_REFERER, 'https://google.com/');
    curl_setopt($ch, CURLOPT_USERAGENT, $refer);
    curl_setopt($ch, CURLOPT_PROXY, $proxy);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 0);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    $page = $mc->addCurl($ch);

    preg_match("/<p class=\"nomargins\"><B>(.*)<\/B>&nbsp&nbsp;<a href=\".\/html\/privacy-check.php\">more...<\/a><\/p>/Ums", $page->response, $real_ip);

    echo 'using proxy: '.$proxy."\n";
    echo 'header page code: '.$page->code."\n";
    echo 'real ip: '.@$real_ip[1];

}
