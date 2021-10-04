<?php 

/**
* The controller that validate forms that should be inserted into a table based on the request url.
each method wil have the structure validate[modelname]Data
*/
class ModelControllerDataValidator extends CI_Model
{
	
	function __construct()
	{
		parent::__construct();
		$this->load->model('webSessionManager');
	}

	public function validateInvestment_pay_trackData(&$data,$type,&$message){
		if($type == 'insert'){
			if(!$data['investment_id']){
				$message="investment type can't be empty";
				return false;
			}
			$query = "select investment.id,investment.amount,rate,pay_times,investment.date_invested,ip.due_date from investment join investment_packages ip on ip.id = investment.investment_packages_id where customer_id = ? and investment.id = ?";
			$result = $this->db->query($query, array($data['customer_id'],$data['investment_id']));
			$result = $result->row();
			if (!$result) {
				$message='invalid parameter';
				return false;
			}
			$paidTimes = $this->isPaymentValid($data['investment_id']);
			$dueTimes = $result->pay_times;
			if($paidTimes >= $dueTimes){
				$message = "The payment days seems to have reached it completion";
				return false;
			}
			$data['amount_paid'] = str_replace(",", '', $data['amount_paid']);
			$startDate = $result->date_invested;
			$dueDate = $result->due_date;
			$paidAmount = (int)$data['amount_paid'];
			$investedAmount = $result->amount;
			$rate = $result->rate;
			$expectedAmount = (int)($investedAmount * $rate);
			if($paidAmount != $expectedAmount){
				$message = "The expected amount should be ".number_format($expectedAmount);
				return false;
			}
			$data['due_date'] = $this->calcNextDatePay($startDate,$dueDate,$paidTimes);
		}
		return true;
	}
	public function validateCustomerData(&$data,$type,&$message){
		if($type == 'insert'){
			if($data['branch_id']) {
				$data['branch_id'] = ($this->webSessionManager->getCurrentUserProp('branch_id')) ? $this->webSessionManager->getCurrentUserProp('branch_id') : 1;
				$amount = str_replace(",", '', $data['amount']);
				if($amount <= 45000){
					$message = "This amount seems not right, ".number_format($amount)."please double check the amount";
					return false;
				}
				$data['amount'] = $amount;
				$data['customer_no'] = $this->generateNumber();
			}
		}
		return true;
	}

	public function validateInvestmentData(&$data,$type,&$message){
		if($type == 'insert'){
			if($data['amount']) {
				$data['amount'] = str_replace(",", '', $data['amount']);
			}
		}
		return true;
	}

	private function isPaymentValid($investment_id){
		$query = "select count(investment_id) as total from investment_pay_track where investment_id = ?";
		$result = $this->db->query($query, array($investment_id));
		$result = $result->result_array();
		return $result[0]['total'];
	}

	private function calcNextDatePay($start_date,$no_days,$pay_times){
		// this $countTrack worked because $pay_times would be in an incrementing order of +1
		$countTrack = 0;
		if($pay_times == 0){
			$countTrack = 1;
		}else{
			$countTrack =  ($pay_times + 2) - 1;
		}
		$startDate = formatToDateOnly($start_date);
		$numOFDays = (string)"P".($no_days * $countTrack)."D";
        $interval = new DateInterval($numOFDays); // interval number day
        $date = new DateTime($startDate);
        $nextDate = $date->add($interval);
        return $nextDate->format('Y-m-d');
	}

	private function generateNumber()
	{
		$orderStart='1000000011';
		$query="select max(customer_no) as order_number from customer";
		$result = $this->db->query($query)->result_array();
		$temp = $result[0]['order_number'];
		if ($orderStart > $temp) {
			return $orderStart;
		}
		return $temp+1;
	}

}
 ?>