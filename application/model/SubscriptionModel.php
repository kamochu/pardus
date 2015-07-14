<?php
namespace Ssg\Model;

use Ssg\Core\PardusXMLParser;
use Ssg\Core\DatabaseFactory;
use Ssg\Core\SQLSRVDatabaseFactory;
use \PDO;
use Ssg\Core\Model;
use Ssg\Core\Config;
use Psr\Log\LoggerInterface;

/**
 * DeliveryModel
 *
 */
class SubscriptionModel extends Model
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
		if($parser->parse($data, true) == 1) {
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
			
			$parameters = $parser->getParameters(); // get the parameters
			$parameters['repeatedParameters'] = $parser->getRepeatedParametersArray(); //append repeated parameters array to the parameters
			
			return array("result"=>"0", "resultDesc"=>"XML Parsing successful", "data"=>$parameters);
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
	protected function save($data)
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
		if (isset($data['key'])) {
			$count = $data['repeatedParameters']['key'];
			$named_parameters_array = array($data['key'] => $data['value']); //initial key and value pair
			for ($i=1; $i<=$count; $i++) {
				if( isset($data['key'.$i])&& isset($data['value'.$i])) $named_parameters_array[$data['key'.$i]]= $data['value'.$i];
			}
			$named_parameters = json_encode($named_parameters_array); //encode into json string
		}	
		
		// add some logic to handle exceptions in this script
		$database=null;
		try {
			$database = DatabaseFactory::getFactory()->getConnection();
		} catch (Exception $ex) {
			return  array('result' => 3, 'resultDesc' => 'Cannot connect to the database. Error: '.$ex->getMessage()); 
		}
		
		try {		
			$database->beginTransaction();
			$sql="INSERT INTO tbl_subscription_messages (subscriber_id, sp_id,  product_id, service_id, service_list, update_type, update_time, update_desc, effective_time, expiry_time, named_parameters, created_on) VALUES (:subscriber_id, :sp_id, :product_id, :service_id, :service_list, :update_type, :update_time, :update_desc, :effective_time, :expiry_time, :named_parameters, NOW());";
			$query = $database->prepare($sql);

			$bind_patameters = array(':subscriber_id' => $subscriber_id , ':sp_id' => $sp_id, ':product_id' => $product_id, ':service_id' => $service_id, ':service_list' => $service_list, ':update_type' => $update_type, ':update_time' => $update_time, ':update_desc' => $update_desc, ':effective_time' => $effective_time,  ':expiry_time' => $expiry_time,  ':named_parameters' => $named_parameters);
			
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
		if (Config::get('SUBSCRIPTION_FORWARDER') == 1) { //forward
			//initialize the parameters
			$id ="";
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
			if(isset($data['_lastInsertID'])) $id = $data['_lastInsertID'];
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
			if (isset($data['key'])) {
				$count = $data['repeatedParameters']['key'];
				$named_parameters_array = array($data['key'] => $data['value']); //initial key and value pair
				for ($i=1; $i<=$count; $i++) {
					if( isset($data['key'.$i])&& isset($data['value'.$i])) $named_parameters_array[$data['key'.$i]]= $data['value'.$i];
				}
				$named_parameters = json_encode($named_parameters_array); //encode into json string
			}	
			
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
				//$sql=$sql="INSERT INTO dbo.tbl_Subscription_messages (id, subscriber_id, sp_id, product_id, service_id, service_list, update_type, update_desc, effective_time, expiry_time, created_on) VALUES (:id,:subscriber_id,:sp_id,:product_id,:service_id,:service_list,:update_type,:update_desc,:effective_time,:expiry_time,CURRENT_TIMESTAMP)";
				$sql="INSERT INTO dbo.tbl_Subscription_messages (id, subscriber_id, sp_id, product_id, service_id, service_list, update_type, update_desc, effective_time, expiry_time, created_on) VALUES (:id,:subscriber_id,:sp_id,:product_id,:service_id,:service_list,:update_type,:update_desc,:effective_time,:expiry_time,CURRENT_TIMESTAMP)";;
				
				$query = $database->prepare($sql);
	
				$bind_patameters = array(':id' => $id, ':subscriber_id' => $subscriber_id , ':sp_id' => $sp_id, ':product_id' => $product_id, ':service_id' => $service_id, ':service_list' => $service_list, ':update_type' => $update_type, ':update_desc' => $update_desc, ':effective_time' => $effective_time,  ':expiry_time' => $expiry_time);
				
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
		
		return array("result"=>"0", "resultDesc"=>"Successful - hook is off",  "data"=>$data);
	}
}
