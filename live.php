<?php

include_once 'core.php';
include_once 'cloudflare.php';

use voku\db\DB;
use PHPHtmlParser\Dom;
use JonnyW\PhantomJs\Client;
//use JonnyW\PhantomJs\DependencyInjection\ServiceContainer;

$db = DB::getInstance('localhost', 'movie', '', 'movies');

if(isset($_POST['url'])){
    $db->update('live', ['url' => $_POST['url']], ['open_id=' => $_POST['file']]);
    //header("HTTP/1.1 301 Moved Permanently");
    //header("Location: /live.php?file=".$_POST['file']);
    exit();
}

if(isset($_GET['file'])){
    $sel = $db->select('live', ['open_id =' => $_GET['file']]);
    $sel_arr = $sel->get();
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: ".$sel_arr->url);
    //echo htmlspecialchars('<movie> <id>'.$sel_arr->open_id.'</id> <name>'.$sel_arr->name.'</name> <year>'.$sel_arr->year.'</year> <url>1</url> <img>'.$sel_arr->poster.'</img><ytid>'.$sel_arr->url.'</ytid> <imdbLink>11</imdbLink> <rating></rating> <storyline>11</storyline><poster>'.$sel_arr->poster.'</poster> <genres>'.$sel_arr->genre.'</genres> <casts>1</casts><casts_photoes>1</casts_photoes> <photoes/></movie>')."<br/><br/>";
    exit();
}

$ref = 'http://123moviesfreez.com';
$q = str_replace(' ', '+', urldecode(@$_GET['q']));

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://123moviesfreez.com/search/'.$q.'.html');
curl_setopt($ch, CURLOPT_REFERER, $ref);
curl_setopt($ch, CURLOPT_USERAGENT, $refer);
curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'/cookie.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__).'/cookie.txt');
$result = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//print_r($code);

$dom = new Dom;
$dom->load($result);
$a = $dom->find('.ml-item a')[0];
if($a === NULL) {
    echo 'not found';
    exit();
}

$client = Client::getInstance();
$client->getEngine()->setPath('/usr/local/bin/phantomjs');
$request = $client->getMessageFactory()->createRequest($a->getAttribute('href'));
$response = $client->getMessageFactory()->createResponse();
$client->send($request, $response);

$dom_two = new Dom;
$dom_two->load($response->getContent());
$item = [];

$id = $dom_two->find('#mediaplayer iframe')[0];
preg_match('/http.*:\/\/.*\/embed\/(.*)\//', $id->getAttribute('src'), $out);
$item['open_id'] = $out[1];

$sel = $db->select('live', ['open_id =' => $item['open_id']]);
if($sel->is_empty()) {
    $title = $dom_two->find('input[name=movies_title]');
    $title_name = $title->getAttribute('value');
    preg_match("/(\(\d{4}\))/", $title_name, $name_array);
    $item['name'] = trim(str_replace($name_array[1], '', $title_name));
    $item['year'] = str_replace(array('(', ')'), '', $name_array[1]);

    $genre = $dom_two->find('input[name=movies_cate]');
    $item['genre'] = $genre->getAttribute('value');

    $poster = $dom_two->find('input[name=phimimg]');
    $item['poster'] = $poster->getAttribute('value');

    $db->insert('live', $item);
}

?>
<html>
<head>
    <script type="application/javascript" src="//code.jquery.com/jquery-3.2.1.min.js"></script>
</head>
    <script type="application/javascript">
        $.ajax({
            type: 'GET',
            url: 'https://api.openload.co/1/file/dlticket',
            //data: { login: "", key: "", file: "<?=$item['open_id']?>" },
            data: { login: "", key: "", file: "<?=$item['open_id']?>" },
            success: function(data) {
                //console.log(data);
                if (data.result !== null) {
                    $.ajax({
                        type: 'GET',
                        url: 'https://api.openload.co/1/file/dl',
                        data: {file: "<?=$item['open_id']?>", ticket: data.result.ticket},
                        success: function (datas) {
                            $.ajax({
                                type: 'POST',
                                data: {file: "<?=$item['open_id']?>", url: datas.result.url},
                                success: function (datas) {
                                    url = "http://site.com/live.php?file=<?=$item['open_id']?>";
                                    $(location).attr("href", url);
                                }
                            });
                            //console.log(datas.result.url);
                        }
                    });
                } else {
                    $('#info').text(data.msg);
                }
            }
        });
    </script>
    <div id="info"></div>
</html>
<?php

exit;





$video = $_GET['id'];
if($video == '') {
    echo 'Where is the media ID?';
    exit();
} else {

    $client = Client::getInstance();

    $fileName = 'script';
    $location = '/var/www/phantomjs/';

    $serviceContainer = ServiceContainer::getInstance();
    $procedureLoader = $serviceContainer->get('procedure_loader_factory')->createProcedureLoader($location);
    $client->setProcedure($fileName);
    $client->getProcedureLoader()->addLoader($procedureLoader);

    $client->getEngine()->setPath('/usr/local/bin/phantomjs');

    if(strpos($video, '.') !== False) {
        $video = explode('.', $video)[0];
    }

    $request = $client->getMessageFactory()->createRequest('https://openload.co/f/'.$video);
    $response = $client->getMessageFactory()->createResponse();

    $client->send($request, $response);

    if($response->getStatus() === 0) { // 200 /* script is fixed */
        $openload = $response->getContent();

        //echo '<pre>';
        //echo $openload;
        //echo '</pre>';

        /*
        if(strpos($openload, 'We are sorry!') !== False) {
            echo json_encode(array('error' => '404', 'msg' => 'File not found'));
            exit();
        }
        $openload = explode('<span id="streamurl">', $openload)[1];
        $file = 'https://oload.tv/stream/'.explode('</span>', $openload)[0];
        $headers = get_headers($file,1);
        */

        $headers = get_headers($openload,1);
        $headers_one = get_headers($headers['Location'],1);
        $path = $headers['Location'];
        //print_r($headers);

        //exit;
        $filename = explode('?', end(explode('/',$headers['Location'])))[0];
        $file = explode('?', $headers['Location'])[0];
        header('Content-Type: video/mp4');
        header('Content-Length: '.$size);

        $f = fopen($file, "rb");
        while (!feof($f)) {
            echo fread($f, 8*1024);
            flush();
            ob_flush();
        }
        exit;


        set_time_limit(0);
        header('HTTP/1.1 206 Partial Content');
        header('Content-Type: video/mp4');
        header('Content-Length: '.$headers_one['Content-Length']);
        #header('Accept-Ranges: bytes');
        #header('Content-Range bytes');

        //readfile($path);
        //exit;

        $handle = fopen($path, "rb");
        while (!feof($handle)) {
            echo fread($handle, 1000 * 1024);
            flush(); ob_flush();
        }
        fclose($handle);

        die();
    } else {
        echo json_encode(array('error' => $response->getStatus(), 'msg' => 'Server error'));
        exit();
    }
}

//// https://packagist.org/packages/paquettg/php-html-parser