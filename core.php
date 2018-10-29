<?php

//echo phpinfo(); exit();

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('date.timezone', 'Europe/Moscow');

$refer = 'Mozilla/5.0 (iPhone; CPU iPhone OS 5_0 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Version/5.1 Mobile/9A334 Safari/7534.48.3';
$refer_one = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36';

require_once 'vendor/autoload.php';

function get_proxy(){

    $proxy = json_decode(file_get_contents('https://kingproxies.com/api/v2/proxies.json?country_code=US&key=&type=elite&alive=true')); // &country_code=DE / US  &protocols=socks5 &new=true

    if($proxy == null){
        //die('proxy retrieve error');
        get_proxy();
    }

    $proxies = [];
    foreach ($proxy->data->proxies as $pr) {
        if($pr->protocols[0] == 'http') {
            $proxies[] = $pr->ip.':'.$pr->port;
        }
    }

    // checking
    /*
    $proxies_c = [];
    $mc = JMathai\PhpMultiCurl\MultiCurl::getInstance();
    foreach($proxies as $prox) {
        $ch = curl_init('https://2ip.ru');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
        curl_setopt($ch, CURLOPT_PROXY, $prox);
        $page = $mc->addCurl($ch);
        if($page->code != 0){
            $proxies_c[] = $prox;
            break;
        }
    }
    */

    return $proxies[rand(min(array_keys($proxies)), max(array_keys($proxies)))];

}
