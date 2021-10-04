<?php
	/**
	* model for loading extra data needed by pages through ajax
	*/
	class AjaxData extends CI_Controller
	{

		function __construct()
		{
			parent::__construct();
			$this->load->model("modelFormBuilder");
			$this->load->database();
			$this->load->model('webSessionManager');
			// $this->load->model('entities/application_log');
			$this->load->helper('string');
			$this->load->helper('array');
			$this->load->helper('date');
			if (!$this->webSessionManager->isSessionActive()) {
				echo "session expired please re login to continue";
				exit;
			}
			$exclude=array('changePassword','savePermission','approve','disapprove');
			$page = $this->getMethod($segments);
			if ($this->webSessionManager->getCurrentUserProp('user_type')=='admin' && in_array($page, $exclude)) {
				loadClass($this->load,'role');
				$this->role->checkWritePermission();
			}
		}

		private function getMethod(&$allSegment)
		{
			$path = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			$base = base_url();
			$left = ltrim($path,$base);
			$result = explode('/', $left);
			$allSegment=$result;
			return $result[0];
		}

		private function returnJSONTransformArray($query,$data=array(),$valMessage='',$errMessage=''){
			$newResult=array();
			$result = $this->db->query($query,$data);
			if($result->num_rows() > 0){
				$result = $result->result_array();
				if($valMessage != ''){
					$result[0]['value'] = $valMessage;
				}
				return json_encode($result[0]);
			}else{
				if($errMessage != ''){
					$dataParam = array('value' => $errMessage);
					return json_encode($dataParam);
				}
				return json_encode(array());
			}
		}

		private function returnJSONFromNonAssocArray($array){
			//process if into id and value then
			$result =array();
			for ($i=0; $i < count($array); $i++) {
				$current =$array[$i];
				$result[]=array('id'=>$current,'value'=>$current);
			}
			return json_encode($result);
		}

		protected function returnJsonFromQueryResult($query,$data=array(),$valMessage='',$errMessage=''){
			$result = $this->db->query($query,$data);
			if ($result->num_rows() > 0) {
				$result = $result->result_array();
				if($valMessage != ''){
					$result[0]['value'] = $valMessage;
				}
				// print_r($result);exit;
				return json_encode($result);
			}
			else{
				if($errMessage != ''){
					$dataParam = array('value' => $errMessage);
					return json_encode($dataParam);
				}
				return "";
			}
		}

		public function savePermission()
		{
			
			if (isset($_POST['role'])) {
				$role = $_POST['role'];
				if (!$role) {
					echo createJsonMessage('status',false,'message','error occured while saving permission','flagAction',false);
				}
				loadClass($this->load,'role');
				try {
					$removeList = json_decode($_POST['remove'],true);
					$updateList = json_decode($_POST['update'],true);
					$this->role->ID=$role;
					$result=$this->role->processPermission($updateList,$removeList);
					echo createJsonMessage('status',$result,'message','permission updated successfully','flagAction',true);
				} catch (Exception $e) {
					echo createJsonMessage('status',false,'message','error occured while saving permission','flagAction',false);
				}
				
			}
		}
		
	}
 ?>
