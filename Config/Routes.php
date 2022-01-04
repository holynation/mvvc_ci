<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (file_exists(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Api');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(false);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */
 
// We get a performance increase by specifying the default
// route since we don't have to scan directories.
$routes->get('/', 'Api::index');

// The route for the application mobile API is here
$routes->group('api',['filter'=>'apiAuth'], function($routes){
    $routes->add('(:any)','Api::mobileApi/$1');
    $routes->add('(:any)/(:any)','Api::mobileApi/$1/$2');
    $routes->add('(:any)/(:any)','Api::mobileApi/$1/$2/$3');
});

// The route for normal operations for the backend
$routes->group('vc/create',function($routes){
    $routes->add('(:any)', 'ViewController::create/$1');
    $routes->add('(:any)/(:any)', 'ViewController::create/$1/$2');
    $routes->add('(:any)/(:any)/(:any)', 'ViewController::create/$1/$2/$3');
    $routes->add('(:any)/(:any)/(:any)/(:any)', 'ViewController::create/$1/$2/$3/$4');
});

$routes->group('vc', function($routes){
    $routes->add('resetPassword/(:any)','ViewController::resetPassword/$1');
    $routes->add('changePassword','ViewController::changePassword');
    $routes->add('changePassword/(:any)','ViewController::changePassword/$1');
    $routes->add('(:any)','ViewController::view/$1');
    $routes->add('(:any)/(:any)','ViewController::view/$1/$2');
    $routes->add('(:any)/(:any)/(:any)','ViewController::view/$1/$2/$3');
});

$routes->group('mc', function($routes){
    $routes->add('(:any)','ModelController::view/$1');
    $routes->add('(:any)/(:any)','ModelController::view/$1/$2');
    $routes->add('(:any)/(:any)/(:any)','ModelController::view/$1/$2/$3');
    $routes->add('(:any)/(:any)/(:any)/(:any)','ModelController::view/$1/$2/$3/$4');
    $routes->add('(:any)/(:any)/(:any)/(:any)/(:any)','ModelController::view/$1/$2/$3/$4/$5');
});

$routes->group('ac', function($routes){
    $routes->add('(:any)','ActionController/$1');
    $routes->add('(:any)/(:any)','ActionController/$1/$2');
    $routes->add('(:any)/(:any)/(:any)','ActionController/$1/$2/$3');
});

$routes->add('delete/(:any)/(:any)','ActionController::delete/$1/$2');
$routes->add('delete/(:any)/(:any)/(:any)','ActionController::delete/$1/$2/$3');
$routes->add('truncate/(:any)','ActionController::truncate/$1');

$routes->add('edit/(:any)/(:any)','ActionController::edit/$1/$2');




/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (file_exists(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
