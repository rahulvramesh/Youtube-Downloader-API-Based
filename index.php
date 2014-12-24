<?php

/*
* File For My Custom Download 
*
*/

include_once('config.php');
ob_start();// if not, some servers will show this php warning: header is already set in line 46...

function clean($string) {
   $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
   return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
}

function formatBytes($bytes, $precision = 2) { 
    $units = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'); 
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . '' . $units[$pow]; 
} 
function is_chrome(){
	$agent=$_SERVER['HTTP_USER_AGENT'];
	if( preg_match("/like\sGecko\)\sChrome\//", $agent) ){	// if user agent is google chrome
		if(!strstr($agent, 'Iron')) // but not Iron
			return true;
	}
	return false;	// if isn't chrome return false
}

function getImage($id){
    
    return "http://i1.ytimg.com/vi/".$id."/default.jpg";
}

$my_vid["status"] = "OK";

if(isset($_REQUEST['videoid'])) {
	$my_id = $_REQUEST['videoid'];
	if(strlen($my_id)>11){
		$url   = parse_url($my_id);
		$my_id = NULL;
		if( is_array($url) && count($url)>0 && isset($url['query']) && !empty($url['query']) ){
			$parts = explode('&',$url['query']);
			if( is_array($parts) && count($parts) > 0 ){
				foreach( $parts as $p ){
					$pattern = '/^v\=/';
					if( preg_match($pattern, $p) ){
						$my_id = preg_replace($pattern,'',$p);
						break;
					}
				}
			}
			if( !$my_id ){
                $my_vid["status"] = "ERR";
                $my_vid["ERR_MSG"] = "No video id passed in";
                $output =  json_encode($my_vid);
                print_r($output);
				exit;
			}
		}else{
			$my_vid["status"] = "ERR";
            $my_vid["ERR_MSG"] = "Ivalid URL";
            $output =  json_encode($my_vid);
            print_r($output);
			exit;
		}
	}
} else {
	$my_vid["status"] = "ERR";
    $my_vid["ERR_MSG"] = "No Video ID Passed In";
    $output =  json_encode($my_vid);
    print_r($output);
	exit;
}

if(isset($_REQUEST['type'])) {
	$my_type =  $_REQUEST['type'];
} else {
	$my_type = 'redirect';
}

if ($my_type == 'Download') {}

$my_video_info = 'http://www.youtube.com/get_video_info?&video_id='. $my_id.'&asv=3&el=detailpage&hl=en_US'; //video details fix *1
$my_video_info = curlGet($my_video_info);

/* TODO: Check return from curl for status code */

$thumbnail_url = $title = $url_encoded_fmt_stream_map = $type = $url = '';

parse_str($my_video_info);

//Store Image And Title To An Array

$my_vid["img"] = getImage($my_id);
$my_vid["title"] = $title;
$cleanedtitle = clean($title);


if(isset($url_encoded_fmt_stream_map)) {
	/* Now get the url_encoded_fmt_stream_map, and explode on comma */
	$my_formats_array = explode(',',$url_encoded_fmt_stream_map);
	
} else {
	echo '<p>No encoded format stream found.</p>';
	echo '<p>Here is what we got from YouTube:</p>';
	echo $my_video_info;
}

if (count($my_formats_array) == 0) {
	echo '<p>No format stream map found - was the video id correct?</p>';
	exit;
}

/* create an array of available download formats */
$avail_formats[] = '';
$i = 0;
$ipbits = $ip = $itag = $sig = $quality = '';
$expire = time(); 

foreach($my_formats_array as $format) {
	parse_str($format);
	$avail_formats[$i]['itag'] = $itag;
	$avail_formats[$i]['quality'] = $quality;
	$type = explode(';',$type);
	$avail_formats[$i]['type'] = $type[0];
	$avail_formats[$i]['url'] = urldecode($url) . '&signature=' . $sig;
	parse_str(urldecode($url));
	$avail_formats[$i]['expires'] = date("G:i:s T", $expire);
	$avail_formats[$i]['ipbits'] = $ipbits;
	$avail_formats[$i]['ip'] = $ip;
	$i++;
}



for ($i = 0; $i < count($avail_formats); $i++) {

    $qty = $avail_formats[$i]['quality'];
    $my_vid["Download"][$i]["quality"] = $qty;
    $my_vid["Download"][$i]["url"] =  $avail_formats[$i]['url'];
    $my_vid["Download"][$i]["size"] = formatBytes(get_size($avail_formats[$i]['url']));
   // $my_vid["Download"][$i]["proxy"] = formatBytes(get_size($avail_formats[$i]['url'])); For Proxy Download
     
}


// Output


$output =  json_encode($my_vid);
print_r($output);

?>