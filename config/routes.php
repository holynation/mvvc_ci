<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/

$route['default_controller'] = 'naijacashback';
$route['index'] = 'naijacashback';
$route['register'] = 'auth/signup';
$route['login'] = 'auth/login';
$route['forget_password'] = 'auth/forget';
$route['about_us'] = 'naijacashback/about_us';
$route['contact_us'] = 'naijacashback/contact_us';
$route['how_to_play'] = 'naijacashback/how_to_play';
$route['cashbackform'] = 'naijacashback/cashbackform';
$route['cashout'] = 'naijacashback/cashout';
$route['terms'] = 'naijacashback/terms';
$route['privacy'] = 'naijacashback/privacy';
$route['faq'] = 'naijacashback/faq';
$route['payment'] = 'auth/initPayment';
$route['customer/cashback'] = 'viewController/view/customer/cashback';
$route['lastest_cashback'] = 'naijacashback/lastest_cashback';
$route['time_archive'] = 'naijacashback/time_archive';
$route['check_number'] = 'naijacashback/check_number';
$route['daily_winner'] = 'naijacashback/daily_winner';
$route['cashback_with_atm'] = 'naijacashback/cashback_with_atm';
$route['learn'] = 'naijacashback/learn_more';
$route['confirm_ticket'] = 'naijacashback/confirm_ticket';

// this are the backend controller
$route['vc/create/(:any)']='viewController/create/$1';
$route['vc/create/(:any)/(:any)']='viewController/create/$1/$2';
$route['vc/create/(:any)/(:any)/(:any)']='viewController/create/$1/$2/$3';
$route['vc/create/(:any)/(:any)/(:any)/(:any)']='viewController/create/$1/$2/$3/$4';
$route['edit/(:any)/(:any)'] = 'viewController/edit/$1/$2';
$route['extra/(:any)/(:any)'] = 'viewController/extra/$1/$2';
$route['extra/(:any)/(:any)/(:any)'] = 'viewController/extra/$1/$2/$3';
$route['vc/resetPassword/(:any)'] = 'viewController/resetPassword/$1/$2';
$route['vc/changePassword'] = 'viewController/changePassword';
$route['vc/changePassword/(:any)'] = 'viewController/changePassword/$1/$2';
$route['vc/secure/(:any)'] = 'viewController/secure/$1';
$route['vc/secure/(:any)/(:any)'] = 'viewController/secure/$1/$2';
$route['vc/secure/(:any)/(:any)/(:any)'] = 'viewController/secure/$1/$2/$3';
$route['vc/(:any)'] = 'viewController/view/$1';
$route['vc/(:any)/(:any)'] = 'viewController/view/$1/$2';
$route['vc/(:any)/(:any)/(:any)'] = 'viewController/view/$1/$2/$3';
$route['vc/(:any)/(:any)/(:any)/(:any)'] = 'viewController/view/$1/$2/$3/$4';
$route['mc/(:any)'] = 'modelController/$1';
$route['mc/(:any)/(:any)'] = 'modelController/$1/$2';
$route['mc/(:any)/(:any)/(:any)'] = 'modelController/$1/$2/$3';
$route['mc/(:any)/(:any)/(:any)/(:any)'] = 'modelController/$1/$2/$3/$4';
$route['mc/(:any)/(:any)/(:any)/(:any)/(:any)'] = 'modelController/$1/$2/$3/$4/$5';
$route['ac/(:any)'] = 'actionController/$1';
$route['delete/(:any)/(:any)']='actionController/delete/$1/$2';
$route['delete/(:any)/(:any)/(:any)']='actionController/delete/$1/$2/$3';
$route['ac/(:any)/(:any)'] = 'actionController/$1/$2';
$route['ac/(:any)/(:any)/(:any)'] = 'actionController/$1/$2/$3';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;
