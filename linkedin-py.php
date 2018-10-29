<?php

if(isset($_GET['q'])) {

    $ssh = ssh2_connect('', 22);
    if (false === $ssh) {
        die('connection failed');
    }

    $user = '';
    $password = '';

    $auth = @ssh2_auth_password($ssh, $user, $password);
    if (false === $auth) {
        die('authentication failed');
    }


    $str = ssh2_exec($ssh, 'cd /var/www/python/linked/;' . 'scrapy crawl linkedin -a query='.$_GET['q']);
    $err_str = ssh2_fetch_stream($str, SSH2_STREAM_STDERR);

    stream_set_blocking($str, true);
    stream_set_blocking($err_str, true);
    stream_get_contents($str); //
    stream_get_contents($err_str); // 
    sleep(10);

    //echo "Output: " . stream_get_contents($str);
    //echo "Error: " . stream_get_contents($err_str);


    $manager = new MongoDB\Driver\Manager('mongodb://localhost:27017');
    $filter = [];
    $options = [
        'sort' => ['_id' => -1],
        'limit' => 1
    ];

    $query = new \MongoDB\Driver\Query($filter, $options);
    $rows = $manager->executeQuery('parsing.linked', $query);

    $doc = [];
    foreach ($rows as $document) {
        $rows = (array)$document;

		foreach($rows as $document_t){
			$doc[] = $document_t;
	}
		$data = $doc;
		echo json_encode($data);

    }


}