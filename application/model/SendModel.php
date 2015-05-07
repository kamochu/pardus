<?php

/**
 * SendModel used in sending messages to external server
 *
 */
class SendModel
{
    /**
     * Notify sms process .
     *
     * @param $data mixed the raw request data to be processed
	 * @return int a result indicating processing status
     */
    public static function process($data='')
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
	protected static function decode($data)
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
	protected static function preProcess($data)
	{
		//check for required parameters 
		if(!(isset($data['message']) && isset($data['sender_address']) && isset($data['dest_address'])  && isset($data['service_id'])))
		{
			return array('result'=>13, 'resultDesc'=>'Expected parameters not found.',  'data'=>$data);
		}
		
		// Check service status configuration 0 - OFF and 1 - ON
		if(Config::get('SERVICE_STATUS_'.$data['service_id']) != 1)
		{
			return array('result'=>15, 'resultDesc'=>'Service with id '.$data['service_id'].' is OFF or is not configured correctly.',  'data'=>$data);
		}
		
		//resolve the end point
		$data['notify_endpoint'] = Config::get('SERVICE_DELIVERY_ENDPOINT_'.$data['service_id']); 
		if(!isset($data['notify_endpoint']) || empty($data['notify_endpoint']))
		{
			//use default enmd point
			$data['notify_endpoint'] = Config::get('SEND_SMS_DEFAULT_DELIVERY_NOTIFICATION_ENDPOINT'); 
		}
		
		//check whether short code exists as part of the request data, if not, load service configuration file
		if(!isset($data['sender_address']) || empty($data['sender_address']))
		{
			$data['sender_address'] = Config::get('SERVICE_CODE_'.$data['service_id']); 
		}
		
		//send request to external server
		$send_response= SDP::sendSms($data['service_id'], $data['dest_address'], $data['correlator'], $data['sender_address'],  $data['message'], $data['link_id']);
		$data['sdp_sendsms_result'] = $send_response;
		
		//check send sms response code
		if($send_response['ResultCode'] == 0) // success
		{
			//send the message to external system 
			$data['send_ref_id'] = '4040901'.date('YmdHisu'); //To be modified
			$data['status'] = 2; //send sms successful
			$data['status_desc'] = 'SENT[Message sent]'; //message sent
		}
		else
		{
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
	protected static function save($data)
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
		$database = DatabaseFactory::getFactory()->getConnection();
		$database->beginTransaction();
		$sql='INSERT INTO tbl_outbound_messages (service_id, link_id, linked_incoming_msg_id, dest_address, sender_address, correlator, batch_id, message, notify_endpoint, send_timestamp, send_ref_id, status, status_desc, created_on, last_updated_on) VALUES(:service_id, :link_id, :linked_incoming_msg_id, :dest_address, :sender_address, :correlator, :batch_id, :message, :notify_endpoint, NOW(), :send_ref_id, :status,  :status_desc, NOW(), NOW());';
		$query = $database->prepare($sql);
		
		$query->execute(array(':service_id' => $service_id , ':link_id' => $link_id, ':linked_incoming_msg_id' => $linked_incoming_msg_id, ':dest_address' => $dest_address, ':sender_address' => $sender_address, ':correlator' => $correlator, ':batch_id' => $batch_id, ':message' => $message, ':notify_endpoint' => $notify_endpoint, ':send_ref_id' => $send_ref_id, ':status' => $status, ':status_desc' => $status_desc));
		
		//add last insert id, may be used in the next method calls
		$data['_lastInsertID'] = $database->lastInsertId();
		
		$row_count = $query->rowCount();
		$database->commit();
		
		if ($row_count == 1) 
		{	
			return array('result'=>0, 'resultDesc'=>'Saving successful', 'data'=>$data);
        }
		
		return array('result'=>14, 'resultDesc'=>'Saving record failed', 'data'=>$data);
	}
	
	/**
     * Encode for purpose of pushing to external sources
     *
     * @param $data mixed data to be saved
	 * @return int array indicating the processing status and data after processing
     */
	protected static function encode($data)
	{
		return array('result'=>0, 'resultDesc'=>'Encoding successful', 'data'=>$data);
	}
	
	
	/**
     * Hook - can be used to forward data to an external system (realtime forwarders)
     *
     * @param $data mixed data to be processed
	 * @return int array indicating the processing status and data after processing
     */
	protected static function hook($data)
	{
		return array('result'=>"0", 'resultDesc'=>'Hook execution successful',  'data'=>$data);
	}
}
