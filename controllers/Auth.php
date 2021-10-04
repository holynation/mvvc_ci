<?php 
/**
* 
*/
class Auth extends CI_Controller
{
	// I WANNA ADD THE SECURITY CHECK TO SOME MODEL IN THE CODE BASE,USING NERMES THAT I READ IN THE FIREFOX BOOKMARK
	// I ALSO WANNA LOOK INTO SOME MINOR ISSUE RELATION TO REDIRECTION IN JAVASCRIPT

	function __construct()
	{
		parent::__construct();
		$this->load->model('entities/user');
		$this->load->model('webSessionManager');
		$this->load->library('hash_created');
		$this->load->library('cookie_created');
		$this->load->helper('cookie');
		$this->load->model('mailer');
	}

	public function signup($data = ''){
		$this->load->view('cashback/signup', $data);
	}

	public function login($data = ''){
		$this->load->view('cashback/login', $data );
	}

	public function forget($data = ''){
		$this->load->view('cashback/forget_password',$data);
	}

	public function register(){
		if(isset($_POST) && count($_POST) > 0 && !empty($_POST)){
			$data = $this->input->post(null, true);
			$fullname = trim($data['fullname'] ?? "");
			$phone_number = trim($data['phone_number'] ?? "");
			$email = trim(@$data['email'] ?? "");

			$fpassword = trim($data['password']);
			$cpassword = trim($data['confirm_password']);

			if (!isNotEmpty($fullname,$phone_number,$email,$fpassword,$cpassword)) {
				$arr['status'] = false;
				$arr['message'] = 'All field are required';
				echo json_encode($arr);
				return;
			}

			if(filter_var($email, FILTER_VALIDATE_EMAIL) == FALSE){
				$arr['status'] = false;
				$arr['message'] = 'Email is not valid';
				echo json_encode($arr);
				return;
			}

			if($fpassword !== $cpassword){
				$arr['status'] = false;
				$arr['message'] = 'New password must match Confirm password...';
				echo json_encode($arr);
				return;
			}
			$result = $this->user->postUser($fullname,$phone_number,$email,$fpassword);

			switch ($result) {
				case 1:
					$arr['status'] = true;
					$arr['message']= 'You have successfully registered.';
					echo json_encode($arr);
					return;
				break;

				case 2:
				$arr['status'] = false;
					$arr['message']= "error occurred while performing the operation";
					echo json_encode($arr);
					return;
				break;

				case 3:
					$arr['status'] = false;			
					$arr['message']= "email already registered on the platform.";
					echo json_encode($arr);
					return;
				break;

				case 4:
					$arr['status'] = false;			
					$arr['message']= "error occurred while performing the operation";
					echo json_encode($arr);
					return;
				break;

				case 5:
					$arr['status'] = false;			
					$arr['message']= "phone number already existed on the platform, choose another mobile number";
					echo json_encode($arr);
					return;
				break;
			}
		}
		$this->signup();
	}

	public function web(){
		if(isset($_POST) && count($_POST) > 0 && !empty($_POST)){
			$username = $this->input->post('email',true);
			$password = $this->input->post('password',true);
			$remember = null;
			$isAjax =  (isset($_POST['isajax']) && $_POST['isajax'] == "true") ? true : false;

			if (!isNotEmpty($username,$password)) {
				if($isAjax){
					echo createJsonMessage('status',false,'message',"Please fill all field and try again");
					return;
				}else{
					$this->webSessionManager->setFlashMessage('error','Please fill all field and try again');
					redirect(base_url('/auth/login'));
				}
				
			}
			$find = $this->user->findBoth($username);
			if($find){
				$checkPass=$this->hash_created->decode_password(trim($password), $this->user->data()[0]['password']);
				if(!$checkPass){
					if ($isAjax) {
						$arr['status']=false;
						$arr['message']= "invalid email or password";
						echo  json_encode($arr);
						return;
					}
					else{
						$this->webSessionManager->setFlashMessage('error','invalid email or password');
						redirect(base_url('/auth/login'));
					}
				}
				$array = array('username'=>$username,'status'=>1);
				$user = $this->user->getWhere($array,$count,0,null,false," order by field('user_type','admin','customer')");
				if($user == false) {
					if ($isAjax) {
						$arr['status']=false;
						$arr['message']= "Invalid email or password";
						echo json_encode($arr);
						return;
					}
					else{
						$this->webSessionManager->setFlashMessage('error','invalid email or password');
						redirect(base_url('/auth/login'));
					}
				}
				else{
					$user = $user[0];
					$baseurl = base_url();
					$this->webSessionManager->saveCurrentUser($user,true);
					$baseurl.=$this->getUserPage($user);
					if ($isAjax) {
						$arr['status']=true;
						$arr['message']= $baseurl;
						echo  json_encode($arr);
						return;
					}else{
						redirect($baseurl);exit;
					}
				}
			}else{
				if($isAjax){
					$arr['status']=false;
					$arr['message'] = 'Invalid email or password';
					echo json_encode($arr);exit;
				}else{
					$this->webSessionManager->setFlashMessage('error','invalid email or password');
					redirect(base_url('/auth/login'));
				}
				
			}
		}

		$this->login();
	}

	private function getUserPage($user){
		$link= array('customer'=>'vc/customer/dashboard','admin'=>'vc/admin/dashboard');
		$roleName = $user->user_type;
		return $link[$roleName];
	}

	// here is another approach to reset password where OTP is sent to customer
	public function requestPasswordReset()
	{
		$email = trim($this->input->post('reset_email',true));
		if (!$email) {
			echo createJsonMessage('status',false,"message","please provide email address");
			return;
		}
		if(filter_var($email, FILTER_VALIDATE_EMAIL) == FALSE){
			$arr['status'] = false;
			$arr['message'] = 'email is not valid';
			echo json_encode($arr);
			return;
		}
		//this will just generate the token and send to the email address
		loadClass($this->load,'customer');
		$customer=$this->customer->getWhere(array('email'=>$email));
		$customer = is_array($customer)?$customer[0]:$customer;
		if (!$customer) {
			$arr['status'] = false;
			$arr['message'] = 'account with that email address does not exist...';
			echo json_encode($arr);
			return;
		}
		//disable all previous OTP by the user
		$customer->disableAllPasswordOTPs();
		$otp = getPasswordOTP($this,$customer);
		if (!$otp) {
			$arr['status'] = false;
			$arr['message'] = 'an error occured on the token';
			echo json_encode($arr);
			return;
		}
		//save the OTP and send the mail
		loadClass($this->load,'password_otp');
		$this->password_otp->otp=$otp;
		$this->password_otp->customer_id=$customer->ID;
		if (!$this->password_otp->insert()) {
			$arr['status'] = false;
			$arr['message'] = 'an error occured while saving the token';
			echo json_encode($arr);
			return;
		}
		
		$sendMail=($this->mailer->sendPasswordOTP($customer,$otp)) ? true : false;
		$message = "An OTP for resetting your password has been sent to your mail, $email. Be sure to check your spam folder if not seen.";
		$arr['status'] = ($sendMail) ? true : false;
		$arr['message'] = ($sendMail) ? $message : 'error occured while performing the operation,please check your network and try again later.';
		echo json_encode($arr);
		return;
	}

	public function changePassword()
	{
		//fetch the customer dedtils first
		$email = trim($this->input->post('mem_email',true));
		$otp = trim($this->input->post('reset_otp',true));
		$password = trim($this->input->post('reset_password',true));
		if (!$email) {
			displayJson(false,'please provide email address');
			return;
		}
		if (!$otp) {
			displayJson(false,'there is no otp provided');
			return;
		}

		loadClass($this->load,'customer');
		$customer =$this->customer->getWhere(array('email'=>$email));
		$customer = is_array($customer)?$customer[0]:$customer;
		if (!$customer) {
			displayJson(false,'sorry, an invalid operation...');
			return;
		}
		if (!$this->verifyPasswordOTP($customer,$otp)) {
			displayJson(false,'sorry an invalid code was provided.');
			return;
		}
		$password = $this->hash_created->encode_password($password);
		$newUser = $this->user->getWhere(array('user_table_id'=>$customer->ID,'user_type'=>'customer'),$count,0,1,false);
		if (!$newUser){
			displayJson(false,"sorry,user can't be verified...");
			return;
		}
		$userID = $newUser[0]->ID;
		$cust = new User(array('ID'=>$userID,'password'=>$password));
		if (!$cust->update()) {
			displayJson(false,"an error occured while resetting password");
			return;		
		}
		$customer->disableAllPasswordOTPs();
		$accSubject = "9jaCashBack Password Recovery Success";
		$this->mailer->sendPasswordResetSuccess($customer);
		displayJson(true,"Your password has been reset! You may now login.");
		return;
	}

	private function verifyPasswordOTP($customer,$otp)
	{
		// the otp can only last 1hr
		$otp =$this->db->conn_id->escape_string($otp);
		$query="select * from password_otp where customer_id=? and otp=? and status=0 and timestampdiff(MINUTE,date_created,current_timestamp) <=120 order by ID desc limit 1";
		$result = $this->db->query($query,array($customer->ID,$otp));
		$result = $result->result_array();
		return $result;
	}

	// here is where user can go to their mail and use a link to reset mail
	public function forgetPassword(){
		if(isset($_POST) && count($_POST) > 0 && !empty($_POST)){
			if($_POST['task'] == 'reset'){
				$email = trim($this->input->post('email',true));
				if (!isNotEmpty($email)) {
			        echo createJsonMessage('status',false,"message","empty field detected.please fill all required field and try again");
			        return;
			    }
				if(filter_var($email, FILTER_VALIDATE_EMAIL) == FALSE){
					$arr['status'] = false;
					$arr['message'] = 'email is not valid';
					echo json_encode($arr);
					return;
				}
				$find = $this->user->find($email);
				if(!$find){
					$arr['status'] = false;
					$arr['message'] = 'the email address appears not to be on our platform...';
					echo json_encode($arr);
					return;
				}
				$this->load->library('email');
				$emailObj = $this->email;
				$sendMail = (sendMailToRecipient($emailObj,$email,'Request to Reset your Password. !',2)) ? true : false;
				$message = "A link to reset your password has been sent to $email.If you don't see it, be sure to check your spam folders too!";
				$arr['status'] = ($sendMail) ? true : false;
				$arr['message'] = ($sendMail) ? $message : 'error occured while performing the operation,please check your network and try again later.';
				echo json_encode($arr);
				return;
			}
			else if ($_POST['task'] == 'update'){
				if(isset($_POST['email'], $_POST['email_hash']))
                {
                	if($_POST['email_hash'] !== sha1($_POST['email'] . $_POST['email_code'])){
                		// either a hacker or they changed their mail in the mail field, just die
	                    $arr['status'] = false;
						$arr['message'] = 'Oops,error updating your password';
						echo json_encode($arr);
						return;
                	}
                	$new = trim($_POST['password']);
				    $confirm = trim($_POST['confirm_password']);
				    $email = trim($_POST['email']);
				    $dataID = $email;

				    if (!isNotEmpty($new,$confirm)) {
				        echo createJsonMessage('status',false,"message","empty field detected.please fill all required field and try again");
				        return;
				    }

				    if ($new !== $confirm) {
				       echo createJsonMessage('status',false,'message','new password does not match with the confirmation password');return;
				    }
				    $this->load->model('entities/user');
				    // i wanna look into this later to find a way for member to reset password
				    if($this->webSessionManager->getCurrentUserProp('user_type') == 'student_biodata'){
				    	loadClass($this->load, 'student_biodata');
				    	$student = $this->student_biodata->getWhere(array('email' => $email),$count,0,1,false);
				    	if($student){
				    		$student = $student[0];
				    		$dataID = $student->ID;
				    	}else{
				    		exit("Sorry, it seems the user doesn't have an email account...");
				    	}
				    }
				    $updatePassword = $this->user->updatePassword($dataID,$new);
				    if($updatePassword){
				    	$arr['status'] = true;
						$arr['message'] = 'your password has been reset! You may now login.';
						echo json_encode($arr);
						return;
				    }else{
				    	$senderEmail = appConfig('company_email');
						$arr['status'] = false;
						$arr['message'] = "error occured while updating your password. Please contact Administrator {$senderEmail}";
						echo json_encode($arr);
						return;
				    }
                    
                }
                
			}
		}
		$this->forget();
	}

	public function verify($email,$hash,$type){
		if(isset($email,$hash,$type)){
			$email = urldecode(trim($email));
			$email = str_replace(array('~az~','~09~'),array('@','.com'),$email);
			$hash = urldecode(trim($hash));
			$email_hash = sha1($email . $hash);

			$user = $this->user->find($email);
			$data = array();
			if(!$user){
				$data['error'] = 'sorry we don\'t seems to have that email account on our platform.';
				$this->load->view('verify',$data);return;
			}

			$check = md5(appConfig('salt') . $email) == $hash;
			if(!$check){
				$data['error'] = 'there seems to be an error in validating your email account,try again later.';
				$this->load->view('verify',$data);return;
			}

			if($user && $check){
				$mailType = appConfig('type');
				if($mailType[$type] == 'register'){
					$id = $this->user->data()[0]['ID'];
					$result = $this->user->updateStatus($id,$email);
					$data['type'] = $mailType[$type];
					if($result){
						//sending the login details to users
						$this->load->library('email');
						$emailObj = $this->email;
						$accSubject = appConfig('company_name'). " authentication summary details...";
						$sendMail = (sendMailToRecipient($emailObj,$email,$accSubject,3)) ? true : false;
						if($sendMail){
							$data['success'] = "Your Account has been verified, welcome on board.<br /><br />Thank you!";
						}else{
							$data['error'] = 'There is an error in network connection, please try again later...';
						}
						 
					}else{
						$data['error'] = 'There seems to be an error in performing the operation...';
					}
				}else if($mailType[$type] == 'forget'){
					$data['type'] = $mailType[$type];
					$data['email_hash'] = $email_hash;
					$data['email_code'] = $hash;
					$data['email'] = $email;
				}
				$this->load->view('verify',$data);return;
			}
			
		}
	}

	private function generateHashRef($type=''){
		$hash = "#".randStrGen(8).randStrGen(10).date("s"); //  the total should be 20 in character
		$ref = randStrGen(10);
		$result = array('receipt' => $hash,'reference' => $ref);
		return $result[$type];
	}

	public function logout(){
		$link ='';
		$base = base_url();
		$this->webSessionManager->logout();
		$path = $base.$link;
		// destroying the cookie if exist
		$id = $this->webSessionManager->getCurrentUserProp('ID');
		if($this->cookie_created->exists($this->config->item('cookie_name'))){
        	$this->db->delete('users_session', array('user_id' => $id));
        	// $this->cookie_created->delete($this->config->item('cookie_name'));
        	delete_cookie($this->config->item('cookie_name'));
        }
		header("location:$path");exit;
	}

}
 ?>