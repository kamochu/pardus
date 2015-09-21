<?php
namespace Ssg\Model;

use Ssg\Core\PardusXMLParser;
use Ssg\Core\DatabaseFactory;
use Ssg\Core\Model;
use Ssg\Core\Config;
use Psr\Log\LoggerInterface;
use \PDO;

/**
 * NotifyModel
 *
 */
class NotifyModel extends Model
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
    public function process($data)
    {
		//decode the dat
		$resultData = self::decode($data);
		if ($resultData['result'] != 0) {
			return $resultData;	
		}	
		
		//preprocess the data
		$resultData = self::preProcess($resultData['data']);
		if ($resultData['result'] != 0) {
			return $resultData;	
		}	
		
		//save data
		$resultData = self::save($resultData['data']);
		if ($resultData['result'] != 0) {
			return $resultData;	
		}
		
		//encode data
		$resultData = self::encode($resultData['data']);
		if ($resultData['result'] != 0) {
			return $resultData;	
		}
		
		//hook
		$resultData = self::hook($resultData['data']);
		if ($resultData['result'] != 0) {
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
		//create a new parser
		$parser = new PardusXMLParser();
		//parse the message //1 means parsing was successful
		if ($parser->parse($data, true) == 1) {
			
			//log the event		
			$this->logger->debug(
				'{class_mame}|{method_name}|parse-xml|parameters_extracted:{paramters_extracted}|repeated_parameters:{repeated_parameters}',
				array(
					'class_mame'=>__CLASS__,
					'method_name'=>__FUNCTION__,
					'paramters_extracted'=>json_encode($parser->getParameters()),
					'repeated_parameters'=>json_encode($parser->getRepeatedParametersArray())
				)
			);
			return array("result"=>"0", "resultDesc"=>"XML Parsing successful", "data"=>$parser->getParameters());
		}
		//log the parsing error event
		$this->logger->error(
			'{class_mame}|{method_name}|parse-xml|parse-error:{error}|line_number:{line_number}|data:{data}',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'error'=>$parser->getParseError(),
				'line_number'=>$parser->getCurrentLineNumber(),
				'data'=>$data
			)
		);
		
		//return parsing failure
		return array("result"=>"12", "resultDesc"=>"XML Parsing failed", "data"=>$data);
		
		
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
	protected function save($data)
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
		$database=null;
		try {
			$database = DatabaseFactory::getFactory()->getConnection();
		} catch (Exception $ex) {
			return  array('result' => 3, 'resultDesc' => 'Cannot connect to the database. Error: '.$ex->getMessage()); 
		}
		
		try {		
			$database->beginTransaction();
			$sql="INSERT INTO tbl_inbound_messages (service_id, link_id, trace_unique_id, correlator, message, sender_address, dest_address, date_time, created_on) VALUES (:service_id, :link_id, :trace_unique_id, :correlator, :message, :sender_address, :dest_address, :date_time, NOW());";
			$query = $database->prepare($sql);
			
			$bind_patameters = array(':service_id' => $service_id , ':link_id' => $link_id, ':trace_unique_id' => $trace_unique_id, ':correlator' => $correlator, ':message' => $message, ':sender_address' => $sender_address, ':dest_address' => $dest_address, ':date_time' => $date_time);
			
			if ($query->execute($bind_patameters)) {
				//add last insert id, may be used in the next method calls
				$data['_lastInsertID'] = $database->lastInsertId();
				
				$row_count = $query->rowCount();
				$database->commit();
				
				if ($row_count == 1) {	
					return array('result'=>0, 'resultDesc'=>'Saving successful', 'data'=>$data);
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
		return array("result"=>"0", "resultDesc"=>"Encoding successful", "data"=>$data);
	}
	
	
	/**
     * Hook - can be used to forward data to an external system (realtime forwarders)
     *
     * @param $data mixed data to be processed
	 * @return int array indicating the processing status and data after processing
     */
	protected function hook($data)
	{
		if (Config::get('INBOX_FORWARDER') == 1) { //forward
			//initialize the parameters
			$message_id ="";
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
			if(isset($data['_lastInsertID'])) $message_id = $data['_lastInsertID'];
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
			$database=null;
			try {
				//$database = SQLSRVDatabaseFactory::getFactory()->getConnection();
				$options = array(PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ, PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING);
				$database = new PDO('sqlsrv:Server=SEMATEL-SERVER;Database=db_Sematel','sa', 'SematelServer2014', $options);
			} catch (Exception $ex) {
				return  array('result' => 3, 'resultDesc' => 'Cannot connect to the database. Error: '.$ex->getMessage()); 
			}
			
			try {		
				$database->beginTransaction();
				$sql="INSERT INTO tbl_inbound_messages (message_id, service_id, link_id, trace_unique_id, correlator, message, sender_address, dest_address, date_time, created_on) VALUES (:message_id, :service_id, :link_id, :trace_unique_id, :correlator, :message, :sender_address, :dest_address, :date_time, CURRENT_TIMESTAMP);";
				
				$query = $database->prepare($sql);
	
				$bind_patameters = array(':message_id'=>$message_id, ':service_id' => $service_id , ':link_id' => $link_id, ':trace_unique_id' => $trace_unique_id, ':correlator' => $correlator, ':message' => $message, ':sender_address' => $sender_address, ':dest_address' => $dest_address, ':date_time' => $date_time);
				
				$this->logger->debug(
						'{class_mame}|{method_name}|{service_id}|forwarding-hook|{query}|bind_parameters:{bind_params}',
						array(
							'class_mame'=>__CLASS__,
							'method_name'=>__FUNCTION__,
							'query'=>$sql,
							'bind_params'=>json_encode($bind_patameters)
						)
					);
				
				if ($query->execute($bind_patameters)) {
					$row_count = $query->rowCount();
					$database->commit();
					
					if ($row_count == 1) {	
						return array('result'=>0, 'resultDesc'=>'Forwarding successful', 'data'=>$data);
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
			
			return array("result"=>"19", "resultDesc"=>"Forwarding record failed ($sql)".$database->errorCode()." ".$database->errorInfo(), "data"=>$data);
		}
		
		return array("result"=>"0", "resultDesc"=>"Hook execution successful",  "data"=>$data);
	}
}
