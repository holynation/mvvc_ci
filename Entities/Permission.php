<?php
	
namespace App\Entities;

use App\Models\Crud;

	/**
		* This class  is automatically generated based on the structure of the table. And it represent the model of the permission table.
		*/
class Permission extends Crud
{
	protected static $tablename='Permission';
	/* this array contains the field that can be null*/
	static $nullArray=array('path' ,'permission' );
	static $compositePrimaryKey=[];
	static $uploadDependency = [];
	/*this array contains the fields that are unique*/
	static $uniqueArray=[];
	/*this is an associative array containing the fieldname and the type of the field*/
	static $typeArray = array('role_id'=>'int','path'=>'varchar','permission'=>'enum');
	/*this is a dictionary that map a field name with the label name that will be shown in a form*/
	static $labelArray=array('ID'=>'','role_id'=>'','path'=>'','permission'=>'');
	/*associative array of fields that have default value*/
	static $defaultArray = [];
	//populate this array with fields that are meant to be displayed as document in the format array('fieldname'=>array('filetype','maxsize',foldertosave','preservefilename'))
	//the folder to save must represent a path from the basepath. it should be a relative path,preserve filename will be either true or false. when true,the file will be uploaded with it default filename else the system will pick the current user id in the session as the name of the file.
	static $documentField = [];//array containing an associative array of field that should be regareded as document field. it will contain the setting for max size and data type.
			
	static $relation=array('role'=>array( 'role_id', 'ID')
	);
	static $tableAction=array('delete'=>'delete/permission','edit'=>'edit/permission');
	function __construct($array=array())
	{
		parent::__construct($array);
	}
	 function getRole_idFormField($value=''){
	$fk=null;//change the value of this variable to array('table'=>'role','display'=>'role_name'); if you want to preload the value from the database where the display key is the name of the field to use for display in the table.

	if (is_null($fk)) {
		return $result="<input type='hidden' value='$value' name='role_id' id='role_id' class='form-control' />
			";
	}
	if (is_array($fk)) {
		$result ="<div class='form-group'>
		<label for='role_id'>Role Id</label>";
		$option = $this->loadOption($fk,$value);
		//load the value from the given table given the name of the table to load and the display field
		$result.="<select name='role_id' id='role_id' class='form-control'>
			$option
		</select>";
	}
	$result.="</div>";
	return  $result;

}
function getPathFormField($value=''){
	return "<div class='form-group'>
	<label for='path' >Path</label>
		<input type='text' name='path' id='path' value='$value' class='form-control'  />
</div> ";

}
function getPermissionFormField($value=''){
	return "<div class='form-group'>
	<label for='permission' >Permission</label><select name='permission' id='permission' value='$value' class='form-control' >
	<option>..choose..</option><option> r </option><option> w </option>
</select>
</div> ";

}

		
protected function getRole(){
	$query ='SELECT * FROM role WHERE id=?';
	if (!isset($this->array['ID'])) {
		return null;
	}
	$id = $this->array['ID'];
	$db = db_connect();
	$result = $db->query($query,[$id]);
	$result =$result->getResultArray();
	if (empty($result)) {
		return false;
	}
	$role = 'App\Entities\Role';
	$resultObject = new $role($result[0]);
	return $resultObject;
}


}
?>