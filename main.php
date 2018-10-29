<?php

use voku\db\DB;
include_once 'core.php';
include_once 'cloudflare.php';

$db = DB::getInstance('localhost', 'movie', '', 'movies'); //mysql
$mc = JMathai\PhpMultiCurl\MultiCurl::getInstance();

if(isset($_GET['q'])) {

    $q = $db->escape($_GET['q']);
    $sel = $db->query('SELECT * FROM 123movie WHERE name like \'%'.$q.'%\' GROUP BY name ORDER BY CASE
              WHEN name like \''.$q.' %\' THEN 0
              WHEN name like \''.$q.'%\' THEN 1
              WHEN name like \'% '.$q.'%\' THEN 2
               ELSE 3
          END, name');
    $item = $sel->get();

    //print_r($q); exit;

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
            $sel = $db->query('SELECT * FROM pubfilmonline_b WHERE name like \'%' . $q . '%\' GROUP BY name ORDER BY CASE
              WHEN name like \'' . $q . ' %\' THEN 0
              WHEN name like \'' . $q . '%\' THEN 1
              WHEN name like \'% ' . $q . '%\' THEN 2
               ELSE 3
          END, name');
            $sel_arr = $sel->get();


            if (isset($sel_arr->id)) {

                if ($sel_arr->url != '' and $sel_arr->url != 'no video') {

                    $ch = curl_init($sel_arr->url);
                    curl_setopt($ch, CURLOPT_USERAGENT, $refer);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_NOBODY, true);
                    curl_setopt($ch, CURLOPT_HEADER, true);
                    //curl_setopt($ch, CURLOPT_PROXY, get_proxy());
                    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
                    $page = $mc->addCurl($ch);

                    //if ($page->code == '403' or $sel_arr->url == 'Temporary not found!') {
                    unset($page);

                    $url = 'http://pubfilmonline.net/?p='.$sel_arr->id;
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    //curl_setopt($ch, CURLOPT_PROXY, get_proxy());
                    curl_setopt($ch, CURLOPT_REFERER, 'http://pubfilmonline.net/');
                    curl_setopt($ch, CURLOPT_USERAGENT, $refer);
                    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'/cookie.txt');
                    curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__).'/cookie.txt');
                    $result = curl_exec($ch);

                    $cf = new CloudflareSolver($url, $result);
                    if($cf->isValid()) {
                        //echo 'Waiting for ' . $cf->getTimeout()/1000 . ' seconds...' . "\r\n";
                        usleep($cf->getTimeout() * 1000);
                        curl_setopt($ch, CURLOPT_URL, $cf->getSolvedUrl());
                        $result = curl_exec($ch);
                    }

                    $item = [];

                    if(preg_match("/sources: {\"error\":\"You don't have permission to access this video.\"}/s", $result)==1){
                        $item['url'] = 'no video';
                    } else {
                        preg_match_all("/{file.*:.*\"(.*)\"/Usm", $result, $url_array);
                        if(end($url_array[1]) != null) {
                            $item['url'] = str_replace('\\', '', end($url_array[1]));
                        }

                        //print_r($item['url']); exit;
                    }

                    if (@$item['url'] != null) {
                        $db->update('pubfilmonline_b', ['url' => trim($item['url'])], 'id=' . $sel_arr->id);
                    } else {
                        $item['url'] = 'Temporary not found!';
                        $db->update('pubfilmonline_b', ['url' => $item['url']], 'id=' . $sel_arr->id);
                    }

                    $sel_arr->url = $item['url'];

                    //}

                    echo htmlspecialchars('<movie> <id>'.$sel_arr->id.'</id> <name>'.$sel_arr->name.'</name> <year>'.$sel_arr->year.'</year> <url>1</url> <img>'.$sel_arr->poster.'</img><ytid>'.$sel_arr->url.'</ytid> <imdbLink>11</imdbLink> <rating>'.$sel_arr->rating.'</rating> <storyline>11</storyline><poster>'.$sel_arr->poster.'</poster> <genres>'.$sel_arr->genre.'</genres> <casts>1</casts><casts_photoes>1</casts_photoes> <photoes/></movie>')."<br/><br/>";


                } else {
                    echo 'not found';
                }

            } else {
                echo 'not found';
            }
        }

    } else {
        $sel = $db->query('SELECT * FROM pubfilmonline_b WHERE name like \'%' . $q . '%\' GROUP BY name ORDER BY CASE
              WHEN name like \'' . $q . ' %\' THEN 0
              WHEN name like \'' . $q . '%\' THEN 1
              WHEN name like \'% ' . $q . '%\' THEN 2
               ELSE 3
          END, name');
        $sel_arr = $sel->get();


        if (isset($sel_arr->id)) {

            if ($sel_arr->url != '' and $sel_arr->url != 'no video') {

                $ch = curl_init($sel_arr->url);
                curl_setopt($ch, CURLOPT_USERAGENT, $refer);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_NOBODY, true);
                curl_setopt($ch, CURLOPT_HEADER, true);
                //curl_setopt($ch, CURLOPT_PROXY, get_proxy());
                curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
                $page = $mc->addCurl($ch);

                //if ($page->code == '403' or $sel_arr->url == 'Temporary not found!') {
                unset($page);

                $url = 'http://pubfilmonline.net/?p='.$sel_arr->id;
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                //curl_setopt($ch, CURLOPT_PROXY, get_proxy());
                curl_setopt($ch, CURLOPT_REFERER, 'http://pubfilmonline.net/');
                curl_setopt($ch, CURLOPT_USERAGENT, $refer);
                curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'/cookie.txt');
                curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__).'/cookie.txt');
                $result = curl_exec($ch);

                $cf = new CloudflareSolver($url, $result);
                if($cf->isValid()) {
                    //echo 'Waiting for ' . $cf->getTimeout()/1000 . ' seconds...' . "\r\n";
                    usleep($cf->getTimeout() * 1000);
                    curl_setopt($ch, CURLOPT_URL, $cf->getSolvedUrl());
                    $result = curl_exec($ch);
                }

                $item = [];

                if(preg_match("/sources: {\"error\":\"You don't have permission to access this video.\"}/s", $result)==1){
                    $item['url'] = 'no video';
                } else {
                    preg_match_all("/{file.*:.*\"(.*)\"/Usm", $result, $url_array);
                    if(end($url_array[1]) != null) {
                        $item['url'] = str_replace('\\', '', end($url_array[1]));
                    }

                    //print_r($item['url']); exit;
                }

                if (@$item['url'] != null) {
                    $db->update('pubfilmonline_b', ['url' => trim($item['url'])], 'id=' . $sel_arr->id);
                } else {
                    $item['url'] = 'Temporary not found!';
                    $db->update('pubfilmonline_b', ['url' => $item['url']], 'id=' . $sel_arr->id);
                }

                $sel_arr->url = $item['url'];

                //}

                echo htmlspecialchars('<movie> <id>'.$sel_arr->id.'</id> <name>'.$sel_arr->name.'</name> <year>'.$sel_arr->year.'</year> <url>1</url> <img>'.$sel_arr->poster.'</img><ytid>'.$sel_arr->url.'</ytid> <imdbLink>11</imdbLink> <rating>'.$sel_arr->rating.'</rating> <storyline>11</storyline><poster>'.$sel_arr->poster.'</poster> <genres>'.$sel_arr->genre.'</genres> <casts>1</casts><casts_photoes>1</casts_photoes> <photoes/></movie>')."<br/><br/>";


            } else {
                echo 'not found';
            }

        } else {
            echo 'not found';
        }
    }

}

