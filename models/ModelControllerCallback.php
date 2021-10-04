<?php 
	/**
	* This class contains  the method for performing extra action performed
	*/
	class ModelControllerCallback extends CI_Model
	{
		
		function __construct()
		{
			parent::__construct();
			$this->load->model('webSessionManager');
			$this->load->helper('string');
			$this->load->library('hash_created');
			$this->load->model('mailer');
		}

		public function onAdminInserted($data,$type,&$db,&$message)
		{
			//remember to remove the file if an error occured here
			//the user type should be admin
			loadClass($this->load,'user');
			if ($type=='insert') {
				// login details as follow: username = email, password = firstname(in lowercase)
				$password = $this->hash_created->encode_password(strtolower($data['firstname']));
				$param = array('user_type'=>'admin','username'=>$data['email'],'password'=>$password,'user_table_id'=>$data['LAST_INSERT_ID']);
				$std = new User($param);
				if ($std->insert($db,$message)) {
					return true;
				}
				return false;
			}
			return true;
		}
			
	}

 ?>