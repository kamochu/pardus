<?php

/**
 * DeliveryModel
 *
 */
class SubscriptionModel
{
	
	 /**
     * Notify sms process .
     *
     * @param $data mixed the raw request data to be processed
	 * @return int a result indicating processing status
     */
    public static function process($data)
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
		//create a new parser
		$parser = new PardusXMLParser();
		//parse the message
		if($parser->parse($data, true) == 1) //1 means parsing was successful
		{
			$parameters = $parser->getParameters(); // get the parameters
			$parameters['repeatedParameters'] = $parser->getRepeatedParametersArray(); //append repeated parameters array to the parameters
			return array("result"=>"0", "resultDesc"=>"XML Parsing successful", "data"=>$parameters);
		}
		
		//return parsing failure
		return array("result"=>"12", "resultDesc"=>"XML Parsing failed", "data"=>$data);
		
		
	}
	
	/**
     * Data preprocessing before it can be saved. Enriching the message to be saved and forwarded.
     *
     * @param $data mixed data to be preprocessed
	 * @return int array indicating the processing status and data after processing
     */
	protected static function preProcess($data)
	{
		//check for required parameters; ID - msisdn, updateType and productID
		if(isset($data['ID']) && isset($data['updateType']) && isset($data['productID']))
		{
			return array("result"=>"0", "resultDesc"=>"Preprocessing successful",  "data"=>$data);
		}
		return array("result"=>"13", "resultDesc"=>"Expected parameters not found.",  "data"=>$data);
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
		$subscriber_id ="";
		$sp_id = "";
		$product_id = "";
		$service_id = "";
		$service_list = "";
		$update_type = "";
		$update_time = "";
		$update_desc = "";
		$effective_time = "";
		$expiry_time = "";
		$named_parameters = "";
		
		//get the data from array
		if(isset($data['ID'])) $subscriber_id = $data['ID'];
		if(isset($data['spID'])) $sp_id = $data['spID'];
		if(isset($data['productID'])) $product_id = $data['productID'];
		if(isset($data['serviceID'])) $service_id = $data['serviceID'];
		if(isset($data['serviceList'])) $service_list = $data['serviceList'];
		if(isset($data['updateType'])) $update_type = $data['updateType'];
		if(isset($data['updateTime'])) $update_time = $data['updateTime'];
		if(isset($data['updateDesc'])) $update_desc = $data['updateDesc'];
		if(isset($data['effectiveTime'])) $effective_time = $data['effectiveTime'];
		if(isset($data['expiryTime'])) $expiry_time = $data['expiryTime'];
	
		
		// process named parameters - key value pairs
		if(isset($data['key']))
		{
			$count = $data['repeatedParameters']['key'];
			$named_parameters_array = array($data['key'] => $data['value']); //initial key and value pair
			
			for($i=1; $i<=$count; $i++)
			{
				$named_parameters_array[$data['key'.$i]]= $data['value'.$i];
			}
			$named_parameters = json_encode($named_parameters_array); //encode into json string
		}	
		
		// add some logic to handle exceptions in this script
		$database = DatabaseFactory::getFactory()->getConnection();
		$database->beginTransaction();
		$sql="INSERT INTO tbl_subscription_messages (subscriber_id, sp_id,  product_id, service_id, service_list, update_type, update_time, update_desc, effective_time, expiry_time, named_parameters, created_on) VALUES (:subscriber_id, :sp_id, :product_id, :service_id, :service_list, :update_type, :update_time, :update_desc, :effective_time, :expiry_time, :named_parameters, NOW());";
		$query = $database->prepare($sql);
		$query->execute(array(':subscriber_id' => $subscriber_id , ':sp_id' => $sp_id, ':product_id' => $product_id, ':service_id' => $service_id, ':service_list' => $service_list, ':update_type' => $update_type, ':update_time' => $update_time, ':update_desc' => $update_desc, ':effective_time' => $effective_time,  ':expiry_time' => $expiry_time,  ':named_parameters' => $named_parameters));	
		
		
		$row_count = $query->rowCount();
		$database->commit();
		
		if ($row_count == 1) {
			
            return array("result"=>"0", "resultDesc"=>"Saving successful", "data"=>$data);
        }
		
		return array("result"=>"14", "resultDesc"=>"Saving record failed ($sql)".$database->errorCode()." ".$database->errorInfo(), "data"=>$data);
	}
	
	/**
     * Encode for purpose of pushing to external sources
     *
     * @param $data mixed data to be saved
	 * @return int array indicating the processing status and data after processing
     */
	protected static function encode($data)
	{
		return array("result"=>"0", "resultDesc"=>"Encoding successful", "data"=>$data);
	}
	
	
	/**
     * Hook - can be used to forward data to an external system (realtime forwarders)
     *
     * @param $data mixed data to be processed
	 * @return int array indicating the processing status and data after processing
     */
	protected static function hook($data)
	{
		return array("result"=>"0", "resultDesc"=>"Hook execution successful",  "data"=>$data);
	}
}
