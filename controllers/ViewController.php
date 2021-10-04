<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ViewController extends CI_Controller{

//field definition section
  private $needId= array();

  private $needMethod=array();
  private $errorMessage; // the error message currently produced from this cal if it is set, it can be used to produce relevant error to the user.
  private $access = array();
  private $appData;

  function __construct(){
		parent::__construct();
		$this->load->model("modelFormBuilder");
		// $this->load->model("tableViewModel");
    $this->load->model("tableWithHeaderModel");
    $this->load->helper('string');
    $this->load->helper('array');
    $this->load->model('webSessionManager');
    $this->load->model('queryHtmlTableModel');
    $this->load->model('queryHtmlTableObjModel');
    $this->load->library('hash_created');
    $this->load->model('custom/CashbackData','cashModel');
    if (!$this->webSessionManager->isSessionActive()) {
      header("Location:".base_url());exit;
    }
	}

// bootstrapping functions 
public function view($model,$page='index',$third='',$fourth=''){
  if ( !(file_exists("application/views/$model/") && file_exists("application/views/$model/$page".'.php')))
  {
    show_404();
  }
  // this number is the default arg that ID is the last arg i.e 3 = id
  $defaultArgNum =4; 
  $tempTitle = removeUnderscore($model);
  $title = $page=='index'?$tempTitle:ucfirst($page)." $tempTitle";
  $modelID = ($fourth == '') ? $third : $fourth;
  $data['id'] = urldecode($modelID);
  $data['extraParam'] = ($fourth != '') ? $third : "";
  if (func_num_args() > $defaultArgNum) {
    $args = func_get_args();
    $this->loadExtraArgs($data,$args,$defaultArgNum);
  }
  $exceptions = array();//pages that does not need active session
  if (!in_array($page, $exceptions)) {
    if (!$this->webSessionManager->isSessionActive()) {
      redirect(base_url());exit;
    }
  }

  if (method_exists($this, $model)) {
    $this->$model($page,$data);
  }
  $methodName = $model.ucfirst($page);

  if (method_exists($this, $model.ucfirst($page))) {
    $this->$methodName($data);
  }

  $data['model'] = $page;
  $data['message']=$this->session->flashdata('message');
  sendPageCookie($model,$page);

  return $this->load->view("$model/$page", $data);
}

private function admin($page,&$data)
{
  $role_id=$this->webSessionManager->getCurrentUserProp('role_id');
  if (!$role_id) {
    show_404();
  }

  $this->load->model('custom/adminData');
  $role=false;
  if ($this->webSessionManager->getCurrentUserProp('user_type')=='admin') {
    loadClass($this->load,'admin');
    $this->admin->ID = $this->webSessionManager->getCurrentUserProp('user_table_id');
    $this->admin->load();
    $data['admin']=$this->admin;
    $role = $this->admin->role;
  }
  $data['currentRole']=$role;
  if (!$role) {
    show_404();exit;
  }
  $path ='vc/admin/'.$page;

  // this approach is use so as to allow this page pass through using a path that is already permitted
  if ($page=='permission') {
    $path ='vc/create/role';
  }

  if($page == 'create'){
    $path = 'vc/create/events';
  }

  if($page == 'view_model'){
    $path = 'vc/create/payment';
  }

  if (!$role->canView($path)) {
    show_access_denied();exit;
  }
  $data['canView']=$this->adminData->getCanViewPages($role);
}

private function adminDashboard(&$data)
{
 $data = array_merge($data,$this->adminData->loadDashboardData());
}

private function adminPermission(&$data)
{
  $data['id'] = urldecode($data['id']);
  if (!isset($data['id']) || !$data['id'] || $data['id']==1) {
    show_404();exit;
  }
  $newRole = new Role(array('ID'=>$data['id']));
  $newRole->load();
  $data['role']=$newRole;
  $data['allPages']=$this->adminData->getAdminSidebar(true);
  $sidebarContent=$this->adminData->getCanViewPages($data['role'],true);
  // print_r($sidebarContent);exit;
  $data['permitPages']=$sidebarContent;
  $data['allStates']=$data['role']->getPermissionArray();
}

private function adminProfile(&$data)
{
  loadClass($this->load,'admin');
  $admin = new Admin();
  $admin->ID=$this->webSessionManager->getCurrentUserProp('user_table_id');
  $admin->load();
  $data['admin']=$admin;
}

private function adminReport(&$data){
  $type = (isset($_GET['report_type']) && $_GET['report_type'] != '') ? $_GET['report_type'] : false;
  $from = (isset($_GET['date_from']) && $_GET['date_from'] != '') ? $_GET['date_from'] : false;
  $to = (isset($_GET['date_to']) && $_GET['date_to'] != '') ? $_GET['date_to'] : false;
  if(!$type || !$from || !$to){
    $this->webSessionManager->setFlashMessage('message','Please choose all fields');
    // header("Location:".base_url('vc/admin/report'));exit;
  }else{
    $data = array_merge($data,$this->adminData->getTotalAmountReport($from,$to,$type));
  }
}

private function adminDaily_winner(&$data){
  if($this->webSessionManager->getCurrentUserProp('user_type')=='customer') {
    $id=$this->webSessionManager->getCurrentUserProp('ID');
    if (!$id) {
      show_404();exit;
    }
    $client_id=$this->webSessionManager->getCurrentUserProp('user_table_id');
    $cash_type = $_GET['profile'];
  }
  $data['dailyWinnerList'] = $this->adminData->getDailyWinner();
}

private function adminView_model(&$data){
  $id = (is_numeric($data['id'])) ? $data['id'] : null;
  $model  = ($data['extraParam']) ? $data['extraParam'] : $data['id'];
  loadClass($this->load,"$model");
  $cash_type='';$client_id='';
  if($model == 'cashback'){
    $userType = $this->webSessionManager->getCurrentUserProp('user_type');
    if($userType == 'admin'){
      $cash_type = $_GET['type'] ?? "";
      $client_id = 'admin';
    }

    if($this->webSessionManager->getCurrentUserProp('user_type')=='customer') {
      $id=$this->webSessionManager->getCurrentUserProp('ID');
      if (!$id) {
        show_404();exit;
      }
      $client_id=$this->webSessionManager->getCurrentUserProp('user_table_id');
      $cash_type = $_GET['profile'];
    }
  }

  if($model == 'activated'){
    $userType = $this->webSessionManager->getCurrentUserProp('user_type');
    $cash_type='';
    $client_id='';
    if($userType == 'admin'){
      $cash_type = $_GET['type'] ?? "";
    }
  }
  $result = $this->$model->viewList($id,$cash_type,$client_id);
  $data['modelName'] = $model;
  $data['viewHistory']=$result;
  $data['dataParam'] = $id;
}


private function customer($page,&$data){
  $this->load->model('custom/customerData');
  loadClass($this->load,'customer');
  $this->customer->ID = $this->webSessionManager->getCurrentUserProp('user_type')=='admin'?$data['id']:$this->webSessionManager->getCurrentUserProp('user_table_id');
  $this->customer->load();
  $this->customerData->setCustomer($this->customer);
  $data['customer']=$this->customer;
}

private function customerDashboard(&$data){
  $data = array_merge($data,$this->customerData->loadDashboardInfo());
}

private function customerTransaction(&$data){
  $data = array_merge($data,$this->customerData->loadCustomerTransaction());
}

private function customerCashback(&$data){
  $data = array();
  $data['cashTypeTask'] = 'profile';
  $data['cashByuser'] = $this->webSessionManager->getCurrentUserProp('user_table_id');

  // checking if request is coming from the customer, thus redirect to the controller handling that request
  if(isset($_POST) && count($_POST) > 0 && !empty($_POST)){
    if($_POST['agree'] == 'agree'){
      $amount = $this->input->post('amount',true);
      $tranx_type = $this->input->post('tranx-type',true);
      $firstname = $this->input->post('first-name',true);
      $lastname = $this->input->post('last-name',true);
      $phoneNumber = $this->input->post('phone-number',true);
      $bankName = $this->input->post('bank-name',true);
      $bankAcc = $this->input->post('bank-account',true);
      $timeHh = $this->input->post('time-check-hh',true);
      $timeMm = $this->input->post('time-check-mm',true);
      $timeSs = $this->input->post('time-check-ss',true);
      $cashbackType = $this->input->post('cashback_type',true);
      $customer_id = $this->input->post('customer_id',true);
      $ip_address = $this->input->ip_address();
      $latitude = getLatLong($ip_address,'latitude');
      $longitude = getLatLong($ip_address,'longitude');

      if($timeHh > 23){
        $this->webSessionManager->setFlashMessage('error',"Hour field is not correctly filled");
        redirect("/customer/cashback/");
      }else if($timeMm > 59){
        $this->webSessionManager->setFlashMessage('error',"Minute field is not correctly filled");
        redirect("/customer/cashback/");
      }else if($timeSs > 59){
        $this->webSessionManager->setFlashMessage('error',"Second field is not correctly filled");
        redirect("/customer/cashback/");
      }else{
        $param = [
          'customer_id' => $data['cashByuser'],
          'customer_name' => $lastname ." ".$firstname,
          'phone_number' => $phoneNumber,
          'amount' => $amount,
          'transaction_type' => $tranx_type,
          'list_of_banks_id' =>$bankName,
          'bank_account' =>$bankAcc,
          'cashback_type' =>$cashbackType,
          'time' => $timeHh.':'.$timeMm.':'.$timeSs,
          'ip_address'=> $ip_address,
          'user_agent' => $this->input->user_agent(),
          'latitude' => $latitude,
          'longitude' => $longitude
        ];
        
        $result = $this->cashModel->postCashBack($param);
        if($result){
          $lastInsertId = getLastInsertId($this->db);
          $param['cashback_id'] = $lastInsertId;
          $data['cashbackSuccess'] = true;
          $data['customerData'] = $param;
        }else{
          $this->webSessionManager->setFlashMessage('error',"Something went wrong, pls try again later");
          redirect("/customer/cashback/");
        }
      }
    }else{
      $this->webSessionManager->setFlashMessage('error',"You have to agree to the terms and conditions");
      redirect("/customer/cashback/");
    }
    // seeing it's within the post request
    $_POST['filterSelect'] = $bankName;
    $this->load->view('cashback/confirm_ticket',$data);return;
  }
  $this->load->view("cashback/cashbackform",$data);
}

private function customerCheck_number(&$data){
  // if(isset($_POST) && count($_POST) > 0 && !empty($_POST)){
  //   $phoneNumber = $this->input->post('my_number', true);
  //   $data['checkList'] = $this->customerData->checkMyNumber($phoneNumber);
  // }
  $phoneNumber = $this->customer->phone_number;
  $data['checkList'] = $this->customerData->checkMyNumber($phoneNumber);
}

public function customerProfile(&$data)
{
  if ($this->webSessionManager->getCurrentUserProp('user_type')=='admin') {
    $this->admin('profile',$data);
    if (!isset($data['id']) || !$data['id']) {
      show_404();exit;
    }
    $std = new Customer(array('ID'=>$data['id']));
    if (!$std->load()) {
      show_404();exit;
    }
    $data['customer']=$std;
  }
}


  //function for loading edit page for general application
  function edit($model,$id){
    $userType=$this->webSessionManager->getCurrentUserProp('user_type');
    if($userType == 'admin'){
      loadClass($this->load,'admin');
      $this->admin->ID = $this->webSessionManager->getCurrentUserProp('user_table_id');
      $this->admin->load();
      $role = $this->admin->role;
      $role->checkWritePermission();
    }else{
      $role = true;
    }
    
    $ref = @$_SERVER['HTTP_REFERER'];
    if ($ref&&!startsWith($ref,base_url())) {
      show_404();
    }
    $this->webSessionManager->setFlashMessage('prev',$ref);
    $exceptionList= array('user');//array('user','applicant','student','staff');
    if (empty($id) || in_array($model, $exceptionList)) {
      show_404();exit;
    }
    $this->load->model('formConfig');
    $formConfig = new formConfig($role);
    $configData=$formConfig->getUpdateConfig($model);
    $exclude = ($configData && array_key_exists('exclude', $configData))?$configData['exclude']:array();
     $formContent= $this->modelFormBuilder->start($model.'_edit')
        ->appendUpdateForm($model,true,$id,$exclude,'')
        ->addSubmitLink(null,false)
        ->appendSubmitButton('Update','btn btn-success')
        ->build();
    $result = $formContent;
    echo createJsonMessage('status',true,'message',$result);exit;
  }

  function extra($model,$id,$_1){
    $role = true;

    $userType=$this->webSessionManager->getCurrentUserProp('user_type');
    if ($userType=='lecturer') {
      loadClass($this->load,'lecturer');
      $this->lecturer->ID = $this->webSessionManager->getCurrentUserProp('user_table_id');
      $this->lecturer->load();
    }
    else{
      loadClass($this->load,'admin');
      $this->admin->ID = $this->webSessionManager->getCurrentUserProp('user_table_id');
      $this->admin->load();
    }

    $ref = @$_SERVER['HTTP_REFERER'];
    if ($ref&&!startsWith($ref,base_url())) {
      show_404();
    }

    $this->webSessionManager->setFlashMessage('prev',$ref);
    $exceptionList= array('user');//array('user','applicant','student','staff');
    if (empty($id) || in_array($model, $exceptionList)) {
      show_404();exit;
    }
    // this is to set the form id of the original form
    $extraParam = array(
        'extra_model' => $id,
        'extra_id'    => $_1
    );
    // $this->webSessionManager->setContent('extra_id',$id);
    $this->webSessionManager->setArrayContent($extraParam);
    $this->load->model('formConfig');
    $formConfig = new formConfig($role);
    $configData=$formConfig->getUpdateConfig($model);
    $exclude = ($configData && array_key_exists('exclude', $configData))?$configData['exclude']:array();
    $hidden = ($configData && array_key_exists('hidden', $configData))?$configData['hidden']:array();
    $showStatus = ($configData && array_key_exists('show_status', $configData))?$configData['show_status']:false;
    $submitLabel = ($configData && array_key_exists('submit_label', $configData))?$configData['submit_label']:"Save";

     $formContent= $this->modelFormBuilder->start($model.'_table')
        ->appendInsertForm($model,true,$hidden,'',$showStatus,$exclude)
        ->addSubmitLink()
        ->appendSubmitButton($submitLabel,'btn btn-success')
        ->build();
        echo createJsonMessage('status',true,'message',$formContent);exit;
  } 

  // this method is for creation of form either in single or combine based on the page desire
  public function create($model,$type='add',$data=null){
    if(!empty($type)){
      if($type=='add'){
        // this is useful for a page that doesn't follow normal procedure of a modal page
        $this->add($model,'add');
      }else{
        // this uses modal to show it content
        $this->add($model,$type,$data,$service);
      }
    }
    return "please specify a type to be created (single page or combine page with view inclusive...)";
  }

  private function add($model,$type,$param=null)
  {
    if (!$this->webSessionManager->isSessionActive()) {
      header("Location:".base_url());exit;
    }

    $role_id=$this->webSessionManager->getCurrentUserProp('role_id');
    $userType=$this->webSessionManager->getCurrentUserProp('user_type');
    if($userType == 'admin'){
      if (!$role_id) {
        show_404();
      }
    }
    $role =false;
    if($userType == 'admin'){
      loadClass($this->load,'admin');
      $this->admin->ID = $this->webSessionManager->getCurrentUserProp('user_table_id');
      $this->admin->load();
      $role = $this->admin->role;
      $data['admin']=$this->admin;
      $data['currentRole']=$role;
      $type = ($type == 'add') ? 'create' : $type;
      $path ="vc/$type/".$model;

      if (!$role->canView($path)) {
        show_access_denied($this->load);exit;
      }
      $type = ($type == 'create') ? 'add' : $type;
      $this->load->model('custom/adminData');
      $sidebarContent=$this->adminData->getCanViewPages($role);
      $data['canView']=$sidebarContent;
    }else if($userType == 'patient'){
      loadClass($this->load,'patient');
      $this->patient->ID = $this->webSessionManager->getCurrentUserProp('user_table_id');
      $this->patient->load();
      $role = true;
      $data['patient']=$this->patient;
    }

    if ($model==false) {
      show_404();
    }

    loadClass($this->load,$model);
    $modelClass = new $model();
    $this->load->model('Crud');
    $this->load->model('modelFormBuilder');
    if (!is_subclass_of($modelClass ,'Crud')) {
      show_404();exit;
    }
    $this->load->model('formConfig');
    $formConfig = new formConfig($role);
    $data['configData']=$formConfig->getInsertConfig($model);
    $data['model']=$model;
    $data['appConfig']=$this->appData;
    $this->load->view("$type",$data);
  }

  function changePassword()
  {
    if(isset($_POST) && count($_POST) > 0 && !empty($_POST)){
      $curr_password = trim($_POST['current_password']);
      $new = trim($_POST['password']);
      $confirm = trim($_POST['confirm_password']);

      if (!isNotEmpty($curr_password,$new,$confirm)) {
        echo createJsonMessage('status',false,'message',"empty field detected.please fill all required field and try again");
        return;
      }
      
      $id= $this->webSessionManager->getCurrentUserProp('ID');
      $this->load->model('entities/user');
      if($this->user->findUserProp($id)){
        // $check = md5(trim($curr_password)) == $this->user->data()[0]['password'];
        $check = $this->hash_created->decode_password(trim($curr_password), $this->user->data()[0]['password']);
        if(!$check){
          echo createJsonMessage('status',false,'message','please type-in your password correctly...','flagAction',false);
          return;
        }
      }

      if ($new !==$confirm) {
        echo createJsonMessage('status',false,'message','new password does not match with the confirmation password','flagAction',false);exit;
      }
      // $new = md5($new);
      $new = $this->hash_created->encode_password($new);
        $query = "update user set password = '$new' where ID=?";
        if ($this->db->query($query,array($id))) {
          $arr['status']=true;
          $arr['message']= 'operation successfull';
          $arr['flagAction'] = true;
          echo json_encode($arr);
          return;
        }
        else{
          $arr['status']=false;
          $arr['message']= 'error occured during operation...';
          $arr['flagAction'] = false;
          echo json_encode($arr);
          return;
        }
    }
    return false;
  }

}

?>
