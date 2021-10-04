<?php 
/**
* This is the class that manages all information and data retrieval needed by the student section of this application.
*/
class CustomerData extends CI_Model
{
	private $customer;
	function __construct()
	{
		parent::__construct();
	}

	public function setCustomer($customer)
	{
		$this->customer=$customer;
	}


	public function loadDashboardInfo()
	{
		#get the iformatin for 
		$result = array();
		loadClass($this->load,'customer');
		loadClass($this->load,'admin');
		loadClass($this->load,'cashback');
		loadClass($this->load,'payment');
		$result['countData']=array('totalTanx'=>$this->customer->totalTransaction(),'totalAmountPaid'=>$this->customer->totalAmountPaid());
		//load the data needed to display graphical information
		$result['eventDistribution']=$this->customer->getEventDistributionByPayment();
		return $result;
	}

	public function checkMyNumber($phone_number){
        loadClass($this->load, 'cashback');
        $cashback = new Cashback();
        $result = $cashback->checkMyLuckyNumber($phone_number);
        // print_r($result);exit;
        return $result;
    }

    public function loadCustomerTransaction(){
    	$result = array();
    	$customer_id = $this->customer->ID;
    	$query = "SELECT pay.ID, customer_name,cashback.amount as cashback_amount,pay.amount as deducted_amount,time as time_chosen,transaction_type,ref,pay_ref from cashback join payment pay on pay.cashback_id = cashback.id where customer_id = ? order by pay.date_created desc";
    	$temp = $this->db->query($query,array($customer_id));
    	if($temp->num_rows() <= 0){
    		return $result;
    	}
    	$result['customerTranx'] = $temp->result_array();
    	return $result;
    }


}
