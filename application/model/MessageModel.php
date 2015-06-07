<?php
namespace Ssg\Model;

use \Ssg\Core\DatabaseFactory;
use Ssg\Core\Model;
use Psr\Log\LoggerInterface;

/**
 * MessageModel - offers functions to manage messages in the system (sql, export, ext)
 *
 */
class MessageModel extends Model
{
	/**
     * Construct this object by extending the basic Model class
     */
    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
    }
	
	/**
     * getInboundMessages - get inbound messages
	 * 
	 * @param string $start_date start time to be used in filter condition 
	 * @param string $end_date end time to be used in the filter condition 
	 * @param string $subscriber_id subscriber number used in filter condition, default is '' - sql all subscribers
	 * @param string $short_code short code used in filter condition, default is '' - sql all short codes
	 * @param string $service_id service id used to filter, default is '' - sql all services 
	 * @param int $start_index the limit used in sql, default is 0
	 * @param int $limit the limit used in sql, default is 10
	 *
	 * @return array containing sql result and result data
     */
	public function getInboundMessages($start_date, $end_date, $subscriber_id='',  $short_code='', $service_id='', $start_index=0, $limit=10, $order='DESC')
	{
		$sql = 'SELECT * FROM tbl_inbound_messages WHERE created_on>:start_date AND created_on<=:end_date ';
		$parameters = array(':start_date'=>$start_date, ':end_date'=>$end_date);
		//include subscriber id filter
		if (isset($subscriber_id) && !empty($subscriber_id)) {
			$sql= $sql." AND sender_address=:subscriber_id";
			$parameters[':subscriber_id']=$subscriber_id;
		}
		//include short_code filter
		if (isset($short_code) && !empty($short_code)) {
			$sql= $sql." AND dest_address=:short_code";
			$parameters[':short_code']=$short_code;
		}
		//include service_id filter
		if (isset($service_id) && !empty($service_id)) {
			$sql= $sql." AND service_id=:service_id";
			$parameters[':service_id']=$service_id;
		}
		$sql= $sql.' ORDER BY message_id '.$order.' LIMIT '.$start_index.', '.$limit;
		
		// add some logic to handle exceptions in this script
		$row_count=0; 
		$messages='';
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
			$query = $database->prepare($sql);	
			if ($query->execute($parameters)) {
				$messages = $query->fetchAll();
				$row_count = $query->rowCount();
				if ($row_count > 0)  {	
					return array('result'=>0, 'resultDesc'=>'Records retrieved successfully.', '_recordsRetrieved' => $row_count, 'messages'=>$messages);
				}
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
		} catch (PDOException $e) {
			$this->logger->error(
				'{class_mame}|{method_name}|{service_id}|PDOException|{error}|{query}|bind_parameters:{bind_params}',
				array(
					'class_mame'=>__CLASS__,
					'method_name'=>__FUNCTION__,
					'error'=>$e->getMessage(),
					'query'=>$sql,
					'bind_params'=>json_encode($parameters)
				)
			);
			return  array('result' => 4, 'resultDesc' => 'Error executing a query. Error: '.$e->getMessage()); 
		}

		return array('result'=>1, 'resultDesc'=>'No records found.', '_recordsRetrieved' => $row_count, 'messages'=>$messages);
	} 
	
	
	/**
     * getOutboundMessages - get outbound messages
	 * 
	 * @param string $start_date start time to be used in filter condition 
	 * @param string $end_date end time to be used in the filter condition 
	 * @param string $subscriber_id subscriber number used in filter condition, default is '' - sql all subscribers
	 * @param string $service_id service id used to filter, default is '' - sql all services 
	 * @param string $batch_id service id used to filter, default is '' - sql all batches 
	 * @param int $start_index the limit used in sql, default is 0
	 * @param int $limit the limit used in sql, default is 10
	 *
	 * @return array containing sql result and result data
     */
	public function getOutboundMessages($start_date, $end_date, $subscriber_id='', $short_code='',  $service_id='', $batch_id='', $start_index=0, $limit=10, $order='DESC')
	{
		$sql = 'SELECT * FROM tbl_outbound_messages WHERE created_on>:start_date AND created_on<=:end_date ';
		$parameters = array(':start_date'=>$start_date, ':end_date'=>$end_date);
		//include subscriber id filter
		if (isset($subscriber_id) && !empty($subscriber_id)) {
			$sql= $sql." AND dest_address=:subscriber_id";
			$parameters[':subscriber_id']=$subscriber_id;
		}
		//include short_code filter
		if (isset($short_code) && !empty($short_code)) {
			$sql= $sql." AND sender_address=:short_code";
			$parameters[':short_code']=$short_code;
		}
		//include service_id filter
		if (isset($service_id) && !empty($service_id)) {
			$sql= $sql." AND service_id=:service_id"; 
			$parameters[':service_id']=$service_id;
		}
		//include batch_id filter
		if (isset($batch_id) && !empty($batch_id)) {
			$sql= $sql." AND batch_id=:batch_id";
			$parameters[':batch_id']=$batch_id;
		}
		$sql= $sql.' ORDER BY message_id '.$order.' LIMIT '.$start_index.', '.$limit;
		
		// add some logic to handle exceptions in this script
		$row_count=0; 
		$messages='';
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
			$query = $database->prepare($sql);	
			if ($query->execute($parameters)) {
				$messages = $query->fetchAll();
				$row_count = $query->rowCount();
				if ($row_count > 0)  {	
					return array('result'=>0, 'resultDesc'=>'Records retrieved successfully.', '_recordsRetrieved' => $row_count, 'messages'=>$messages);
				}
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
		} catch (PDOException $e) {
			$this->logger->error(
				'{class_mame}|{method_name}|{service_id}|PDOException|{error}|{query}|bind_parameters:{bind_params}',
				array(
					'class_mame'=>__CLASS__,
					'method_name'=>__FUNCTION__,
					'error'=>$e->getMessage(),
					'query'=>$sql,
					'bind_params'=>json_encode($parameters)
				)
			);
			return  array('result' => 4, 'resultDesc' => 'Error executing a query. Error: '.$e->getMessage()); 
		}
		
		return array('result'=>1, 'resultDesc'=>'No records found.', '_recordsRetrieved' => $row_count, 'messages'=>$messages);
	} 
	
	
	/**
     * getSubscriptionMessages - get outbound messages
	 * 
	 * @param string $start_date start time to be used in filter condition 
	 * @param string $end_date end time to be used in the filter condition 
	 * @param string $subscriber_id subscriber number used in filter condition, default is '' - sql all subscribers
	 * @param string $service_id service id used to filter, default is '' - sql all services 
	 * @param int $update_type update service type, default is  '' - sql all updates messages
	 * @param int $start_index the start index used in sql, default is 0
	 * @param int $limit the limit used in sql, default is 10
	 *
	 * @return array containing sql result and result data
     */
	public function getSubscriptionMessages($start_date, $end_date, $subscriber_id='', $service_id='', $product_id='', $update_type='', $start_index=0, $limit=10, $order='DESC')
	{
		$sql = 'SELECT * FROM tbl_subscription_messages WHERE created_on>:start_date AND created_on<=:end_date ';
		$parameters = array(':start_date'=>$start_date, ':end_date'=>$end_date);
		//include subscriber id filter
		if (isset($subscriber_id) && !empty($subscriber_id)) {
			$sql= $sql." AND subscriber_id=:subscriber_id";
			$parameters[':subscriber_id']=$subscriber_id;
		}
		//include service_id filter
		if (isset($service_id) && !empty($service_id)) {
			$sql= $sql." AND service_id=:service_id";
			$parameters[':service_id']=$service_id;
		}
		//include product_id filter
		if (isset($product_id) && !empty($product_id)) {
			$sql= $sql." AND product_id=:product_id";
			$parameters[':product_id']=$product_id;
		}
		//include update_type filter
		if (isset($update_type) && !empty($update_type)) {
			$sql= $sql." AND update_type=:update_type";
			$parameters[':update_type']=$update_type;
		}
		$sql= $sql.' ORDER BY id '.$order.' LIMIT '.$start_index.', '.$limit;
		
		// add some logic to handle exceptions in this script
		$row_count=0; 
		$messages='';
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
			$query = $database->prepare($sql);	
			if ($query->execute($parameters)) {
				$messages = $query->fetchAll();
				$row_count = $query->rowCount();
				if ($row_count > 0)  {	
					return array('result'=>0, 'resultDesc'=>'Records retrieved successfully.', '_recordsRetrieved' => $row_count, 'messages'=>$messages);
				}
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
		} catch (PDOException $e) {
			$this->logger->error(
				'{class_mame}|{method_name}|{service_id}|PDOException|{error}|{query}|bind_parameters:{bind_params}',
				array(
					'class_mame'=>__CLASS__,
					'method_name'=>__FUNCTION__,
					'error'=>$e->getMessage(),
					'query'=>$sql,
					'bind_params'=>json_encode($parameters)
				)
			);
			return  array('result' => 4, 'resultDesc' => 'Error executing a query. Error: '.$e->getMessage()); 
		}
		
		return array('result'=>1, 'resultDesc'=>'No records found.', '_recordsRetrieved' => $row_count, 'messages'=>$messages);
	} 
	
	
	/**
     * getDeliveryMessages - get delivery receipt messages
	 * 
	 * @param string $start_date start time to be used in filter condition 
	 * @param string $end_date end time to be used in the filter condition 
	 * @param string $subscriber_id subscriber number used in filter condition, default is '' - query all subscribers
	 * @param string $correlator correlator used to filter, default is '' - return all correlators
	 * @param int $start_index the start index used in sql, default is 0
	 * @param int $limit the limit used in sql, default is 10
	 *
	 * @return array containing sql result and result data
     */
	public function getDeliveryMessages($start_date, $end_date, $subscriber_id='', $correlator='', $start_index=0, $limit=10, $order='DESC')
	{
		$sql = 'SELECT * FROM tbl_delivery_receipts WHERE created_on>:start_date AND created_on<=:end_date ';
		$parameters = array(':start_date'=>$start_date, ':end_date'=>$end_date);
		//include subscriber id filter
		if (isset($subscriber_id) && !empty($subscriber_id)) {
			$sql= $sql." AND dest_address=:subscriber_id";
			$parameters[':subscriber_id']=$subscriber_id;
		}
		//include correlator filter
		if (isset($correlator) && !empty($correlator)) {
			$sql= $sql." AND correlator=:correlator";
			$parameters[':correlator']=$correlator;
		}
		$sql= $sql.' ORDER BY id '.$order.' LIMIT '.$start_index.', '.$limit;
		
		// add some logic to handle exceptions in this script
		$row_count=0; 
		$messages='';
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
			$query = $database->prepare($sql);	
			if ($query->execute($parameters)) {
				$messages = $query->fetchAll();
				$row_count = $query->rowCount();
				if ($row_count > 0)  {	
					return array('result'=>0, 'resultDesc'=>'Records retrieved successfully.', '_recordsRetrieved' => $row_count, 'messages'=>$messages);
				}
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
		} catch (PDOException $e) {
			$this->logger->error(
				'{class_mame}|{method_name}|{service_id}|PDOException|{error}|{query}|bind_parameters:{bind_params}',
				array(
					'class_mame'=>__CLASS__,
					'method_name'=>__FUNCTION__,
					'error'=>$e->getMessage(),
					'query'=>$sql,
					'bind_params'=>json_encode($parameters)
				)
			);
			return  array('result' => 4, 'resultDesc' => 'Error executing a query. Error: '.$e->getMessage()); 
		}
		return array('result'=>1, 'resultDesc'=>'No records found.', '_recordsRetrieved' => $row_count, 'messages'=>$messages);
	} 
}
