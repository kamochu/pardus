<?php

/**
 * NotifyModel
 *
 */
class NotifyModel
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
			return array("result"=>"0", "resultDesc"=>"XML Parsing successful", "data"=>$parser->getParameters());
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
		//check for required parameters 
		if(isset($data['message']) && isset($data['senderAddress']) && isset($data['smsServiceActivationNumber']))
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
		$sp_rev_id ="";
		$sp_re_password = "";
		$sp_id = "";
		$service_id = "";
		$link_id = "";
		$trace_unique_id = "";
		$correlator = "";
		$message = "";
		$sender_address = "";
		$dest_address = "";
		$date_time = "";
		
		//get the data from array
		if(isset($data['spRevId'])) $sp_rev_id = $data['spRevId'];
		if(isset($data['spRevpassword'])) $sp_re_password = $data['spRevpassword'];
		if(isset($data['spId'])) $sp_id = $data['spId'];
		if(isset($data['serviceId'])) $service_id = $data['serviceId'];
		if(isset($data['linkid'])) $link_id = $data['linkid'];
		if(isset($data['traceUniqueID'])) $trace_unique_id = $data['traceUniqueID'];
		if(isset($data['correlator'])) $correlator = $data['correlator'];
		if(isset($data['message'])) $message = $data['message'];
		if(isset($data['senderAddress'])) $sender_address = $data['senderAddress'];
		if(isset($data['smsServiceActivationNumber'])) $dest_address = $data['smsServiceActivationNumber'];
		if(isset($data['dateTime'])) $date_time = $data['dateTime'];
		
		// add some logic to handle exceptions in this script
		$database = DatabaseFactory::getFactory()->getConnection();
		$database->beginTransaction();
		$sql="INSERT INTO tbl_inbound_messages (service_id, link_id, trace_unique_id, correlator, message, sender_address, dest_address, date_time, created_on) VALUES (:service_id, :link_id, :trace_unique_id, :correlator, :message, :sender_address, :dest_address, :date_time, NOW());";
		$query = $database->prepare($sql);
		
		$query->execute(array(':service_id' => $service_id , ':link_id' => $link_id, ':trace_unique_id' => $trace_unique_id, ':correlator' => $correlator, ':message' => $message, ':sender_address' => $sender_address, ':dest_address' => $dest_address, ':date_time' => $date_time));	
		
		//add last insert id, may be used in the next method calls
		$data['_lastInsertID'] = $database->lastInsertId();
		
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
