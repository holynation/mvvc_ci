<?php 

/**
 * This is the Model that manages webApi specific request
 */
namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\RequestInterface;
use App\Models\WebSessionManager;
use App\Models\Mailer;

class ApiModel extends Model
{
	protected $request;
	protected $response;
	protected $db;
	private $webSessionManager;
	private $entitiesNameSpace = 'App\Entities';

	function __construct(RequestInterface $request=null, ResponseInterface $response=null)
	{
		$this->db = db_connect();
		$this->request = $request;
		$this->response = $response;
		$this->webSessionManager = new WebSessionManager;
	}

	public function auth(){
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
        $user = loadClass('user');
        $array = array('username'=>$username,'status'=>1);
        $user = $user->getWhere($array,$count,0,null,false);
        if ($user==false) {
            $response = array('status'=>false,'message'=>'invalid username or password');
            echo json_encode($response);
            return;             
        }
        else{
            $user = $user[0];
            if (!decode_password($password, $user->password)) {
                $response = array('status'=>false,'message'=>'invalid username or password');
                echo json_encode($response);
                return; 
            }
            $baseurl = base_url();
            $userType = $this->webSessionManager->saveCurrentUser($user,true);
            $baseurl.=$this->getUserPage($user);

            $key = env('jwtKey');
			$token = generateJwt($userType,$key);
			$payload['token']=$token;
			$payload['details']=$userType;
			$arr['payload'] = $payload;
            $arr['status']=true;
            $arr['message']= $baseurl;
            echo  json_encode($arr);
            return;
        }
    }

	public function register()
	{
		//get all the information validate and create the necessary account
		$firstname= $this->request->getPost('firstname');
		$surname= $this->request->getPost('surname');
		$othername= $this->request->getPost('othernames');
		$email=$this->request->getPost('email');
		if (!filter_var($email,FILTER_VALIDATE_EMAIL)) {
			displayJson(false,"invalid email address");
			return;
		}

		$gender = $this->request->getPost('gender');
		if (!in_array($gender, array('male','female'))) {
			displayJson(false,"invalid gender, can either be 'male','female'");
			return;
		}
		$phone = $this->request->getPost('phone_number');
		if (!isValidPhone($phone)) {
			displayJson(false,"invalid phone number");
			return;
		}
		$dob = $this->request->getPost('dob');
		$occupation = $this->request->getPost('occupation');
		$address = $this->request->getPost('address');
		if(!$address){
			displayJson(false,"contact address can't be empty");
			return;
		}

		if (!isUniqueEmailAndPhone($this->db,$email,$phone)) {
			displayJson(false,"email or phone number already exists");
			return;
		}

		//hash the password
		$password = trim($this->request->getPost('password'));
		if (!verifyPassword($password)) {
			displayJson(false,"password should be minimum of 8 character with at least one uppercase letter and a number");
			return;
		}
		$password = encode_password($password);
		$toInsert = array('firstname'=>$firstname,'surname'=>$surname,'other_names'=>$othername,'email'=>$email,'phonenumber'=>$phone,'dob'=>$dob,'contact_address'=>$address,'gender'=>$gender,'occupation'=>$occupation);
		$client = loadClass('client');
		$client = new $client($toInsert);
		if (!$client->insert()) {
			displayJson(false,"an error occured while creating account");
			return;
		}
		$user = loadClass('user');
		$lastInsertID = getLastInsertId($this->db);
		$userData = [
			'username'=>$email,
			'password'=>$password,
			'user_type'=>'client',
			'user_table_id'=>$lastInsertID
		];
		$user = new $user($userData);
		if (!$user->insert()) {
			displayJson(false,"an error occured while creating account");
			return;
		}
		displayJson(true,"account created successfully");
	}

	private function getUserPage($user){
		$link= array('client'=>'vc/client','admin'=>'vc/admin/dashboard');
		$roleName = $user->user_type;
		return $link[$roleName];
	}

	public function requestPasswordReset()
	{
		$email = trim($this->request->getPost('email'));
		if (!$email) {
			displayJson(false,'please provide email address');
			return;
		}
		//this will just generate the token and send to the email address
		$client = loadClass('client');
		$client=$client->getWhere(array('email'=>$email),$count,0,null,false);
		$client = is_array($client)?$client[0]:$client;
		if (!$client) {
			displayJson(false,'account with that email address does not exist');
			return;
		}
		//disable all previous OTP by the user
		$client->disableAllPasswordOTPs();
		$otp = getPasswordOTP($this->db,$client);
		if (!$otp) {
			displayJson(false,'an error occured');
			return;
		}
		//save the OTP and send the mail
		$password_otp = loadClass('password_otp');
		$password_otp->otp=$otp;
		$password_otp->client_id=$client->ID;
		if (!$password_otp->insert()) {
			displayJson(false,'an error occured');
			return;
		}
		$mailer = new Mailer;
		$clientName = $client->firstname.' '.$client->surname;
		$mailer->sendCustomerMail($email,'password_app_token',14,$clientName,$otp);
		$result = array('code'=>$otp,'expired_in'=>'1h');
		displayJson(true,'success',$result);
		return;
	}

	public function changePassword()
	{
		//fetch the customer dedtils first
		$email = trim($this->request->getPost('email'));
		$otp = trim($this->request->getPost('otp'));
		$password = trim($this->request->getPost('password'));
		if (!$email) {
			displayJson(false,'please provide email address');
			return;
		}
		if (!$otp) {
			displayJson(false,'no otp provided');
			return;
		}

		$client = loadClass('client');
		$client =$client->getWhere(array('email'=>$email),$count,0,null,false);
		$client = is_array($client)?$client[0]:$client;
		if (!$client) {
			displayJson(false,'invalid operation');
			return;
		}
		if (!$this->verifyPasswordOTP($client,$otp)) {
			displayJson(false,'invalid code');
			return;
		}
		if (!verifyPassword($password)) {
			displayJson(false,"password should be minimum of 8 character with at least one uppercase letter and a number");
			return;
		}
		$password = encode_password($password);
		$newUser = loadClass('user');
		$temp = $newUser->getWhere(array('user_table_id'=>$client->ID,'user_type'=>'client'),$count,0,1,false);
		if (!$temp){
			displayJson(false,'sorry, an invalid operation...');
			return;
		}
		$userID = $temp[0]->ID;
		$cust = new $newUser(array('ID'=>$userID,'password'=>$password));
		if (!$cust->update()) {
			displayJson(false,"an error occured while resetting password");
			return;		
		}
		$client->disableAllPasswordOTPs();
		$mailer = new Mailer;
		$clientName = $client->firstname.' '.$client->surname;
		$mailer->sendCustomerMail($email,'password_reset_success',4,$clientName);
		displayJson(true,"password has been reset successfully");
		return;	
	}

	private function verifyPasswordOTP($client,$otp)
	{
		$query="select * from password_otp where client_id=?  and otp=? and status=0 and timestampdiff(MINUTE,date_created,current_timestamp) <=120 order by ID desc limit 1";
		$result = $this->db->query($query,array($client->ID,$otp));
		$result = $result->getResultArray();
		return $result;
	}

	public function profile()
	{
		//check for get and post to the able to perform the necessary update as required
		$customer = getCustomer();
		if ($_SERVER['REQUEST_METHOD']=='GET') {
			displayJson(true,"success",$customer);
			return;
		}
		if ($_SERVER['REQUEST_METHOD']=='POST') {
			loadClass($this->load,'customer');
			$this->load->model('entityCreator');
			//remove email and password, that is field that should not be editable
			$nonEditable = array('email','customer_password','date_created','gender');
			$param  = $_POST;
			foreach ($nonEditable as $value) {
				unset($param[$value]);
			}
			$this->entityCreator->outputResult=false;
			$result = $this->entityCreator->update('customer',$customer->ID,
				true,$param);
			$this->entityCreator->outputResult=true;
			if (!$result) {
				displayJson(false,"error occured");
				return;
			}
			$newCustomer = new Customer(array('ID'=>$customer->ID));
			$newCustomer->load();
			$myResult = (object)$newCustomer->toArray();
			unset($myResult->customer_password);
			$_SERVER['current_user']=$myResult;
			displayJson(true,"success",$myResult);
			return;
		}
	}

}

 ?>