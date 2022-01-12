<?php 

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use Firebase\JWT\JWT;
use App\Entities\User;

class ApiAuth implements FilterInterface
{

    public function before(RequestInterface $request, $arguments = null)
    {
        // Do something here
        $response = service('response');
        $response->setHeader('Access-Control-Allow-Origin','*');
        $response->setHeader('Access-Control-Allow-Headers','Origin, X-Requested-With, Content-Type, Accept');
        $response->setHeader('Content-Type','application/json');

        if (!$this->validateHeader($request)) {
            return $response->setStatusCode(405)->setJSON(['status'=>false,'message'=>'denied']);
        }
        if (!$this->canProceed($request,$request->getUri()->getSegments())) {
            return $response->setStatusCode(405)->setJSON(['status'=>false,'message'=>'denied']);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do something 
    }

    private function validateHeader($request)
    {
        $apiKey = env('xAppKey');
        return array_key_exists('HTTP_X_APP_KEY', $_SERVER) && $request->getServer('HTTP_X_APP_KEY')==$apiKey;
    }

    private function canProceed($request,$args)
    {
        $isExempted = $this->isExempted($request, $args);
        if ($isExempted) {
            return true;
        }
        return $this->validateAPIRequest();
    }

    private function validateAPIRequest()
    {
        helper('url');
        try{
            $token = getBearerToken();
            $jwtKey = env('jwtKey');
            JWT::$leeway = 10; // $leeway in seconds
            $res=JWT::decode($token,$jwtKey,array('HS256'));
            $id=$res->user_table_id;
            $client = loadClass('client');
            $temp  = new Client(array('ID'=>$id));
            if (!$temp->load() || !$temp->status) {
                return false;
            }
            $ttemp=(object)$temp->toArray();
            $_SERVER['current_user']=$ttemp;
            return true;

        }
        catch(\Exception $e){
            return false;
        }
    }

    private function isExempted($request,$arguments)
    {
        $exemptionList= array('POST/signup','POST/reset_password','POST/change_pass','POST/auth');
        $argument = $arguments[1];
        $argPath=strtoupper($request->getMethod()).'/'.$argument;
        return in_array($argPath, $exemptionList);
    }
}