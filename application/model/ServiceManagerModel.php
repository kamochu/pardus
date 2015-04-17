<?php

/**
 * ServiceManagerModel - offers utility functions to manage service
 *
 */
class ServiceManagerModel
{
	/**
     * Enable service
     *
     * @param string $service_id the service id
	 * @return array containing query result and service data
     */
	public static function enable($service_id)
	{
		//get the serivce parameters from the database 
		$response =  self::getServiceData($service_id);
		
		//confirm the status of the service
		if($response['result'] != 0) // not successful
		{
			return $response; 
		}
		
		//extract service data
		$service_data = $response['service'];
		
		if($service_data->status == Config::get('SMS_SERVICE_ON'))
		{
			 return array('result' => 9 , 'resultDesc' => 'Service already enabled.', 'service' => $service_data); 
		}
		
		//check whether the service requires sending a request to SDP for on demand service type
		if($service_data->service_type == Config::get('SMS_ON_DEMAND_SERVICE_TYPE')) 
		{
			//send request to SDP if application 	
			$response = self::sendStartSmsNotification($service_data);
			if($response['result'] == 0) //success
			{
				$service_data->status = Config::get('SMS_SERVICE_ON'); // activate
			}
			else
			{
				return $response; // return as is
			}
		}
		else
		{
			$service_data->status = Config::get('SMS_SERVICE_ON'); // activate
		}
		
		//update the database
		$response = self::saveServiceData($service_data);
		
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
	public static function disable($service_id)
	{
		//get the serivce parameters from the database 
		$response =  self::getServiceData($service_id);
		
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
		
		//update the database
		$response = self::saveServiceData($service_data);
		
		//update the configuration file - Future task
		
		//return 
		return $response;
	}
	
	
	/**
     * Data saving into the local database
     *
     * @param string $service_id service id
	 * @return array containing query result and service data
     */
	private static function getServiceData($service_id)
	{	
		 $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "SELECT service_id, service_name, service_type, short_code, service_endpoint, 
				citeria, delivery_notification_endpoint, interface_name, correlator, status
                FROM tbl_services WHERE service_id = :service_id LIMIT 1";
        $query = $database->prepare($sql);
        $query->execute(array(':service_id' => $service_id));

        $service = $query->fetch();

        if ($query->rowCount() < 1)
		{	
           return array('result' => 1, 'resultDesc' => 'Service with id '.$service_id.' not found.', 'service' => new stdClass()); 
        }
		
        return array('result' => 0, 'resultDesc' => 'Service found.', 'service' => $service); 
	}
	
	
	/**
     * Update the service table to indicate the new status.
     *
     * @param string $service_data service data
	 * @return array containing query result and service data
     */
	private static function saveServiceData($service_data)
	{
		//get the parameters to be used in saving 
		$service_id = $service_data->service_id;
		$correlator =$service_data->correlator;
		$status =$service_data->status;
		
		// add some logic to handle exceptions in this script
		$database = DatabaseFactory::getFactory()->getConnection();
		$database->beginTransaction();
		$sql='UPDATE tbl_services SET status=:status, correlator=:correlator, last_updated_on = NOW() WHERE service_id=:service_id';
		$query = $database->prepare($sql);
		
		$query->execute(array(':service_id' => $service_id , ':correlator' => $correlator, ':status' => $status));
		
		$row_count = $query->rowCount();
		$errorCode = $database->errorCode();
		$database->commit();
		
		if ($row_count == 1) 
		{	
			return array('result'=>0, 'resultDesc'=>'Saving successful', 'service'=>$service_data);
        }
		return array('result'=>1, 'resultDesc'=>'Saving record failed - '.$errorCode, 'service'=>$service_data);
	}
	
	/**
     * sendStartSmsNotification to SDP
     *
     * @param array $service_data service data to be used in sending request
	 * @return bool TRUE if successful an FALSE if it fails
     */
	private static function sendStartSmsNotification($service_data)
	{
		//generate correlator - change this to call the generate correlator method
		$service_data->correlator = date("YmdHis"); 
		
		//send the request to SDP
		$response = SDP::startSmsNotification($service_data->service_id, $service_data->service_endpoint, 
		$service_data->correlator, $service_data->short_code, $service_data->citeria);
		
		//check response
		if($response['ResultCode'] == 0 ) // success
		{
			return array('result' => 0, 'resultDesc' => 'Successful.', 'service' => $service_data); 
		}
		
		//return 
		return  array('result' => 1, 
			'resultDesc' => 'Start sms failed ('.$response['ResultCode'].' - '.$response['ResultDesc'].' - '.$response['ResultDetails'].').', 
			'service' => $service_data); ;
	}
	
	
	/**
     * sendStopSmsNotification to SDP
     *
     * @param array $service_data service data to be used in sending request
	 * @return bool TRUE if successful an FALSE if it fails
     */
	private static function sendStopSmsNotification($service_data)
	{
		//send the request to SDP
		$response = SDP::stopSmsNotification($service_data->service_id, $service_data->correlator);
		
				//check response
		if($response['ResultCode'] == 0 ) // success
		{
			return array('result' => 0, 'resultDesc' => 'Successful.', 'service' => $service_data); 
		}
		
		//return 
		return  array('result' => 1, 
			'resultDesc' => 'Stop sms failed ('.$response['ResultCode'].' - '.$response['ResultDesc'].' - '.$response['ResultDetails'].').', 
			'service' => $service_data); ;
	}
	
	
	/**
     * generateCorrelator generate correlator to be used in sending request to SDP
     *
	 * @return string correlator
     */
	private static function generateCorrelator()
	{
		return date("YmdHis"); 
	}
}
