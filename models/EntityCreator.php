<?php
/**
* The model for performing update, insert and delete for entities
*/
namespace App\Models;

use CodeIgniter\Model;
use App\Models\ModelControllerCallback;
use App\Models\ModelControllerDataValidator;
use App\Models\WebSessionManager;
use CodeIgniter\HTTP\RequestInterface;

class EntityCreator extends Model
{
	public $outputResult = true;

	private $modelControllerCallback;
	private $modelControllerDataValidator;
	private $webSessionManager;
	protected $db;
	private $entitiesNameSpace = 'App\Entities\\';
	private $crudNameSpace = 'App\Models\Crud';
	protected $request;

	function __construct(RequestInterface $request)
	{
		$this->modelControllerCallback = new ModelControllerCallback;
		$this->modelControllerDataValidator = new ModelControllerDataValidator;
		$this->webSessionManager = new WebSessionManager;
		$this->db = db_connect();
		$this->request = $request;

	}
	private function checkPermission(){
		return true;
		//check that the user has permission to modify
		$cookie = getPageCookie();
		if (!in_array($this->webSessionManager->getCurrentUserProp('user_type'), array('student','admin','lecturer')) && @!$this->role->canModify($cookie[0],$cookie[1])) {
		  # who the access denied page.
			if (isset($_GET['a']) && $_GET['a']) {
				displayJson(false,'you do not  have permission to perform this action');exit;
			}
		  echo show_operation_denied();
		}
	}

	function add($model,$filter=false,$parent=''){//the parent field is optional
		try{
			if (empty($model)) { //make sure empty value for model is not allowed.
				displayJson(false,'an error occured while processing information');
				return;

			}
			unset($_POST['MAX_FILE_SIZE']);
			// $this->log($model,"inserting $model");
			return $this->insertSingle($model,$filter);
		}
		catch(Exception $ex){
			// echo $ex->getMessage();
			$this->db->transRollback();
			displayJson(false,$ex->getMessage());
		}

	}


	//this function is used to  document
	private function processFormUpload(string $model,$parameter){
		$oldModel = $model;
		$model = $this->entitiesNameSpace.$model;
		$paramFile= $model::$documentField;
		$fields = array_keys($_FILES);
		if (empty($paramFile)) {
			return $parameter;
		}
		foreach ($paramFile as $name => $value) {
			// $this->log($model,"uploading file $name");
			if (in_array($name, $fields)) {//if the field name is present in the fields the upload the document
				list($type,$size,$directory) = $value;
				$method ="get".ucfirst($oldModel)."Directory";
				$uploadDirectoryManager = loadClass('uploadDirectoryManager');
				if (method_exists($uploadDirectoryManager, $method)) {
					$dir  = $uploadDirectoryManager->$method($parameter);
					if ($dir===false) {
						showUploadErrorMessage($this->webSessionManager,"Error while uploading file",false);
					}
					$directory.=$dir;
				}
				$currentUpload = $this->uploadFile($name,$type,$size,$directory,$message);

				if ($currentUpload==false) {
					return $parameter;				}
				// $this->log($model,"file $name uploaded successfully");
				$parameter[$name]=$message;
			}
			else{
				// $this->log($model,"error uploading file $name");
				continue;
			}
		}
		return $parameter;
	}

	private function uploadFile($name,$type,$maxSize,$destination,&$message=''){
		if (!$this->checkFile($name,$message)) {
			return false;
		}
		$filename=$_FILES[$name]['name'];
		$ext = getFileExtension($filename);
		$fileSize = $_FILES[$name]['size'];
		$typeValid = is_array($type)?in_array(strtolower($ext), $type):strtolower($ext)==strtolower($type);
		if (!empty($filename) &&  $typeValid  && !empty($destination)) {
			if ($fileSize > $maxSize) {
				$message='file too large to be saved';return false;
			}
			$user = uniqueString();
			$destination='uploads/'.$destination;
			if (!is_dir($destination)) {
				mkdir($destination,0777,true);
			}
			$destination.="$user.".$ext;//the test should be replaced by the name of the current user.		
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
			$message = "error while uploading file. please try again";return false;
			// exit(createJsonMessage('status',false,'message','error while uploading file. please try again conddition not satisfy'));
		}
		$message='error while uploading file. please try again';
		return false;
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
			$this->db->transRollback();
			$message='uploaded file not found';
			return false;
		}
		return true;
	}


	//this function will return the last auto generated id of the last insert statement
	private function getLastInsertId(){
		$query = "SELECT LAST_INSERT_ID() AS last";//sud specify the table
		$result =$this->db->query($query);
		$result = $result->result_array();
		return $result[0]['last'];

	}
	private function DoAfterInsertion($model,$type,$data,&$db,&$message=''){
		$method = 'on'.ucfirst($model).'Inserted';
		if (method_exists($this->modelControllerCallback, $method)) {
			return $this->modelControllerCallback->$method($data,$type,$db,$message);
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

	private function  insertSingle($model){
		$this->modelCheck($model,'c');
		$message ='';
		$data = $this->input->post(null,true);
		$data = $this->processFormUpload($model,$data);
		if (in_array('password', array_keys($data))) {
			$data['password']=@password_hash($data['password'], PASSWORD_DEFAULT);
		}
		$newModel = loadClass("$model");
		$parameter = $this->extractSubset($data,$newModel);
		$parameter = removeEmptyAssoc($parameter);
		if (!$this->validateModel($newModel,$message) || $this->validateModelData($model,'insert',$parameter,$message)==false) {
			if (!$this->outputResult) {
				return false;
			}
			displayJson(false,$message);
			return;
		}
		$newModel->setArray($parameter);
		$message = '';
		$this->db->transBegin();
		if($newModel->insert($this->db,$message)){
			$inserted = $this->getLastInsertId();
			$data['LAST_INSERT_ID']= $inserted;
			if($this->DoAfterInsertion($model,'insert',$data,$this->db,$message)){
				$this->db->transCommit();
				$message = empty($message)?'operation successfull ':$message;
				if (!$this->outputResult) {
				return true;
			}
				displayJson(true,$message,$inserted);
				// $this->log($model,"inserted new $model information");//log the activity
				return;
			}
		}
		$this->db->transRollback();
		$message = empty($message)?"an error occured while saving information":$message;
		if (!$this->outputResult) {
				return false;
		}
		displayJson(false,$message);
		// $this->log($model,"unable to insert $model information");
	}

	private function log($model,$description){
		$this->application_log->log($model,$description);
	}

	function update($model,$id='',$filter=false,$param=false){
		if (empty($id) || empty($model)) {
			if (!$this->outputResult) {
				return false;
			}
			displayJson(false,'an error occured while processing information');
			return;
		}
		return $this->updateSingle($model,$id,__METHOD__,$filter,$param);
	}

	private function updateSingle($model,$id,$method,$filter=false,$param=false){
		$this->modelCheck($model,'u');
		$newModel = loadClass("$model");
		$data = $param?$param:$this->request->post(null);
		$data = $this->processFormUpload($model,$data);
		//pass in the value needed by the model itself and discard the rest.
		$parameter = $this->extractSubset($data,$newModel);
		
		$this->db->transBegin();
		$parameter['ID']=$id;
		if (!$this->validateModelData($model,'update',$parameter,$message)){
			 displayJson(false,$message);
			return ;
		} 
		$newModel->setArray($parameter);
		if ($newModel->update($id,$this->db)) {
			$data['ID']=$id;
		if($this->DoAfterInsertion($model,'update',$data,$this->db,$message)){
			$this->db->transCommit();
			$message = empty($message)?'operation successfull':$message;
			if (!$this->outputResult) {
				return true;
			}
			displayJson(true,$message);
			return;
		}
		else{
			$this->db->transRollback();
			if (!$this->outputResult) {
				return false;
			}
			 displayJson(false,$message);
			return ;
		}
		}
		else{
			$this->db->transRollback();
			if (!$this->outputResult) {
				return false;
			}
			displayJson(false,$message);
			return ;
		}
	}

//innplement deleter where function here.
	function delete($model,$id=''){
		if (isset($_POST['ID'])) {
			$id = $_POST['ID'];
		}
		if (empty($id)) {
			return false;
		}

		$this->modelCheck($model,'d');
		$model = loadClass("$model");
		return $model->delete($id);
	}
	
	private function modelCheck($model,$method){
		if (!$this->isModel($model)) {
			displayJson(false,'error occured while deleting information');
			exit;
		}
	}
	//this function checks if the argument id actually  a model
	private function isModel($model){
		$model = loadClass("$model");
		if (!empty($model) && $model instanceof $this->crudNameSpace) {
			return true;
		}
		return false;
	}
	//check that the algorithm fit and that required data are not empty
	private function validateModel($model,&$message){
		return $model->validateInsert($message);
	}
		//function to extract a subset of fields from a particular field
	private function extractSubset($array, $model){
		//check that the model is instance of crud
		//take care of user upload substitute the necessary value for the username
		//dont specify username directly
		
		$result =array();
		if ($model instanceof $this->crudNameSpace) {
			$keys = array_keys($model::$labelArray);
			$valueKeys = array_keys($array);
			$temp =array_intersect($valueKeys, $keys);
			foreach ($temp as $value) {
				$result[$value]= $array[$value];
			}
		}
		return $result;
	}

	//function for downloading data template
	function template($model){
		//validate permission here too.
		if (empty($model)) {
			show_404();exit;
		}
		$model = loadClass("$model");
		if (!is_subclass_of($model, $this->crudNameSpace)) {
			show_404();exit;
		}
		$exception = null;
		if (isset($_GET['exc'])) {
			$exception = explode('-', $_GET['exc']);
		}
		$model->downloadTemplate($exception);
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
		$model = loadClass("$model");
		if (!is_subclass_of($model, $this->crudNameSpace)) {
			show_404();exit;
		}
		$model->export($condition);
	}


// just create a the template function below to generate the needed paramter.
	function sFile($model){
		$content = $this->loadUploadedFileContent();
		$content = trim($content);
		$array = stringToCsv($content);
		$header = array_shift($array);
		$defaultValues = null;
		$args = func_get_args();
		if (count($args) > 1) {
			$method = 'upload'.ucfirst($args[1]);
			if (method_exists($this, $method)) {
				$defaultValues = $this->$method();
				$keys = array_keys($defaultValues);
				for ($i=0; $i < count($keys); $i++) { 
					$header[]=$keys[$i];
				}
				// $header = array_merge($header,);
				foreach ($defaultValues as $field => $value) {
					replaceIndexWith($array,$field,$value);
				}
			}
		}
		//check for rarecases when the information in one of the fields needed to be replaces
		if (isset($_GET['rp'] ) && $_GET['rp']) {
			$funcName = $_GET['rp'];
			# go ahead and call the function make the change
			$funcName = 'replace'.ucfirst($funcName);
			if (method_exists($this, $funcName)) {
				//the function must accept the parameter as a reference
				$this->$funcName($header,$array);
			}
		}
		$model = loadClass("$model");

		$result = $model->upload($header,$array,$message);
		$data=array();
		$data['pageTitle']='file upload report';
		if ($result) {
			$data['status']=true;
			$data['message']=$message;
			$data['model']=$model;
			$data['insert_info']=$this->db->conn_id->info;
		}
		else{
			$data['status']=false;
			$data['message']=$message;
			$data['model']=$model;
		}
		echo view('uploadreport',$data);
	}

}