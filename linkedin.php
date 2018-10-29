<?php

use voku\db\DB;
include_once 'core.php';

$db = DB::getInstance('localhost', 'linked', '', 'default');

$file = file('newfile.txt');
$m_comp = explode(',', $file[0]);
$companies = [];
$m_comp = array_unique($m_comp);
foreach ($m_comp as $k_comp) {
    if($k_comp == null) continue;
    $companies[] = $k_comp;
}

//print_r($companies); exit();
//print_r(phpinfo()); exit();

//$companies = [];$companies[0]= 'Sirius XM Holdings Inc'; // DEBUG

exit;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://www.linkedin.com/uas/login');
curl_setopt($ch, CURLOPT_REFERER, 'https://www.linkedin.com');
curl_setopt($ch, CURLOPT_USERAGENT, $refer);
curl_setopt($ch, CURLOPT_PROXY, get_proxy());
curl_setopt($ch, CURLOPT_AUTOREFERER, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'cookie.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__).'cookie.txt');
$ex = curl_exec($ch);

$sfr = [];
preg_match('/<input type="hidden" name="loginCsrfParam" value="(.*)" id="loginCsrfParam-login">/siU', $ex, $sfr['1']);
preg_match('/<input type="hidden" name="csrfToken" value="(.*)" id="csrfToken-login">/siU', $ex, $sfr['2']);
preg_match('/<input type="hidden" name="sourceAlias" value="(.*)" id="sourceAlias-login">/siU', $ex, $sfr['3']);

$var = array(
    'isJsEnabled' => 'false',
    'source_app' => '',
    'clickedSuggestion' => 'false',
    'session_key' => trim(''),
    'session_password' => trim(''),
    'signin' => 'Sign In',
    'session_redirect' => '',
    'trk' => '',
    'fromEmail' => '',
    'loginCsrfParam' => $sfr['1']['1'],
    'csrfToken' => $sfr['2']['1'],
    'sourceAlias' => $sfr['3']['1']
);

$post_array = array();
foreach ($var as $key => $value)
{
    $post_array[] = urlencode($key) . '=' . urlencode($value);
}
$post_string = implode('&', $post_array);

curl_setopt($ch, CURLOPT_URL, 'https://www.linkedin.com/uas/login-submit');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_PROXY, get_proxy());
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
curl_exec($ch);


foreach ($companies as $company_name):

    curl_setopt($ch, CURLOPT_URL, 'https://www.linkedin.com/search/results/companies/?keywords='.urlencode(trim(strtolower($company_name))));
    curl_setopt($ch, CURLOPT_PROXY, get_proxy());
    $search = curl_exec($ch);

    preg_match('/company:([0-9]+)\&quot;,&quot;\$type&quot;:&quot;com.linkedin.voyager.search.SearchCompany&quot;,&quot;\$id&quot;:&quot;.{22}&#61;&#61;,0,elements,.{8}-.{4}-.{4}-.{4}-.{12}-0,hitInfo,com/siU', $search, $company_id);
    preg_match('/fs_miniCompany:'.$company_id[1].'&quot;,&quot;name&quot;:&quot;(.*)&quot;/siU', $search, $real_name);

    if(!$db->select('linked', 'id='.$company_id[1])->is_empty()){
        continue;
    }

//print_r($company_id); exit;
    curl_setopt($ch, CURLOPT_URL, 'https://www.linkedin.com/company-beta/'.$company_id[1].'/');
    curl_setopt($ch, CURLOPT_PROXY, get_proxy());
//curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-IsAJAXForm: 1'));
    $page = curl_exec($ch);

    preg_match('/fs_normalized_jobPosting:.*&quot;],&quot;paging&quot;:{&quot;total&quot;:([0-9]+),&quot;count&quot;:/siU', $page, $jobs);
    preg_match('/&quot;staffCount&quot;:([0-9]+),&quot;companyEmployeesSearchPageUrl&quot;:/siU', $page, $employ);
//preg_match('/&quot;name&quot;:&quot;(.*)&quot;,&quot;jobSearchPageUrl/siU', $page, $real_name);


    curl_setopt($ch, CURLOPT_URL, 'https://www.linkedin.com/voyager/api/feed/updates?companyId='.$company_id[1].'&count=3&q=companyFeed');
    curl_setopt($ch, CURLOPT_PROXY, get_proxy());
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "cookie: JSESSIONID=\"".$sfr['2']['1']."\";",
        "csrf-token: ".$sfr['2']['1']
    ));

    $api = curl_exec($ch);

    preg_match('/{"entityUrn":"urn:li:fs_followingInfo:urn:li:company:'.$company_id[1].'","following":false,"followerCount":([0-9]+)}/siU', $api, $followers);

    if(@$company_id[1] != null) {
        $insertArray = ['id' => @$company_id[1]];
        if(@$company_name != null) { $insertArray['company'] = $company_name; }
        if(@$employ[1] != null) { $insertArray['employees'] = $employ[1]; }
        if(@$followers[1] != null) { $insertArray['followers'] = $followers[1]; }
        if(@$jobs[1] != null) { $insertArray['jobs'] = $jobs[1]; }
        if(@$real_name[1] != null) { $insertArray['real_company'] = htmlspecialchars_decode($real_name[1]); }
        $db->insert('linked', $insertArray);

        if ($db->errors()) {
            echo $db->lastError();
        }
    }

endforeach;

curl_close($ch);


