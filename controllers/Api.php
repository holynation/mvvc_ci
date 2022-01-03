<?php 

namespace App\Controllers;

use Firebase\JWT\JWT;
use App\Models\Crud;
use App\Entities\User;
use App\Models\WebSessionManager;
use App\Models\EntityModel;
use App\Models\ApiModel;
use App\Models\WebApiModel;

class Api extends BaseController
{
    protected $db;
    private $webSessionManager;
    private $user;
    private $entityModel;
    private $apiModel;
    private $webApiModel;

    function __construct()
    {
        helper(['array','string']);
        $this->db = db_connect();
        $this->webSessionManager = new WebSessionManager;
        $this->user = new User;

        // using this to know if request was coming from react client
        if(strpos(@$_SERVER['PATH_INFO'], 'api') !== FALSE || strpos(@$_SERVER['ORIG_PATH_INFO'], 'api') !== FALSE){
            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
            header("Content-Type:application/json");
        }
        $exemptedMethod= array('register');
    }

        public function web(){
            $username = $this->request->getPost('username');
            $password = $this->request->getPost('password');

            if (!($username || $password)) {
                $response= json_encode(array('status'=>false,'message'=>"invalid entry data"));
                echo $response;
                return;
            }
            if (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
              $response= json_encode(array('status'=>false,'message'=>"invalid email address"));
              echo $response;
              return;
            }

            $array = array('username'=>$username,'status'=>1);
            $user = $this->user->getWhere($array);
            if ($user==false) {
                $response = array('status'=>false,'message'=>'invalid username or password');
                echo json_encode($response);
                return;             
            }
            else{
                $user = $user[0];
                if (!password_verify($password, $user->password)) {
                    $response = array('status'=>false,'message'=>'invalid username or password');
                    echo json_encode($response);
                    return; 
                }
                $baseurl = base_url();
                $this->webSessionManager->saveCurrentUser($user,true);
                $baseurl.=$this->getUserPage($user);//'statics/sample';//redirect to the original dashboard page;
                // $this->application_log->log('login','user logged in successfully');
                $arr['status']=true;
                $arr['message']= $baseurl;
                echo  json_encode($arr);
                return;
            }
        }

        private function validateAPIRequest()
        {
            try{
                $token = getBearerToken();
                JWT::$leeway = 10; // $leeway in seconds
                $res=JWT::decode($token,$this->config->item('jwt_key'),array('HS256'));
                $id=$res->user_table_id;
                $client = loadClass("$client");
                $temp  = new Client(array('ID'=>$id));
                if (!$temp->load() || !$temp->status) {
                    return false;
                }
                $ttemp=(object)$temp->toArray();
                unset($ttemp->customer_password);
                $_SERVER['current_user']=$ttemp;
                return true;

            }
            catch(\Exception $e){
                return false;
            }
        }

        private function validateWebApiAccess($value='')
        {
            // loadClass($this->load,'member');
            // $id = $this->webSessionManager->getCurrentUserProp('user_table_id');
            // $temp  = new Member(array('ID'=>$id));
            // if (!$temp->load() || !$temp->status) {
            //  return false;
            // }
            // $ttemp=(object)$temp->toArray();
            // $_SERVER['current_user']=$ttemp;
            return $this->webSessionManager->isSessionActive();
        }

        private function validateHeader()
        {
            return array_key_exists('HTTP_X_APP_KEY', $_SERVER) && $_SERVER['HTTP_X_APP_KEY']==$this->config->item('api_key');
        }

        private function isExempted($arguments)
        {
            $exemptionList= array('POST/signup','POST/reset_password','POST/change_pass','POST/auth');

            $argPath=$_SERVER['REQUEST_METHOD'].'/'.implode('/', $arguments);
            return in_array($argPath, $exemptionList);
        }

        private function canProceed($args)
        {
            $isExempted = $this->isExempted($args);
            if ($isExempted) {
                return true;
            }
            return $this->validateAPIRequest();
        }

        public function mobileApi($entity){
            if (!$this->validateHeader()) {
                http_response_code(405);
                displayJson(false,'denied');
                return;
            }
            if (!$this->canProceed(func_get_args())) {
                http_response_code(405);
                displayJson(false,'denied');
                return;
            }
            // $this->validateAPIRequest();
            $dictionary = getAPIEntityTranslation();
            $method = array_key_exists($entity, $dictionary)?$dictionary[$entity]:$entity;
            $entities = listAPIEntities($this->db);
            $args = array_slice(func_get_args(),1);
            if (in_array($method, $entities)) {
                $entityModel = new EntityModel;
                $entityModel->process($method,$args);
                return;
            }
            //define the set of methods in another model called ApiMOdel
            $apiModel = new ApiModel;;
            $entity = array_key_exists($entity, $dictionary)?$dictionary[$entity]:$entity;
            if (method_exists($apiModel, $entity)) {
                $apiModel->$entity($args);
                return;
            }else{
                //method no dey exist for this place 00
                http_response_code(405);
                displayJson(false,'denied');
                return;
            }
        }


        public function webApi($entity)
        {
            if (!$this->validateWebApiAccess()) {
                http_response_code(405);
                displayJson(false,'denied');
                redirect('auth/login');
                return;
            }
            $dictionary = getEntityTranslation();
            $method = array_key_exists($entity, $dictionary)?$dictionary[$entity]:$entity;
            $entities = listEntities($this->db);
            $args = array_slice(func_get_args(),1);
            // this check if the method is equivalent to any entity model to get it equiv result
            if (in_array($method, $entities)) {
                $entityModel = new EntityModel;
                $entityModel->process($method,$args);
                return;
            }
            //define the set of methods in another model called WebApiModel
            $webApiModel = new WebApiModel;
            if (method_exists($webApiModel, $entity)) {
                $webApiModel->$entity($args);
                return;
            }else{
                //method no dey exist for this place 00
                http_response_code(405);
                displayJson(false,'denied');
                return;
            }
            
        }

}
 ?>