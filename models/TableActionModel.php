<?php 
/**
* model to manage table action information
*/
class tableActionModel extends CI_Model
{
	
	function __construct()
	{
		parent::__construct();
	}

		//function to generate the action link needed for the enable and disable field
	public function getEnabled($object,$classname=''){
		$classname = empty($classname)?lcfirst(get_class($object)):ucfirst($classname);
		$link = base_url("ac/disable/$classname");
		$label = "disable";
		if (strtolower($classname)=='customer') {
			$label='Ban';
		}else if(strtolower($classname)=='admin'){
			$label ='Ban';
		}
		$status = is_array($object)?$object['status']:$object->status;
		if (!$status) {
			$link = base_url("ac/enable/$classname");
			$label = "enable";
			if (strtolower($classname)=='customer') {
				$label='UnBan';
			}else if(strtolower($classname)=='admin'){
				$label ='UnBan';
			}
		}
		return $this->buildActionArray($label,$link,1,1);
	}

		//function to return the array need
	protected function buildActionArray($label,$link,$critical,$ajax){
		$result = array();
		$result['label']=$label;
		$result['link']=$link;
		$result['isCritical']=$critical;
		$result['ajax']=$ajax;
		return $result;
	}
}
 ?>