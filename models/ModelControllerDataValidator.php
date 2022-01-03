<?php 

/**
* The controller that validate forms that should be inserted into a table based on the request url.
each method wil have the structure validate[modelname]Data
*/
namespace App\Models;

use CodeIgniter\Model;
use App\Models\WebSessionManager;
class ModelControllerDataValidator extends Model
{
	
	function __construct()
	{
		$this->webSessionManager = new WebSessionManager;
	}

	// public function validate{Modelname}Data($data,$type,&$message)
	// {
			// return true;
	// }

}
 ?>