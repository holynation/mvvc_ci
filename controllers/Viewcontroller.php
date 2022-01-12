<?php
namespace App\Controllers;

use App\Models\WebSessionManager;
use App\Models\ModelFormBuilder;
use App\Models\TableWithHeaderModel;
use App\Models\QueryHtmlTableModel;
use App\Models\QueryHtmlTableObjModel;

class Viewcontroller extends BaseController{

  private $errorMessage; // the error message currently produced from this cal if it is set, it can be used to produce relevant error to the user.
  private $access = array();
  private $appData;

  private $modelFormBuilder;
  private $webSessionManager;
  private $tableWithHeaderModel;
  protected $db;
  private $adminData;
  private $customerData;
  private $companyData;


  private $crudNameSpace = 'App\Models\Crud';

  function __construct(){
    helper(['array','string']);

    $this->db = db_connect();
    $this->webSessionManager = new WebSessionManager;
    $this->modelFormBuilder = new ModelFormBuilder;
    $this->tableWithHeaderModel = new TableWithHeaderModel;
    $this->queryHtmlTableModel = new QueryHtmlTableModel;
    $this->queryHtmlTableObjModel = new QueryHtmlTableObjModel;

    $this->adminData = new \App\Models\Custom\AdminData();
    $this->customerData = new \App\Models\Custom\CustomerData($this->request);
    $this->companyData = new \App\Models\Custom\CompanyData($this->request);

    if (!$this->webSessionManager->isSessionActive()) {
      header("Location:".base_url());exit;
    }
  }

// bootstrapping functions 
public function view($model,$page='index',$third='',$fourth=''){
  if ( !(file_exists(APPPATH."Views/$model/") && file_exists(APPPATH."Views/$model/$page".'.php')))
  {
    throw new \CodeIgniter\Exceptions\PageNotFoundException($page);
  }
  // this number is the default arg that ID is the last arg i.e 3 = id

  $tempTitle = removeUnderscore($model);
  $title = $page=='index'?$tempTitle:ucfirst($page)." $tempTitle";
  $modelID = ($fourth == '') ? $third : $fourth;
  $data['id'] = urldecode($modelID);
  $data['extraParam'] = ($fourth != '') ? $third : "";

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
  $data['message']=$this->webSessionManager->getFlashMessage('message');
  $data['webSessionManager'] = $this->webSessionManager;
  sendPageCookie($model,$page);

  echo view("$model/$page", $data);
}

private function admin($page,&$data)
{
  $role_id=$this->webSessionManager->getCurrentUserProp('role_id');
  if (!$role_id) {
    throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
  }

  $role=false;
  if ($this->webSessionManager->getCurrentUserProp('user_type')=='admin') {
    $admin = loadClass('admin');
    $admin->ID = $this->webSessionManager->getCurrentUserProp('user_table_id');
    $admin->load();
    $data['admin']=$admin;
    $role = $admin->role;
  }
  $data['currentRole']=$role;
  if (!$role) {
    throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
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
    echo show_access_denied();exit;
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
  $role = loadClass('role');
  $newRole = new $role(array('ID'=>$data['id']));
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
  $admin = loadClass('admin');
  $admin = new $admin();
  $admin->ID=$this->webSessionManager->getCurrentUserProp('user_table_id');
  $admin->load();
  $data['admin']=$admin;
}

private function adminView_model(&$data){
  $id = (is_numeric($data['id'])) ? $data['id'] : null;
  $model  = ($data['extraParam']) ? $data['extraParam'] : $data['id'];
  $newModel = loadClass("$model");
  if($model == 'cashback'){
    $userType = $this->webSessionManager->getCurrentUserProp('user_type');
    $cash_type='';
    $client_id='';
    if($userType == 'admin'){
      $cash_type = $_GET['type'] ?? "";
      $client_id = 'admin';
    }

    if($this->webSessionManager->getCurrentUserProp('user_type')=='customer') {
      $id=$this->webSessionManager->getCurrentUserProp('ID');
      if (!$id) {
        throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
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
  
  $result = $newModel->viewList($id,$cash_type,$client_id);
  $data['modelName'] = $model;
  $data['viewHistory']=$result;
  $data['dataParam'] = $id;
}


private function customer($page,&$data){
  $customer = loadClass('customer');
  $this->customer->ID = $this->webSessionManager->getCurrentUserProp('user_type')=='admin'?$data['id']:$this->webSessionManager->getCurrentUserProp('user_table_id');
  $customer->load();
  $this->customerData->setCustomer($customer);
  $data['customer']=$customer;
}

private function customerDashboard(&$data){
  $data = array_merge($data,$this->customerData->loadDashboardInfo());
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
      throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
    }
    $data['customer']=$std;
  }
}

private function company($page,&$data){
  $company = loadClass('company');
  $company->ID = $this->webSessionManager->getCurrentUserProp('user_type')=='admin'?$data['id']:$this->webSessionManager->getCurrentUserProp('user_table_id');
  $company->load();
  $this->companyData->setCompany($company);
  $data['company']=$company;
}

private function companyDashboard(&$data){
  $data = array_merge($data,$this->companyData->loadDashboardInfo());
}


//function for loading edit page for general application
public function edit($model,$id){
  $userType=$this->webSessionManager->getCurrentUserProp('user_type');
  if($userType == 'admin'){
    $admin = loadClass('admin');
    $admin->ID = $this->webSessionManager->getCurrentUserProp('user_table_id');
    $admin->load();
    $role = $admin->role;
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
    throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
  }
  $formConfig = model('App\Models\formConfig');
  $formConfig = new $formConfig($role);
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
      $admin = loadClass('admin');
      $admin->ID = $this->webSessionManager->getCurrentUserProp('user_table_id');
      $admin->load();
      $role = $admin->role;
      $data['admin']=$admin;
      $data['currentRole']=$role;
      $type = ($type == 'add') ? 'create' : $type;
      $path ="vc/$type/".$model;

      if (!$role->canView($path)) {
        echo show_access_denied();exit;
      }
      $type = ($type == 'create') ? 'add' : $type;
      $sidebarContent=$this->adminData->getCanViewPages($role);
      $data['canView']=$sidebarContent;
    }else if($userType == 'customer'){
      $customer = loadClass('customer');
      $customer->ID = $this->webSessionManager->getCurrentUserProp('user_table_id');
      $customer->load();
      $role = true;
      $data['customer']=$customer;
    }

    if ($model==false) {
      show_404();
    }

    $newModel = loadClass($model);
    $modelClass = new $newModel();
    $crud = model('App\Models\Crud');
    if (!is_subclass_of($modelClass ,$this->crudNameSpace)) {
      throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
    }
    $formConfig = model('App\Models\FormConfig');
    $formConfig = new $formConfig($role);
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
      $user = loadClass('user');
      if($user->findUserProp($id)){
        // $check = md5(trim($curr_password)) == $this->user->data()[0]['password'];
        $check = decode_password(trim($curr_password), $this->user->data()[0]['password']);
        if(!$check){
          echo createJsonMessage('status',false,'message','please type-in your password correctly...','flagAction',false);
          return;
        }
      }

      if ($new !==$confirm) {
        echo createJsonMessage('status',false,'message','new password does not match with the confirmation password','flagAction',false);exit;
      }
      // $new = md5($new);
      $new = encode_password($new);
      $passDate = date('Y-m-d H:i:s');
        $query = "update user set password = '$new',last_change_password = '$passDate' where ID=?";
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
