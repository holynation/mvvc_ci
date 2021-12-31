<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Models\Crud;

class WebSessionManager extends Model
{
   private $defaultType = array("admin","lecturer");
   private $session;
	function __construct()
	{
		parent::__construct();
		$this->session = session();
      helper('string');
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
      $moreInfo = [];
      $userType = loadClass("$userType");

      $moreInfo = $userType->getWhere(array('id'=>$uid,'status'=>1),$c,0,null,false);
      if (!$moreInfo) {
         echo "sorry an unexpected error occured";exit;
      }
      $moreInfo = $moreInfo[0];
      $userArray = $moreInfo->toArray();
      $temp =$user->toArray();
      $all = array_merge($userArray,$temp);
      $this->session->set($all);
	}

	public function getCurrentUserDefaultRole(){
		$rolename = $this->getCurrentUserProp('usertype');
		if ($rolename==false) {
			redirect(base_url().'auth/logout');
		}
		return in_array($rolename, $this->defaultType)?$rolename:'admin';
	}
	public function getCurrentUser(&$more){
		$userType = $this->session->get('usertype');
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
			$temp =$this->session->get($field[$i]);
			if (!$temp) {
				continue;
			}
			$array[]= $temp;
		}
		return new $classname($array);//return the object for some process
	}
	public function logout(){
		//just clear the session
		$this->session->destroy();
	}
	/**
	 * get the user property saved in the session
	 * @param  [string] $propname [the property to get from the session]
	 * @return [mixed]           [the value saved in the session with the key or empty string if the item is not present in the database]
	 */
	public function getCurrentUserProp($propname){
		return $this->session->get($propname);
   }
	/**
	 * checks if the session is active or not
	 * @return boolean [true if the session is active or false otherwise]
	 */
	public function isSessionActive(){
		$userid = $this->session->get('ID');
		if (!empty($userid)) {
			return true;
		}
		else{
			return false;
		}
	}

	public function getFlashMessage($name){
		return $this->session->getFlashdata($name);
	}

	public function setFlashMessage($name,$value){
		$this->session->setFlashdata($name,$value);
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
		$this->session->set($name,$value);
	}
	function setArrayContent($array){
		$this->session->set($array);
	}
   public function unsetContent($name){
      $this->session->remove($name);
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
      
      $userType = loadClass($userType);
      $result = new $userType(array('ID'=>$st));
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

   function getAllData(){
      return $this->session->get();
   }
}

 ?>
