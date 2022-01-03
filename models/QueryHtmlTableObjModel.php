<?php

	/**
	* This class will be used to generate table using normal query and 
	* will also contain action to be performed on the table. It will
	* also contain paging ability on the table data
	* @package	MVVC FRAMEWORK
 	* @author	ALATISE OLUWASEUN aka HOLYNATION
 	* @since	Version 1.0.0
	*/
namespace App\Models;

use CodeIgniter\Model;
class QueryHtmlTableObjModel extends Model
{

	/**
	 * Classname from the query statement
	 *
	 * @var	void
	*/
	private $classname;

	/**
	 * Query data export
	 *
	 * @var	bool
	*/
	public $export=false;

	/**
	 * Query statement from in the sql
	 *
	 * @var	string
	*/
	protected $from = 'from';

	/**
	 * Query statement
	 *
	 * @var	string empty
	*/
	private $_query = '';

	/**
	 * Query data binding
	 *
	 * @var	array empty
	*/
	private $_queryData = array();

	/**
	 * Query sql limit
	 *
	 * @var	string empty
	*/
	private $_limit = '';

	/**
	 * Paginate the result
	 *
	 * @var	string
	*/
	private $_paging = false;

	/**
	 * Total result count on page
	 *
	 * @var	int
	*/
	private $_totalLength = 0;

	/**
	 * paging lower starting value
	 *
	 * @var	int
	*/
	private $_lower = 0;

	/**
	 * paging length value
	 *
	 * @var	int
	*/
	private $_length = 0;

	/**
	 * Upload directory
	 *
	 * @var	string
	*/
	const UPLOADED_FOLDER_NAME = 'uploads';

	/**
	 * Flag for foreign table presence
	 *
	 * @var	string
	*/
	const FOREIGN_KEY_END='_id';

	/**
	 * Page default paging number
	 *
	 * @var	int
	*/
	const DEFAULT_PAGE_LENGTH =50;

	/**
	 * Table opening tag
	 *
	 * @var string
	*/
	private $_openTable = '';

	/**
	 * Table is open
	 *
	 * @var bool
	*/
	private $_isTableOPen = false;

	/**
	 * Table attribute in the html
	 *
	 * @var array
	*/
	private $_tableAttr = array();

	/**
	 * Table header
	 *
	 * @var array
	*/
	private $_header = null;

	/**
	 * Table action array
	 *
	 * @var array
	*/
	private $_actionArray = array();

	/**
	 * Append checkbox to the table
	 *
	 * @var bool
	*/
	private $_checkBox = false;

	/**
	 * Append attribute to checkbox
	 *
	 * @var array
	*/
	private $_checkBoxAttr = array();

	/**
	 * @var bool
	*/ 
	private $_exlcudeSerialNumber = false;

	/**
	 * @var object
	*/ 
	protected $db;


	function __construct()
	{
		$this->db = db_connect();
		helper('string');
	}

	public function buildOrdinaryTable($data,$action=array(),$header=null){
		return $this->buildHtmlAndAction($data,$action,$header);
	}

	/**
	* @param string $query sql query string
	* @param array $data data to be bind to the query
	* @param array $excludeArray header to be remove from the table header
	*/

	public function openTableHeader($query, $queryData=array(),$parentModel=null,$tableAttr=array(),$excludeArray=array()){
		if (empty($query)) {
			throw new Exception("You must specify query to be used");
		}
		$limit="";
		$array = array();
		$onclause ="";
		$foreignTable = array();
		$dataQuery='';

		// check that the query is a select query and that there is id or * field
		// specified in the query statement
		if (( strpos($query, "ID") ===false || strpos($query, " * ")===false)  && strpos(strtolower($query), "select") ===false) {
			throw new Exception("The query must be a select query and the an id field must be set");
		}

		$this->_query = $query;
		$this->_queryData = $queryData;

		if($parentModel != null){
			// $onclause is return by reference containing all the join statement
			// $foreigntable is return by reference containing all the foreign table
			$fieldList = $this->buildDataJoinQuery($this->_queryData,$parentModel,$excludeArray,$onclause,$foreignTable);
			$joinStatement = $onclause;
			$dataQuery = "select $fieldList from $parentModel $joinStatement $limit";
		}else{
			$dataQuery = $this->_query;
		}

		$this->_query = $dataQuery;
		$this->_openTable = $this->openTableTag($tableAttr);
		return $this;
	}

	/**
	 * @param bool exlcuding s/n from the table data
	 * @return object
	 */
	public function excludeSerialNumber($exclude = false){
		$this->_exlcudeSerialNumber = $exclude;
		return $this;
	}

	/**
	* @param bool $paged if paging should be used or not
	* @param int $lower here is the starting point
	* @param int @length here is the length to
	* @return 
	*/

	public function paging($paged=false,$lower = 0, $length=null){
		if(!$this->_query){
			throw new Exception("The openTableHeader method must come first");
		}
		$this->_paging = ($paged) ? $paged : false;
		$length = $length?$length:self::DEFAULT_PAGE_LENGTH;
		//use get function for the len and the start index for the sorting
		$lower = (isset($_GET['p_start'])&& is_numeric($_GET['p_start']) )?(int)$_GET['p_start']:$lower;
		$length = (isset($_GET['p_len'])&& is_numeric($_GET['p_len']) )?(int)$_GET['p_len']:$length;

		$this->_lower = $lower;
		$this->_length = $length;
		if ($length!=NULL && !$this->export) {
			$this->_limit = " LIMIT $lower,$length ";
		}

		$this->_query=$paged?replaceFirst("select", "select SQL_CALC_FOUND_ROWS ", $this->_query):$this->_query;
		$this->_query .= $this->_limit;
		return $this;
	}

	/**
	* @return the generated table data of the model
	*/
	public function generateTable(){
		if (empty($this->_query)) {
			throw new Exception("You must specify query to be used.");
		}
		$result = $this->db->query($this->_query,$this->_queryData);

		if ($this->_paging) {
			$result2 = $this->db->query("SELECT FOUND_ROWS() as totalCount");
			$result2=$result2->getResultArray();
			$this->_totalLength = $result2[0]['totalCount'];
		}

		// TODO: FIND A WAY TO ENSURE USER CLICK EXPORT BUTTON FOR IT TO EXPORT DATA
		$result = $result->getResultArray();
		if ($this->export) {
			$exportName = $this->classname."_table_data";
			$this->loadExportTable($exportName,$result);
		}

		$totalLength= $this->_totalLength?$this->_totalLength:count($result);
		$extra='';

		// TODO: THERE IS A LAST PAGING ANCHOR LINK BUG THAT IS NOT MEANT TO BE THERE IN THE LINK
		if ($this->_paging) {
			$classname = $this->extractClassnameFromQuery($this->_query);
			$extra = $this->generatePagedFooter($totalLength,$this->_lower,$this->_length);
		}
		return $this->buildHtmlAndAction($result,$extra);
	}

	/**
	* @param array $tableAttr table attribute
	* @param array $header table header for html table
	* @param array $action listing the action array
	* @return $this object for chaining to continue 
	*/
	public function appendTableAction($action=array(),$header=array()){
		if(!$this->_query){
			throw new Exception("The openTableHeader method must come first");
		}
		$this->_actionArray = $action;
		$this->_header = $header;
		return $this;
	}

	/**
	* @param bool $checkbox
	* @param array attr to the checkbox like id,class and any other attribute
	*/
	public function appendCheckBox($checkBox = true,$attr=array()){
		if(!$this->_query){
			throw new Exception("The openTableHeader method must come first");
		}

		if($checkBox){
			$this->_checkBox = true;
			$this->_checkBoxAttr = $attr;
		}
		return $this;
	}

	/**
	* @param array $data table row data
	* @param string $extra html paging footer
	* @return string html table row data
	*/

	private function buildHtmlAndAction($data,$extra){
		if (empty($data)) {
			return "<div class='empty-data alert alert-primary text-dark'>NO RECORD FOUND</div>";	
		}
		$result = $this->_openTable;
		$result.= $this->extractheader(empty($this->_header)?array_keys($data[0]):$this->_header,!empty($this->_actionArray));
		$result.= $this->buildTableBody($data);
		$result.= $this->closeTableTag();
		$result.= $this->buildTableFooter($extra);
		// clearing the data of memory and ensuring fresh object data
		$this->clearVar();
		return $result;
	}

	/**
	* @param array tableAttr table attribute to pass the html table as attribute
	* @return string formated html table header
	*/
	private function openTableTag($tableAttr=array()){
		$attr = (!empty($tableAttr)) ? attrToString($tableAttr) : "";
		return "<div class=\"box\"><div class=\"table-responsive no-padding\"><table $attr class='table table-bordered'>\n";
	}

	/**
	* @param array $keys header to use for table
	* @param bool $includeAction
	* @return formatted html string header 
	*/
	private function extractheader($keys,$includeAction=true){
		$result='<thead>';
		$emptyHeader='';
		$sn='';
		if ($includeAction) {
			$keys[]='Action';
		}

		if($this->_checkBox){
			$emptyHeader = "<th></th>";
		}

		if(!$this->_exlcudeSerialNumber){
			$sn = "<th>S/N</th>";
		}
		
		$result.="
		<tr> $emptyHeader $sn";

		for ($i=0; $i < count($keys); $i++) { 
			if ($keys[$i]=='ID' ||$keys[$i]=='id' ) {
				continue;
			}
			$header =removeUnderscore($keys[$i]);
			$result.="<th>$header</th>";
		}
		$result.="</tr>
		<thead>";
		return $result;
	}

	/**
	* @param array $data table data from the query
	* @return build table row data
	*/
	private function buildTableBody($data){
		$result ="<tbody>";
		for ($i=0; $i < count($data); $i++) { 
			$current = $data[$i];
			$result.=$this->buildTableRow($current,$this->_actionArray,@$_GET['p_start']+$i);
		}
		$result.='</tbody>';
		return $result;
	}

	private function buildTableFooter($extra=''){
		if(!$extra){
			return '';
		}
		$result = "<div>";
		$result .= "$extra";
		$result .= "</div>";
		return $result;
	}

	/**
	* @param array $data table data from the query
	* @param array action table action
	* @param int $index table sequence count
	*/
	private function buildTableRow($data,$action,$index=false){
		$result ='<tr class="append-content">';
		if($this->_checkBox){
			extract($this->_checkBoxAttr);
			//when extracted name,class are default var gotten
			$tableName = $this->classname;
			$id = isset($data['ID'])?$data['ID']:'';
			$name = isset($name) ? $name : $tableName;
			$name .= '-checkbox';
			$inputForm = "<input type='checkbox' class='$class' name='".$name."[]' id='".$name."[]' value='$id' />";
			$result.="<td width='60' class='form-check'>$inputForm</td>";
		}

		if ($index!==false && !$this->_exlcudeSerialNumber) {
			$index+=1;
			$result.="<td>$index</td>";
		}
		$keys = array_keys($data);
		for ($i=0; $i < count($keys); $i++) { 
			if ($keys[$i]=='ID' || $keys[$i]=='id') {
				continue;
			}
			$current = $data[$keys[$i]];

			if (isFilePath($current)) {
				$typeExt = getMediaType($current);
				$link = base_url($current);
				if($typeExt == 'audio'){
					$fileMsg = 'Hear Audio';
					$current = "<a href='$link' target='_blank'>$fileMsg</a>";
				}else if($typeExt == 'image'){
					$fileMsg = 'View Image';
					$current = "<a href='$link' target='_blank'>$fileMsg</a>";
				}else if($typeExt == 'video'){
					$fileMsg = 'View Video';
					$current = "<a href='$link' target='_blank'>$fileMsg</a>";
				}else{
					$link = base_url($current);
					$current = "<a href='$link' target='_blank' class='btn btn-info'>Download</a>";
				}
			}

			if (strtolower($keys[$i])=='status') {
				if($this->classname == 'customer'){
					$current = $data[$keys[$i]]?'Approved':'Unapproved';
				}else{
					$current = $data[$keys[$i]]?'Enabled':'Disabled';
				}
			}
			if (strtolower($keys[$i])=='disability') {
				$current = $data[$keys[$i]]?'Yes':'No';
			}
			if(strpos($current,'href')){
				$current = $current;
			}else{
				$current = wordwrap($current,50,"...",true);
			}
			$result.="<td>$current </td>";
		}
		$result.=empty($action)?'':$this->addTableAction($action,$data);
		$result.='</tr>';
		return $result;
	}

	/**
	* @param array $action table action
	* @param array $data current table row data
	* @return formatted string for html table action
	*/
	private function addTableAction($action,$data){
		$result= "<td class='action-column'><div class='list-icons'>
		<div class='dropdown'><a href='#' class='list-icons-item' data-toggle='dropdown' style='cursor:pointer;'><i class='icon-menu9'></i></a>
		<ul class='dropdown-menu' data-model=''> 
		";
		foreach ($action as $key => $value) {
			$critical = 0;
			$ajax=0;
			$link ='';
			$label =$key;
			$default=1;
			// this section is used for getting another column alongside the ID of that model
			// something like this vc/admin/edit/model_name/new_column/id
			if(is_array($value)){
				$temp = $value;
				$otherParam = $temp[1];
				$otherParam = $data[$otherParam];
				// check if the additional param include the default upload folder
				// this also means that the uploaded folder would start the directory of the path
				if(!empty($otherParam)){
					if(startsWith($otherParam,self::UPLOADED_FOLDER_NAME)){
						$tempParam = explode('/',$otherParam);
						$otherParam = "/".urlencode(base64_encode($tempParam[2])); // this would be the file name used
					}
				}
				$value = $temp[0].$otherParam;
			}
			$tableActionModel = loadClass('tableActionModel');
			if (method_exists($tableActionModel, $value)) {
				$value = $tableActionModel->$value($data,$this->classname);
				$value = array_values($value);
				$key = array_shift($value);
				$label = $key;
			} 
			$id = isset($data['ID'])?$data['ID']:'';
			if (is_array($value)) {
				$link=endsWith($value[0],'=')?($value[0].$id):($value[0].'/'.$id);
				$critical =$value[1];
				$ajax =$value[2];
			}
			else{
				$criticalArray = array('delete','disable','reset password','remove');
				if (in_array(strtolower($key), $criticalArray)) {
					$critical =1;
				}
				$link = endsWith($value,'=')?($value.$id):($value.'/'.$id);
				$link = base_url($link);
			}
			
			$editClass='';
			if($label=='edit' || $label=='update'){
				$editClass = "data-ajax-edit='1'";
			}else if ($label == 'editor'){
				$editClass = "data-ajax-editor='1'";
			}else{
				$editClass ='';
			}
			$result.="<li data-ajax='$ajax' data-critical='$critical' $editClass ><a class='dropdown-item text-center text-capitalize'  href='$link'>$label</a></li>";
		}
		$result.= '</ul></div></div></td>';
		return $result;
	}

	/**
	* @return formatted html table close tag
	*/
	private function closeTableTag(){
		return "</table></div></div>";
	}

	/**
	* @param array $queryData data to bind to query
	* @param string $parentModel
	* @param array $excludeArray column to exclude from the table
	* @param string &$onclause join statement for foreign table
	* @param string &$foreignTable foreign table
	* @return the query alognside the join statement 
	*/
	private function buildDataJoinQuery($queryData,$parentModel,$excludeArray=array(),&$onclause,&$foreignTable){
		if (empty($this->_query)) {
			throw new Exception("You must specify query to be used");
		}

		$query = $this->_query;
		$queryString = '';
		$data = [];
		$result = $this->db->query($query,$queryData);
		$results = $result->getResultArray();
		$display='';
		$foreignVal = '';
		if($result->getNumRows() > 0){
			$fields = array_keys($results[0]);
			foreach($fields as $key => $val){
				if(!empty($excludeArray)){
					if(in_array($val,$excludeArray)){
						continue;
					}
				}

				if(endsWith($val,self::FOREIGN_KEY_END)){
					$tablename = substr($val, 0,strlen($val)-strlen(self::FOREIGN_KEY_END));
					$tablename = strtolower($tablename);
					if (!class_exists($tablename)) {
						$tablename = "App\\Entities\\".ucfirst($tablename);
					}
					if (isset($tablename::$displayField)) {
						if (is_array($tablename::$displayField)) {
							$display="concat_ws(' '";
							foreach ($tablename::$displayField as $tval) {
								$display.=",".$tablename.'.'.$tval;
							}
							$display.=") as $val";
						}
						else{
							$display = strtolower($tablename::$tablename).'.'.$tablename::$displayField.' as '.$val;
						}
						$foreignTable[]=$tablename;
						$temp = $parentModel.'.'.$tablename.self::FOREIGN_KEY_END;
						$usse = isset($tablename::$joinField)?$tablename.'.'.$tablename::$joinField :"$tablename.ID";
						$onclause.=" left join $tablename on $temp =$usse ";
					}else{
						$display = $parentModel.'.'.$val;
					}
					$val = $display;
				}else{
					$val = $parentModel.'.'.$val;
				}

				if($val == "$parentModel.date_created"){
					continue;
				}

				$data[]= $val;

			}
		}else{
			echo "NO RECORD FOUND";
		}

		$queryString = implode(",", $data);
		return $queryString;
	}

	/**
	* @param int $totalPage the total data count
	* @param int $currentCount the start count per page
	* @param int $pageLength the total to display per page
	* @return this function generate page footer with link to navigate through the pages
	*/
	public function generatePagedFooter($totalPage,$currentcount,$pageLength){
	 	$beginCount =10;
	 	// echo $currentcount;exit;
	 	
	 	if ($totalPage <= $pageLength) {
	 		return;
	 	}
	 	// $links = base_url('');
		$result="<nav class='navigation'>
			<div class='row form-group'>
				<label class='col-sm-4 col-form-label'>Page Size :
					<input class='form-control' type='text' style='width:50px;display:inline-block;background-color:white;' id='page_size' value='$pageLength' disabled/>
				</label>
				<div class='col-sm-9'>
					<ul class='pagination'>
					";
		// $pageArray=$this->generatePageArray($totalPage,$pageLength);
		$totalPaged = ceil($totalPage/$pageLength);
		$currentIndex =$this->calculateCurrentIndex($currentcount,$pageLength);
		$start=0;
		if ($totalPaged > ($beginCount)) {
			$start=$currentIndex-(ceil($beginCount/2)+1) ;//half of the content before showing the current index
			$start =$start<0?0:$start;
			// $len = $start+$beginCount;
			$prev = $currentIndex> 0? ($currentIndex-1):0;
			$prev*=$pageLength;
			$disable = $prev==0?'disabled':'';
			$result.="<li data-start='$prev' data-length='$pageLength' class='page-item $disable'>«</li>";
			$len = $start+ceil($beginCount/2);
			for ($i=$start; $i < $len; $i++) { 
				$current =1+ $i;
				$itemClass ='page-item';
				if ($i==$currentIndex) {
					$itemClass ='active page-item';
				}
				$start =  ($current > 1) ? ($current * $pageLength) - $pageLength : 0;
				$end = $current*$pageLength;
				$result.="<li data-start='$end' data-length='$pageLength' class='$itemClass '>
				<a class='page-link' href='?p_start=".$start."&p_len=".$pageLength."'> $current</a>
				</li>";
				// $start = $end;
			}
			$result.="<li data-start='' data-length='$pageLength' class='page-item  break'>
			<a class='page-link'>...</a>
			</li>";
			$len =floor($beginCount/2);
			$start = ($totalPaged-(1+$len));
			$len+=$start;
			for ($i=$start; $i < $len; $i++) { 
				$current =1+ $i;
				$itemClass ='page-item';
				if ($i==$currentIndex) {
					$itemClass ='page-item active';
				}
				$start =  ($current > 1) ? ($current * $pageLength) - $pageLength : 0;
				$end = $current * $pageLength;
				$result.="<li data-start='$end' data-length='$pageLength' class='$itemClass '>
				<a class='page-link' href='?p_start=".$start."&p_len=".$pageLength."'>$current</a>
				</li>";
				// $start = $end;
			}
			$prev = $currentIndex < $totalPaged? ($currentIndex+1):$totalPaged-1;
			$last =$prev *$pageLength;
			$result.="<li data-start='$last' data-length='$pageLength' class='page-item'>
			<a class='page-link'>»</a>
			</li>";
			$len = $start+$beginCount;
		}
		else{
			for ($i=0; $i <= $totalPaged; $i++) { 
				$current =$i + 1;
				$itemClass ='page-item';
				if ($i==$currentIndex) {
					$itemClass ='page-item active';
				}
				$start =  ($current > 1) ? ($current * $pageLength) - $pageLength : 0;
				$end = $start * $pageLength;
				$result.="<li data-start='$end' data-length='$pageLength' class='$itemClass'>
				<a class='page-link' href='?p_start=".$start."&p_len=".$pageLength."'>$current</a>
				</li>";
				// $start = $end;
			}
		}
		$result.="<div class='clearfix'></div></ul></div></div>
		</nav>";
		return $result;
	}

	/** 
	* @return the current index in the paging data 
	*/
	private function calculateCurrentIndex($current,$pageLength){
		return ceil($current/$pageLength);
	}

	/**
	* @return the number of count array for the paging
	*/
	private function generatePageArray($totalPage,$pageLength){
		$count = ceil(($totalPage/$pageLength));
		$result= array();
		for ($i=0; $i < $count ; $i++) { 
			$result[]=$i+1;
		}
		return $result;
	}

	/**
	* @param string $query sql query
	* @return model/table name from the query
	*/
	private function extractClassnameFromQuery($query){
		$pos = strpos($query, ".ID");
		if ($pos!==false) {
			$len = strlen($query);
			$div = $pos - $len;
			$spaceIndex = strrpos(substr($query,0,$pos), ' ');
			$this->classname = trim(substr($query, $spaceIndex+1,($pos - ($spaceIndex+1))));
			return;
		}
		//if .id is not present then validate the id is present and get the first string after the from keywork
		$pos = strpos($query, "ID");
		if ($pos!==false) {
			//get the index of from and then get the index of space 
			$from = stripos($query, 'from');
			$from+=strlen("from") + 1;

			$classname = substr($query,$from,strpos($query, ' ',$from) -$from);
			$this->classname = $classname;
		}
	}

	/**
	* @param string $model parameter is the name of the model
	* @param $data table data
	* @return csv data 
	*/
	public function loadExportTable($model,$data)
	{
		$res =array2csv($data);
	  	sendDownload($res,'text/csv',$model.'.csv');exit;
	}

	/**
	* @return the total length of the data
	*/
	public function getTotalLength(){
		return $this->_totalLength;
	}

	private function clearVar(){
		$this->classname = "";
		$this->_query = "";
		$this->_paging = false;
		$this->_lower = 0;
		$this->_length = null;
		$this->_limit = "";
		$this->_totalLength = 0;
		$this->_queryData = array();
		$this->_checkBox = false;
		$this->_checkBoxAttr = array();
		$this->_openTable = '';
		$this->_isTableOPen = false;
	}
}