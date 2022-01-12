<?php 

/**
 * this will get different entity details that can be use inside api
 */
namespace App\Models;

use CodeIgniter\Model;

class EntityDetails extends Model
{
	
	function __construct()
	{

	}

	public function getCustomer_orderDetails($id)
	{
		//get the order history for the tracking purpose
		if ($this->webSessionManager->getCurrentUserProp('ID')) {
			$result = $this->getCustomerOrderDetailsAdmin($id);
			return $result;
		}

		loadClass($this->load,'customer_order');
		$result = $this->customer_order->getDetailWithHistory($id);
		return $result;
	}
}
?>