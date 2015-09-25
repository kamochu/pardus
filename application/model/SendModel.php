<?php
namespace Ssg\Model;

use Ssg\Core\Config;
use Ssg\Core\SDP;
use Ssg\Core\DatabaseFactory;
use Ssg\Core\Model;
use Psr\Log\LoggerInterface;
use \stdClass;

/**
 * SendModel used in sending messages to external server
 *
 */
class SendModel extends Model
{
	/**
     * Construct this object by extending the basic Model class
     */
    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
    }
	
    /**
     * Notify sms process .
     *
     * @param $data mixed the raw request data to be processed
	 * @return int a result indicating processing status
     */
    public function process($data='')
    {
		//decode the data
		$resultData = self::decode($data);
		if($resultData['result'] != 0)
		{
			return $resultData;	
		}	
		
		//preprocess the data
		$resultData = self::preProcess($resultData['data']);
		if($resultData['result'] != 0)
		{
			return $resultData;	
		}	
		
		//save data
		$resultData = self::save($resultData['data']);
		if($resultData['result'] != 0)
		{
			return $resultData;	
		}
		
		//encode data
		$resultData = self::encode($resultData['data']);
		if($resultData['result'] != 0)
		{
			return $resultData;	
		}
		
		//hook
		$resultData = self::hook($resultData['data']);
		if($resultData['result'] != 0)
		{
			return $resultData;	
		}
		
		//overwrite the result desciption (from hook execution succcessful)
		$resultData['resultDesc']="Processing successful";
		return $resultData;
    }
	
	/**
     * Decode the request data
     *
     * @param $data mixed data to be decoded
	 * @return int array indicating the processing status and data after processing
     */
	protected function decode($data)
	{
		//add some logic to normalize the sender and destination address
		// add some logic to get data request post data
		return array('result'=>0, 'resultDesc'=>'Data processing', 'data'=>$data);
	}
	
	/**
     * Data preprocessing before it can be saved. Enriching the message to be saved and forwarded.
     *
     * @param $data mixed data to be preprocessed
	 * @return int array indicating the processing status and data after processing
     */
	protected function preProcess($data)
	{
		//check for required parameters 
		if(!(isset($data['message']) && isset($data['sender_address']) && isset($data['dest_address'])  && isset($data['service_id'])))
		{
			return array('result'=>13, 'resultDesc'=>'Expected parameters not found.',  'data'=>$data);
		}
		
		//get the service data
		$response  = $this->getService($data['service_id']);
		//print_r($response);
		if ($response['result'] != 0) { //retrieving service failed
			$this->logger->debug(
				'{class_mame}|{method_name}|error-loading-service|{result}|{result_desc}',
				array(
					'class_mame'=>__CLASS__,
					'method_name'=>__FUNCTION__,
					'request_data'=>implode('|',$data),
					'result'=>$response['result'],
					'sdp_result_desc'=>$response['resultDesc']
					)
			);
			return $response;
		}
		
		//extract service data from 
		$service = $response['data']; //service data
		
		// Check service status configuration 0 - OFF and 1 - ON
		if ($service->status != 1) {
			return array('result'=>15, 'resultDesc'=>'Service with id '.$data['service_id'].' is OFF or is not configured correctly.',  'data'=>$data);
		}
		
		//resolve the end point
		$data['notify_endpoint'] = $service->delivery_notification_endpoint; 
		if (!isset($data['notify_endpoint']) || empty($data['notify_endpoint'])) {
			//use default enmd point
			$data['notify_endpoint'] = Config::get('SEND_SMS_DEFAULT_DELIVERY_NOTIFICATION_ENDPOINT'); 
		}
		
		//check whether short code exists as part of the request data, if not, load service configuration file
		if (!isset($data['sender_address']) || empty($data['sender_address'])) {
			$data['sender_address'] = $service->short_code;
		}
		
		//send request to external server
		$send_response= SDP::sendSms($this->logger, $data['service_id'], $data['dest_address'], $data['correlator'], $data['sender_address'],  $data['message'], $data['link_id']);
		
		//log the event
		$this->logger->debug(
			'{class_mame}|{method_name}|sdp-result|{request_data}|{sdp_result}|{sdp_result_desc}',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'request_data'=>implode('|',$data),
				'sdp_result'=>$send_response['ResultCode'],
				'sdp_result_desc'=>$send_response['ResultDesc']
				)
		);		
		$data['sdp_sendsms_result'] = $send_response;

		//check send sms response code
		if ($send_response['ResultCode'] == 0) {// success
			//send the message to external system 
			$data['send_ref_id'] =  $send_response['ResultDetails']['result']; //ref id of SDP to be changed with API change
			$data['status'] = 2; //send sms successful
			$data['status_desc'] = 'SENT[Message sent]'; //message sent
		} else {
			$data['status'] = 4; //sending failed
			$data['status_desc'] = 'FAIED[Sending failed. '.$send_response['ResultCode'].' - '.$send_response['ResultDesc'].': '.$send_response['ResultDetails'].']'; //message send failed 
		}
		return array('result'=>"0", 'resultDesc'=>'Preprocessing successful',  'data'=>$data);
	}
	
	
	/**
     * Data saving into the local database
     *
     * @param $data mixed data to be saved
	 * @return int array indicating the processing status and data after processing
     */
	protected function save($data)
	{	
		//initialize the parameters
		$service_id ='';
		$link_id = '';
		$linked_incoming_msg_id = '';
		$dest_address = '';
		$sender_address = '';
		$correlator = '';
		$batch_id = '';
		$message = '';
		$notify_endpoint = '';
		$send_ref_id = '';
		$status = 0;
		$status_desc = '';
		
		//get the data from array
		if(isset($data['service_id'])) $service_id = $data['service_id'];
		if(isset($data['link_id'])) $link_id = $data['link_id'];
		if(isset($data['linked_incoming_msg_id'])) $linked_incoming_msg_id = $data['linked_incoming_msg_id'];
		if(isset($data['dest_address'])) $dest_address = $data['dest_address'];
		if(isset($data['sender_address'])) $sender_address = $data['sender_address'];
		if(isset($data['correlator'])) $correlator = $data['correlator'];
		if(isset($data['batch_id'])) $batch_id = $data['batch_id'];
		if(isset($data['message'])) $message = $data['message'];
		if(isset($data['notify_endpoint'])) $notify_endpoint = $data['notify_endpoint'];
		if(isset($data['send_ref_id'])) $send_ref_id = $data['send_ref_id'];
		if(isset($data['status'])) $status = $data['status'];
		if(isset($data['status_desc'])) $status_desc = $data['status_desc'];
		
		// add some logic to handle exceptions in this script
		$database=null;
		try {
			$database = DatabaseFactory::getFactory()->getConnection();
		} catch (Exception $ex) {
			return  array('result' => 3, 'resultDesc' => 'Cannot connect to the database. Error: '.$ex->getMessage()); 
		}
		
		//saving the data
		try{
			$database->beginTransaction();
			$sql='INSERT INTO tbl_outbound_messages (service_id, link_id, linked_incoming_msg_id, dest_address, sender_address, correlator, batch_id, message, notify_endpoint, send_timestamp, send_ref_id, status, status_desc, created_on, last_updated_on) VALUES(:service_id, :link_id, :linked_incoming_msg_id, :dest_address, :sender_address, :correlator, :batch_id, :message, :notify_endpoint, NOW(), :send_ref_id, :status,  :status_desc, NOW(), NOW());';
			$query = $database->prepare($sql);
			$bind_parameters = array(':service_id' => $service_id , ':link_id' => $link_id, ':linked_incoming_msg_id' => $linked_incoming_msg_id, ':dest_address' => $dest_address, ':sender_address' => $sender_address, ':correlator' => $correlator, ':batch_id' => $batch_id, ':message' => $message, ':notify_endpoint' => $notify_endpoint, ':send_ref_id' => $send_ref_id, ':status' => $status, ':status_desc' => $status_desc);
			
			if ($query->execute($bind_parameters)) {
				//add last insert id, may be used in the next method calls
				$data['_lastInsertID'] = $database->lastInsertId();
				
				$row_count = $query->rowCount();
				$database->commit();
				
				if ($row_count == 1) {	
					return array('result'=>0, 'resultDesc'=>'Saving successful', 'data'=>$data);
				}
				
			} else {
				$this->logger->error(
					'{class_mame}|{method_name}|{service_id}|error executing the query|{query}|bind_parameters:{bind_params}',
					array(
						'class_mame'=>__CLASS__,
						'method_name'=>__FUNCTION__,
						'service_id'=>$service_id,
						'query'=>$sql,
						'bind_params'=>json_encode($bind_parameters)
					)
				);
				return  array('result' => 5, 'resultDesc' => 'Error executing a query.'); 
			}
		} catch (PDOException $e) {
			return  array('result' => 4, 'resultDesc' => 'Error executing a query. Error: '.$e->getMessage()); 
		}
		
		return array('result'=>14, 'resultDesc'=>'Saving record failed', 'data'=>$data);
	}
	
	/**
     * Encode for purpose of pushing to external sources
     *
     * @param $data mixed data to be saved
	 * @return int array indicating the processing status and data after processing
     */
	protected function encode($data)
	{
		return array('result'=>0, 'resultDesc'=>'Encoding successful', 'data'=>$data);
	}
	
	
	/**
     * Hook - can be used to forward data to an external system (realtime forwarders)
     *
     * @param $data mixed data to be processed
	 * @return int array indicating the processing status and data after processing
     */
	protected function hook($data)
	{
		return array('result'=>"0", 'resultDesc'=>'Hook execution successful',  'data'=>$data);
	}
	
	
	private function getService($service_id)
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
					return array('result' => 0, 'resultDesc' => 'Service found.', 'data' => $service); 
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
		
        return array('result' => 7, 'resultDesc' => 'Unknown error', 'data' => new stdClass()); 
	}
}
