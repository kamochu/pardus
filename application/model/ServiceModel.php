<?php

/**
 * ServiceManagerModel - offers utility functions to manage service
 *
 */
class ServiceModel
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
		$response =  self::getService($service_id);
		
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
		$response = self::saveServiceStatus($service_data);
		
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
		
		//update the database
		$response = self::saveServiceStatus($service_data);
		
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
	
	
	/**
     * Get service data
     *
     * @param string $service_id service id
	 * @return array containing query result and service data
     */
	public static function getService($service_id)
	{	
		 $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "SELECT id, service_id, service_name, service_type, short_code, service_endpoint, 
				criteria, delivery_notification_endpoint, interface_name, correlator, status
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
     * Update the service table to indicate the new status and the correlator.
     *
     * @param string $service_data service data
	 * @return array containing query result and service data
     */
	private static function saveServiceStatus($service_data)
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
     * Add a new service (Remember to add configurations in the configuration file).
     *
     * @param string $service_data service data
	 * @return array containing query result and service data
     */
	public static function addService($service_data)
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
		
		// add some logic to handle exceptions in this script
		$database = DatabaseFactory::getFactory()->getConnection();
		$database->beginTransaction();
		$sql='INSERT INTO tbl_services(service_id,service_name,service_type,short_code,criteria,service_endpoint,delivery_notification_endpoint,interface_name,correlator,status,created_on,last_updated_on,last_updated_by) 
VALUES(:service_id,:service_name,:service_type,:short_code,:criteria,:service_endpoint,:delivery_notification_endpoint,:interface_name,:correlator,:status,NOW(),NOW(),:last_updated_by)';
		$query = $database->prepare($sql);
		
		$query->execute(array(':service_id' => $service_id , ':service_name' => $service_name, ':service_type' => $service_type, ':short_code' => $short_code, ':criteria' => $criteria, ':service_endpoint' => $service_endpoint, ':delivery_notification_endpoint' => $delivery_notification_endpoint, ':interface_name' => $interface_name, ':correlator' => $correlator, ':status' => $status, ':last_updated_by' => $last_updated_by));
		
		$row_count = $query->rowCount();
		$errorCode = $database->errorCode();
		$database->commit();
		
		if ($row_count == 1) 
		{	
			return array('result'=>0, 'resultDesc'=>'Service added successfully. ', 'service'=>$service_data);
        }
		return array('result'=>1, 'resultDesc'=>'Adding service record failed - '.$errorCode, 'service'=>$service_data);
	} 
	
	
	/**
     * updateService - updates existing service data except status and correlator 
	 * which are manipulated by enable and disable service methods
     *
     * @param string $service_data service data
	 * @return array containing query result and service data
     */
	public static function updateService($service_data)
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
		
		if($query_result['result'] != 0) //query failure
		{
			return $query_result; // return the query response error 
		}
		
		
		// add some logic to handle exceptions in this script
		$database = DatabaseFactory::getFactory()->getConnection();
		$database->beginTransaction();
		$sql='UPDATE tbl_services SET service_id=:service_id, service_name=:service_name, service_type = :service_type, short_code = :short_code, criteria = :criteria, service_endpoint = :service_endpoint, delivery_notification_endpoint = :delivery_notification_endpoint, interface_name = :interface_name, last_updated_on=NOW(), last_updated_by = :last_updated_by WHERE id=:id';
		$query = $database->prepare($sql);
		
		$query->execute(array(':id' => $id, ':service_id' => $service_id , ':service_name' => $service_name, ':service_type' => $service_type, ':short_code' => $short_code, ':criteria' => $criteria, ':service_endpoint' => $service_endpoint, ':delivery_notification_endpoint' => $delivery_notification_endpoint, ':interface_name' => $interface_name, ':last_updated_by' => $last_updated_by));
		
		$row_count = $query->rowCount();
		$errorCode = $database->errorCode();
		$database->commit();
		
		if ($row_count == 1) 
		{	
			return array('result'=>0, 'resultDesc'=>'Service updated successfully.', 'service'=>$service_data);
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
	public static function deleteService($service_id)
	{
		//check whether ther service exists
		$query_result = self::getService($service_id);
		
		if($query_result['result'] != 0) //query failure
		{
			return $query_result; // return the query response error 
		}
		
		// add some logic to handle exceptions in this script
		$database = DatabaseFactory::getFactory()->getConnection();
		$database->beginTransaction();
		$sql='DELETE FROM tbl_services WHERE service_id = :service_id LIMIT 1';
		$query = $database->prepare($sql);
		
		$query->execute(array(':service_id' => $service_id));
		
		$row_count = $query->rowCount();
		$errorCode = $database->errorCode();
		$database->commit();
		
		if ($row_count == 1) 
		{	
			return array('result'=>0, 'resultDesc'=>'Service deleted successsfully', 'service'=>$query_result['service']);
        }
		return array('result'=>1, 'resultDesc'=>'No record deleted - '.$errorCode, 'service'=>$query_result['service']);
	} 
	
	
	/**
     * deleteService - deletes the service from the system
	 * 
	 * @return array containing query result and service data
     */
	public static function getAllServices()
	{
		$database = DatabaseFactory::getFactory()->getConnection();

        $sql = "SELECT id, service_id, service_name, service_type, short_code, criteria, service_endpoint, delivery_notification_endpoint, interface_name, correlator, status, created_on, last_updated_on, last_updated_by FROM tbl_services";
        $query = $database->prepare($sql);
        $query->execute();

        // fetchAll() is the PDO method that gets all result rows
         $services = $query->fetchAll();
		
		if ($query->rowCount() > 0) 
		{	
			return array('result'=>0, 'resultDesc'=>'Records retrieved successfully.', 'services'=>$services);
        }
		return array('result'=>1, 'resultDesc'=>'No records found - '.$errorCode, 'services'=>$services);
	} 
}
