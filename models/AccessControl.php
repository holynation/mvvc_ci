<?php
/**
* This class coontrols the user access to permission
*/
class AccessControl extends CI_Model
{

	function __construct()
	{
		parent::__construct();
		$this->load->model('entities/role');
		$this->load->model('entities/permission');
		$this->load->model('webSessionManager');
	}
	//this function is equivalent to asking for a table access
	//the first parameter is the name of the mmodel that is to be access, that is the name of the table
	// the second paratmetr is the operation needed to be perfored it could be r, d, c or u . it must be a single character
	public function moduleAccess($modulename,$operation){//the operation must be a character string
		//get the user id and check if the user has the permission for the listed operation
		//check that theoperation is hust one sngle character.
		$len = strlen($operation);
		if ($len> 4) {
			throw new Exception("invalid operation argument detected.");
		}
		$roleid = $this->webSessionManager->getCurrentUserProp('role_ID');
		$result = $this->permission->getWhere(array('role_ID'=>$roleid,'module_name'=>$modulename));//this should return one result
		$perm = $result[0]->privileges;
		$pos = strpos($perm, $operation);
		if ($pos== -1) {
			return false;
		}
		return true;
	}
	// public function getCurrentUserRole(){
	// 	$userid = $this->userdata('userid');
	// 	return $this->role->getWhere(array('user_ID'=>$userid));
	// }
	//this funcntion create all the default roles used by the system
	public function createDefaultRoles(){
		$studentPermissionList = array();
		$lecturerPermissionList =array();
		$adminPermissionList = array();
		$superuserPermissionList = array();
		$this->db->trans_start();
		if(!$this->createRole('student','assigned to all student users on the system',$studentPermissionList)){
			$this->db->trans_rollback();
			throw new Exception("Error occured while creating default role");

		}//add the permission list
		if(!$this->createRole('lecturer','assigned to all lecturer users on the system',$lecturerPermissionList)){
			$this->db->trans_rollback();
			throw new Exception("Error occured while creating default role");
		}//add the permission list
		if($this->createRole('superuser','assigned to system superuser',$superuserPermissionList)){
			$this->db->trans_rollback();
			throw new Exception("Error occured while creating default role");
		}//add the permission list
		if($this->createRole('admin','assigned to all admin users on the system',$adminPermissionList)){
			$this->db->trans_rollback();
			throw new Exception("Error occured while creating default role");
		}//add the permission list
		if($this->createRole('staff','assigned to all admin users on the system',$adminPermissionList)){
			$this->db->trans_rollback();
			throw new Exception("Error occured while creating default role");
		}//add the permission list
		$this->db->trans_commit();
		return true;
	}

	public function createRole($rolename,$description,$permissionList=null,$isDefault=0,$autoCommit=true){
		$this->db->trans_start();//start transaction
		$data=array('role_name'=>$rolename,'description'=>$description);

		//check if the role already exist and skip
		if ($this->role->exists()) {
			return true;
		}
		$data['isDefault']=$isDefault;
		$this->role->setArray($data);
		if($this->role->insert($this->db)){
			if (is_null($permissionList)) {
				if($autoCommit){
					$this->db->trans_commit();
				}
				return true;
			}
			//incase the permission list is specifieed. The permission list must be in the format modulename=>crud or modulename=>a for all, each letter represent a permission
			$roleid = $this->getLastInsertId();
			foreach ($permissionList as $modulename => $permission) {
				if(!$this->addPermission($roleid,$modulename,$permission)){
					$this->db->trans_rollback();
					return false;
				}
			}
			if($autoCommit){
				$this->db->trans_commit();
			}
			return true;
		}
		else{
			return false;
		}
	}
	public function addPermission($roleid,$modulename,$permission,$description=''){
		//check that the role name already exists before adding permission
		$permission=$this->sortString($permission);
		if (!$this->validatePermissionString($permission)) {
			return false;
		}
		$data = array('role_ID' =>$roleid ,'modulename'=>$module,'privileges'=>$permission);
		$res = $this->permission->getWhere($data,$this->db);
		if ($res) {
			$perm = $res[0]->privileges;
			if (strpos($perm, $permission)!=-1) {
				return true;
			}
			$newPerm =$this->sortString($perm.$permission);
			if ($this->validatePermissionString($newPerm)) {
				$data['permission'] =$newPerm;
			}
			else{
				return false;
			}

		}
		$data['description']=$description;
		// print_r($data);exit;
		$this->permission->setArray($data);
		return $this->permission->insert($this->db);
	}
	private function validatePermissionString($permission){
		$compareString = 'cdru';
		if (empty($permission)) {
			return false;
		}
		$len = 4;//the maximum length
		if (strlen($permission) > 4) {
			return false;
		}
		//the string must be sorted already
		$index = strpos($compareString, $permission);
		if ($index==-1) {
			return false;
		}
		return true;
		//crud//cdru
	}
	private function sortString($string){
		$split = str_split($string);
		$split = sort($split);
		return implode('', $split);
	}
	private function getLastInsertId(){
		return getLastInsertId();
	}
}
 ?>
