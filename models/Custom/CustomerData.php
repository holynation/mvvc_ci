<?php 
/**
* This is the class that manages all information and data retrieval needed by the student section of this application.
*/
namespace App\Models\Custom;

use CodeIgniter\Model;
use App\Models\WebSessionManager;
use App\Models\Mailer;
use CodeIgniter\HTTP\RequestInterface;

class CustomerData extends Model
{
	private $customer;
	private $mailer;
	private $webSessionManager;
	protected $db;
	protected $request;

	function __construct(RequestInterface $request=null)
	{
		helper(['string','array']);
		$this->db = db_connect();
		$this->request = $request;
		$this->webSessionManager = new WebSessionManager;
		$this->mailer = new Mailer;
	}

	public function setCustomer($customer)
	{
		$this->customer=$customer;
	}


	public function loadDashboardInfo()
	{
		#get the iformatin for 
		$result = array();
		$admin = loadClass('admin');

		// $result['countData']=array('totalTanx'=>$this->customer->totalTransaction(),'totalAmountPaid'=>$this->customer->totalAmountPaid());
		//load the data needed to display graphical information
		// $result['eventDistribution']=$this->customer->getEventDistributionByPayment();
		return $result;
	}

}
