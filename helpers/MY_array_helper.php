<?php 
//this function checks if an array is sequential or not
//returns -1 if array is null 0 if not sequential and 1 if it is
include_once('MY_string_helper.php');

function isSequential($array){
	if (empty($array)) {
		return -1;
	}
	if(isset($array[0]) && isset($array[count($array)-1])){
		return 1;
	}
	else{
		return 0;
	}
}

//this function can get a range of element from a given array
//useful for asssociative array will work for non-associative but will be slower. user subArray instead.
function subArrayAssoc($array,$start,$len){
	$count = count($array);
	$validity = $count - ($start + $len);//confirmed
	if ($validity < 0) {
		throw new Exception("error occur validity=$validity count is $count", 1);
		
		return false;
	}
	//extract the array
	$keys = array_keys($array);
	$result = array();
	for ($i=$start; $i < ($start + $len); $i++) { 
		$key = $keys[$i];
		$result[$key] =$array[$key];
	}
	return $result;
}

//this function will return a subArray of an array(most useful if the array is not associative)
function subArray($array,$start,$len){
	$count = count($array);
	echo "the count is $count";
	$validity = $count - ($start + $len);
	if ($validity < 0) {
		return false;
	}
	$result = array();
	for ($i=$start; $i < ($start + $len); $i++) { 
		$result[] =$array[$i];
	}
	return $result;
}

function exception_error_handler($errno, $errstr, $errfile, $errline ) {
	throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}
function replaceIndexWith(&$array,$index,$value){
	for ($i=0; $i < count($array); $i++) { 
		$array[$i][]=$value;;// $array[$i][$index]=$value;
	}
}
//this function check if an element is empty in 
function checkEmpty($array,$except=array()){
	if (empty($array)) {
		return false;
	}
	foreach ($array as $key => $value) {
		if (!in_array($value, $except) && empty($value) ) {
			return $key;
		}
	}
	return false;
}

//function to get get the last insert index given the database object
function getLastInsert($db){
	$query = 'select last_insert_id() as last';
	$result = $db->query($query);
	$result = $result->result_array();
	return $result[0]['last'];
}

function query($db,$query,$data=array()){
	$result =$db->query($query,$data);
	if (is_bool($result)) {
		return $result;
	}
	return $result->result_array();
}
//function to help return to the previous page while setting error message

//function to load states
function loadStates(){
	$list =scandir('assets/states');
	$result = array();
	//process the list well before they are returned
	for ($i=0; $i < count($list); $i++) { 
		$current = $list[$i];
		if (startsWith($current,'.')) {
			continue;
		}
		$result[]=trim($current);
	}
	return $result;
}
function loadLga($state){
	if (!file_exists("assets/states/$state")) {
		return '';
	}
	$content =file_get_contents("assets/states/$state");
	$content = trim($content);
	$result = explode("\n", $content);
	for ($i=0; $i < count($result); $i++) { 
		$result[$i]=trim($result[$i]);
	}
	return $result;
}

function removeDuplicateValues($array){
	$result = array();
	foreach ($array as  $value) {
		if (in_array($value, $result)) {
			continue;
		}
		$result[]=$value;
	}
	return $result;
}

function arrayToCsvString($array,$header=null){
	$result = "";
	$key = $header==null?array_keys($array[0]):$header;
	array_unshift($array, $key);
	for ($i=0; $i < count($array); $i++) { 
		$current = $array[$i];
		$result.=singleRowToCsvString($current);
	}
	return $result;
}
function singleRowToCsvString($row){
	$result='';
	$result.=implode(',', $row);
	$result.="\n";
	return $result;
}

//function to extract a section of columns from a two dimsntional array
function copyMultiArrayWithIndex($indexArray,$data){
	$result= array();
	for ($i=0; $i < count($data); $i++) { 
		$current = $data[$i];
		$result[]=extractArrayPortion($indexArray,$current);
	}
	return $result;
}

function extractArrayPortion($index,$data){
	if (max($index) >= count($data)) {
		# there will be an error just throw exception or exit
		exit('error occur while performing operation');
	}
	$result = array();
	for ($i=0; $i < count($index); $i++) { 
		$result[]=$data[$index[$i]];
	}
	return $result;
}

function convertToAssoc($data,$first,$second){
	$result=array();
	for ($i=0; $i < count($data); $i++) { 
		$key = $data[$i][$first];
		$value = $data[$i][$second];
		$result[$key]=$value;
	}
	return $result;
}
function removeEmptyAssoc($arr)
{
	$result = array();
	foreach ($arr as $key => $value) {
		// this is to skip and validate an array value 
		if(is_array($value)){
			$result[$key] = $value;
			continue;
		}
		if (trim($value)!=='') {
			$result[$key]=$value;
		}
	}
	return $result;
}
function removeEmptyArrayElement($arr){
	$result=array();
	for ($i=0; $i < count($arr); $i++) { 
		if (trim($arr[$i])=='') {
			continue;
		}
		$result[]= $arr[$i];
	}
	return $result;
}

//this function initialize n number of array with the
function initArray($size,$default)
{
	$result= array();
	for ($i=0; $i < $size; $i++) { 
		$result[$i]=$default;
	}
	return $result;
}

function removeValue($arr,$val)
{
	$result = array();
	foreach ($arr as $value) {
		if ($value==$val) {
			continue;
		}
		$result[]=$value;
	}
	return $result;
}

function fetchField($array,$field)
{
	$result = array();
	if ($array) {
		foreach ($array as $val) {
			$result[]=$val[$field];
		}
	}
	return $result;
}


?>
