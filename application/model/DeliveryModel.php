<?php
namespace Ssg\Model;

use Ssg\Core\DatabaseFactory;
use Ssg\Core\PardusXMLParser;
use Ssg\Core\Model;
use Ssg\Core\Config;
use Psr\Log\LoggerInterface;
use \PDO;


/**
 * DeliveryModel
 *
 */
class DeliveryModel extends Model
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
		if(isset($data['deliveryStatus']) && isset($data['address']) && isset($data['correlator']))
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
		$time_stamp ="";
		$sub_req_id = "";
		$trace_unique_id = "";
		$correlator = "";
		$dest_address = "";
		$delivery_status = "";
		
		//get the data from array
		if(isset($data['timeStamp'])) $time_stamp = $data['timeStamp'];
		if(isset($data['subReqID'])) $sub_req_id = $data['subReqID'];
		if(isset($data['traceUniqueID'])) $trace_unique_id = $data['traceUniqueID'];
		if(isset($data['correlator'])) $correlator = $data['correlator'];
		if(isset($data['address'])) $dest_address = $data['address'];
		if(isset($data['deliveryStatus'])) $delivery_status = $data['deliveryStatus'];
		
	   // add some logic to handle exceptions in this script
		$database=null;
		try {
			$database = DatabaseFactory::getFactory()->getConnection();
		} catch (Exception $ex) {
			return  array('result' => 3, 'resultDesc' => 'Cannot connect to the database. Error: '.$ex->getMessage()); 
		}
		
		try {	
			$database->beginTransaction();
			$sql="INSERT INTO tbl_delivery_receipts (time_stamp, sub_req_id, trace_unique_id, correlator, dest_address, delivery_status, created_on) VALUES (:time_stamp, :sub_req_id, :trace_unique_id, :correlator, :dest_address, :delivery_status, NOW());";
			$query = $database->prepare($sql);
			
			$bind_parameters=array(':time_stamp' => $time_stamp , ':sub_req_id' => $sub_req_id, ':trace_unique_id' => $trace_unique_id, ':correlator' => $correlator,':dest_address' => $dest_address, ':delivery_status' => $delivery_status);
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
		
		return array("result"=>"14", "resultDesc"=>"Saving record failed ($sql)".$database->errorCode()." ".$database->errorInfo(), "data"=>$data);
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
		/* Update outgoing table with the parameters */
		//initialize the parameters
		$correlator = "";
		$dest_address = "";
		$delivery_status = "";
		$delivery_receipt_id=0;
		
		//get the data from array
		if(isset($data['correlator'])) $correlator = $data['correlator'];
		if(isset($data['address'])) $dest_address = $data['address'];
		if(isset($data['deliveryStatus'])) $delivery_status = $data['deliveryStatus'];
		if(isset($data['_lastInsertID'])) $delivery_receipt_id = $data['_lastInsertID'];
		
		// add some logic to handle exceptions in this script
		$database = DatabaseFactory::getFactory()->getConnection();
		$database->beginTransaction();
		$sql="UPDATE tbl_outbound_messages SET delivery_timestamp = NOW(), delivery_status=:delivery_status, delivery_notif_type=2, delivery_receipt_id =:delivery_receipt_id, last_updated_on=NOW() WHERE dest_address=:dest_address AND correlator=:correlator";// IMPORTANT - note dest_addresses in the where clause (to be visited later)
		$query = $database->prepare($sql);
		$query->execute(array(':delivery_status' => $delivery_status , ':delivery_receipt_id' => $delivery_receipt_id, ':dest_address' => $dest_address, ':correlator' => $correlator));	
		
		$row_count = $query->rowCount();
		$data['_recordsUpdated'] = $row_count;
		$database->commit();
		
		if ($database->errorCode() != "0000") {	 //success
            return array("result"=>"18", "resultDesc"=>"Saving FAILED. Error: ".$database->errorCode(), "data"=>$data);
        }
		
		//forward the request
		if (Config::get('DELIVERY_FORWARDER') == 1) { //
		
			//initialize the parameters
			$time_stamp ="";
			$sub_req_id = "";
			$trace_unique_id = "";
			$correlator = "";
			$dest_address = "";
			$delivery_status = "";
			$id = "";
			
			//get the data from array
			if(isset($data['timeStamp'])) $time_stamp = $data['timeStamp'];
			if(isset($data['subReqID'])) $sub_req_id = $data['subReqID'];
			if(isset($data['traceUniqueID'])) $trace_unique_id = $data['traceUniqueID'];
			if(isset($data['correlator'])) $correlator = $data['correlator'];
			if(isset($data['address'])) $dest_address = $data['address'];
			if(isset($data['deliveryStatus'])) $delivery_status = $data['deliveryStatus'];
			if(isset($data['_lastInsertID'])) $id = $data['_lastInsertID'];
			
			// add some logic to handle exceptions in this script
			$database=null;
			try {
				//$database = SQLSRVDatabaseFactory::getFactory()->getConnection();
				$options = array(PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ, PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING);
				$database = new PDO('sqlsrv:Server=SEMATEL-SERVER;Database=db_Sematel','sa', 'SematelServer2014', $options);
			} catch (Exception $ex) {
				return  array('result' => 3, 'resultDesc' => 'Cannot connect to the database. Error: '.$ex->getMessage()); 
			}
			
			$bind_patameters =array();
			
			try {		
				$database->beginTransaction();
				
				$sql="INSERT INTO dbo.tbl_delivery_receipts (id, time_stamp, sub_req_id, trace_unique_id, correlator, dest_address, delivery_status, created_on) VALUES (:id, :time_stamp, :sub_req_id, :trace_unique_id, :correlator, :dest_address, :delivery_status, CURRENT_TIMESTAMP);";
				$query = $database->prepare($sql);
				
				$bind_patameters=array(':id'=>$id, ':time_stamp' => $time_stamp , ':sub_req_id' => $sub_req_id, ':trace_unique_id' => $trace_unique_id, ':correlator' => $correlator,':dest_address' => $dest_address, ':delivery_status' => $delivery_status);
				
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
				
				$this->logger->error(
						'{class_mame}|{method_name}|{service_id}|forwarding-hook|{query}|bind_parameters:{bind_params}|{error}',
						array(
							'class_mame'=>__CLASS__,
							'method_name'=>__FUNCTION__,
							'query'=>$sql,
							'bind_params'=>json_encode($bind_patameters),
							'error'=>$e->getMessage()
						)
					);
					
				return  array('result' => 4, 'resultDesc' => 'Error executing a query. Error: '.$e->getMessage()); 
			}
			
			return array("result"=>"19", "resultDesc"=>"Forwarding record failed ($sql)".$database->errorCode()." ".$database->errorInfo(), "data"=>$data);
		}
		
		return array("result"=>"0", "resultDesc"=>"Hook execution successful", "data"=>$data);
	}
}
