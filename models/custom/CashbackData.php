<?php 
/**
* This is the class that manages all information and data retrieval needed by the home section of this application.
*/
class CashbackData extends CI_Model
{
    private $public;
    function __construct()
    {
        parent::__construct();
    }

    public function loadHomeInfo()
    {
        #get the information for home page
        $result = array();
        return $result;
    }

    public function postCashBack($args){
        if($args){
            $this->db->trans_begin();
            if(!$this->db->insert('cashback', $args)){
                $this->db->trans_rollback();
                return false;
            }
            $this->db->trans_commit();
            return true;
        }   
    }

    public function postCashBackPayment($args,$cashback_id){
        if($args){
            $this->db->trans_begin();
            if(!$this->db->insert('payment', $args)){
                $this->db->trans_rollback();
                return false;
            }

            if($this->db->update('cashback', array('payment_status'=>'1'), array('ID'=>$cashback_id))){
                $this->db->trans_commit();
                return true;
            }
            return false;   
        }   
    }

    public function getCashback($fullname){
        $query = "select * from cashback where customer_name = '$fullname' order by date_created desc limit 1";
        $result = $this->db->query($query);
        if($result->num_rows() <= 0){
            return false;
        }
        return $result->result_array();
    }

    public function checkPayment($cashback_id){
        $query = "select * from cashback where ID = '$cashback_id' and payment_status = '1'";
        $result = $this->db->query($query);
        if($result->num_rows() <= 0){
            return false;
        }
        return true;
    }

    public function getRandomPercentage(){
        $query = "select id,percentage_value as value from time_percentage order by rand() limit 1";
        $result = $this->db->query($query);
        return $result->result_array()[0]['value'];
    }

    public function getDailyTimestamp($time_id){
        $query = "SELECT id,time_order,hour(time_stamp_perm) as t_hour,minute(time_stamp_perm) as t_minute, second(time_stamp_perm) as t_second, time_stamp_perm as tp_timer,time_stamp_in_24 as timer24 from timestamp_perm where id = $time_id and status = '1' order by id asc limit 1";
        $temp =  $this->db->query($query);
        if($temp->num_rows() <= 0){
            return false;
        }
        $result = array();
        $current = $temp->result_array()[0];
        $current['percentage'] = $this->getRandomPercentage();
        $result[] = $current;
        return $result[0];
    }

    public function getLastestCashbackTime(){
        $query = "SELECT id,hour(timestamp_perm) as t_hour,minute(timestamp_perm) as t_minute, second(timestamp_perm) as t_second,timestamp_perm as tp_timer,percentage from daily_timestamp order by date_created desc limit 1";
        $result = $this->db->query($query);
        if($result->num_rows() <= 0){
            return false;
        }
        return $result->result_array()[0];
    }

    public function getAllCashbackTime(){
        $query = "SELECT id,timestamp_perm as tp_timer,date_created as date_clocked from daily_timestamp order by date_created desc";
        $result = $this->db->query($query);
        if($result->num_rows() <= 0){
            return false;
        }
        return $result->result_array();
    }

    public function checkMyNumber($phone_number){
        loadClass($this->load, 'cashback');
        $cashback = new Cashback();
        $result = $cashback->checkMyLuckyNumber($phone_number);
        // print_r($result);exit;
        return $result;
    }

    public function getDailyWinner(){
        loadClass($this->load, 'cashback');
        $cashback = new Cashback();
        $result = $cashback->checkDailyLuckyNumber();
        return $result;
    }
}
