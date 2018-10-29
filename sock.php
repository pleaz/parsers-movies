<?php

use voku\db\DB;
include_once 'core.php';

$db = DB::getInstance('localhost', 'movie', '', 'movies'); //mysql
//$db = DB::getInstance('mysql', 'movie', '', 'movies'); //mysql
$mc = JMathai\PhpMultiCurl\MultiCurl::getInstance();

if(@$_GET['go'] == 'ALL') {

    $handle = fopen('code_log.txt', 'w+');

### step 1

    $start = file_get_contents('http://sockshare.net/new-movies.html');
    preg_match("/<a class=\"pagelink\" onfocus=\"this.blur\(\)\" href=http:\/\/sockshare.net\/new-movies\/page-([0-9]+).html onClick='return showFilm\(http:\/\/sockshare.net\/new-movies,[0-9]+,[0-9]+,,\); return false;'>»<\/a>/Ums", $start, $end_page);

    $urls = [];
    for ($i = 0; $i <= @$end_page[1]; $i++) { // ~1653
        $urls[] = 'http://sockshare.net/new-movies/page-'.$i.'.html';
    }

    $threads = 50;
    $urls_pack = [];
    for ($i = 1; $i < count($urls); $i = $i + $threads) {
        $urls_pack[] = array_slice($urls, $i, $threads, TRUE);
    }
    unset($urls);

//print_r($urls_pack); exit();
//$urls_pack = []; $urls_pack[0][1] = 'http://sockshare.net/new-movies/page-1.html'; // test

    $urls_p = [];

    foreach ($urls_pack as $pack) {

        $page = [];

        foreach ($pack as $k => $url) {

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
            curl_setopt($ch, CURLOPT_REFERER, 'http://sockshare.net/');
            curl_setopt($ch, CURLOPT_USERAGENT, $refer);
            //curl_setopt($ch, CURLOPT_PROXY, get_proxy());
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            $page[$k] = $mc->addCurl($ch);

        }

        foreach ($page as $k => $p) {

            $code = $p->code;

            fwrite($handle, date('H:i:s') . ' (' . $code . ') - id:' . $k . '(' . $pack[$k] . ')' . "\r\n");

            if ($code != '200') continue;

            preg_match_all("/<li class=\"item\">(.*)<\/li>/Ums", $p->response, $items);
            if (@$items[1] != null) {
                foreach ($items[1] as $item) {

                    preg_match("/href=\"(.*)\"/Ums", $item, $item_url);
                    preg_match("/<b>(.*)<\/b>/Ums", $item, $item_name);
                    preg_match("/<div class=\"status status-year\">.*([0-9]{4}).*<\/div>/Ums", $item, $tmp_year);

                    if (@$item_url[1] != null & @$item_name[1] != null & @$tmp_year[1] != null) {

                        $movie = [];
                        $movie['name'] = trim(html_entity_decode($item_name[1], ENT_QUOTES));
                        $movie['tmp_year'] = trim($tmp_year[1]);
                        $movie['tmp_url'] = trim($item_url[1]);

                        preg_match("/http:\/\/sockshare.net\/watch\/([0-9A-Z-a-z]+)-/Ums", $item_url[1], $item_code);
                        if(@$item_code[1] != null) {
                            $movie['code'] = trim($item_code[1]);
                        }

                        if (@$movie['code'] != null) {

                            $sel = $db->query('SELECT * FROM `sock` WHERE `code` = \''.$movie['code'].'\'');
                            if (!$sel->is_empty()) {
                                die('done!'); // die('done!'); // break; //continue;
                            }

                            $insertArray = [
                                'name' => $movie['name'],
                                'tmp_year' => $movie['tmp_year'],
                                'tmp_url' => $movie['tmp_url'],
                                'code' => $movie['code']
                            ];

                            $db->insert('sock', $insertArray);
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
    $sel = $db->select('sock', '`tmp_url` IS NOT NULL AND `one_url` IS NULL');
    foreach ($sel->fetchAllArray() as $item) {
        $urls_p[$item['id']] = $item['tmp_url'];
    }

    $threads = 60;
    $urls_pack = [];
    for ($i = 0; $i < count($urls_p); $i = $i + $threads) {
        $urls_pack[] = array_slice($urls_p, $i, $threads, TRUE);
    }
    unset($urls_p);

//print_r($urls_pack); exit();
//$urls_pack = []; $urls_pack[0][11034] = 'http://sockshare.net/watch/zGO3EyGK-line-walker.html'; // test

    foreach ($urls_pack as $pack) {

        $page = [];

        foreach ($pack as $k => $url) {

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
            curl_setopt($ch, CURLOPT_REFERER, 'http://sockshare.net/');
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

            if(preg_match("/<div id=\"player\">[\r\t\n ]+<div class=\"player\">[\r\t\n ]+<center><a title=\"[A-Za-z ]+\" href=\"(.*)\"/Ums", $p->response, $source_url)) {
                $db->update('sock', ['one_url' => trim($source_url[1])], 'id=' . $k);
            } elseif (preg_match("/<title>(File) Invalid Or Deleted | SockShare<\/title>/Um", $p->response)) {
                $db->update('sock', ['one_url' => 'not found'], 'id=' . $k);
            } elseif (preg_match("/<div id=\"player\">[\r\t\n ]+<div class=\"player\">[\r\t\n ]+<script type=\"text\/javascript\">(.*)<\/script>/Um", $p->response)) {
                $db->update('sock', ['one_url' => 'ready'], 'id=' . $k);
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

#### step 2

    $urls_p = [];
    $sel = $db->query('SELECT * FROM `sock` WHERE `tmp_url` IS NOT NULL AND `js` IS NULL  AND `one_url` = \'ready\'');
    foreach ($sel->fetchAllArray() as $item) {
        $urls_p[$item['id']] = $item['tmp_url'];
    }

    $threads = 65;
    $urls_pack = [];
    for ($i = 0; $i < count($urls_p); $i = $i + $threads) {
        $urls_pack[] = array_slice($urls_p, $i, $threads, TRUE);
    }
    unset($urls_p);

//print_r($urls_pack); exit();
//$urls_pack = []; $urls_pack[0][175] = 'http://sockshare.net/watch/OGgDWEdR-the-human-comedy.html'; // test

    foreach ($urls_pack as $pack) {

        $page = [];

        foreach ($pack as $k => $url) {

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
            curl_setopt($ch, CURLOPT_REFERER, 'http://sockshare.net/');
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

            if (preg_match("/<div id=\"player\">[\r\t\n ]+<div class=\"player\">[\r\t\n ]+<script type=\"text\/javascript\">(.*)<\/script>/Um", $p->response, $script)) {
                $db->update('sock', ['js' => $db->escape($script[1])], 'id=' . $k);
            }

        }

        unset($page);

    }

    unset($urls_pack);

    echo $mc->getSequence()->renderAscii();

    fclose($handle);

}


if(@$_GET['go'] == 'ALL4') {

    $sel = $db->select('sock', '`js` IS NOT NULL');
    foreach ($sel->fetchAllArray() as $item) {

        preg_match('/document.write\(Base64.decode\(\\\"(.*)\\\"\)\);/', $item['js'], $base64);
        $code = base64_decode(@$base64[1]);
        if(preg_match('/<iframe.*src="(.*)".*><\/iframe>/Um', $code, $src)){
            $db->update('sock', ['js' => "NULL", 'one_url' => $src[1]], 'id=' . $item['id']);
        } elseif(preg_match("/link:\"(.*)\"/U", $code, $hash)) {
            $db->update('sock', ['js' => "NULL", 'one_url' => $hash[1]], 'id=' . $item['id']);
        }

    }

    if ($db->errors()) {
        echo $db->lastError();
    }

}

if(isset($_GET['q'])) {

    $q = $db->escape($_GET['q']);

    $sel = $db->query('SELECT * FROM `sock` WHERE name like \'%' . $q . '%\' GROUP BY name ORDER BY CASE
              WHEN name like \'' . $q . ' %\' THEN 0
              WHEN name like \'' . $q . '%\' THEN 1
              WHEN name like \'% ' . $q . '%\' THEN 2
               ELSE 3
          END, name');
    $sel_arr = $sel->get();

    if (isset($sel_arr->one_url)) {
        if(strpos($sel_arr->one_url, 'entervideo.net') !== false) {

            if(isset($sel_arr->done_url)) {
                $done_url[1] = $sel_arr->done_url;
            } else {
                $ch = curl_init($sel_arr->one_url);
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
                curl_setopt($ch, CURLOPT_REFERER, 'http://sockshare.net/');
                curl_setopt($ch, CURLOPT_USERAGENT, $refer);
                //curl_setopt($ch, CURLOPT_PROXY, get_proxy());
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
                curl_setopt($ch, CURLOPT_TIMEOUT, 0);
                curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
                $page = $mc->addCurl($ch);

                preg_match("/<source src=\"(.*)\" type=/Usm", $page->response, $done_url);
            }

            if (isset($done_url[1])) {
                $ch = curl_init($done_url[1]);
                curl_setopt($ch, CURLOPT_USERAGENT, $refer);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_NOBODY, true);
                curl_setopt($ch, CURLOPT_HEADER, true);
                curl_setopt($ch, CURLOPT_REFERER, 'http://entervideo.net/');
                //curl_setopt($ch, CURLOPT_PROXY, get_proxy());
                curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
                $page_two = $mc->addCurl($ch);

                if($page_two->code == 200){

                    $item = [];
                    $item['done_url'] = $done_url[1];
                    $item['id'] = $sel_arr->id;
                    $item['name'] = $sel_arr->name;
                    $item['year'] = $sel_arr->tmp_year;

                    if(!isset($sel_arr->done_url)) {

                        $ch = curl_init('http://www.omdbapi.com/?t="' . urlencode($sel_arr->name) . '"&y="' . $sel_arr->tmp_year . '"&apikey=');
                        curl_setopt($ch, CURLOPT_HEADER, false);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
                        curl_setopt($ch, CURLOPT_REFERER, 'http://www.omdbapi.com/');
                        curl_setopt($ch, CURLOPT_USERAGENT, $refer);
                        //curl_setopt($ch, CURLOPT_PROXY, get_proxy());
                        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
                        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
                        $page_three = $mc->addCurl($ch);

                        if ($page_three->code == '200') {
                            $result = $page_three->response;
                            $imdb = json_decode($result, true);

                            $item['real_name'] = @$imdb['Title'];
                            $item['genre'] = @$imdb['Genre'];
                            $item['real_year'] = @$imdb['Year'];
                            $item['poster'] = @$imdb['Poster'];
                            $item['rating'] = @$imdb['imdbRating'];
                        }

                    }

                    if(isset($item['real_name'])) {
                        $db->update('sock', [
                            'done_url' => $item['done_url'],
                            'real_name' => @$item['real_name'],
                            'genre' => @$item['genre'],
                            'real_year' => @$item['real_year'],
                            'poster' => @$item['poster'],
                            'rating' => @$item['rating']
                        ], 'id=' . $item['id']);

                        $item['name'] = $item['real_name'];
                        $item['year'] = $item['real_year'];
                    } else {
                        $db->update('sock', [
                            'done_url' => $item['done_url'],
                        ], 'id=' . $item['id']);
                        $item['genre'] = @$sel_arr->genre;
                        $item['poster'] = @$sel_arr->poster;
                        $item['rating'] = @$sel_arr->rating;
                    }

                    echo htmlspecialchars('<movie> <id>'.$item['id'].'</id> <name>'.$item['name'].'</name> <year>'.$item['year'].'</year> <url>1</url> <img>'.@$item['poster'].'</img><ytid>'.$item['done_url'].'</ytid> <imdbLink>11</imdbLink> <rating>'.@$item['rating'].'</rating> <storyline>11</storyline><poster>'.@$item['poster'].'</poster> <genres>'.@$item['genre'].'</genres> <casts>1</casts><casts_photoes>1</casts_photoes> <photoes/></movie>')."<br/><br/>";

                    echo '<a href="'.$item['done_url'].'">link</a>';

                } else {
                    die('broken video link');
                }
            } else {
                die('not found url');
            }
        } else {
            die('not compatible source');
        }

    } else {
        die('not found in db');
    }



}

if(isset($_GET['qq'])) {

    $sel = $db->query('SELECT `name`, `one_url` from `sock` ORDER BY `tmp_year` DESC');
    $items = $sel->getAll();

    foreach ($items as $item) {
        if (strpos($item->one_url, 'entervideo.net') !== false) {
            echo htmlspecialchars('<name>' . $item->name . '</name>') . '<br/>'. "\r\n";
        }
    }

}