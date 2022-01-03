<?php 
	/**
	* This class like other controller class will have full access control capability
	*/
	namespace App\Controllers;

	use App\Models\WebSessionManager;

	class Actioncontroller extends BaseController
	{
		private $uploadedFolderName = 'uploads';
		private $crudNameSpace = 'App\Models\Crud';
		private $db;
		private $webSessionManager;

		function __construct()
		{
			$this->db = db_connect();
			$this->webSessionManager = new WebSessionManager;
			// basically the admin should be the one accessing this module
			if ($this->webSessionManager->getCurrentUserprop('user_type')=='admin') {
				$role = loadClass('role');
				$role->checkWritePermission();
			}
		}

		public function disable($model,$id){
			$model = loadClass("$model");
			//check that model is actually a subclass
			if ( empty($id)===false && is_subclass_of($model,$this->crudNameSpace)) {
				if($model->disable($id,$this->db)){

					echo createJsonMessage('status',true,'message',"item disable successfully...",'flagAction',true);
				}else{
					echo createJsonMessage('status',false,'message',"cannot disable item...",'flagAction',false);
				}
			}
			else{
				echo createJsonMessage('status',false,'message',"cannot disable item...",'flagAction',false);
			}
		}
		public function enable($model,$id){
			$model = loadClass("$model");
			//check that model is actually a subclass
			if ( !empty($id) && is_subclass_of($model,$this->crudNameSpace ) && $model->enable($id,$this->db)) {
				echo createJsonMessage('status',true,'message',"item enabled successfully...",'flagAction',true);
			}
			else{
				echo createJsonMessage('status',false,'message',"cannot enable item...",'flagAction',false);
			}
		}
		public function view($model,$id){

		}

		public function truncate($model){
			if($model){
				$builder = $this->db->table($model);
				if($builder->truncate()){
					echo createJsonMessage('status',true,'message',"item successfully truncated...",'flagAction',true);
				}else{
					echo createJsonMessage('status',false,'message',"cannot truncate item...",'flagAction',false);
				}
			}
		}

		public function deleteModelByUserId($model,$field,$value){
			$db=$this->db;
		    $db->transBegin();
		    $query="delete from $model where $field=?";
		    if($db->query($query,[$value])){
		        $db->transCommit();
		        echo createJsonMessage('status',true,'message','item deleted successfully...','flagAction',true);
		        return true;
		    }
		    else{
		        $db->transRollback();
		        echo createJsonMessage('status',false,'message','cannot delete item(s)...','flagAction',true);
		        return false;
		    }
		}

		public function delete($model,$extra='',$id=''){
			//kindly verify this action before performing it
			$id = ($id == '') ? $extra : $id;
			$extra = ($extra != '' && $id != '') ? base64_decode(urldecode($extra)) : $id;
			// this extra param is a method to find a file and removing it from the server
			if($extra){
				$newModel = loadClass("$model");
				$paramFile = $newModel::$documentField;
				$directoryName = $model.'_path';
				$filePath =  $this->uploadedFolderName.'/'.@$paramFile[$directoryName]['directory'].$extra;
				if(file_exists($filePath)){
					@chmod($filePath, 0777);
					@unlink($filePath);
				}
			}
			$newModel = loadClass("$model");
			//check that model is actually a subclass
			if ( !empty($id) && is_subclass_of($newModel,$this->crudNameSpace )&&$newModel->delete($id)) {
				$desc = "deleting the model $model with id {$id}";
				$this->logAction($this->webSessionManager->getCurrentUserProp('ID'),$model,$desc);
				echo createJsonMessage('status',true,'message','item deleted successfully...','flagAction',true);
				return true;
			}
			else{
				echo createJsonMessage('status',false,'message','cannot delete item...','flagAction',true);
				return false;
			}
		}
		public function multipleDelete($model,$id){
			//kindly verify this action before performing it
			// 1 => success
			// 0 => not exists
			// 2 => failed
			$model = loadClass("$model");
			//check that model is actually a subclass
			if ( !empty($id) && is_subclass_of($model,$this->crudNameSpace )) {
				if(!$model->exists($id)){
					$result = $model->delete($id);
					return 'done'; // 1
				}else{
					return 'exists'; // 0
				}
			}
			else{
				return 'wrong'; // 2
			}
		}

		public function multipleAction($model){
			if($model != ''){
				if(isset($_GET['task'])){
					if($_GET['task'] === 'multipleDelete'){
						try{
							$model = $this->db->escape(trim($model));
							$checkBoxData = json_decode($_GET['checkBoxAction'],true);
							if(empty($checkBoxData)){
								echo createJsonMessage('status',false,'message','You have not chosen any item to perform the operation upon...');
								exit;
							}

							$result = '';
							for($i=0; $i<count($checkBoxData); $i++){
								$id = $this->db->escape($checkBoxData[$i]['checkBoxData']);
								$result .= $this->multipleDelete($model,$id);
							}

							if(strpos($result, 'done') !== false){
								echo createJsonMessage('status',true,'message','item(s) deleted successfully...','flagAction',true);
								return true;
							}else if(strpos($result, 'exists') !== false){
								echo createJsonMessage('status',false,'message','item(s) cannot be deleted...','flagAction',false);
								return false;
							}else{
								echo createJsonMessage('status',false,'message','error occured while performing the operation...','flagAction',false);
							}

						}catch(Exception $e){
							echo createJsonMessage('status',false,'message','error occured while performing the operation...','flagAction',false);
						}
					}
					
				}
				
			}else{
				echo createJsonMessage('status',false,'message','there is no model to perform operation upon...','flagAction',false);
			}
		}

		private function logAction($user,$model,$description){
			$applicationLog = loadClass('application_log');
			$applicationLog->log($user,$model,$description);
		}



	}

?>