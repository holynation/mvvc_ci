<?php

namespace App\Controllers;

use App\Models\WebSessionManager;
use App\Models\User;
use App\Models\Mailer;
use Firebase\JWT\JWT;
/**
* 
*/
class Authapi extends BaseController
{	
	private $user;
	private $webSessionManager;
	private $mailer;
	
	function __construct()
	{

		helper(['cookie','string','url']);

		$this->user = new User;
		$this->webSessionManager = new WebSessionManager;
		$this->mailer = new Mailer;
	}

	function web(){
		// header("Content-Type:application/json");
		$this->message->setHeader('Content-Type','application/json');
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


	public function appLogin()
	{
		$email = trim($this->request->getPost('email'));
		$password = trim($this->request->getPost('password'));
		if (!$email) {
			displayJson(false,'email cannot be empty');
			return;
		}
		if (!$password) {
			displayJson(false,'please enter password');
			return;
		}
		$client = loadClass('client');
		$client = $client->getWhere(array('email'=>$email));
		$client = $client?$client[0]:$client;
		if (!$client) {
			displayJson(false,'invalid username or password');
			return;
		}
		//TODO: CONTINUE FROM HERE
		$verified = password_verify($password, $client->customer_password);
		if (!$verified) {
			displayJson(false,'invalid username or password');
			return;
		}
		//geenerate token
		$key = $this->config->item('jwt_key');
		$payload = $customer->toArray();
		unset($payload['customer_password']);
		$token = generateJwt($payload,$key);
		$result['token']=$token;
		$result['details']=$payload;
		displayJson(true,'success',$result);
	}

	private function getUserPage($user){
		return "vc/admin/";
	}
		
	function logout(){
		$link ='';
		$base = base_url();
		// if (isset($_GET['rdr'])) {
		// 	$link = 'vc/admissions/login';
		// }
		$this->webSessionManager->logout();
		$this->application_log->log('logout','user logs out');
		$path = $base.$link;
		header("location:$path");exit;
	}
	//the is the mobile entry point for the mobile application
	function appAuth()
	{
		# the jwt code will  be here
		if ($_SERVER['REQUEST_METHOD']!='POST') {
			restResponse($this,['status'=> FALSE,'message'=>'Inappropriate method action for the request.'],'BAD_REQUEST');
		}

		$username = $this->input->post('username',true);
		$password = $this->input->post('password',true);

		loadClass($this->load,'customer_login');
		$array = array('customer_login'=>$username);
		$user = $this->customer_login->getWhere($array);
		if ($user==false) {
			$response = array('status'=>FALSE,'message'=>'invalid username or password');
			restResponse($this,$response,'FORBIDDEN');
		}
		else{
			$user = $user[0];
			if (!password_verify($password, $user->customer_password)) {
				$response = array('status'=>FALSE,'message'=>'invalid username or password');
				restResponse($this,$response,'BAD_REQUEST');
			}
			$payload = [
				'iat' => time(),
				'iss' => 'localhost',
				'exp' => time() + (15*60),
				'nbf' => 1357000000
			];
			$token = JWT::encode($payload, $this->config->item('jwt_token'));
			$decoded = JWT::decode($token,$this->config->item('jwt_token'),array('HS256')); // testing decoding
			$arr['status'] = TRUE;
			$arr['token'] = $token;
			$arr['decoded'] = $decoded; // this is for testing to show it worked here
			restResponse($this,$arr,'OK');
		}
	}
}
 ?>