<?php 
/**
* this class help save the configuration needed by the form in order to use a single file for all the form code.
* you only need to include the configuration data that matters. the default value will be substituted for other configuration value that does not have a key  for a particular entity.
*/
class FormConfig extends CI_Model
{
	private  $insertConfig=array();
	private $updateConfig;
	public $currentRole;
	
	function __construct($currentRole=false)
	{
		$this->currentRole=$currentRole;
		if ($currentRole) {
			$this->buildInsertConfig();
			$this->buildUpdateConfig();
		}
		
	}

	/**
	 * this is the function to change when an entry for a particular entitiy needed to be addded. this is only necessary for entities that has a custom configuration for the form.Each of the key for the form model append insert option is included. This option inculde:
	 * form_name the value to set as the name and as the id of the form. The value will be overridden by the default value if the value if false.
	 * has_upload this field is used to determine if the form should include a form upload section for the table form list
	 * hidden this  are the field that should be pre-filled. This must contain an associative array where the key of the array is the field and the value is the value to be pre-filled on the value.
	 * showStatus field is used to show the status flag on the form. once the value is true the status field will be visible on the form and false otherwise.
	 * exclude contains the list of entities field name that should not be shown in the form. The filed for this form will not be display on the form.
	 * submit_label is the label that is going to be displayed on the submit button
	 * 	table_exclude is the list of field that should be removed when displaying the table.
	 * table_action contains an associative arrays action to be displayed on the action table and the link to perform the action.
	 * the query paramete is used to specify a query for getting the data out of the entity
	 * upload_param contains the name of the function to be called to perform
	 * 
	 */ 
	private function buildInsertConfig()
	{
		$this->insertConfig= array
		(
			'customer'=>array
			(
				'show_add' => false,
				'exclude'=>array(),
				'table_exclude'=>array(),
				'table_action'=>array('enable'=>'getEnabled'),
				'header_title'=>'Manage registered user(s)',
				'table_title'=>'Manage registered user(s)',
				'has_upload'=>true,
				'hidden'=>array(),
				'show_status'=>false,
				'table_attr' => array('id'=> 'datatable-buttonss'),
				'search'=>array('fullname'),
				'search_placeholder'=>array('Search...'),
				'order_by' => array('fullname'),
				'query'=>"select customer.ID,fullname,customer.phone_number,email,customer_path,status from customer"
			),
			'admin'=>array
			(
				'table_title' => 'Admin Table',
				'show_status' => true,
				'table_exclude' => array('middlename'),
				'table_action'=> array('enable'=>'getEnabled',"edit"=>"edit/admin"),
				'header_title' => 'Manage Admin(s)'
				// 'search'=>array('phone_number','firstname','middlename','lastname'),
				// 'search_placeholder'=> array('phone num','admin name'),
			),
			'role'=>array(
				'query'=>'select * from role where ID<>1'
			),
			'news'=>array(
				'show_add'=> true
			),
			'payment'=>array(
				'show_add'=> false,
				'table_action' => array(),
				'table_exclude'=>array('cashback_id'),
			),
			'activate_payment'=>array(
				'show_add' => false,
				'table_action' => array(),
				'table_attr' => array('id'=>'datatable-buttons-customer')
			),
			'daily_timestamp'=>array(
				'show_add'=> false,
				'table_exclude' => array('time_stamp_in_24','status'),
				'table_action' => array(),
				'table_title' => 'Clock Archieve'
			),
		//add new entry to this array
		);
	}

	private function getFilter($tablename)
	{
		$result= array(
			// 'payment'=>array(
			// 	array(
			// 		'filter_label'=>'date_created',
			// 		'filter_display'=>'date_created',
			// 		'preload_query'=>'select distinct cast(date_created as date) as id,cast(date_created as date) as value from payment order by ID desc',
			// 	)
				
			// )
		);

		if (array_key_exists($tablename, $result)) {
			return $result[$tablename];
		}
		return false;
	}
	/**
	 * This is the configuration for the edit form of the entities.
	 * exclude take an array of fields in the entities that should be removed from the form.
	 */
	private function buildUpdateConfig()
	{
		$userType = $this->webSessionManager->getCurrentUserProp('user_type');
		$exclude = array();
		if($userType == 'customer'){
			$exclude = array('email','customer_path');
		}
		$this->updateConfig= array
		(
		'customer'=>array
			(
				'exclude'=>$exclude,		
			),
		//add new entry to this array
		);
	}
	function getInsertConfig($entities)
	{
		if (array_key_exists($entities, $this->insertConfig)) {
			$result=$this->insertConfig[$entities];
			if (($fil=$this->getFilter($entities))) {
				$result['filter']=$fil;
			}
			return $result;
		}
		if (($fil=$this->getFilter($entities))) {
			return array('filter'=>$fil);
		}
		return false;
	}

	function getUpdateConfig($entities)
	{
		if (array_key_exists($entities, $this->updateConfig)) {
			$result=$this->updateConfig[$entities];
			if (($fil=$this->getFilter($entities))) {
				$result['filter']=$fil;
			}
			return $result;
		}
		if (($fil=$this->getFilter($entities))) {
			return array('filter'=>$fil);
		}
		return false;
	}
}
 ?>