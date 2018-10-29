<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

use voku\db\DB;
require_once 'vendor/autoload.php';
include_once 'cloudflare.php';

$db = DB::getInstance('localhost', 'movie', '', 'movies');

$url = 'http://pubfilmonline.net';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_REFERER, 'http://pubfilmonline.net/');
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; CPU iPhone OS 5_0 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Version/5.1 Mobile/9A334 Safari/7534.48.3');

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

$end = 0;
// 139 (All currently) - not haven last page with ids 1230 1217 1213 1212 1211 1209 1208 1207 1206
for ($i=1; $i<=10; $i++){
    //if($end == 1) { break; } // NEW
    if($i == 1) { $url = 'http://pubfilmonline.net/movies/'; } else { $url = 'http://pubfilmonline.net/movies/page/'.$i.'/'; }
    curl_setopt($ch, CURLOPT_URL, $url);
    $result = curl_exec($ch);
    $cf = new CloudflareSolver($url, $result);
    if($cf->isValid()) {
        //echo 'Waiting for ' . $cf->getTimeout()/1000 . ' seconds...' . "\r\n";
        usleep($cf->getTimeout() * 1000);
        curl_setopt($ch, CURLOPT_URL, $cf->getSolvedUrl());
        $result = curl_exec($ch);
    }
    preg_match_all("/<article id=\"post-[0-9]+\" class=\"item movies\">(.*)<\/article>/Us", $result, $result_array);

    foreach ($result_array[1] as $kk => $article){

        preg_match("/<a href=\"(.*)\"><img src=\".*\" alt=\"(.*)\"><\/a>/s", $article, $article_array);

        /*
         * // NEW
        preg_match("/<article id=\"post-([0-9]+)\" class=/", $result_array[0][$kk], $film_id);
        $sel = $db->select('pubfilmonline', 'id='.$film_id[1]);
        $sel_arr = $sel->get();
        if(!$sel->is_empty()) {
            $end = 1;
            break;
        }
        */

        $item = [];
        $item['url'] = $article_array[1];
        $item['name'] = html_entity_decode($article_array[2]);

        preg_match("/(\(\d{4}\))/", $item['name'], $name_array);
        $item['year'] = str_replace(array('(', ')'), '', $name_array[1]);
        $item['clear_name'] = trim(str_replace($name_array[1], '', $item['name']));

        curl_setopt($ch, CURLOPT_URL, $item['url']);
        $result = curl_exec($ch);
        $cf = new CloudflareSolver($url, $result);
        if($cf->isValid()) {
            //echo 'Waiting for ' . $cf->getTimeout()/1000 . ' seconds...' . "\r\n";
            usleep($cf->getTimeout() * 1000);
            curl_setopt($ch, CURLOPT_URL, $cf->getSolvedUrl());
            $result = curl_exec($ch);
        }

        if(preg_match("/sources: {\"error\":\"You don't have permission to access this video.\"}/s", $result)==1){
            $item['real_url'] = 'no video';
        } else {
            preg_match_all("/sources: \[{\"file\":\"(.*)\",\"type\":\"/Usi", $result, $url_array);
            $item['real_url'] = str_replace('\\', '', @$url_array[1][0]);
        }

        preg_match_all("/<input type=\"hidden\" name=\"idpost\" value=\"([0-9]+)\">/s", $result, $article_id_array);

        $item['id'] = $article_id_array[1][0];


        curl_setopt($ch, CURLOPT_URL, 'http://www.omdbapi.com/?t="'.urlencode($item['clear_name']).'"&y="'.$item['year'].'"&apikey=');
        curl_setopt($ch, CURLOPT_REFERER, 'http://www.omdbapi.com/');
        $result = curl_exec($ch);

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

    }

}

curl_close($ch);


$result = $db->query("SELECT * FROM `pubfilmonline` WHERE `url` != '' AND `url` != 'no video' AND `genre` != '' ORDER BY `id` DESC LIMIT 255");
$db->query("TRUNCATE TABLE `pubfilmonline`"); // NEW
$movies = $result->fetchAll();

if ($db->errors()) {
    //echo $db->lastError();
}

foreach ($movies as $movie){
    echo htmlspecialchars('<movie> <id>'.$movie->id.'</id> <name>'.$movie->name.'</name> <year>'.$movie->year.'</year> <url>1</url> <img>'.$movie->poster.'</img><ytid>'.$movie->url.'</ytid> <imdbLink>11</imdbLink> <rating>'.$movie->rating.'</rating> <storyline>11</storyline><poster>'.$movie->poster.'</poster> <genres>'.$movie->genre.'</genres> <casts>1</casts><casts_photoes>1</casts_photoes> <photoes/></movie>')."<br/><br/>";
}
