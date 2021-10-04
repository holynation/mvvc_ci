<?php 

/**
* The controller that validate forms that should be inserted into a table based on the request url.
each method wil have the structure validate[modelname]Data
*/
class ModelSuspendCallback extends CI_Model
{
	
	function __construct()
	{
		parent::__construct();
		$this->load->model('webSessionManager');
	}

	public function onAppointmentSuspend($data,$type,&$db,&$message)
	{
		// echo "got here";exit;
		// $this->db->trans_commit();
		// // redirect('/vc/admin/diagnose/','refresh');
		return true
		
	}
}
 ?>