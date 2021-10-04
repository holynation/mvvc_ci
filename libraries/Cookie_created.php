<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Cookie_created{

 	public function exists($name){
 		return (isset($_COOKIE[$name])) ? true : false;
 	} 

	public function get($name){
	 	return $_COOKIE[$name];
	}

    public function put($name, $value, $expiry){
  		if(setcookie($name, $value, time() + $expiry, '/')){
  			return true;
  		}
  		return false;
    }

 	public function delete($name){
 	 	$this->put($name, '', time() - 1 );
 	}
}

?>