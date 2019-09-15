<?php

function curlGet($url)
{
	// Initialising cURL session
	$ch = curl_init();

	// Setting cURL options
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/html;charset=utf-8"));

	// Executing cURL session
	$results = curl_exec($ch);

	// Closing cURL session
	curl_close($ch);

	return $results;
}

function strBetStr($string) {
	$str = explode("window._sharedData = ", $string);
	$str = explode(";</script>", $str[1]);
	return $str[0];
}

function downloadImg($img, $code, $count){
	file_put_contents('photo/' . $code . ".jpg", file_get_contents($img));
	echo $count . "- ". $code . ".jpg has download.\n";
}

function getUserData($user) {
	$reqResult = curlGet("https://www.instagram.com/" . $user);

	if (strpos($reqResult, "Sorry, this page isn&#39;t available.") !== false) {
		echo "It's been you are looking for acount, that is not exist.\n";
		return exit;
	}
	
	$json = json_decode(strBetStr($reqResult));
	$userData = $json->entry_data->ProfilePage[0]->graphql->user;

	if ($userData->is_private) {
		echo "This Account is Private.\n";
		return exit;
	}

	return $userData;
}

function getAllPhoto($id, $next) {
	$url = "https://www.instagram.com/graphql/query/?query_hash=58b6785bea111c67129decbe6a448951&variables=";
	$url .= urlencode('{"id":"'.$id.'","first":12,"after":"'.$next.'"}');
	$json = json_decode(curlGet($url));
	$pageData = $json->data->user->edge_owner_to_timeline_media;
	return $pageData;
}

function checkNum($count, $num) {
	if ($num == -1) {
		return false;
	}
	else {
		return $num <= $count;
	}
}


if ($argc > 3 || $argc < 2) {
	echo "Usage: ./instadown.php [Username] [Option numberofphotos]\n";
	return exit;
}

$numOfPhoto = -1;

if (isset($argv[2])) {
	$numOfPhoto = $argv[2];
}


$user = getUserData($argv[1]);
$count = 0;

$userId = $user->id;
$userPhotos = $user->edge_owner_to_timeline_media->edges;
$pageInfo = $user->edge_owner_to_timeline_media->page_info;


if ($userPhotos > 0) {
	for ($i=0; $i < count($userPhotos); $i++) { 
		if (checkNum($count, $numOfPhoto)) {
			echo "The download has completed.\n";
			return exit;
		}
		downloadImg($userPhotos[$i]->node->display_url, $userPhotos[$i]->node->id, ++$count);
	}
	while ($pageInfo->has_next_page) {
		$user = getAllPhoto($userId, $pageInfo->end_cursor);
		$userPhotos = $user->edges;
		$pageInfo = $user->page_info;

		for ($i=0; $i < count($userPhotos); $i++) {
			if (checkNum($count, $numOfPhoto)) {
				echo "The download has completed.\n";
				return exit;
			}
			downloadImg($userPhotos[$i]->node->display_url, $userPhotos[$i]->node->id, ++$count);
		}

	}
}



?>