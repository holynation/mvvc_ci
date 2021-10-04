<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Hash_created{

	public function make($string, $salt = ''){
 		return hash('sha256', $string . $salt);
 	}

	public function salt($length){
	 	return mcrypt_create_iv($length);
	}

 	public function encode_password($password){
 		return password_hash($password, PASSWORD_BCRYPT, array(
 			'cost'  => 10
 			));		 		
 	}

 	public function decode_password($userData,$fromDb){
 		if($userData != NULL){
 			return password_verify($userData, $fromDb);
 		}

 		return false;		
 	}

 	public function unique(){
 		return $this->make(uniqid());
 	}

}