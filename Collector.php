<?php
//penghui@gmail.com

include 'vendor/autoload.php';

echo "<pre>";

$login = @$_REQUEST["login"];
$token = @$_REQUEST["token"];

$owner = @$_REQUEST["owner"];
$repo  = @$_REQUEST["repo"];

$startPRNumber = isset($_REQUEST["startPRNumber"]) ? $_REQUEST["startPRNumber"] : 1;
$endPRNumber = isset($_REQUEST["endPRNumber"]) ? $_REQUEST["endPRNumber"] : 100;

//check params
if (!(is_numeric($startPRNumber) && is_numeric($endPRNumber) && $startPRNumber <= $endPRNumber && $startPRNumber > 0)) {
	exit("wrong parameter startPRNumber or endPRNumber");
}
if (mb_strlen($login) == 0 || mb_strlen($token) == 0) {
	exit("parameter login or token is empty");
}
if (mb_strlen($owner) == 0 || mb_strlen($repo) == 0) {
	exit("parameter owner or repo is empty");
}

$client = new \GuzzleHttp\Client();
$options = ["auth" => [$login, $token], "headers" => ["Accept" => "application/vnd.github.black-cat-preview+json"]];

//check auth
try {
	$loginUser = json_decode($client->request("GET", "https://api.github.com/user", ["auth" => [$login, $token]])->getBody());
	if (!$loginUser->login) {
		exit("auth failed");
	}
}catch(Exception $e){exit($e->getMessage());}

//check data dir exists and has write permission
if (!is_dir(__DIR__ . "/data")) {
	exit("data dir does not exist");
}

if (!is_writable(__DIR__ . "/data")) {
	exit("data dir is not writable");
}

for ($pr = $startPRNumber; $pr <= $endPRNumber; $pr++) {
	$filename = __DIR__ . "/data/" . $owner . "_" . $repo . "_" . $pr . ".json";
	if (file_exists($filename)) {
		echo "skipping ".$pr."\n";
		ob_flush();
		flush();
		continue;
	}

	echo "fetching ".$pr."\n";
	ob_flush();
	flush();

	$pullURL = "https://api.github.com/repos/".$owner."/".$repo."/pulls/".$pr;
	$pullURLResponse = $client->request("GET", $pullURL, $options);
	$pullObj = json_decode($pullURLResponse->getBody());

	$reviewsURL = "https://api.github.com/repos/".$owner."/".$repo."/pulls/".$pr."/reviews";
	$reviewsURLResponse = $client->request("GET", $reviewsURL, $options);
	$reviewsObj = json_decode($reviewsURLResponse->getBody());

	$pullObj->reviews = $reviewsObj;


	file_put_contents($filename, json_encode($pullObj));
}

echo ('done');