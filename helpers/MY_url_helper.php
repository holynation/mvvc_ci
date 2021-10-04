<?php 

//function for sending post http request using curl
function sendPost($url,$post,&$errorMessage,$returnResult=false){
	$res = curl_init($url);
	curl_setopt($res, CURLOPT_POST,true);
	curl_setopt($res, CURLOPT_POSTFIELDS, $post);
	$certPath =str_replace( "application\helpers\MY_url_helper.php",'cacert.pem', __FILE__);
	curl_setopt($res, CURLOPT_CAINFO, $certPath);
	if ($returnResult) {
		curl_setopt($res, CURLOPT_RETURNTRANSFER, true);
	}
	$referer = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	curl_setopt($res, CURLOPT_REFERER, $referer);
	$result = curl_exec($res);
	$errorMessage = curl_error($res);
	curl_close($res);
	return $result;
}

function request($url,$type,$headers=array(),$param,$return=false,&$output='',&$errorMessage=''){
    $res = curl_init($url);
    if ($type=='post') {
        curl_setopt($res, CURLOPT_POST,true);
        curl_setopt($res, CURLOPT_POSTFIELDS, $param);
    }
    else if($type=='get'){
        $getOption =array(
              CURLOPT_ENCODING => "",
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 30,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => "GET");
        curl_setopt_array($res, $getOption);
    }
    //check if the quest is a secure one
    if (strtolower(substr($url, 0,5))=='https') {
        // curl_setopt($res, CURLOPT_CAINFO, $certPath);
        curl_setopt($res, CURLOPT_SSL_VERIFYSTATUS, false);
        curl_setopt($res, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($res, CURLOPT_SSL_VERIFYPEER, false);
    }
    
    if ($return) {
        curl_setopt($res, CURLOPT_RETURNTRANSFER, true);
    }

    if(!empty($headers)){
    	$formattedHeader = formatHeader($headers);
    	curl_setopt($res, CURLOPT_HTTPHEADER, $formattedHeader);
    }
    
    $result = curl_exec($res);
    $errorMessage = curl_error($res);
    $output = $result;
    curl_close($res);
    return !$errorMessage;
}

function formatHeader($header)
{
    if (!$header) {
        return $header;
    }
    $keys = array_keys($header);
    if (is_numeric($keys[0])) {
        //if has numberic index, should me the header has already been formated inthe right way
        return $header;
    }
    $result = array();
    foreach ($header as $key => $value) {
        $temp = "$key: $value";
        $result[]=$temp;
    }
    return $result;
}

function iPtoLocation($ip){ 
    $apiURL = 'https://api.ipgeolocationapi.com/geolocate/'.$ip; 
     
    // Make HTTP GET request using cURL 
    $ch = curl_init($apiURL); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    $apiResponse = curl_exec($ch); 
    if($apiResponse === FALSE) { 
        $msg = curl_error($ch); 
        curl_close($ch); 
        return false; 
    } 
    curl_close($ch); 
     
    // Retrieve IP data from API response 
    $ipData = json_decode($apiResponse, true); 
     
    // Return geolocation data 
    return !empty($ipData)?$ipData:false; 
}

function getLatLong($ip,$type){
    $data = iPtoLocation($ip);
    if($data){
        return $data['geo'][$type];
    }
}

function getRef()
{
    return Date("Ymdhis");
}

?>