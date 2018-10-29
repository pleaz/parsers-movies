<?php

use voku\db\DB;
include_once 'core.php';

$db = DB::getInstance('localhost', 'movie', '', 'movies'); //mysql
$mc = JMathai\PhpMultiCurl\MultiCurl::getInstance();

if(@$_GET['go'] == 'ALL') {

    $handle = fopen('code_log.txt', 'w+');

### step 1

    $q = $db->query('SELECT `id` FROM `123movie` ORDER BY `id` DESC LIMIT 1');
    $last_id = $q->get()->id;

    $chh = curl_init();
    curl_setopt($chh, CURLOPT_URL, 'https://123movies.co/movie/');
    curl_setopt($chh, CURLOPT_USERAGENT, $refer);
    curl_setopt($chh, CURLOPT_PROXY, get_proxy());
    curl_setopt($chh, CURLOPT_ENCODING, 'gzip');
    curl_setopt($chh, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
    $tmp_page = curl_exec($chh);
    $count_per_page = preg_match_all("/<article id=\"post-([0-9]+)\" class=\"item movies singleVideo\" .*><\/article>/Us", $tmp_page, $item);
    preg_match("/<header><h1>Movies<\/h1> <span>([0-9]+)<\/span><\/header>/Us", $tmp_page, $count);
    $pages = ceil($count[1]/$count_per_page);

    if($item[1][0] == $last_id) { die('up to date!'); }


    //print_r($item[1][0]); exit();


    $urls = [];
    for ($i = 1; $i <= $pages; $i++) { // 431(9.06.17)

        if ($i == 1) {
            $urls[] = 'https://123movies.co/movie/';
        } else {
            $urls[] = 'https://123movies.co/movie/page/' . $i . '/';
        }

    }

    $threads = 50;
    $urls_pack = [];
    for ($i = 0; $i < count($urls); $i = $i + $threads) {
        $urls_pack[] = array_slice($urls, $i, $threads, TRUE);
    }
    unset($urls);

//print_r($urls_pack); exit();
//$urls_pack = []; $urls_pack[0][0] = 'https://123movies.co/movie/'; // test

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
            curl_setopt($ch, CURLOPT_ENCODING, "gzip");
            curl_setopt($ch, CURLOPT_REFERER, 'https://123movies.co/');
            curl_setopt($ch, CURLOPT_USERAGENT, $refer);
            curl_setopt($ch, CURLOPT_PROXY, get_proxy());
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            $page[$k] = $mc->addCurl($ch);

        }

        foreach ($page as $k => $p) {

            $code = $p->code;
            fwrite($handle, date('H:i:s').' ('.$code.') - id:'.$k."\r\n");

            if ($code != '200') continue;

            preg_match_all("/<article id=\"post-[0-9]+\" class=\"item movies singleVideo\" tooltip=\"yes\" title=\".*\"><div class=\"poster\"><a class=\"ml-mask jt\" oldtitle=\".*\" title=\".*\" href=\"(.*)\"><img/Us", $p->response, $result_array);
            foreach ($result_array[1] as $article) {
                $urls_p[] = $article . '?watching';
            }

        }

        unset($page);

    }

    unset($urls_pack);

#### step 2

    $urls_pack = [];
    for ($i = 0; $i < count($urls_p); $i = $i + $threads) {
        $urls_pack[] = array_slice($urls_p, $i, $threads, TRUE);
    }
    unset($urls_p);

//print_r($urls_pack); exit();
//$urls_pack = []; $urls_pack[0][0] = 'https://123movies.co/movie/child-of-satan-2/?watching'; // test



    foreach ($urls_pack as $pack) {

        $page = [];

        foreach ($pack as $k => $url) {

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_ENCODING, "gzip");
            curl_setopt($ch, CURLOPT_REFERER, 'https://123movies.co/');
            curl_setopt($ch, CURLOPT_USERAGENT, $refer);
            curl_setopt($ch, CURLOPT_PROXY, get_proxy());
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            $page[$k] = $mc->addCurl($ch);

        }

        foreach ($page as $k => $p) {

            $code = $p->code;
            fwrite($handle, date('H:i:s').' ('.$code.') - id:'.$k."\r\n");

            if ($p->code != '200') continue;

            $item = [];

            $result = $p->response;

            preg_match("/<iframe class=\"metaframe rptss\" src=\"(.*)\" frameborder=\"0\" allowfullscreen><\/iframe>/U", $result, $tmp_url);
            preg_match("/<div class=\"starstruck starstruck-main \" data-id=\"(.*)\" data-rating=\"[0-9]+\" data-type=\"post\"><\/div>/U", $result, $id);
            preg_match("/<meta itemprop=\"name\" content=\"(.*)\">/U", $result, $name);
            preg_match("/<p><span>Release date: <\/span>(.*)<\/p>/U", $result, $date);
            preg_match("/([0-9]{4})/U", $date[1], $year);

            $item['id'] = @$id[1];
            $item['name'] = trim(html_entity_decode(@$name[1], ENT_QUOTES));
            $item['tmp_year'] = @$year[1];
            $item['tmp_url'] = @$tmp_url[1];

            if (@$item['id'] != null) {

                $sel = $db->select('123movie', 'id=' . $item['id']);
                if (!$sel->is_empty()) {
                    die('done!'); // break; //continue;
                }


                $insertArray = ['id' => @$item['id']];
                if (@$item['name'] != null) {
                    $insertArray['name'] = $item['name'];
                }
                if (@$item['tmp_url'] != null) {
                    $insertArray['tmp_url'] = $item['tmp_url'];
                }
                if (@$item['tmp_year'] != null) {
                    $insertArray['tmp_year'] = $item['tmp_year'];
                }
                $db->insert('123movie', $insertArray);
                unset($insertArray);
            }

            unset($item);

        }

        unset($page);

    }

    unset($urls_pack);

    echo $mc->getSequence()->renderAscii();

    fclose($handle);

}

if(@$_GET['go'] == 'LINK_ONE') {

    $sel = $db->select('123movie', '`done_url` IS NULL AND `mirror_url_one` IS NULL');
    $links = $sel->getArray();
    $urls = [];
    //print_r($links); exit; // check

    foreach ($links as $link){
        $urls[$link['id']] = $link['tmp_url'];
    }

    $threads = 50;
    $urls_pack = [];
    for ($i = 0; $i < count($urls); $i = $i + $threads) {
        $urls_pack[] = array_slice($urls, $i, $threads, TRUE);
    }

    //$urls_pack = []; $urls_pack[0][25198] = 'https://putstream.com/movie/the-legend-of-sarila?watching=y1zCEAmcIWptpmZf9jyAKwnqa'; // test

    $urls_p = [];
    $page = [];

    foreach ($urls_pack as $pack) {

        foreach ($pack as $k => $url) {

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_ENCODING, "gzip");
            curl_setopt($ch, CURLOPT_REFERER, 'https://putstream.com/');
            curl_setopt($ch, CURLOPT_USERAGENT, $refer);
            curl_setopt($ch, CURLOPT_PROXY, get_proxy());
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            $page[$k] = $mc->addCurl($ch);

        }

        foreach ($page as $k => $p) {

            if ($p->code != '200') continue;

            $result = $p->response;

            if(preg_match("/<script.*> window.location = \"(.*)\"; <\/script>/Us", $result, $mirror_url_one) == 1) {
                $db->update('123movie', ['mirror_url_one' => trim($mirror_url_one[1])], 'id='.$k);
            } else {
                preg_match("/<source src=\"(.*)\" type=\"/Us", $result, $done_url);
                if (@$done_url[1] != null) {
                    $db->update('123movie', ['done_url' => trim($done_url[1])], 'id=' . $k);
                }
            }

        }
        $page = [];

    }

    echo $mc->getSequence()->renderAscii();

}

if(@$_GET['go'] == 'LINK_TWO') {

    $sel = $db->select('123movie', '`done_url` IS NULL AND `mirror_url_two` IS NULL AND `mirror_url_one` IS NOT NULL');
    $links = $sel->getArray();
    $urls = [];
    //print_r($links); exit; // check

    foreach ($links as $link){
        $urls[$link['id']] = $link['mirror_url_one'];
    }

    $threads = 50;
    $urls_pack = [];
    for ($i = 0; $i < count($urls); $i = $i + $threads) {
        $urls_pack[] = array_slice($urls, $i, $threads, TRUE);
    }

    //$urls_pack = []; $urls_pack[0][25198] = 'https://putstream.com/movie/the-legend-of-sarila?watching=y1zCEAmcIWptpmZf9jyAKwnqa'; // test

    $urls_p = [];
    $page = [];

    foreach ($urls_pack as $pack) {

        foreach ($pack as $k => $url) {

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_ENCODING, "gzip");
            curl_setopt($ch, CURLOPT_REFERER, 'https://putstream.com/');
            curl_setopt($ch, CURLOPT_USERAGENT, $refer);
            curl_setopt($ch, CURLOPT_PROXY, get_proxy());
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            $page[$k] = $mc->addCurl($ch);

        }

        foreach ($page as $k => $p) {

            if ($p->code != '200') continue;

            $result = $p->response;

            if(preg_match("/<script.*> window.location = \"(.*)\"; <\/script>/Us", $result, $mirror_url_one) == 1) {
                $db->update('123movie', ['mirror_url_two' => trim($mirror_url_one[1])], 'id='.$k);
            } else {
                preg_match("/<source src=\"(.*)\" type=\"/Us", $result, $done_url);
                if (@$done_url[1] != null) {
                    $db->update('123movie', ['done_url' => trim(@$done_url[1])], 'id=' . $k);
                }
            }

        }
        $page = [];

    }

    echo $mc->getSequence()->renderAscii();

}

if(@$_GET['go'] == 'LINK_THREE') {

    $sel = $db->select('123movie', '`done_url` IS NULL AND `mirror_url_three` IS NULL AND `mirror_url_two` IS NOT NULL');
    $links = $sel->getArray();
    $urls = [];
    //print_r($links); exit; // check

    foreach ($links as $link){
        $urls[$link['id']] = $link['mirror_url_two'];
    }

    $threads = 50;
    $urls_pack = [];
    for ($i = 0; $i < count($urls); $i = $i + $threads) {
        $urls_pack[] = array_slice($urls, $i, $threads, TRUE);
    }

    //$urls_pack = []; $urls_pack[0][25198] = 'https://putstream.com/movie/the-legend-of-sarila?watching=y1zCEAmcIWptpmZf9jyAKwnqa'; // test

    $urls_p = [];
    $page = [];

    foreach ($urls_pack as $pack) {

        foreach ($pack as $k => $url) {

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_ENCODING, "gzip");
            curl_setopt($ch, CURLOPT_REFERER, 'https://putstream.com/');
            curl_setopt($ch, CURLOPT_USERAGENT, $refer);
            curl_setopt($ch, CURLOPT_PROXY, get_proxy());
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            $page[$k] = $mc->addCurl($ch);

        }

        foreach ($page as $k => $p) {

            if ($p->code != '200') continue;

            $result = $p->response;

            if(preg_match("/<script.*> window.location = \"(.*)\"; <\/script>/Us", $result, $mirror_url_one) == 1) {
                $db->update('123movie', ['mirror_url_three' => trim($mirror_url_one[1])], 'id='.$k);
            } else {
                preg_match("/<source src=\"(.*)\" type=\"/Us", $result, $done_url);
                if (@$done_url[1] != null) {
                    $db->update('123movie', ['done_url' => trim(@$done_url[1])], 'id=' . $k);
                }
            }

        }
        $page = [];

    }

    echo $mc->getSequence()->renderAscii();

}

if(@$_GET['go'] == 'IMDB') {

    //$sel = $db->select('123movie', '`done_url` IS NOT NULL AND `real_year` IS NULL'); // first_gen
    $sel = $db->select('123movie', '`done_url` IS NOT NULL AND `imdb_id` IS NULL'); // second_gen
    $links = $sel->getArray();
    $urls = [];
    //print_r($links); exit; // check

    foreach ($links as $link){
        $urls[$link['id']] = 'http://www.omdbapi.com/?t="'.urlencode($link['name']).'"&y="'.$link['tmp_year'].'"&apikey=';
    }


    $threads = 30;
    $urls_pack = [];
    for ($i = 0; $i < count($urls); $i = $i + $threads) {
        $urls_pack[] = array_slice($urls, $i, $threads, TRUE);
    }



    $page = [];

    foreach ($urls_pack as $pack) {

        foreach ($pack as $k => $url) {

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_ENCODING, "gzip");
            curl_setopt($ch, CURLOPT_REFERER, 'http://www.omdbapi.com/');
            curl_setopt($ch, CURLOPT_USERAGENT, $refer);
            //curl_setopt($ch, CURLOPT_PROXY, get_proxy());
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            $page[$k] = $mc->addCurl($ch);

        }

        foreach ($page as $k => $p) {

            if ($p->code != '200') continue;

            $result = $p->response;

            $imdb = json_decode($result, true);

            $item = [];
            //$item['genre'] = @$imdb['Genre']; // first_gen
            //$item['real_year'] = @$imdb['Year']; // first_gen
            //$item['poster'] = @$imdb['Poster']; // first_gen
            $item['imdb_id'] = @$imdb['imdbID']; // second_gen
            $item['votes'] = @$imdb['imdbVotes']; // second_gen
            //$item['rating'] = @$imdb['imdbRating']; // first_gen

            //$db->update('123movie', ['genre' => $item['genre'], 'real_year' => $item['real_year'], 'poster' => $item['poster'], 'rating' => $item['rating']], 'id=' . $k); // first_gen
            $db->update('123movie', ['imdb_id' => $item['imdb_id'], 'votes' => $item['votes']], 'id=' . $k); // second_gen

        }
        $page = [];

    }

    echo $mc->getSequence()->renderAscii();


}


if(isset($_GET['q'])) {

    $q = $db->escape($_GET['q']);
    $sel = $db->query('SELECT * FROM 123movie WHERE name like \'%'.$q.'%\' GROUP BY name ORDER BY CASE
              WHEN name like \''.$q.' %\' THEN 0
              WHEN name like \''.$q.'%\' THEN 1
              WHEN name like \'% '.$q.'%\' THEN 2
               ELSE 3
          END, name');
    $item = $sel->get();

    if(isset($item->id)){

        if(isset($item->done_url)){

            $ch = curl_init($item->done_url);
            curl_setopt($ch, CURLOPT_USERAGENT, $refer);
            //curl_setopt($ch, CURLOPT_PROXY, get_proxy());
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            $page = $mc->addCurl($ch);

            //print_r($page->response); exit;
            if($page->code == '302') {
                die('error');
            }

            if($page->code == '403') {
                unset($page);
                if (isset($item->mirror_url_three)) {
                    $url = $item->mirror_url_three;
                } elseif (isset($item->mirror_url_two)) {
                    $url = $item->mirror_url_two;
                } elseif (isset($item->mirror_url_one)) {
                    $url = $item->mirror_url_one;
                } else {
                    $url = $item->tmp_url;
                }

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                //curl_setopt($ch, CURLOPT_PROXY, get_proxy());
                curl_setopt($ch, CURLOPT_HEADER, true);
                $page = $mc->addCurl($ch);

                preg_match("/<source src=\"(.*)\" type=\"/Us", $page->response, $done_url);
                if (@$done_url[1] != null) {
                    $db->update('123movie', ['done_url' => trim($done_url[1])], 'id=' . $item->id);
                }
            }

            unset($item);
            $sel = $db->query('SELECT * FROM 123movie WHERE name like \'%'.$q.'%\' GROUP BY name ORDER BY CASE
              WHEN name like \''.$q.' %\' THEN 0
              WHEN name like \''.$q.'%\' THEN 1
              WHEN name like \'% '.$q.'%\' THEN 2
               ELSE 3
          END, name');
            $item = $sel->get();

            echo htmlspecialchars('<movie> <id>'.$item->id.'</id> <name>'.$item->name.'</name> <year>'.$item->real_year.'</year> <url>1</url> <img>'.$item->poster.'</img><ytid>'.$item->done_url.'</ytid> <imdbLink>11</imdbLink> <rating>'.$item->rating.'</rating> <storyline>11</storyline><poster>'.$item->poster.'</poster> <genres>'.$item->genre.'</genres> <casts>1</casts><casts_photoes>1</casts_photoes> <photoes/></movie>')."<br/><br/>";

        } else {
            die('no video');
        }

    } else {
        die('not found');
    }

}


if(isset($_GET['qq'])) {

    //$sel = $db->query('SELECT `name`, `done_url` from `123movie` ORDER BY `tmp_year` DESC'); // first_gen
    $sel = $db->query('SELECT `name`, `done_url` from `123movie` ORDER BY TRIM(REPLACE(`votes`, \',\', \'\')) * 1  DESC');
    $items = $sel->getAll();

    foreach ($items as $item) {
        if (isset($item->done_url)) {
            echo htmlspecialchars('<name>' . $item->name . '</name>') . '<br/>'. "\r\n";
        }
    }

}
