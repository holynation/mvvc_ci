<?php 
	/**
	* This is the class that contain the method that will be called whenever any data is inserted for a particular table.
	* the url path should be linked to this page so that the correct operation is performed ultimately. T
	*/
	namespace App\Models;

	use CodeIgniter\Model;
	use App\Models\WebSessionManager;

	class ModelControllerCallback extends Model
	{
		
	function __construct()
	{
		$this->webSessionManager = new WebSessionManager;
		$this->db = db_connect();
		helper('string');
	}

	// public function on{Modelname}Inserted($data,$type,$db,&$message)
	// {
	// 	return true;
	// }
		
	}
 ?>