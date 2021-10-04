<?php
/**
*
*/
class WebSessionManager extends CI_Model
{
   private $defaultType = array("admin","lecturer");
	// private $defaultRole = array('applicant','student','lecturer','staff');
	function __construct()
	{
		parent::__construct();
		// $this->load->library('session');
		$this->load->model('crud');
      $this->load->helper('string');
	}
	/**
	 * This functio save the current user into the session
	 * @param  Crud    $user        [The user object needed to be saved in the session]
	 * @param  boolean $saveAllInfo [specify to save the user category data that the user belongs to]
	 * @return void               void
	 */
	public function saveCurrentUser(Crud $user,$saveAllInfo=false){
      $userType = $user->user_type;
      $uid = $user->user_table_id;
      $moreInfo = array();
       // echo $userType; exit;
      loadClass($this->load,$userType);

      $moreInfo = $this->$userType->getWhere(array('id'=>$uid,'status'=>1),$c,0,null,false);
      if (!$moreInfo) {
         echo "sorry an unexpected error occured";exit;
      }
      $moreInfo = $moreInfo[0];
      $userArray = $moreInfo->toArray();
      $temp =$user->toArray();
      // $new_temp = $this->switchType($temp);
      // print_r($new_temp);exit;
      // unset($temp['ID']);
      $all = array_merge($userArray,$temp);
      $this->session->set_userdata($all);
	}

   private function switchType($userArray){
      $userTypeList = $this->defaultType;
      $old_user = $userArray;
      $new_user = array();
      $name = appBuildName('userType');
      $admin = $name . "_admin";
      $lecturer = $name . "_lecturer";
      foreach($old_user as $key => $value){
         if($value == $userTypeList[0]){
            $value = $admin;
         }else if($value == $userTypeList[1]){
            $value = $lecturer;
         }
         $new_user[$key] = $value;
      }
      return $new_user;
   } 

	public function getCurrentUserDefaultRole(){
		$rolename = $this->getCurrentUserProp('usertype');
		if ($rolename==false) {
			redirect(base_url().'auth/logout');
		}
		return in_array($rolename, $this->defaultRole)?$rolename:'admin';
	}
	public function getCurrentUser(&$more){
		$userType = $this->session->userdata('usertype');
		$user = $this->loadObjectFromSession('User');
		$len = func_num_args();
		if ($len == 1) {
			$more = $this->loadObjectFromSession(ucfirst($userType));
		}
		return $user;
	}
	private function loadObjectFromSession($classname){
		$this->load->model(lcfirst($classname));
		$field = array_keys($classname::$fieldLabel);
		for ($i=0; $i < count($field); $i++) {
			$temp =$this->session->userdata($field[$i]);
			if (!$temp) {
				continue;
			}
			$array[]= $temp;
		}
		return new $classname($array);//return the object for some process
	}
	public function logout(){
		//just clear the session
		$this->session->sess_destroy();
	}
	/**
	 * get the user property saved in the session
	 * @param  [string] $propname [the property to get from the session]
	 * @return [mixed]           [the value saved in the session with the key or empty string if the item is not present in the database]
	 */
	public function getCurrentUserProp($propname){
		return $this->session->userdata($propname);
   }
	/**
	 * checks if the session is active or not
	 * @return boolean [true if the session is active or false otherwise]
	 */
	public function isSessionActive(){
		$userid = $this->session->userdata('ID');
		if (!empty($userid)) {
			return true;
		}
		else{
			return false;
		}
	}

	public function getFlashMessage($name){
		return $this->session->flashdata($name);
	}

	public function setFlashMessage($name,$value){
		$this->session->set_flashData($name,$value);
	}

	public function isApplicantSessionActive(){
		$userid = $this->getCurrentUserProp('ID');
		$application = $this->getCurrentUserProp('admission_Application_ID');
		if (!(empty($userid) || empty($application))) {
			return true;
		}
		else{
			return false;
		}
	}

//this function is used to set content on the session. This is delegating to the default session function on codeigniter
   	public function setContent($name,$value){
   		$this->session->set_userdata($name,$value);
   	}
   	function setArrayContent($array){
   		$this->session->set_userdata($array);
   	}
   	private function loadClass($classname){
   		if (!class_exists(ucfirst($classname))) {
   			$this->load->model("entities/$classname");
   		}
   	}
      public function unsetContent($name){
         $this->session->unset_userdata($name);
      }

   	// this set of function check the type of user that is currently logged in
   	function isCurrentUserType($userType,$userId=''){
         $temp=$userType==$this->getCurrentUserProp('user_type');
         if (!$temp) {
            return false;
         }
         $st='';
         if($userId != ''){
            $st = $userId;
         }else{
            $st= $this->getCurrentUserProp('user_table_id');
         }
         
         loadClass($this->load,$userType);
         $className = ucfirst($userType);
         $result = new $className(array('ID'=>$st));
         $result->load();
         return $result;
   	}

      function getNameInitial($userId=''){
         $user='';
         if($userId != ''){
            $user = $this->getCurrentLecturer($userId);
         }else{
            $user = $this->isCurrentUserType('lecturer');
         }

         $surname = ucfirst($user->surname);
         $firstname = ucfirst(getFirstString($user->firstname));
         $middlename = ucfirst(getFirstString($user->middlename));
         return $surname .", ".$firstname .". ".$middlename.".";
      }

   	function getUserDisplayName(){
         return $this->getCurrentUserProp('firstname').' '.$this->getCurrentUserProp('lastname');
   	}

	//function to get user type object, that is get lecturer, student etc,admin etc
   function checkActualUserData(){
      $user_id = $this->getCurrentUserProp('ID');
      if(empty($user_id)){
         return false;
      }
      $query = "select ID,username,user_type,user_table_id from user where ID = ?";
      $query = $this->db->query($query, array($user_id));
      $result = $query->row();
      if($query->num_rows() > 0){
         return true;
      }
      return false;
   }

   function getAllData(){
      return $this->session->all_userdata();
   }
}

 ?>
