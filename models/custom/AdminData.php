<?php 
/**
* This is the class that manages all information and data retrieval needed by the admin section of this application.
*/
class AdminData extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}

	public function loadDashboardData()
	{
		//check the permmission first
		$result = array();
		loadClass($this->load,'customer');
		loadClass($this->load,'admin');
		loadClass($this->load,'cashback');
		loadClass($this->load,'payment');
		$result['countData']=array('customer'=>$this->customer->totalCount("where status = '1'"),'admin'=>$this->admin->totalCount(),'cashback_activate'=>$this->cashback->totalCount("where payment_status = '1'"),'cashback_not_acitvate'=>$this->cashback->totalCount("where payment_status = '0'"),'payment'=>$this->payment->totalAmount(),'cash_profile'=>$this->cashback->totalCount("where cashback_type = 'profile'"),'cash_phone'=>$this->cashback->totalCount("where cashback_type = 'phone_number'"));
		//load the data needed to display graphical information
		$result['eventDistribution']=$this->payment->getEventDistributionByPayment();
		$result['cashbackDistribution']=$this->cashback->getCashbackDistribution();
		$result['cashbackTranxDistribution']=$this->cashback->getCashbackTranxDistribution();
		// print_r($result);exit;
		return $result;
	}

	public function getDailyWinner(){
		loadClass($this->load, 'cashback');
        $cashback = new Cashback();
        $result = $cashback->checkMyLuckyNumber();
        // print_r($result);exit;
        return $result;
	}

	public function getAdminSidebar($combine = false)
	{
		loadClass($this->load,'role');
		$role = new Role();
		// using $combine parameter to take into consideration path that're not captured in the admin sidebar
		$output = ($combine) ? array_merge($role->getModules(),$role->getExtraModules()) : $role->getModules();
		return $output;
	}
	public function getCanViewPages($role,$merge=false)
	{
		$result =array();
		$allPages =$this->getAdminSidebar($merge);
		$permissions = $role->getPermissionArray();
		foreach ($allPages as $module => $pages) {
			$has = $this->hasModule($permissions,$pages,$inter);
			$allowedModule =$this->getAllowedModules($inter,$pages['children']);
			$allPages[$module]['children']=$allowedModule;
			$allPages[$module]['state']=$has;
		}
		return $allPages;
	}

	private function getAllowedModules($includesPermission,$children)
	{
		$result = $children;
		$result=array();
		foreach($children as $key=>$child){
			if(is_array($child)){
				foreach($child as $childKey => $childValue){
					if (in_array($childValue, $includesPermission)) {
						$result[$key]=$child;
					}
				}
			}else{
				if (in_array($child, $includesPermission)) {
					$result[$key]=$child;
				}
			}
			
		}
		return $result;
	}

	private function hasModule($permission,$module,&$res)
	{
		if(is_array(array_values($module['children']))){
			$res =array_intersect(array_keys($permission), array_values_recursive($module['children']));
		}else{
			$res =array_intersect(array_keys($permission), array_values($module['children']));
		}
		
		if (count($res)==count($module['children'])) {
			return 2;
		}
		if (count($res)==0) {
			return 0;
		}
		else{
			return 1;
		}

	}

}

 ?>