<?php
/**
* This class coontrols the user access to permission
*/
namespace App\Models;

use CodeIgniter\Model;
use App\Models\WebSessionManager;
use App\Entities\Permission;

class AccessControl extends Model
{
	protected $db;
	private $webSessionManager;
	function __construct()
	{
		$this->webSessionManager = new WebSessionManager;
		$this->db = db_connect();
	}
	//this function is equivalent to asking for a table access
	//the first parameter is the name of the mmodel that is to be access, that is the name of the table
	// the second paratmetr is the operation needed to be perfored it could be r, d, c or u . it must be a single character
	public function moduleAccess($modulename,$operation){//the operation must be a character string
		//get the user id and check if the user has the permission for the listed operation
		//check that theoperation is hust one sngle character.
		$len = strlen($operation);
		if ($len> 4) {
			throw new \Exception("invalid operation argument detected.");
		}
		$roleid = $this->webSessionManager->getCurrentUserProp('role_ID');
		$permission = loadClass('permission');
		$result = $permission->getWhere(array('role_ID'=>$roleid,'module_name'=>$modulename));//this should return one result
		$perm = $result[0]->privileges;
		$pos = strpos($perm, $operation);
		if ($pos== -1) {
			return false;
		}
		return true;
	}

	//this funcntion create all the default roles used by the system
	public function createDefaultRoles(){
		$studentPermissionList = [];
		$lecturerPermissionList = [];
		$adminPermissionList = [];
		$superuserPermissionList = [];
		$this->db->transBegin();
		if(!$this->createRole('student','assigned to all student users on the system',$studentPermissionList)){
			$this->db->transRollback();
			throw new \Exception("Error occured while creating default role");

		}//add the permission list
		if(!$this->createRole('lecturer','assigned to all lecturer users on the system',$lecturerPermissionList)){
			$this->db->transRollback();
			throw new \Exception("Error occured while creating default role");
		}//add the permission list
		if($this->createRole('superuser','assigned to system superuser',$superuserPermissionList)){
			$this->db->transRollback();
			throw new \Exception("Error occured while creating default role");
		}//add the permission list
		if($this->createRole('admin','assigned to all admin users on the system',$adminPermissionList)){
			$this->db->transRollback();
			throw new \Exception("Error occured while creating default role");
		}//add the permission list
		if($this->createRole('staff','assigned to all admin users on the system',$adminPermissionList)){
			$this->db->transRollback();
			throw new \Exception("Error occured while creating default role");
		}//add the permission list
		$this->db->transCommit();
		return true;
	}

	public function createRole($rolename,$description,$permissionList=null,$isDefault=0,$autoCommit=true){
		$this->db->transBegin();//start transaction
		$data=array('role_name'=>$rolename,'description'=>$description);

		//check if the role already exist and skip
		$role = loadClass("role");
		if ($role->exists()) {
			return true;
		}
		$data['isDefault']=$isDefault;
		$role->setArray($data);
		if($role->insert($this->db)){
			if (is_null($permissionList)) {
				if($autoCommit){
					$this->db->transCommit();
				}
				return true;
			}
			//incase the permission list is specifieed. The permission list must be in the format modulename=>crud or modulename=>a for all, each letter represent a permission
			$roleid = $this->getLastInsertId();
			foreach ($permissionList as $modulename => $permission) {
				if(!$this->addPermission($roleid,$modulename,$permission)){
					$this->db->transRollback();
					return false;
				}
			}
			if($autoCommit){
				$this->db->transCommit();
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
		$permission = loadClass("permission");
		$res = $permission->getWhere($data,$this->db);
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
		$permission->setArray($data);
		return $permission->insert($this->db);
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
