<?php
namespace Ssg\Model;

use \Ssg\Core\Config;
use \Ssg\Core\SDP;
use \Ssg\Core\Model;
use \Ssg\Core\DatabaseFactory;
use \Psr\Log\LoggerInterface;
use \Psr\Log\NullLogger;
use \Exception;
use \stdClass;

/**
 * ServiceManagerModel - offers utility functions to manage service
 *
 */
class ServiceModel extends Model
{
	/**
     * Construct this object by extending the basic Model class
     */
    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
    }
	
	/**
     * Enable service
     *
     * @param string $service_id the service id
	 * @return array containing query result and service data
     */
	public function enable($service_id)
	{
		//get the serivce parameters from the database 
		$response =  self::getService($service_id);
		
		//confirm the status of the service// not successful
		if ($response['result'] != 0) {
			return $response; 
		}
		
		//extract service data
		$service_data = $response['service'];
		
		if ($service_data->status == Config::get('SMS_SERVICE_ON')) {
			 return array('result' => 9 , 'resultDesc' => 'Service already enabled.', 'service' => $service_data); 
		}
		
		//check whether the service requires sending a request to SDP for on demand service type
		if ($service_data->service_type == Config::get('SMS_ON_DEMAND_SERVICE_TYPE')) {
			//send request to SDP if application 	
			$response = self::sendStartSmsNotification($service_data);
			
			//success
			if ($response['result'] == 0) {
				$service_data->status = Config::get('SMS_SERVICE_ON'); // activate
			} else {
				return $response; // return as is
			}
		}
		else
		{
			$service_data->status = Config::get('SMS_SERVICE_ON'); // activate
		}
		
		$response['service']=$service_data;
		
		//update the database
		$response = self::saveServiceStatus($response);
		
		//update the configuration file - Future task
		
		//return 
		return $response;
	}
	
	/**
     * Disable service
     *
     * @param string $service_id service id
	 * @return bool TRUE if enable is successful, FALSE if enable fails
     */
	public function disable($service_id)
	{
		//get the serivce parameters from the database 
		$response =  self::getService($service_id);
		
		//confirm the status of the service
		if($response['result'] != 0) // not successful
		{
			return $response; 
		}
		
		//extract service data
		$service_data = $response['service'];
		
		if($service_data->status == Config::get('SMS_SERVICE_OFF'))
		{
			 return array('result' => 9 , 'resultDesc' => 'Service already disabled.', 'service' => $service_data); 
		}
		
		//check whether the service requires sending a request to SDP for on demand service type
		if($service_data->service_type == Config::get('SMS_ON_DEMAND_SERVICE_TYPE')) 
		{	
			//send request to SDP if application 	
			$response = self::sendStopSmsNotification($service_data);
			
			if($response['result'] == 0) //success
			{
				$service_data->status = Config::get('SMS_SERVICE_OFF'); // disable
			}
			else
			{
				return $response; // return as is
			}
		}
		else
		{
			$service_data->status = Config::get('SMS_SERVICE_OFF'); // disable
		}
		
		$response['service'] = $service_data;
		
		//update the database
		$response = self::saveServiceStatus($response);
		
		//update the configuration file - Future task
		
		//return 
		return $response;
	}
	
	
	/**
     * sendStartSmsNotification to SDP
     *
     * @param array $service_data service data to be used in sending request
	 * @return bool TRUE if successful an FALSE if it fails
     */
	private function sendStartSmsNotification($service_data)
	{
		//generate correlator - change this to call the generate correlator method
		$service_data->correlator = date("YmdHis"); 
		
		//send the request to SDP
		$response = SDP::startSmsNotification($this->logger, $service_data->service_id, $service_data->service_endpoint, 
		$service_data->correlator, $service_data->short_code, $service_data->criteria);
		
		//check response // success
		if($response['ResultCode'] == 0 ) {
			return array('result' => 0, 'resultDesc' => 'Successful.', 'service' => $service_data, 'sdp_data' => $response); 
		}
		
		//return 
		return  array('result' => 1, 
			'resultDesc' => 'Start sms failed('.$response['ResultCode'].' - '.$response['ResultDesc'].' - '.$response['ResultDetails'].').', 
			'service' => $service_data, 'sdp_data' => $response); ;
	}
	
	
	/**
     * sendStopSmsNotification to SDP
     *
     * @param array $service_data service data to be used in sending request
	 * @return bool TRUE if successful an FALSE if it fails
     */
	private function sendStopSmsNotification($service_data)
	{
		//send the request to SDP
		$response = SDP::stopSmsNotification($this->logger, $service_data->service_id, $service_data->correlator);
		
		//check response
		if($response['ResultCode'] == 0 ) // success
		{
			return array('result' => 0, 'resultDesc' => 'Successful.', 'service' => $service_data, 'sdp_data' => $response); 
		}
		//return 
		return  array('result' => 1,'resultDesc' => 'Stop sms failed('.$response['ResultCode'].' - '.$response['ResultDesc'].' - '.$response['ResultDetails'].').', 'service' => $service_data, 'sdp_data' => $response); ;
	}
	
	
	/**
     * generateCorrelator generate correlator to be used in sending request to SDP
     *
	 * @return string correlator
     */
	private function generateCorrelator()
	{
		return date("YmdHis"); 
	}
	
	
	/**
     * Get service data
     *
     * @param string $service_id service id
	 * @return array containing query result and service data
     */
	public function getService($service_id)
	{	
		//get the database connection
		$database=null;
		try {
			$database = DatabaseFactory::getFactory()->getConnection();
		} catch (Exception $ex) {
			return  array('result' => 3, 'resultDesc' => 'Cannot connect to the database. Error: '.$ex->getMessage()); 
		}
		
		//prepare and execute the query
		try {		
			$sql = "SELECT * FROM tbl_services WHERE service_id = :service_id LIMIT 1";
			$query = $database->prepare($sql);
			$bind_parameters = array(':service_id' => $service_id);
			
			if ($query->execute($bind_parameters)) {
				$service = $query->fetch();
				if ($query->rowCount() < 1) {	
				   return array('result' => 1, 'resultDesc' => 'Service with id '.$service_id.' not found.', 'service' => new stdClass()); 
				}else{
					return array('result' => 0, 'resultDesc' => 'Service found.', 'service' => $service); 
				}
			} else {	
				$this->logger->error(
					'{class_mame}|{method_name}|{service_id}|error executing the query|{error}|{query}|bind_parameters:{bind_params}',
					array(
						'class_mame'=>__CLASS__,
						'method_name'=>__FUNCTION__,
						'error'=>$database->errorCode(),
						'query'=>$sql,
						'bind_params'=>json_encode($bind_parameters)
					)
				);
				return  array('result' => 5, 'resultDesc' => 'Error executing a query.'); 
			}
		} catch (PDOException $e) {
			return  array('result' => 4, 'resultDesc' => 'Error executing a query. Error: '.$e->getMessage()); 
		}
		
        return array('result' => 7, 'resultDesc' => 'Unknown error', 'service' => new stdClass()); 
	}
	
	
	/**
     * Update the service table to indicate the new status and the correlator.
     *
     * @param string $service_data service data
	 * @return array containing query result and service data
     */
	private function saveServiceStatus($response)
	{
		//get the parameters to be used in saving 
		$service_id = $response['service']->service_id;
		$correlator =$response['service']->correlator;
		$status =$response['service']->status;
		
		//initialize the database connection
		$database=null;
		$errorCode='';
		try {
			$database = DatabaseFactory::getFactory()->getConnection();
		} catch (Exception $ex) {
			$this->logger->error(
				'{class_mame}|{method_name}|{service_id}|cannot connect to database|{exception}',
				array(
					'class_mame'=>__CLASS__,
					'method_name'=>__FUNCTION__,
					'service_id'=>$service_id,
					'exception'=>$ex->getMessage()
				)
			);
			return  array('result' => 3, 'resultDesc' => 'Cannot connect to the database. Error: '.$ex->getMessage()); 
		}
		
		//saving the data
		try{
			$database->beginTransaction();
			$sql='UPDATE tbl_services SET status=:status, correlator=:correlator, last_updated_on = NOW() WHERE service_id=:service_id';
			$query = $database->prepare($sql);
			
			//execute the query and check the status
			if ($query->execute(array(':service_id' => $service_id , ':correlator' => $correlator, ':status' => $status))) {
				$row_count = $query->rowCount();
				$errorCode = $database->errorCode();
				$database->commit();
				
				if ($row_count == 1) {	
					$response['resultDesc'] = 'Saving successful';
					return $response;
				}
				
			}else{
				$this->logger->error(
					'{class_mame}|{method_name}|{service_id}|error executing the query|{query}|bind_parameters:{bind_params}',
					array(
						'class_mame'=>__CLASS__,
						'method_name'=>__FUNCTION__,
						'service_id'=>$service_id,
						'query'=>$sql,
						'bind_params'=>implode(',',array(':service_id' => $service_id , ':correlator' => $correlator, ':status' => $status))
					)
				);
				return  array('result' => 5, 'resultDesc' => 'Error executing a query.'); 
			}
		} catch (PDOException $e) {
			return  array('result' => 4, 'resultDesc' => 'Error executing a query. Error: '.$e->getMessage()); 
		}
		//defauled 
		$response['result'] = 'Saving successful';
		$response['resultDesc'] = 'Saving successful';
		return $response;
	}
	
	
	/**
     * Add a new service (Remember to add configurations in the configuration file).
     *
     * @param string $service_data service data
	 * @return array containing query result and service data
     */
	public function addService($service_data)
	{
		//initialize service data
		$service_id="";
		$service_name="";
		$service_type="";
		$short_code="";
		$criteria="";
		$service_endpoint="";
		$delivery_notification_endpoint="";
		$interface_name="";
		$correlator="";
		$status=0;
		$last_updated_by=0;
		
		//populate the data with the request data
		if(isset($service_data['service_id'])) $service_id=$service_data['service_id'];
		if(isset($service_data['service_name'])) $service_name=$service_data['service_name'];
		if(isset($service_data['service_type'])) $service_type=$service_data['service_type'];
		if(isset($service_data['short_code'])) $short_code=$service_data['short_code'];
		if(isset($service_data['criteria'])) $criteria=$service_data['criteria'];
		if(isset($service_data['service_endpoint'])) $service_endpoint=$service_data['service_endpoint'];
		if(isset($service_data['delivery_notification_endpoint'])) $delivery_notification_endpoint=$service_data['delivery_notification_endpoint'];
		if(isset($service_data['interface_name'])) $interface_name=$service_data['interface_name'];
		if(isset($service_data['correlator'])) $correlator=$service_data['correlator'];
		if(isset($service_data['status'])) $status=$service_data['status'];
		if(isset($service_data['last_updated_by'])) $last_updated_by=$service_data['last_updated_by'];
		
		if ( 
			empty($service_id) || !isset($service_id) || 
			empty($service_name) || !isset($service_name) || 
			empty($service_type) || !isset($service_type) || 
			empty($short_code) || !isset($short_code)  ||
			empty($service_endpoint) || !isset($service_endpoint)  ||
			empty($delivery_notification_endpoint) || !isset($delivery_notification_endpoint)  
		) {
			return  array('result' => 10, 'resultDesc' => 'Missing mandatory data. The following are required; service id, service name, service type, short code, service url, and delivery notification url'); 
		}
		
		$database=null;
		try {
			$database = DatabaseFactory::getFactory()->getConnection();
		} catch (Exception $ex) {
			return  array('result' => 3, 'resultDesc' => 'Cannot connect to the database. Error: '.$ex->getMessage()); 
		}
		
		try {		
			$database->beginTransaction();
			$sql='INSERT INTO tbl_services (service_id, service_name, service_type, short_code, criteria, service_endpoint, delivery_notification_endpoint, interface_name, correlator,status, created_on, last_updated_on, last_updated_by) VALUES (:service_id, :service_name, :service_type, :short_code, :criteria, :service_endpoint, :delivery_notification_endpoint, :interface_name, :correlator, :status, NOW(), NOW(), :last_updated_by)';
			$query = $database->prepare($sql);
			
			$bind_patameters = array(':service_id' => $service_id , ':service_name' => $service_name, ':service_type' => $service_type, ':short_code' => $short_code, ':criteria' => $criteria, ':service_endpoint' => $service_endpoint, ':delivery_notification_endpoint' => $delivery_notification_endpoint, ':interface_name' => $interface_name, ':correlator' => $correlator, ':status' => $status, ':last_updated_by' => $last_updated_by);
			
			$this->logger->debug(
				'{class_mame}|{method_name}|{service_id}|error executing the query|{error}|{query}|bind_parameters:{bind_params}',
				array(
					'class_mame'=>__CLASS__,
					'method_name'=>__FUNCTION__,
					'error'=>$database->errorCode(),
					'query'=>$sql,
					'bind_params'=>json_encode($bind_patameters)
				)
			);	
			
			if ($query->execute($bind_patameters)) {
				//add last insert id, may be used in the next method calls
				$last_insert_id = $database->lastInsertId();
				
				$row_count = $query->rowCount();
				$database->commit();
				
				if ($row_count == 1) {	
					return array('result'=>0, 'resultDesc'=>'Saving successful', '_lastInsertID'=>$last_insert_id );
				}
			} else {	
				$this->logger->error(
					'{class_mame}|{method_name}|{service_id}|error executing the query|{error}|{query}|bind_parameters:{bind_params}',
					array(
						'class_mame'=>__CLASS__,
						'method_name'=>__FUNCTION__,
						'error'=>$database->errorCode(),
						'query'=>$sql,
						'bind_params'=>json_encode($bind_patameters)
					)
				);
				return  array('result' => 5, 'resultDesc' => 'Error executing a query.'); 
			}
		} catch (PDOException $e) {
			return  array('result' => 4, 'resultDesc' => 'Error executing a query. Error: '.$e->getMessage()); 
		}
		$this->logger->error(
			'{class_mame}|{method_name}|{service_id}|error executing the query|{error}|{query}|bind_parameters:{bind_params}',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'error'=>$database->errorCode(),
				'query'=>$sql,
				'bind_params'=>json_encode($bind_patameters)
			)
		);
		
		return array('result'=>1, 'resultDesc'=>'Adding service record failed - '.$errorCode, 'service'=>$service_data);
	} 
	
	
	/**
     * updateService - updates existing service data except status and correlator 
	 * which are manipulated by enable and disable service methods
     *
     * @param string $service_data service data
	 * @return array containing query result and service data
     */
	public function updateService($service_data)
	{	
		//initialize service data
		$id="";
		$service_id="";
		$service_name="";
		$service_type="";
		$short_code="";
		$criteria="";
		$service_endpoint="";
		$delivery_notification_endpoint="";
		$interface_name="";
		$last_updated_by=0;
		
		//populate the data with the request data
		if(isset($service_data['id'])) $id=$service_data['id'];
		if(isset($service_data['service_id'])) $service_id=$service_data['service_id'];
		if(isset($service_data['service_name'])) $service_name=$service_data['service_name'];
		if(isset($service_data['service_type'])) $service_type=$service_data['service_type'];
		if(isset($service_data['short_code'])) $short_code=$service_data['short_code'];
		if(isset($service_data['criteria'])) $criteria=$service_data['criteria'];
		if(isset($service_data['service_endpoint'])) $service_endpoint=$service_data['service_endpoint'];
		if(isset($service_data['delivery_notification_endpoint'])) $delivery_notification_endpoint=$service_data['delivery_notification_endpoint'];
		if(isset($service_data['interface_name'])) $interface_name=$service_data['interface_name'];
		if(isset($service_data['last_updated_by'])) $last_updated_by=$service_data['last_updated_by'];
		
		//check whether ther service exists
		$query_result = self::getService($service_id);
		
		//query failure
		if($query_result['result'] != 0) {
			return $query_result; // return the query response error 
		}
		
		$database=null;
		try {
			$database = DatabaseFactory::getFactory()->getConnection();
		} catch (Exception $ex) {
			return  array('result' => 3, 'resultDesc' => 'Cannot connect to the database. Error: '.$ex->getMessage()); 
		}
		
		try {		
			$database->beginTransaction();
			$sql='UPDATE tbl_services SET service_id=:service_id, service_name=:service_name, service_type = :service_type, short_code = :short_code, criteria = :criteria, service_endpoint = :service_endpoint, delivery_notification_endpoint = :delivery_notification_endpoint, interface_name = :interface_name, last_updated_on=NOW(), last_updated_by = :last_updated_by WHERE id=:id';
			$query = $database->prepare($sql);
		
			$bind_patameters = array(':id' => $id, ':service_id' => $service_id , ':service_name' => $service_name, ':service_type' => $service_type, ':short_code' => $short_code, ':criteria' => $criteria, ':service_endpoint' => $service_endpoint, ':delivery_notification_endpoint' => $delivery_notification_endpoint, ':interface_name' => $interface_name, ':last_updated_by' => $last_updated_by);
			
			$this->logger->debug(
				'{class_mame}|{method_name}|{service_id}|error executing the query|{error}|{query}|bind_parameters:{bind_params}',
				array(
					'class_mame'=>__CLASS__,
					'method_name'=>__FUNCTION__,
					'error'=>$database->errorCode(),
					'query'=>$sql,
					'bind_params'=>json_encode($bind_patameters)
				)
			);	
			
			if ($query->execute($bind_patameters)) {
				$row_count = $query->rowCount();
				$errorCode = $database->errorCode();
				$database->commit();
				
				if ($row_count == 1) {	
					return array('result'=>0, 'resultDesc'=>'Service updated successfully.', 'service'=>$service_data);
				}
			} else {	
				$this->logger->error(
					'{class_mame}|{method_name}|{service_id}|error executing the query|{error}|{query}|bind_parameters:{bind_params}',
					array(
						'class_mame'=>__CLASS__,
						'method_name'=>__FUNCTION__,
						'error'=>$database->errorCode(),
						'query'=>$sql,
						'bind_params'=>json_encode($bind_patameters)
					)
				);
				return  array('result' => 5, 'resultDesc' => 'Error executing a query.'); 
			}
		} catch (PDOException $e) {
			return  array('result' => 4, 'resultDesc' => 'Error executing a query. Error: '.$e->getMessage()); 
		}
		
		return array('result'=>1, 'resultDesc'=>'Updating records failed - '.$errorCode, 'service'=>$service_data);
	} 
	
	
	/**
     * deleteService - deletes the service from the system
	 * Note: Remember to delete the configurations file
     *
     * @param string $service_id service data
	 * @return array containing query result and service data
     */
	public function deleteService($service_id)
	{	
		$database=null;
		try {
			$database = DatabaseFactory::getFactory()->getConnection();
		} catch (Exception $ex) {
			return  array('result' => 3, 'resultDesc' => 'Cannot connect to the database. Error: '.$ex->getMessage()); 
		}
		
		try {		
			$database->beginTransaction();
			$sql='DELETE FROM tbl_services WHERE service_id = :service_id LIMIT 1';
			$query = $database->prepare($sql);
			
			$bind_patameters = array(':service_id' => $service_id);
			
			if ($query->execute($bind_patameters)) {
				
				$row_count = $query->rowCount();
				$errorCode = $database->errorCode();
				$database->commit();
				
				if ($row_count == 1) {	
					return array('result'=>0, 'resultDesc'=>'Service deleted successsfully', 'service'=> new stdClass()); ;
				}
			} else {	
				$this->logger->error(
					'{class_mame}|{method_name}|{service_id}|error executing the query|{error}|{query}|bind_parameters:{bind_params}',
					array(
						'class_mame'=>__CLASS__,
						'method_name'=>__FUNCTION__,
						'error'=>$database->errorCode(),
						'query'=>$sql,
						'bind_params'=>json_encode($bind_patameters)
					)
				);
				return  array('result' => 5, 'resultDesc' => 'Error executing a query.'); 
			}
		} catch (PDOException $e) {
			return  array('result' => 4, 'resultDesc' => 'Error executing a query. Error: '.$e->getMessage()); 
		}
		
		return array('result'=>1, 'resultDesc'=>'No record deleted - '.$errorCode, 'service'=>new stdClass());
	} 
	
	
	/**
     * deleteService - deletes the service from the system
	 * 
	 * @return array containing query result and service data
     */
	public function getServices($service_id='', $service_type='', $short_code='', $start_index=0, $limit=10, $order='DESC')
	{
        $sql = 'SELECT id, service_id, service_name, service_type, short_code, criteria, service_endpoint, delivery_notification_endpoint, interface_name, correlator, status, created_on, last_updated_on, last_updated_by FROM tbl_services WHERE 1 ';
		$parameters = array();
		//include service_id filter
		if (isset($service_id) && !empty($service_id)) {
			$sql= $sql." AND service_id=:service_id";
			$parameters[':service_id']=$service_id;
		}
		//include service_type filter
		if (isset($service_type) && !empty($service_type)) {
			$sql= $sql." AND service_type=:service_type";
			$parameters[':service_type']=$service_type;
		}
		//include short_code filter
		if (isset($short_code) && !empty($short_code)) {
			$sql= $sql." AND short_code=:short_code";
			$parameters[':short_code']=$short_code;
		}
		$query_total = $sql; // copy query to be used to get the total number of reords (without the group by and limit clause)
		$sql= $sql.' ORDER BY id '.$order.' LIMIT '.$start_index.', '.$limit;
		

		
		// add some logic to handle exceptions in this script
		$row_count=0; 
		$total_records=0;
		$services='';
		$database=null;
		try {
			$database = DatabaseFactory::getFactory()->getConnection();
		} catch (Exception $ex) {
			$this->logger->error(
				'{class_mame}|{method_name}|{service_id}|PDOException|{error}|{query}|bind_parameters:{bind_params}',
				array(
					'class_mame'=>__CLASS__,
					'method_name'=>__FUNCTION__,
					'error'=>$e->getMessage()
				)
			);
			return  array('result' => 3, 'resultDesc' => 'Cannot connect to the database. Error: '.$ex->getMessage()); 
		}
		
		try {
			//get total records for pagination
			$query = $database->prepare($query_total);	
			if ($query->execute($parameters)) {
				$total_records = $query->rowCount();
			} else {	
				$this->logger->error(
					'{class_mame}|{method_name}|{service_id}|error executing the query|{error}|{query}|bind_parameters:{bind_params}',
					array(
						'class_mame'=>__CLASS__,
						'method_name'=>__FUNCTION__,
						'error'=>$database->errorCode(),
						'query'=>$sql,
						'bind_params'=>json_encode($parameters)
					)
				);
				return  array('result' => 5, 'resultDesc' => 'Error executing a query.'); 
			}
			
			//get records
			$query = $database->prepare($sql);	
			if ($query->execute($parameters)) {
				// fetchAll() is the PDO method that gets all result rows
		        $services = $query->fetchAll();
				$row_count = $query->rowCount();
				
				if ($row_count >= 0)  {	
					return array('result'=>0, 'resultDesc'=>'Records retrieved successfully.', '_recordsRetrieved' => $row_count, '_totalRecords' => $total_records, 'services'=>$services );
				}
			} else {	
				$this->logger->error(
					'{class_mame}|{method_name}|{service_id}|error executing the query|{error}|query:{query}|bind_params:{bind_params}',
					array(
						'class_mame'=>__CLASS__,
						'method_name'=>__FUNCTION__,
						'error'=>$database->errorCode(),
						'query'=>$sql,
						'bind_params'=>json_encode($parameters)
					)
				);
				return  array('result' => 5, 'resultDesc' => 'Error executing a query.'); 
			}
		} catch (PDOException $e) {
			$this->logger->error(
				'{class_mame}|{method_name}|{service_id}|PDOException|{error}|{query}',
				array(
					'class_mame'=>__CLASS__,
					'method_name'=>__FUNCTION__,
					'error'=>$e->getMessage(),
					'query'=>$sql
				)
			);
			return  array('result' => 4, 'resultDesc' => 'Error executing a query. Error: '.$e->getMessage()); 
		}
		
		return array('result'=>1, 'resultDesc'=>'No records found', 'services'=>$services);
	} 
}
