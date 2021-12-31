<?php
/**
* The controller that link to the model.
*all response in this class returns a json object return
*/

class ModelController extends CI_Controller
{
	private $_rootUploadsDirectory = "uploads/";
	// RULE: date_created comes first,then date_modified or any named date
	// NOTE: it only accept two diff date,nothing more than that.
	private $_dateParam = array('company_device'=> array('date_created','date_updated'), 'default' => array('date_created','date_modified'));

	function __construct()
	{
		parent::__construct();
		$this->load->model('accessControl');//for authentication authorization validation
		// $this->load->model('entities/application_log');
		$this->load->helper('array');
		$this->load->helper('string');
		$this->load->model('modelControllerDataValidator');
		$this->load->model('webSessionManager');
		$this->load->model('modelControllerCallback');
		$this->load->model('entities/role');
		if ($this->webSessionManager->getCurrentuserProp('user_type')=='admin') {
			$this->role->checkWritePermission();
		}
		
	}

	//function that will enable the ajax call and return just the table content by passing the url link
	function tableContent($model,$start=0,$len=100,$paged=false){
		if (!$this->isModel($model)) {
			show_404();
			exit;
		}
		$this->load->model('tableViewModel');
		$html =  $this->tableViewModel->getTableHtml($model,$message,array(),array(),$paged,$start,$len);
		$data['tableData']=$html==true?$html:$message;
		$this->load->view('pages/modelTableView',$data);
	}

	function add($model,$filter=false,$parent='',$noArrSkip=false){//the parent field is optional
		try{
			if (empty($model)) { //make sure empty value for model is not allowed.
				echo createJsonMessage('status',false,'message','an error occured while processing information','description','the model parameter is null so it must not be null');
				return;

			}

			unset($_POST['MAX_FILE_SIZE']);
			if ($model=='many') {
				$this->insertMany($filter,$parent);
			}
		else{
				// $this->log($model,"inserting $model");
				$this->insertSingle($model,$filter,$noArrSkip);
			}
		}
		catch(Exception $ex){
			echo $ex->getMessage();
			$this->db->trans_rollback();
		}

	}

	private function insertMany($filter){
		$appended = '_id';
		//make sure the parent name exist
		if (!isset($_POST['parent_generated'])) {
			throw new Exception("is like you forgot to set a parent table for this form,kindly do and try again", 1);
		}
		//first validate the model
		$parentName =$_POST['parent_generated'];//remove the appended from the back
		unset($_POST['parent_generated']);
		$parent= $parentName.$appended;
		$prevCount = 0;
		$models =$this->validateModels('c',$message);//validate the models and return the model arrays on success of return false and return message
		$desc = implode(' , ', $models);
		// $this->log($desc,"attempting to insert $desc");
		if (!$models) {
			echo createJsonMessage('status',false,'message','an error occured while processing information','description',$message);
			exit;
		}
		$inTable =array_key_exists($parentName, $models);
		$this->db->trans_begin();//start transaction
		$data = $this->input->post(null,$filter);
		$parentValue=@$data[$parent];
		$isFirst=true;
		$insertids='';
		$message='';
		foreach ($models as $model => $prop) {
			if (is_array($prop) || !is_int($prop)) {
				$this->db->trans_rollback();
				throw new Exception("invalid model properties");
			}
			if (!class_exists(ucfirst($model))) {
				$this->load->model("entities/$model");
			}
			$data = $this->processFormUpload($model,$data,false);
			$parameter = $this->extractSubset($data,$model);
			$parameter = removeEmptyAssoc($parameter);
			if (!$this->validateModelData($model,'insert',$parameter,$message)) {
				$this->db->trans_rollback();
				echo createJsonMessage('status',false,'message',$message);
				return;
			}
			$parentSet= false;
			if ($parentName==$model || $isFirst) {//if this is the parent or the first table
				$this->$model->setArray($parameter);
				if(!$this->$model->insert($this->db,$message)){
					//if tere is any problem with the current insertion just remove rollback the transaction and  exit with error that will be faster.
					$this->db->trans_rollback();
					echo createJsonMessage('status',false,'message',$message);
					return false;
					// break;
				}
				$prevCount=$prop;
				if ($inTable) {
					$parentValue = $this->getLastInsertId();//or another means of getting the parent value
					$insertids .=$parentValue.'#';
					$parentSet = true;
				}
				$isFirst=false;
				continue;
			}
			$ins = $this->getLastInsertId();
			$ins.='#';
			$insertids.=$parentSet?"":$ins;
			$parameter[$parent] = $parentValue;
			if ($model=='next_of_kin') {
				unset($parameter['guardian_ID']);
			}
			$this->$model->setArray($parameter);
			$this->$model->insert($this->db);
			$prevCount=$prop;
		}
		if ($this->db->trans_status() === FALSE) {
			$this->db->trans_rollback();
			$message = empty($message)?'error occured while inserting record':$message;
			echo createJsonMessage('status',false,'message',$message);
			// $this->log($desc,$message);
			return false;
		}
		//load the insert many method here before the db is committed so that the transaction is atomic.
		$data['LAST_INSERT_ID']= $insertids;
		if($this->afterManyInserts(array_keys($models),'insert',$data,$this->db)){
			$this->db->trans_commit();//end the transaction
			// $result = array('status'=>)
			echo createJsonMessage('status',true,'message','records inserted successfully','data',$parentValue);
			// $this->log($desc," $desc Inserted");
			return true;
		}
		$this->db->trans_rollback();
		echo createJsonMessage('status',false,'message','error occured while inserting records');
		// $this->log($desc," error inserting $desc");
		return false;
	}
	// the models is the array of all the models inserted, type specify if it an update or an insert,
	// data is the data that was worked on. the filter post data.
	// the db is the database passed as reference.
	private function afterManyInserts($models,$type,$data,&$db){
		//delegate to a method in the callback class
		$method= 'onInsertMany';
		if (method_exists($this->modelControllerCallback, $method)) {
			return $this->modelControllerCallback->$method($models,$type,$data,$db);
		}
		return true;
	}
	private function updateMany($filter){
		//first validate the model
		$appended = '_id';
		//make sure the parent name exist
		if (!isset($_POST['parent_generated'])) {
			throw new Exception("is like you forgot to set a parent table for this form,kindly do and try again", 1);
		}
		//first validate the model
		$parentName =$_POST['parent_generated'];//remove the appended from the back
		unset($_POST['parent_generated']);
		unset($_POST['MAX_FILE_SIZE']);
		$parent= $parentName.$appended;
		$prevCount = 0;
		$models =$this->validateModels('u',$message);//validate the models and return the model arrays on success of return false and return message
		if (!$models) {
			echo createJsonMessage('status',false,'message',$message);
			return;
		}
		// $inTable =array_key_exists($parentName, $models);
		$this->db->trans_begin();//start transaction
		$data = $this->input->post(null,$filter);
		$parentValue=isset($data[$parent])?$data[$parent]:false;
		$isFirst = true;
		foreach ($models as $model => $prop) {
			if (empty($prop) || !is_array($prop) || count($prop)!=2) {
				$this->db->trans_rollback();
				throw new Exception("invalid model properties");
			}
			//load the model
			$this->load->model("entities/$model");
			// $parameter = subArrayAssoc($data,$prevCount,$prop[0]-$prevCount);
			$data = $this->processFormUpload($model,$data,$prop[1]);
			$parameter = $this->extractSubset($data,$model);
			if (empty($parameter) || $this->validateModelData($model,'update',$parameter,$message)==false) {
				$this->db->trans_rollback();
				if (empty($message)) {
					$message ='error occured while performing operation';
				}
				throw new Exception($message, 1);

			}
			if ($parentName==$model || $isFirst) {//this is the first transaction
				$this->$model->setArray($parameter);
			
				$this->$model->update($prop[1],$this->db);
				$prevCount=$prop[0];
				$isFirst= false;
				continue;
			}
			if ($model=='next_of_kin') {
				
				// print_r($parameter);
				// echo "got here";exit;
				unset($parameter['guardian_ID']);
			}

			$this->$model->setArray($parameter);
			$this->$model->update($prop[1],$this->db);
			$prevCount=$prop[0];
		}

		if ($this->db->trans_status() === FALSE) {
			echo createJsonMessage('status',true,'message','error occured while updating record');
			return false;
		}
		if($this->afterManyInserts(array_keys($models),'update',$data,$this->db)){
			$this->db->trans_commit();//end the transaction
			echo createJsonMessage('status',true,'message','records updated successfully','data',$parentValue);
			return true;
		}
		$this->db->trans_rollback();
		echo createJsonMessage('status',false,'message','error occured while updating record');
		return false;
	}

	//this function is used to  document
	private function processFormUpload($model,$parameter,$insertType=false){
		$paramFile= $model::$documentField;
		$fields = array_keys($_FILES);
		if (empty($paramFile) || empty($_FILES)) {
			return $parameter;
		}
		foreach ($paramFile as $name => $value) {
			// $this->log($model,"uploading file $name");
			//if the field name is present in the fields the upload the document
			if (in_array($name, $fields)) {

				// list($type,$size,$directory,$preserve,@$max_width,@$max_height) = $value;
				// this is a precaution if no keys of this name are not set in the array
				$preserve=false;
				$max_width = 0;
				$max_height = 0;
				$directory="";
				extract($value);

				$method ="get".ucfirst($model)."Directory";
				$this->load->model('uploadDirectoryManager');
				if (method_exists($this->uploadDirectoryManager, $method)) {
					$dir  = $this->uploadDirectoryManager->$method($parameter);
					if ($dir===false) {
						exit(createJsonMessage('status',false,'message','Error while uploading file'));
					}
					$directory.=$dir;
				}

				$currentUpload = $this->uploadFile($model,$name,$type,$size,$directory,$message,$insertType,$preserve,$max_width,$max_height);
				if ($currentUpload==false) {
					return $parameter;
				}
				$parameter[$name]=$message;
			}
			else{
				continue;
			}
		}
		return $parameter;
	}

	private function uploadFile($model,$name,$type,$maxSize,$destination,&$message='',$insertType=false,$preserve=false,$max_width=0,$max_height=0){
		if (!$this->checkFile($name,$message)) {
			return false;
		}
		$filename=$_FILES[$name]['name'];
		$ext = strtolower(getFileExtension($filename));
		$fileSize = $_FILES[$name]['size'];
		$typeValid = is_array($type)?in_array(strtolower($ext), $type):strtolower($ext)==strtolower($type);
		if (!empty($filename) &&  $typeValid  && !empty($destination)) {
			if (!is_null($maxSize) && $fileSize > $maxSize) {
				// $message='file too large to be saved';return false;
				$calcsize = calc_size($maxSize);
				exit(createJsonMessage('status',false,'message',"The file you are attempting to upload is larger than the permitted size ($calcsize)"));
			}
			$destination=$this->_rootUploadsDirectory.$destination;
			if (!is_dir($destination)) {
				mkdir($destination,0777,true);
			}

			// using this is to check whether max_width or max_height was passed
			if(($max_width !== 0 && $max_height !== 0) || $max_width !== 0 || $max_height !== 0){
                $config['max_width'] = $max_width;
                $config['max_height'] = $max_height;
                $temp_name = $_FILES[$name]['tmp_name'];

                if (!$this->isAllowedDimensions($temp_name,$max_width,$max_height))
                {
                    // $message = 'The image you are attempting to upload doesn\'t fit into the allowed dimensions.';return false;
                    exit(createJsonMessage('status',false,'message',"The image you are attempting to upload doesn't fit into the allowed dimensions (max_width:$max_width x max_height:$max_height)."));
                }
			}

			$naming= '';
			$new_name = $this->webSessionManager->getCurrentuserProp('user_table_id').'_'.uniqid()."_".date('Y-m-d').'.'.$ext;
			if($insertType){
				$getUpload = $this->getUploadID($model,$insertType,$name);
				if($getUpload === 'insert'){
					// this means inserting
					$naming = ($preserve) ? $filename : $new_name; 
				}else{
					$naming = basename($getUpload); // this means updating
				}
				
			}else{
				// this means inserting
				$naming = ($preserve) ? $filename : $new_name; 
			}
			$pos = $naming;
			$destination.=$pos;//the test should be replaced by the name of the current user.
			if(move_uploaded_file($_FILES[$name]['tmp_name'], $destination)){
				$message=$destination;
				return true;//$destination;
			}
			else{
				$message = "error while uploading file. please try again";return false;
				// exit(createJsonMessage('status',false,'message','error while uploading file. please try again'));
			}
		}
		else{
			// $message = "error while uploading file. please try again";return false;
			exit(createJsonMessage('status',false,'message','error while uploading file. please try again condition not satisfy'));
		}
		// $message='error while uploading file. please try again';return false;
		exit(createJsonMessage('status',false,'message','error while uploading file. please try again'));
	}
	private function isAllowedDimensions($temp,$max_width=0,$max_height=0)
	{

		if (function_exists('getimagesize'))
		{
			$D = @getimagesize($temp);

			if ($max_width > 0 && $D[0] > $max_width)
			{
				return FALSE;
			}

			if ($max_height > 0 && $D[1] > $max_height)
			{
				return FALSE;
			}

			// if ($min_width > 0 && $D[0] < $min_width)
			// {
			// 	return FALSE;
			// }

			// if ($min_height > 0 && $D[1] < $min_height)
			// {
			// 	return FALSE;
			// }
		}

		return TRUE;
	}
	private function getUploadID($model,$id,$name='')
	{
		if ($id) {
			// return $id;
			// this means that it is updating
			$query="select $name from $model where id = ?";
			$result = $this->db->query($query,array($id));
			$result=$result->result_array();
			
			// the return message 'insert' is a rare case whereby there is no media file at first
			// yet one want to add the media file through update action
			return (!empty($result[0][$name])) ? $result[0][$name] : 'insert';
		}
		else{
			// this means it is inserting
			$query="select id from $model order by id desc limit 1";
			$result = $this->db->query($query);
			$result=$result->result_array();
			if ($result) {
				return $result[0]['id'];
			}
			return 1; //if no initial record
		}

	}
	private function checkFile($name,&$message=''){
		$error = !$_FILES[$name]['name'] || $_FILES[$name]['error'];
		if ($error) {
			if ((int)$error===2) {
				$message = 'file larger than expected';
				return false;
			}
			return false;
		}
		
		if (!is_uploaded_file($_FILES[$name]['tmp_name'])) {
			$this->db->trans_rollback();
			$message='uploaded file not found';
			return false;
		}
		return true;
	}


	//this function will return the last auto generated id of the last insert statement
	private function getLastInsertId(){
		return getLastInsertId($this->db);
	}
	private function DoAfterInsertion($model,$type,$data,&$db,&$message='',&$redirect=''){
		$method = 'on'.ucfirst($model).'Inserted';
		if (method_exists($this->modelControllerCallback, $method)) {
			return $this->modelControllerCallback->$method($data,$type,$db,$message,$redirect);
		}
		return true;
	}

	private function suspendAfterInsertionResult($model,$type,$data,&$db,&$message=''){
		$method = 'on'.ucfirst(($model)).'Suspend';
		if(method_exists($this->modelSuspendCallback, $method)){
			return $this->modelSuspendCallback->$method($data,$type,$db,$message);
		}
		return true;
	}

// the message variable will give the eror message if there is an error and the variable is passed
	private function validateModelData($model,$type,&$data,&$message=''){
		$method = 'validate'.ucfirst($model).'Data';
		if (method_exists($this->modelControllerDataValidator, $method)) {
			$result =$this->modelControllerDataValidator->$method($data,$type,$message);
			return $result;
		}
		return true;
	}

	private function validateModels($method,&$message){
		if (!isset($_POST['edu-submit'])) {
			$message= 'fatal error!';
			return false;
		}
		$jsonEncode = $_POST['combined-models'];
		unset($_POST['edu-submit'],$_POST['edu-reset'],$_POST['combined-models']);
		$arr = json_decode($jsonEncode,true);
		$keys = array_keys($arr);
		$allGood = $this->isAllModel($keys,$method,$message);
		if ($allGood) {
			return $arr;
		}
		return false;
	}

	private function isAllModel($keys,$method,$message){
		for ($i=0; $i < count($keys); $i++) {
			$model = $keys[$i];
			if (!$this->isModel($model) ) {
				$message ="$model is not a valid model";
				return false;
			}
			// if (!$this->accessControl->moduleAccess($model,$method)) {
			// 	$message="access denied";
			// 	return false;
			// }
		}
		return true;
	}

	//this method is called when a single insertion is to be made.
	private function  insertSingle($model,$filter,$noArrSkip){
		$this->modelCheck($model,'c');
		$message ='';
		$filter = (bool)$filter;
		$noArrSkip = (bool)$noArrSkip; // this is use to allow extra param array if needed later in the code
		$data = $this->input->post(null,$filter);
		$data = $this->processFormUpload($model,$data,false);
		if (in_array('password', array_keys($data))) {
			$data['password']=@md5($data['password']);
		}
		unset($data["edu-submit"]);
		$this->load->model("entities/$model");
		$parameter=$data;
		// this is allow param not stated in the entity typeArray property to pass through without being removed from the array
		if(!$noArrSkip){
			$parameter = $this->extractSubset($parameter,$model);
		}
		$parameter = removeEmptyAssoc($parameter);
		if ($this->validateModelData($model,'insert',$parameter,$message)==false) {
			echo createJsonMessage('status',false,'message',$message);
			return;
		}

		// using this to skip a param from the other param for insertion and later use in modelcallback function further processing in the code

		if(property_exists($model, 'skipParam')){
			$skip = $model::$skipParam;
			if($skip){
				foreach($skip as $sk){
					if(array_key_exists($sk, $parameter)){
						unset($parameter[$sk]);
					}
				}
			} // ended here
		}

		// check if date_modified or date_created is part of the entity
		$labelArray = array_keys($model::$labelArray);
		$dateLabel = "default";
		if(array_key_exists($model,$this->_dateParam)){
			$dateLabel = $this->_dateParam[$model];
		}

		$dateParam = $this->_dateParam[$dateLabel];
		if(in_array($dateParam[0], $labelArray)){
			$parameter[$dateParam[0]] = date('Y-m-d H:i:s');
		}
		if(in_array($dateParam[1], $labelArray)){
			$parameter[$dateParam[1]] = date('Y-m-d H:i:s');
		}
		
		$this->$model->setArray($parameter);
		if (!$this->validateModel($model,$message)) {
			echo createJsonMessage('status',false,'message',$message);
			return;
		}
		$message = '';
		$this->db->trans_begin();
		if($this->$model->insert($this->db,$message)){
			$inserted = $this->getLastInsertId($this->db);
			$data['LAST_INSERT_ID']= $inserted;

			if($this->DoAfterInsertion($model,'insert',$data,$this->db,$message,$redirect)){
				$this->db->trans_commit();
				if($redirect != ''){
					// echo createJsonMessage('status',true,'message',$redirect);
					$arr = array();
					$arr['status'] = true;
					$arr['message'] = $redirect;
					echo json_encode($arr); return;
				}else{
					$message = empty($message)?'Operation Successful ':$message;
				}
				echo createJsonMessage('status',true,'message',$message,'data',$inserted);
				// $this->log($model,"inserted new $model information");//log the activity
				return;
			}
			// echo createJsonMessage('status',false,'message',$message);
		}
		$this->db->trans_rollback();
		$message = empty($message)?"an error occured while saving information":$message;
		echo createJsonMessage('status',false,'message',$message);
		// $this->log($model,"unable to insert $model information");
	}

	// private function log($model,$description){
	// 	$this->application_log->log($model,$description);
	// }

	function update($model,$id='',$filter=false,$flagAction = false){
		if (empty($id) || empty($model)) {
			echo createJsonMessage('status',false,'message','an error occured while processing information','description','the model parameter is null so it must not be null');
			return;
		}
		if ($model=='many') {
			$this->updateMany($filter);
		} else {
			$this->updateSingle($model,$id,__METHOD__,$filter,$flagAction);
		}

	}

	private function updateSingle($model,$id,$method,$filter,$flagAction=false){
		$this->modelCheck($model,'u');
		$this->load->model("entities/$model");
		$filter = (bool)$filter;
		$data = $this->input->post(null,$filter);
		unset($data["edu-submit"],$data["edu-reset"]);
		$data = $this->processFormUpload($model,$data,$id);
		//pass in the value needed by the model itself and discard the rest.
		$parameter = $this->extractSubset($data,$model);
		$this->db->trans_begin();
		if ($this->validateModelData($model,'update',$parameter,$message) ) {
			// check if date_modified is part of the entity
			$labelArray = array_keys($model::$labelArray);
			$dateLabel = "default";
			if(array_key_exists($model,$this->_dateParam)){
				$dateLabel = $this->_dateParam[$model];
			}

			$dateParam = $this->_dateParam[$dateLabel];
			if(in_array($dateParam[1], $labelArray)){
				$parameter[$dateParam[1]] = date('Y-m-d H:i:s');
			}

			$this->$model->setArray($parameter);
			if (!$this->$model->update($id,$this->db)) {
				$this->db->trans_rollback();
				// $message="cannot perform update";
				$arr['status']=false;
		        $arr['message']= 'cannot perform update';
		        if($flagAction){
		        	$arr['flagAction'] = $flagAction;
		        }
		        echo json_encode($arr);
				 // echo createJsonMessage('status',false,'message',$message);
				return ;
			}
			$data['ID']=$id;
			if($this->DoAfterInsertion($model,'update',$data,$this->db,$message,$redirect)){
				$this->db->trans_commit();
				if($redirect != ''){
					// echo createJsonMessage('status',true,'message',$redirect);
					$arr = array();
					$arr['status'] = true;
					$arr['message'] = $redirect;
					echo json_encode($arr); return;
				}else{
					$message = empty($message)?'Operation Successful ':$message;
				}
				$arr['status'] = true;
		        $arr['message']= $message;
		        if($flagAction){
		        	$arr['flagAction'] = $flagAction;
		        }
		        echo json_encode($arr);

				// echo createJsonMessage('status',true,'message',$message);
				return;
			}
			else{
				$this->db->trans_rollback();
				$arr['status']=false;
		        $arr['message']= $message;
		        if($flagAction){
		        	$arr['flagAction'] = $flagAction;
		        }
		        echo json_encode($arr);
				 // echo createJsonMessage('status',false,'message',$message);
				return;
			}
		}
		else{
			$this->db->trans_rollback();
			$arr['status']=false;
	        $arr['message']= $message;
	        if($flagAction){
	        	$arr['flagAction'] = $flagAction;
	        }
	        echo json_encode($arr);
			 // echo createJsonMessage('status',false,'message',$message);
			return;
		}
	}

//innplement deleter where function here.
	function delete($model,$id=''){
		if (isset($_POST['ID'])) {
			$id = $_POST['ID'];
		}
		if (empty($id)) {
			echo createJsonMessage('status',false,'message','error occured while deleting information');
			return;
		}

		$this->modelCheck($model,'d');
		$this->load->model("entities/$model");
		if ($this->$model->delete($id)) {
			echo createJsonMessage('status',true,'message','information deleted successfully');
		}
		else{
			echo createJsonMessage('status',false,'message','error occured while deleting information');
		}
	}
	private function modelCheck($model,$method){
		// if (!isset($_POST["edu-submit"])) {
		// 	echo createJsonMessage('status',false,'message','error occured while deleting information');
		// 	exit;
		// }
		if (!$this->isModel($model)) {
			echo createJsonMessage('status',false,'message','error occured while deleting information');
			exit;
		}
		// echo "got here";
		// if (!$this->accessControl->moduleAccess($model,$method)) {
		// 	echo createJsonMessage('status',false,'message','operation access denied');
		// 	exit;
		// }
	}
	//this function checks if the argument id actually  a model
	private function isModel($model){
		$this->load->model("entities/$model");
		if (!empty($model) && $this->$model instanceof Crud) {
			return true;
		}
		return false;
	}
	//check that the algorithm fit and that required data are not empty
	private function validateModel($model,&$message){
		return $this->$model->validateInsert($message);
	}
		//function to extract a subset of fields from a particular field
	private function extractSubset($array, $model){
		//check that the model is instance of crud
		//take care of user upload substitute the necessary value for the username
		//dont specify username directly
		$result =array();
		if ($this->$model instanceof $this->crud) {
			$keys = array_keys($model::$labelArray);
			$valueKeys = array_keys($array);
			$temp =array_intersect($valueKeys, $keys);
			foreach ($temp as $value) {
				$result[$value]= $array[$value];
			}
		}
		if ($model=='user') {
			$result =$this->processUser($array,$result);
		}
		return $result;
	}

	private function goPrevious($message,$path=''){
		$location=isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'';
		if (empty($location) || !startsWith($location,base_url())) {
			$location= $path;
		}
		$this->session->set_flashdata('message',$message);
		header("location:$location");
	}

	//function for downloading data template
	function template($model){
		//validate permission here too.
		if (empty($model)) {
			show_404();exit;
		}
		loadClass($this->load,$model);
		if (!is_subclass_of($this->$model, 'Crud')) {
			show_404();exit;
		}
		$exception = null;
		if (isset($_GET['exc'])) {
			$exception = explode('-', $_GET['exc']);
		}
		$this->$model->downloadTemplate($exception);
	}
	function export($model){
		$condition = null;
		$args  =func_get_args();
		if (count($args) > 1) {
			$method = 'export'.ucfirst($args[1]);
			if (method_exists($this, $method)) {
				$condition = $this->$method();
			}
		}
		if (empty($model)) {
			show_404();exit;
		}
		loadClass($this->load,$model);
		if (!is_subclass_of($this->$model, 'Crud')) {
			show_404();exit;
		}
		$this->$model->export($condition);
	}

	public function upload_timestamp(){
		if(empty($_FILES)){
			$param = array('status'=>false,'message'=>'Please choose a file to upload','backLink'=>$_SERVER['HTTP_REFERER'],'model'=>'timestamp');
			$this->load->view('uploadreport',$param);return;
		}

		$filePath = 'uploads/timestamp/';
		if (!is_dir($filePath)) {
			mkdir($filePath,0777,true);
		}

		$filePath.='timestamp_perm_'.date('Y-m-d h-i-s').'.csv';
		$content = $this->loadUploadedFileContent($filePath,'time-upload');
		$content = trim($content);
		$array = stringToCsv($content);
		$insertString='';

		$countOrder = 1;
		$countArr = count($array);
		foreach ($array as $key => $value) {
			$currentRow = $value[0];
			$timeOrder = $countOrder;
			if ($insertString) {
				$insertString.=',';
			}
			$insertString.=" ('$currentRow',STR_TO_DATE('$currentRow','%H:%i:%s'),'$timeOrder')";
			$countOrder++;
		}

		// for($i = 0; $i < $countOrder; $i++){
		// 	$currentRow = $array[$i][0];
		// 	// print_r($currentRow);exit;
		// 	$timeOrder = $countOrder;
		// 	if ($insertString) {
		// 		$insertString.=',';
		// 	}
		// 	$insertString.=" ('$currentRow',STR_TO_DATE('$currentRow','%H:%i:%s'),'$timeOrder')";
		// 	$countOrder++;
		// }

		if ($insertString==false) {
			$param = array('status'=>false,'message'=>"no data available for insertion",'backLink'=>$_SERVER['HTTP_REFERER'],'model'=>'timestamp');
			$this->load->view('uploadreport',$param);return;
		}

		$query ="insert ignore into timestamp_perm(time_stamp_perm, time_stamp_in_24,time_order) values $insertString on duplicate key update time_stamp_perm = values(time_stamp_perm)";
		$result = $this->db->query($query);
		if (!$result) {
			$this->trans_rollback();
			$param = array('status'=>false,'message'=>"no data available for insertion",'backLink'=>$_SERVER['HTTP_REFERER'],'model'=>'timestamp');
			$this->load->view('uploadreport',$param);return;
		}

		$param = array('status'=>true,'message'=>'You have successfully uploaded the timestamp','backLink'=>$_SERVER['HTTP_REFERER'],'model'=>'timestamp');
		if ($this->webSessionManager->getCurrentuserProp('user_type')=='admin') {
			$param['canView']=$this->getAdminSidebar();
		}
		$this->db->trans_commit();
		$this->load->view('uploadreport',$param);
	}

	private function loadUploadedFileContent($filePath=false,$filename=''){
		$filename = ($filename != '') ? $filename : 'bulk-upload';
		$status = $this->checkFile($filename,$message);
		if ($status) {
			if(!endsWith($_FILES[$filename]['name'],'.csv')){
				echo "Invalid file format";exit;
			}
			$path = $_FILES[$filename]['tmp_name'];
			$content = file_get_contents($path);
			if ($filePath) {
				$res =move_uploaded_file($_FILES[$filename]['tmp_name'], $filePath);
				if (!$res) {
					exit("error occured while performing file upload");
				}
			}
			return $content;
		}
		else{
			echo "$message";exit;
		}
	}

	private function getAdminSidebar()
	{
		$this->load->model('custom/adminData');
		loadClass($this->load,'admin');
		$admin = new Admin();
		$admin->ID= $this->webSessionManager->getCurrentuserProp('user_table_id');
		$admin->load();
		$role = $admin->role;
		return $this->adminData->getCanViewPages($role);
	}

}