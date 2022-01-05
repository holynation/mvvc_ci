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

    private function validateWebApiAccess($value='')
    {
        // $client =  loadClass('client');
        // $id = $this->webSessionManager->getCurrentUserProp('user_table_id');
        // $temp  = new Client(array('ID'=>$id));
        // if (!$temp->load() || !$temp->status) {
        //  return false;
        // }
        // $ttemp=(object)$temp->toArray();
        // $_SERVER['current_user']=$ttemp;
        return $this->webSessionManager->isSessionActive();
    }

    public function mobileApi($entity){
        $dictionary = getAPIEntityTranslation();
        $method = array_key_exists($entity, $dictionary)?$dictionary[$entity]:$entity;
        $entities = listAPIEntities($this->db);
        $args = $this->request->getUri()->getSegments();
        $args = array_slice($args,1);
        if (in_array($method, $entities)) {
            $entityModel = new EntityModel($this->response);
            $entityModel->process($method,$args);
            return;
        }
        //define the set of methods in another model called ApiMOdel
        $apiModel = new ApiModel($this->request,$this->response);
        $entity = array_key_exists($entity, $dictionary)?$dictionary[$entity]:$entity;
        if (method_exists($apiModel, $entity)) {
            $apiModel->$entity($args);
            return;
        }else{
            //method no dey exist for this place 00
            return $this->response->setStatusCode(405)->setJSON(['status'=>false,'message'=>'denied']);
        }
    }


    public function webApi($entity)
    {
        if (!$this->validateWebApiAccess()) {
            echo $response->setStatusCode(405)->setJSON(['status'=>false,'message'=>'denied']);
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
            return $this->response->setStatusCode(405)->setJSON(['status'=>false,'message'=>'denied']);
        }
        
    }

}
 ?>