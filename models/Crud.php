<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities;

class Crud
{
	protected $array = [];//array containing the field  and value of the object inform of an associateive array
	protected $foreignKeyEnd='_id';
	static $baseurl;
	protected $db;
	function __construct($array=array())
	{
		// parent::__construct();
		if (!is_array($array)) {
			throw new Exception("Constructor argument must be an array");
		}
		$this->array = $array;
		helper(['string','text']);
		static::$baseurl = base_url();
		$this->db = db_connect();
	}
	/**
	 * This function builds the select clause needed to retrieve feed of this table while substituting the foreign key id with the display name.
	 * @return string the sql select clause that will replace the foreign key id with the corresponding display name
	 * @param calfound determines if calfound rows should be included in the generated query
	 */
	private function  buildSelectClause($calfound=true){
		if (isset(static::$selectQuery) && static::$selectQuery) {
			return static::$selectQuery;
		}
		$whereClause ='';
		$foundrowString = $calfound? 'SQL_CALC_FOUND_ROWS ':'';
		$thisTable = $this->getTableName();
		$onclause ="";
		$foreignTable = array();
		$fields = array_keys(static::$labelArray);
		if (!$this->foreignKeyPresent($fields)) {
			return "select * from $thisTable";
		}
		$this->buildTableJoinQuery($thisTable,$fields,$onclause,$foreignTable);
		$fieldList = implode(',', $fields);
		$tableList = '('.implode(',', $foreignTable) .')';	
		// $joinStatement = empty($foreignTable)?'':"left join $tableList on ($onclause)";
		$joinStatement = $onclause;
		$result = "select $foundrowString $fieldList from {$thisTable} $joinStatement ";
		return $result;
	}
	//function for getting the name of the table
	function getTableName($external=''){
		$tableName='';
		if($external == ''){
			$tableName = strtolower(static::$tablename);
		}else{
			$tableName = strtolower($external);
		}
		
		return $tableName;
	}
	function buildUrl($url){
		return self::$baseurl.$url;
	}

	private function foreignKeyPresent($fields){
		for ($i=0; $i < count($fields); $i++) { 
			$field = $fields[$i];
		 	if (endsWith($field,$this->foreignKeyEnd)) {
				return  true;
			}
		 } 
		return false;
	}
	public function buildTableJoinQuery($thisTable,&$fields,&$onclause,&$foreignTable){
		for ($i=0; $i < count($fields); $i++) { 
			$field= $fields[$i];
			if (endsWith($field,$this->foreignKeyEnd)) {
				$tablename = substr($field, 0,strlen($field)-strlen($this->foreignKeyEnd));
				$tablename = strtolower($tablename);
				if (!class_exists($tablename)) {
					$modelName = "Entities\\".$tablename;
					$tablename = new $modelName;
				}
				if (isset($tablename::$displayField)) {
					$display='';
					if (is_array($tablename::$displayField)) {
						$display="concat_ws(' '";
						foreach ($tablename::$displayField as $tval) {
							$display.=",".$tablename.'.'.$tval;
						}
						$display.=") as $field";
					}
					else{
						$display =strtolower($tablename::$tablename).'.'.$tablename::$displayField.' as '.$field;
					}
					$foreignTable[]=$tablename;
					$temp = $thisTable.'.'.$tablename.$this->foreignKeyEnd;
					
					$usse = isset($tablename::$joinField)?$tablename.'.'.$tablename::$joinField :"$tablename.ID";
					
					$onclause.=" left join $tablename on $temp =$usse ";
					
				}
				else{
					$display = $thisTable.'.'.$field;
				}
				$fields[$i]=$display;
			}
			else{
				$fields[$i] = $thisTable.'.'.$field;
			}

		}
		// $onclause = substr($onclause, 0,strlen($onclause)-4);

	}
	//function to get arrayProperties
	function __set($name,$value){
		$this->array[$name]=$value;
	}

	function __get($name){
		$methodName="get".ucfirst($name);
		if (array_key_exists($name, $this->array)) {
			return $this->array[$name];
		}
		else if(method_exists($this, $methodName)){
			return $this->$methodName();
		}
		else{
			return null;
		}
	}

	function toArray(){
		return $this->array;
	}
	function setArray($array){
		$this->array = $array;
	}

	function exists($id,&$dbObject=null,&$arrData=array()){
		$tablename =$this->getTableName();
		$wherelist = $this->buildExistWhereString($id,$data);
		$arrData = $data;
		$query = "select count(*) as amount from $tablename where $wherelist";
		$result = $this->query($query,$data,$dbObject);
		return $result[0]['amount'] != 0;
	}

	//function that can just save object, it uupdate is present and insert if not
	function save($dbObject= null){
		$arr = $this->array;
		$temp = $this->getWhere($arr,$totalRow,0,NULL,FALSE,$dbObject);
		if ($temp) {
			$this->ID = $temp[0]->ID;
			return $this->update($dbObject);
		}
		else{
			return $this->insert($dbObject);
		}
	}
	private function buildExistWhereString($id,&$data){
		if (empty($id)) {
			return '';
		}
		$data = array();
		$result = "";
		for ($i=0; $i < count($id); $i++) { 
			$current = $id[$i];
			$result.=$i == 0?" $current = ?":" and $current = ? ";
			$data[]=@$this->array[$current];
		}
		return $result;
	}

	private function buildWhereString($id,&$data){
		$result='';
		$data=array();
		if (is_array($id)) {
			$keys= array_keys($id);
			$data=array_values($id);
			for ($i=0; $i < count($keys); $i++) {
				if (($pos=strpos($keys[$i], ' like '))!==false) {
					$keys[$i] = $i==0?$keys[$i].'?':" AND ".$keys[$i].'?';
					continue;
				}
				$keys[$i] = $i==0?$keys[$i].'=?':" AND ".$keys[$i].'=?';
			}
			return implode(' ', $keys);
		}
		else {
				//assumes its a string
			$data[]=$id;
			if ($this->getTableName()=='payment_log') {
				return "transaction_number=?";
			}
			return "id=?";
		}
	}
	//the where contains the list of fieldname and the value
	function getWhere($parameter,&$totalRow=-1,$start=0,$length=NULL,$resolveForeign=true,$sort='',&$dbObject=null){
		$tablename =$this->getTableName();
		$classname = ucfirst($tablename);
		$limit="";
		$array=array();
		if ($length!=NULL) {
			$limit = " LIMIT ?,?";
			$array=array($start,$length);
		}
		$whereString = $this->buildWhereString($parameter,$data);
		if (!empty($array)) {
			$data = array_merge($data,$array);
		}
		if ($whereString) {
			$query = $resolveForeign?$this->buildSelectClause()." where  $whereString $sort $limit":"SELECT SQL_CALC_FOUND_ROWS * from $tablename where  $whereString $sort $limit";

		}
		else{
			$query = $resolveForeign?$this->buildSelectClause()." $sort  $limit":"SELECT SQL_CALC_FOUND_ROWS * from $tablename  $sort $limit";

		}
		// $query.=' '.$sort;
		$result= $this->query($query,$data,$dbObject);
		$result2 = $this->query("SELECT FOUND_ROWS() as totalCount");
		$totalRow=$result2[0]['totalCount'];
		if ($result ==true) {
			return $this->buildObject($classname,$result);
		}
		else{
			return false;
		}

	}

	//get where without returning an object
	//the where contains the list of fieldname and the value
	function getWhereNonObject($parameter,&$totalRow=-1,$start=0,$length=NULL,$resolveForeign=true,$sort='',&$dbObject=null){
		$tablename =$this->getTableName();
		$classname = ucfirst($tablename);
		$limit="";
		$array=array();
		if ($length!=NULL) {
			$limit = " LIMIT ?,?";
			$array=array($start,$length);
		}
		$whereString = $this->buildWhereString($parameter,$data);
		if (!empty($array)) {
			$data = array_merge($data,$array);
		}
		if ($whereString) {
			$query = $resolveForeign?$this->buildSelectClause()." where  $whereString $sort $limit":"SELECT SQL_CALC_FOUND_ROWS * from $tablename where  $whereString $sort $limit";

		}
		else{
			$query = $resolveForeign?$this->buildSelectClause()." $sort  $limit":"SELECT SQL_CALC_FOUND_ROWS * from $tablename  $sort $limit";

		}
		// $query.=' '.$sort;
		$result= $this->query($query,$data,$dbObject);
		$result2 = $this->query("SELECT FOUND_ROWS() as totalCount");
		$totalRow=$result2[0]['totalCount'];
		if ($result ==true) {
			return $result;
		}
		else{
			return false;
		}

	}
	/**
	 * This function build a crud object array from an array of array
	 * @param  string $classname the name of the class
	 * @param  array of array $result    The array needed to be converted to crud object.
	 * @return [array[object]]            The array of object built
	 */
	protected function buildObject($classname,$result){
		$objectArray =array();
		if (!class_exists($classname)) {
			$modelName = "Entities\\".$classname;
			$classname = new $modelName;
		}
		for ($i=0; $i < count($result); $i++) {
			$current= $result[$i];
			$objectArray[] = new $classname($current);
		}
		return $objectArray;
	}
	function allNonObject(&$totalRow=0,$resolveForeign=true,$lower=0,$length=NULL,$sort='',$where=''){
		$tablename =$this->getTableName();
		$limit="";
		$array=array();
		if ($length!=NULL) {
			$limit = " LIMIT ?,?";
			$array=array($lower,$length);
		}
		$query =$resolveForeign?$this->buildSelectClause()." $where $sort $limit":"SELECT SQL_CALC_FOUND_ROWS * FROM $tablename $where $sort $limit ";
		
		$result = $this->query($query,$array);
		$result2 = $this->query("SELECT FOUND_ROWS() as totalCount");
		$totalRow=$result2[0]['totalCount'];
		return $result;
	}

	function all(&$totalRow=0,$resolveForeign=true,$lower=0,$length=NULL,$sort='',$where=''){
		$tablename =$this->getTableName();
		$limit="";
		$array=array();
		if ($length!=NULL) {
			$limit = " LIMIT ?,?";
			$array=array($lower,$length);
		}

		$query =$resolveForeign?$this->buildSelectClause()." $where $sort $limit":"SELECT SQL_CALC_FOUND_ROWS * FROM $tablename $where $sort $limit ";
		$result = $this->query($query,$array);
		$result2 = $this->query("SELECT FOUND_ROWS() as totalCount");
		$totalRow=$result2[0]['totalCount'];
		return $this->buildObject($tablename,$result);
	}

	// added this for json request and response using framework like vue
	//the where contains the list of fieldname and the value
	function allListFiltered($parameter,&$totalRow=-1,$start=0,$length=NULL,$resolveForeign=true,$sort=' order by ID desc ',$queryString='',&$dbObject=null){
		$tablename =$this->getTableName();
		$classname = ucfirst($tablename);
		$limit="";
		$array=array();
		if ($length!=NULL) {
			$start =$this->db->escape($start);
			$length =$this->db->escape($length);
			$limit = " LIMIT $start,$length";
			// $array=array($start,$length);
		}
		$whereString = $this->buildWhereString($parameter,$data);
		if (!empty($array)) {
			$data = array_merge($data,$array);
		}
		if ($whereString) {
			if ($queryString) {
				$queryString = " and ($queryString) ";
				$whereString.=$queryString;
			}
			$query = $resolveForeign?$this->buildSelectClause()." where  $whereString $sort $limit":"SELECT SQL_CALC_FOUND_ROWS * from $tablename where  $whereString $sort $limit";
		}
		else{
			if ($queryString) {
				$queryString = " where ($queryString) ";
			}
			$query = $resolveForeign?$this->buildSelectClause()." $queryString $sort  $limit":"SELECT SQL_CALC_FOUND_ROWS * from $tablename $queryString $sort $limit";
		}
		$result= $this->query($query,$data,$dbObject);
		$result2 = $this->query("SELECT FOUND_ROWS() as totalCount");
		$totalRow=$result2[0]['totalCount'];
		return array($result,$totalRow);

	}
	
	function allList(&$totalRow=0,$resolveForeign=true,$lower=0,$length=NULL,$filter=false,$q=false,$sort='order by ID desc'){
		$tablename =$this->getTableName();
		$limit="";
		$array=array();
		if ($length!=NULL) {
			$limit = " LIMIT ?,?";
			$array=array($lower,$length);
		}
		$query =$resolveForeign?$this->buildSelectClause()." $sort $limit":"SELECT SQL_CALC_FOUND_ROWS * FROM $tablename $sort $limit ";
		$result = $this->query($query,$array);
		$result2 = $this->query("SELECT FOUND_ROWS() as totalCount");
		$totalRow=$result2[0]['totalCount'];
		return $result;
	}

	public function allQuery($query)
	{
		$tempQuery =$this->db->escape($query);
		$fields = array_keys(static::$labelArray);
		$isFirst = true;
		$result ="";
		foreach ($field as $fields) {
			if (!isFirst) {
				$result.=' or ';
				$isFirst=false;
				continue;
			}
			$result.=" $field like '%$tempQuery%' ";
		}
		return $result;

	}

	function search($q,&$totalRow=0,$resolveForeign=true,$lower=0,$length=NULL,$sort=''){
		$tablename =$this->getTableName();
		$limit="";
		$array=array();
		if ($length!=NULL) {
			$limit = " LIMIT ?,?";
			$array=array($lower,$length);
		}
		$allQuery = $this->getAllQuery($q);
		$query =$resolveForeign?$this->buildSelectClause($allQuery)." $sort $limit":"SELECT SQL_CALC_FOUND_ROWS * FROM $tablename $sort $limit ";
		$result = $this->query($query,$array);
		$result2 = $this->query("SELECT FOUND_ROWS() as totalCount");
		$totalRow=$result2[0]['totalCount'];
		return $result;
	}

	// ended here
	
	function view($id='',&$dbObject=null){
		if (empty($id)) {
			if (array_key_exists('ID', $this->array)) {
				$id=$this->array['ID'];
			}
			else{
				throw new Exception('please specify the index or set the index value as a parameter');
			}
		}
		$tablename=$this->getTableName();
		$query="SELECT * FROM $tablename where id=?";
		$result = $this->query($query,array($id),$dbObject);
		if (count($result) == 0) {
			return false;
		}
		$result = $result[0];
		$resultobject = new  $tablename($result);
		return $resultobject;
	}
	function load($id= null,&$dbObject= null){
		$result = $this->view($id,$dbObject);
		if ($result) {
			$this->array = $result->toArray();
			return true;
		}
		else{
			return false;
		}
	}
	function queryTable($query,$data=array(),&$dbObject=null){
		$tablename =$this->getTableName();
		$result =$db->query($query,$data,$dbObject);
		$resultObjects = array();
		foreach ($result as $value) {
			$resultObjects[] = new $tablename($value);
		}
		return $resultObjects;
	}

	function query($query,$data=array(),&$dbObject=null){
		$db=$this->db;
		if ($dbObject!=null) {
			$db=$dbObject;
		}
		$result =$db->query($query,$data);
		if (!is_object($result)) {
			return $result;
		}
		$result = $result->getResultArray();
		return $result;
	}

	function update($id=NULL,&$dbObject=null){
		if (empty($id) && !isset($this->array['ID'])) {
			throw new Exception("null id field found");
		}
		
		
		$tablename =$this->getTableName();
		if ($tablename=='payment_log') {
			$id = $id==null?$this->array['transaction_number']:$id;
		}
		else{
			$id = $id==null?$this->array['ID']:$id;
		}
		$query="UPDATE $tablename SET ";
		$query.=$this->buildUpdateQuery($data);
		$whereCondition = $this->buildWhereString($id,$temp);
		$query .=" WHERE $whereCondition";
		$data = array_merge($data,$temp);
		// $data[]=$id;
		$result = $this->query($query,$data,$dbObject);
		if ($result > 0) {
			return true;
		}
		else{
			return false;
		}

	}
	private function buildUpdateQuery(&$data){
		$result = " ";
		$data = array();
		$keys = array_keys($this->array);
		$new = true;
		for ($i=0; $i < count($keys) ; $i++) {
			$key = $keys[$i];
			if ($key=="ID") {
				continue;
			}
			$result.=$new?" $key = ?":", $key = ?";
			$new = false;
			$data[]=$this->array[$key];
		}
		return $result;
	}
	private function checkExist(){
		if (isset(static::$uniqueArray) && !empty(static::$uniqueArray)) {
			$uniqueKeys = static::$uniqueArray;
			foreach ($uniqueKeys as $value) {
				$result = $this->exists(array($value),$dbObject,$arrData);
				if ($result) {
					return (!empty($arrData)) ? $arrData : true;
					// return true;
				}
			}
		}

		if (isset(static::$compositePrimaryKey) && !empty(static::$compositePrimaryKey)) {
			$uniqueKeys = static::$compositePrimaryKey;
			$result = $this->exists($uniqueKeys);
			return $result;
		}
		return false;
	}

	function insert(&$dbObject=null,&$message=''){
		if (empty($this->array)) {
			throw new Exception("no value to insert");
		}
		if ($this->checkExist()) {
			$checkMsg = $this->checkExist();
			if(is_array($checkMsg)){
				$string = implode(",", $checkMsg);
				$string = ellipsize($string,40,1);
				$message = "'$string' already exists";
			}else{
				$message='Duplicate entry row already exist...';
			}
			
			return false;
		}
		$tablename =$this->getTableName();
		$query = "INSERT INTO $tablename (";
		$data = array();
		$partTwo ="";
		$keys = array_keys($this->array);
		for ($i=0; $i < count($keys); $i++) {
			$key = $keys[$i];
			$query.=$i==0?"$key":","."$key";
			$data[]=$this->array[$key];
			$partTwo.=$i==0?"?":","."?";
		}
		$query.=") VALUES (";
		$query.=$partTwo.")";
		$result = $this->query($query,$this->array,$dbObject);
		if ($result > 0) {
			return true;
		}
		else{
			return false;
		}
	}

	function delete($id=NULL,&$dbObject=NULL){
		if ($id==NULL && !isset($this->array['ID'])) {
			throw new Exception("object does not have id");
		}
		if ($id ==NULL) {
			$id = $this->array["ID"];
		}
		$tablename =$this->getTableName();
		$query = "delete from $tablename where id=?";
		return $this->query($query,[$id],$dbObject);

	}
	public function enable($id=null,&$dbObject=null){
		if ($id==NULL && !isset($this->array['ID'])) {
			throw new Exception("object does not have id");
		}
		if ($id ==NULL) {
			$id = $this->array["ID"];
		}
		return $this->setEnabled($id,1,$dbObject);
	}
	public function disable($id=null,&$dbObject=null){
		if ($id==NULL && !isset($this->array['ID'])) {
			throw new Exception("object does not have id");
		}
		if ($id ==NULL) {
			$id = $this->array["ID"];
		}
		return $this->setEnabled($id,0,$dbObject);
	}
	protected function setEnabled($id ,$value,&$dbObject){
		$tablename =$this->getTableName();
		$query = "update $tablename set status = ? where id=?";
		$result = $this->query($query,array($value,$id),$dbObject);
		if ($result) {
			$this->array['status'] = $value;
			$dbObject->transCommit(); //for any transaction related operation before this place
			return true;
		}
		else{
			return false;
		}

	}
	//function to return the array need
	protected function buildActionArray($label,$link,$critical,$ajax){
		$result = array();
		$result['label']=$label;
		$result['link']=$link;
		$result['isCritical']=$critical;
		$result['ajax']=$ajax;
	}
	//this method return  true if the method is validated or false. if false, the message is passed to the message variable displayed.
	function validateInsert(&$message,$validateType=false){
		$result =0;
		$message='';
		$condition =empty(static::$nullArray);
		foreach (static::$typeArray as $key => $value) {
			$value = @$this->array[$key];
			if ((empty($value) && $value!=='0') && ($condition || !in_array($key,static::$nullArray))) {
				if ($result!==0) {
					$message.=',';
				}
				$message.= empty(static::$labelArray[$key])?$this->generateFormlabel($key):static::$labelArray[$key];
				$result++;
			}

		}
		if ($result) {
			if ($result == 1) {
				$message =$message.' cannot be empty';
			} else {
				$message ='The following fields cannot be empty '.$message;
			}

			return false;
		}
		else{
			return true;
		}
	}
	private function generateFormLabel($fieldname){
		$result = '';
		if (empty($fieldname)) {
			return $result;
		}
		$list = explode("_", $fieldname);

		for ($i=0; $i < count($list); $i++) {
			$current= ucfirst($list[$i]);
			$result.=$i==0?$current:" $current";
		}
		return $result;
	}

	//the array must contain the table name and the display value field then it can load options
	public function loadOption($array,$val=false,$prepend ='',$orderBy='',$hidden=false){
		extract($array);
		// the $hidden parameter means to use the display value has the hidden value for select option

		if($orderBy != ''){
			$orderBy = $orderBy;
		}else{
			$orderBy = $display;
		}

		if (is_array($table) && count($table)==2) {
			$query = $this->buildJoin($table,$display);
		}
		else{
			$orderBy = ($orderBy != '') ? $orderBy : "order by ID asc"; 
			$query = "SELECT id,$display as value FROM $table $orderBy";
		}
		$result =$this->query($query);
		if (is_array($prepend)) {
			$result = array_merge($prepend,$result);
		}
		return $this->buildSelectOption($result,$val,$hidden);

	}
	private function buildJoin($table,$display){
		$first =$table[0];
		$second = $table[1];
		$firstWithId = $first.'.id';
		$secondWithId = $second.'.id';
		$result = "select $firstWithId as id, $display as value from $first left join $second on $firstWithId = $secondWithId";
		return $result;
	}
	public function loadValueOption($arrayValue,$value=false){
		$result = "<option value='' selected='selected'>..choose..</option>";
		if ($val) {
			$result = "<option value=''>..choose..</option>";
		}
		for ($i=0; $i < count($arrayValue); $i++) {
			$current = $arrayValue[$i];
			if ($value && $value==$current) {
				$result.="<option value='$current' selected='selected'>$current</option>";
				continue;
			}
			$result.="<option value='$id'>$value</option>";
		}
		return $result;
	}
	public function buildSelectOption($array,$val,$hidden=false){

		$result = "<option value='' selected='selected'>...choose option...</option>";
		if ($val) {
			$result = "<option value=''>..choose..</option>";
		}
		for ($i=0; $i < count($array); $i++) {
			$current = $array[$i];
			extract($current);
			$hiddenValue='';
			if($hidden){
				$hiddenValue = $value;
			}else{
				$hiddenValue= $id;
			}
			if ($val && $val==$id) {
				$result.="<option value='$id' selected='selected'>$value</option>";
				continue;
			}else if($val && $val == $value){
				$result.="<option value='$value' selected='selected'>$value</option>";
				continue;
			}
			$result.="<option value='$hiddenValue'>$value</option>";
		}
		return $result;
	}
	public function buildSelectOptionNonAssoc($array,$val){
		$result = '';
		for ($i=0; $i < count($array); $i++) {
			$current = $array[$i];
			if ($val && $val==$current) {
				$result.="<option value='$current' selected='selected'>$current</option>";
				continue;
			}
			$result.="<option value='$current'>$current</option>";
		}
		return $result;
	}
	public function loadMultipleCheckBox($array,$val=false){
		extract($array);
		$result =$this->query("SELECT id,$display as value FROM $table");
		return $this->buildCheckBox($result,$table);

	}
	private function buildCheckBox($array,$name){
		$result = "";
		for ($i=0; $i < count($array); $i++) {
			$current = $array[$i];
			extract($current);
			// find a way to get selected boxes when loading update form
			$result.="<input type='checkbox' name='".$name."list[]' value='$id'>$value />";
		}
		return $result;
	}

	// a function to upload multiple row of data
	public function upload($fields,$data,&$message='',$dbObject= null,$translate=true){
		$tables = $this->getTemplateTablesUpload();
		if (is_array($tables)) {
			return $this->uploadMultiples($tables,$fields,$data,$message,$dbObject,$translate);
		}
		else{
			return $this->uploadSingle($tables,$fields,$data,$message,$dbObject,$translate);
		}
	}
	private function getTemplateTablesUpload(){
		$tablename =$this->getTableName();
		if (isset(static::$uploadDependency) && static::$uploadDependency) {
			$result= static::$uploadDependency;
			$result[] = $tablename;
			return $result;
		}
		return $tablename;
	}
	private function buildOnUpdateField($fields)
	{
		$result =" on duplicate key update ";
		foreach ($fields as $field) {
			$result.="$field=values($field) ,";
		}
		$result = rtrim($result,',');
		return $result;
	}
	public function uploadSingle($model,$fields,$data,&$message='',$dbObject,$translate=false){
		if (empty($data)) {
			$message = "no data found in the template file uploaded";
			return false;
		}
		$temp = $fields;
		if (!$this->validateHeader($temp,$model)) {
			$message = 'invalid file format. make sure you are using the downloaded template.';
			return false;
		}
		$hashPassword = strtolower($model)=='user';
		$data =  $translate?$this->transformData($model,$data,$fields,$hashPassword):$data;
		if (is_string($data)) {
			$message=$data;
			return false;
		}
		$query = $this->buildInsertSection($fields,$model);
		$query.= $this->buildmultipleInsertValue($data,$fields);
		$query.=$this->buildOnUpdateField($fields);
		$db = $dbObject== null?$this->db:$dbObject;
		$result=   $db->query($query,[],$dbObject);
		$mess = $db->query("show errors");
		$message = $mess->getResultArray();
		$message = $message?$message[0]['Message']:'';
		return $result;
	}

	private function validateHeader($header,$model){
		$modelHeader = $this->buildTemplate($model);
		$diff = array_diff($header, $modelHeader);
		return empty($diff);
		// return $header==$common;
	}
	// this function will replace the content of the array with the foreign table id,
	//in case password is present make sure you has the value before sending into the database. so as not to introduce any error.
	private function transformData($model,$data,&$fields,$hashPassword=false){
		$temp = $this->buildTemplate($model,false);
		$foreign = $this->extractFk($temp);
		if (!empty($foreign)) {
			$fields = $this->replaceForeignField($fields,$foreign);
		}
		$passwordIndex='';
		$usernameIndex ='';
		if ($hashPassword) {
			$passwordIndex = array_search('password', $fields);
			if ($passwordIndex == false) {
				// $fields[]='password';
				throw new Exception("there is no password field to hash so set the user field currectly.", 1);
			}
			$usernameIndex = array_search('username', $fields);
		}
		for ($i=0; $i < count($data); $i++) { 
			if (!isset($data[$i])) {
				continue;
			}
			foreach ($foreign as $key => $value) {
			 	// $index = $fkKeys[$j];
			 	if (!isset($data[$i][$value])) {
			 		continue;
			 	}
			 	$key = loadClass($key);
			 	if (isset($key::$displayField)) {
			 		$fieldName = $key::$displayField;
			 		$tp = $this->getFieldID($fieldName,$data[$i][$value],$key);

			 		if ($tp===false) {
			 			$dt=$data[$i][$value];
			 			$line = $i+1;
			 			exit("$dt does not exist  for $key on line $line, please check your file and try again");
			 		}

			 		$data[$i][$value] = $tp;
			 	}

			 } 
			 if($hashPassword){
			 	$data[$i][$passwordIndex]=	@crypt($data[$i][$usernameIndex]);//hash the password;
			 } 
			 $assoc = array_combine($fields, $data[$i]);
			 $this->setArray($assoc);
			 $status = $this->validateInsert($message);
			 if (!($status || $this->checkExist())) {
			 	//check if the value already exist
			 	return "$message on line ".($i+1)." in the uploaded file ";
			 }
		}
		return $data;
	}


	private function replaceForeignField($fields,&$foreign){
		$dictionary = $this->buildFkDictionary($foreign);
		$len = count($fields);
		for ($i=0; $i < $len; $i++) { 
			if (!isset($fields[$i])) {
				// $fields[$i];
				$len++;
				continue;
			}
			if (isset($dictionary[$fields[$i]])) {
				$fields[$i]= $dictionary[$fields[$i]];
				$classname = substr($fields[$i], 0,strlen($fields[$i])-3);
				$foreign[$classname]=$i;
			}
		}
		return $fields;
	}
	private function buildFkDictionary($fk){
		$fk = array_keys($fk);
		$result = array();
		for ($i=0; $i < count($fk); $i++) { 
			$current = $fk[$i];
			$current = loadClass($current);	
			if (isset($current::$displayField)) {
				$displayName = $current::$displayField;
				$result[$displayName] = $current.'_id';
			}
		}
		return $result;
	}
	private function extractFk($fields){
		$result= array();
		for ($i=0; $i < count($fields); $i++) { 
			if (endsWith($fields[$i],'_id')) {
				$fieldName = substr($fields[$i], 0,strlen($fields[$i])-strlen("_id"));
				$result[$fieldName]= $i;
			}
		}
		return $result;
	}
	private function getFieldID($fieldname,$fieldValue,$tablename){
		$fieldname=trim($fieldname);
		$fieldValue=trim($fieldValue);
		$query = "select id from $tablename where $fieldname =?";
		$result = $this->query($query,[$fieldValue]);
		if ($result) {
			return $result[0]['id'];
		}
		// var_dump($tablename);
		// echo "$fieldname $fieldValue $tablename";
		// echo "There is an error in the value(s) provided. please check and try again";exit;
		
		return false;
	}
	private function buildInsertSection($field,$tablename){
		$fieldList = implode(',', $field);
		$query = "insert into $tablename ($fieldList) values ";
		return $query;
	}
	// it is assumed that the file is save for data  insertion
	private function buildmultipleInsertValue($data,$fields=false){
		$result ='';
		for ($i=0; $i < count($data); $i++) { 
			$current = $data[$i];
			$current = $this->performValueCheck($current,$fields);
			$temp = implode(',', $current);
			if ($i!=0) {
				$result.=','; 
			}
			$result.="($temp)";
		}
		return $result;
	}
	private function sqlDateFormat($date)
	{
		$d =  Datetime::createFromFormat('d/m/Y',$date);
		$result = $d->format('Y-m-d');
		return $result;
	}
		// this function checks that the data does not contain sql query by filtering the value and checking for invalid value
	private function performValueCheck($values,$fields){
		for ($i=0; $i < count($values); $i++) { 
			$value = $values[$i];
			$values[$i]=trim($value);
			if (strpos($fields[$i], 'date')) {
				$values[$i]=$this->sqlDateFormat($value);
			}
			if (!$this->isSqlSafeInput($value)) {
				exit('error while processing request. please try again');
			}
			$temp = $this->db->escape($values[$i]);
			$values[$i]="'$temp'";
		}
		return $values;
	}
	// could be implemented if necessary for full security
	private function isSqlSafeInput($value){
		return true;
	}

	/*
	|	this function will export data to .csv extension format.
	|	@return the .csv data exported for download even if a where clause is stated as a condition
	*/
	public function export($condition= null){
		$data = $this->getExportData($condition);
		if (!$data) {
			exit("no data found to export.");
		}
		$filename = static::$tablename.'_export.csv';
		$header = 'text/csv';
		$content=arrayToCsvString($data);//convert the two dimenensional array to csv here.
		sendDownload($content,$header,$filename);
	}

	/*
	|	this function will get the necessary data to be use as content in the exported file
	|	NOTE: it also accomomdate a conditional statement (i.e a where clause)
	|	@return the necessary data from the db
	*/
	private function getExportData($condition= null){
		$fields = $this->getModelTemplateHeader(true,true);
		$fieldList = implode(',', $fields);
		$joinString = $this->getExportJoinString(static::$tablename);
		$data= array();
		$conditionString = $condition == null?'':$this->buildWhereString($condition,$data);
		$query = "select $fieldList from $joinString $conditionString";
		$result = $this->query($query,$data);
		return $result;
	}
	/*
	|	this  function is use to resolve any foreign table in a model
	|	@return a proper formal result for a join string statement to be use in query 
	*/
	private function getExportJoinString($mainTable){
		$tables = $this->getModelTemplateHeader(false,false,$ending);
		//reverse the array
		$temp = $this->extractFk($tables);
		$tables = array_keys($temp);
		array_unshift($tables, $mainTable);
		if (empty($tables)) {
			throw new Exception("error while processing kindly check your code and the model file.", 1);
		}
		$parents = empty($ending)?array():array_keys($ending);
		$result = $tables[0];
		$parent = empty($ending)?$tables[0]:$parents[0];//initialize the pareent variable
		$count = 0;
		for ($i=1; $i < count($tables); $i++) {
			$table = $tables[$i];
			if (!empty($ending)) {
				$key = $temp[$table]; 
				if ($key >= $count) {
					$parent = $this->parentWithMinIndex($key,$ending);
				}
			}
			$result.=" left join $table on $parent.$table"."_ID = $table.ID ";
		}
		return $result;
	}

	private function parentWithMinIndex($item,$array){
		foreach ($array as $key => $value) {
			if ($value > $item) {
				return $key;
			}
		}
	}
	private function buildAllRequiredTables($tables){
		if (is_array($tables)) {
			$result = array();
			for ($i=0; $i < count($tables); $i++) { 
				$result+=$this->buildSingleRequiredTable($tables[$i]);
			}
		}
		return $this->buildSingleRequiredTable($tables);
	}

	//function to get the upload template for a table
	/*
	|	this function is use to return a list of header for the template
	|	@return lists of template header for the model,
	|	resolving any foreign table or an array of tables (i.e multiple table if stated in the entity model)
	*/
	private function getModelTemplateHeader($resolve=true,$isSql=false,&$ending=''){
		$tables = $this->getTemplateTables();
		if (is_array($tables)) {
			return $this->combineFields($tables,$resolve,$isSql,$ending);
		}
		else{
			return $this->getTemplates($tables,$resolve,$isSql);
		}
	}

	/*
	|	this function get the necessary table needed for the template
	|	NOTE: if static::uploadDependency is stated in the entity model,
	|	array of table name will be returned
	|	@return either a single table name or an array of table name for the template
	*/
	private function getTemplateTables(){
		$tablename =$this->getTableName();
		if (isset(static::$uploadDependency)) {
			$result= array($tablename);
			$result= array_merge($result,static::$uploadDependency);
			// $result[] = $tablename;
			return $result;
		}
		return $tablename;
	}
	/*
	|	this function is use to generate a list of field containing an array of table name
	|	@return a list of array field since it involves an array of table
	*/
	private function combineFields($tables,$resolve=true,$isSql=false,&$ending=''){
		$result = array();
		$ending = array();
		$previousCount =0;
		for ($i=0; $i < count($tables); $i++) { 
			$temp = $this->getTemplates($tables[$i],$resolve,$isSql);
			$ending[$tables[$i]]=count($temp) +$previousCount;
			$previousCount +=count($temp);
			$result =array_merge($result,$temp);
		}
		return $result;
	}

	/*
	|	this function is use to get the field needed for the template download
	|	@return a list of array field needed for the template
	*/
	private function getTemplates($table,$resolve=true,$isSql=false){
		$fields='';
		$table = loadClass($table);
		// static::$uploadFields contain the fields you want to include only in the template with the name of the fields to be included as the values for the array element
		if (isset($table::$uploadFields)) {
			$fields = $table::$uploadFields;
		}
		else{
			$fields = $this->buildTemplate($table,$resolve,$isSql);
		}
		return $fields;
	}
	/**
	|	this function is used to download model template for batch uploading.
	|	This function is dynamic in nature and not hard-coded.
	|	@return .csv file extension is returned for download
	*/
	function downloadTemplate($exception= null){
		$fields = $this->getModelTemplateHeader();
		if ($exception!= null) {
			$fields = array_diff($fields, $exception); 
		}
		$fields=array_unique($fields);
		$filename = static::$tablename.'_template.csv';
		$content = singleRowToCsvString($fields);
		$header = 'text/csv';
		sendDownload($content,$header,$filename);
	}
	/**
	|	this is use to generate the fields to be included in the download template,
	|	provided the static::uploadFields is not stated in the entity file model
	|	It will resolve any foreign key in the model to their respective table.
	|	@return an array of list of generated field resolving any foreign key issue 
	*/
	private function buildTemplate($model='',$resolve=true,$isSql=false){
		if (empty($model)) {
			$model =$this->getTableName();
		}
		$model = loadClass($model);
		$labels = $model::$labelArray;
		unset($labels['status'],$labels['ID']);
		$fields = array_keys($labels);
		if ($resolve && !isset(static::$ignoreTranslation)) {
			for ($i=0; $i < count($fields); $i++) { 
				$current = $fields[$i];
				if (endsWith($current,'_id')) {
					$classname = substr($current, 0,strrpos($current, '_id',-1));
					$classname = loadClass($classname);
					$classname = ucfirst($classname);
					$joiner ='';
					if ($isSql) {
						$joiner = isset($classname::$displayField)?$classname.'.':$model.'.';
					}
					$fields[$i]= isset($classname::$displayField)?$joiner.$classname::$displayField:$joiner.$fields[$i];
				}
				else{
					if ($isSql) {
						$fields[$i]=$model.'.'.$fields[$i];
					}
					
				}
				$fields[$i]=trim($fields[$i]);
			}
		}
		return $fields;
	}

	//function that handles uploads that involves multiple tables
	function uploadMultiples($tables,$fields,$data,&$message='',$dbObject=null,$translate=false){
		$tableFields = array();// will have the same length as that of the real table
		$tableData = array();
		if ($dbObject == null) {
			$dbObject = $this->db;		
		}
		//think about how to include last insert id and how to undo the reverse
		$dbObject->transBegin();
		     for ($i=0; $i < count($tables); $i++) { 
                      $table = $tables[$i];
                      $tableField= $this->extractTableInfos($table,$fields,$data,$tableData,$message,$translate);
                       if ($tableField == false) {
                              return false;
                      }
                       $res = $this->uploadSingle($table,$tableField,$tableData,$message,$dbObject,$translate);
                       if (!$res) {
                               $dbObject->transRollback();
                               return false;
                       }
	}
	$dbObject->transCommit();
	//add the message based on the information that could be derived
	return true;
}
// this function need to extract  the field needed by the model and the corresponding data needed by the  field for insertion. note that upload single will perform data transformation when inserting the record
	private function extractTableInfos($model,$fields,$data,&$tableData,&$message=''){
		$result= array();
		$indexes= array(); 
		$defaultFields =$this->buildTemplate($model);
		for ($i=0; $i <count($defaultFields) ; $i++) { 
			$current = $defaultFields[$i];
			if (($index=array_search($current, $fields)) !==false){
				$result[]=$current;
				$indexes[]= $index;
			}
		}

		$tableData =copyMultiArrayWithIndex($indexes,$data);
		return $result;
	}

	function getLastInsertId(){
		$query = "SELECT LAST_INSERT_ID() AS last";//sud specify the table
		$result =$this->db->query($query);
		$result = $result->getResultArray();
		return $result[0]['last'];

	}

	//function to get all the total value of item present in  the database
	public function totalCount($whereClause = '')
	{
		$tablename = strtolower(static::$tablename);
		$query = "select count(*) as total from $tablename $whereClause";
		$result = $this->query($query);
		return ($result) ? $result[0]['total'] : 0;
	}

	public function removeProp($property)
	{
		unset($this->array[$property]);
	}
}
 ?>
