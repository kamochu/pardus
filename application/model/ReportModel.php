<?php
namespace Ssg\Model;

use \Ssg\Core\DatabaseFactory;
use Ssg\Core\Model;
use Psr\Log\LoggerInterface;

/**
 * MessageModel - offers functions to manage messages in the system (sql, export, ext)
 *
 */
class ReportModel extends Model
{
	/**
     * Construct this object by extending the basic Model class
     */
    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
    }
	
	/**
     * generateInboxReport
	 * 
	 * @param string $start_date start time to be used in filter condition 
	 * @param string $end_date end time to be used in the filter condition 
	 * @param string $service_id service id used to filter, default is '' - sql all services 
	 * @param int $start_index the limit used in sql, default is 0
	 * @param int $order the order by clause, default DESC
	 *
	 * @return array containing sql result and result data
     */
	public function generateInboxReport($start_date, $end_date, $service_id='', $order='DESC')
	{
		$sql_total = ''; //query for total records
		$sql = ''; //query for retrieving actual records
		$parameters = array(':start_date' => $start_date, ':end_date' => $end_date); //bind parameters
		
		if (isset($service_id) && !empty($service_id)) { //query report for one service
			$sql_total = "SELECT message_id FROM tbl_inbound_messages a WHERE a.service_id=:service_id AND a.created_on>:start_date AND a.created_on<=:end_date";
			$sql = "SELECT date(a.created_on) as calendar_date, a.service_id as service_id, b.service_name as service_name, a.dest_address as dest_address, count(message_id) as message_count FROM tbl_inbound_messages a, tbl_services b WHERE a.service_id = b.service_id AND a.service_id=:service_id AND a.created_on>:start_date AND a.created_on<=:end_date GROUP BY a.service_id, a.dest_address, calendar_date ORDER BY message_count DESC LIMIT 0,30";
			$parameters['service_id']=$service_id;
		} else { //query report for many services 	
			$sql_total = "SELECT message_id FROM tbl_inbound_messages a WHERE a.created_on>:start_date AND a.created_on<=:end_date";
			$sql = "SELECT 'N/A' as calendar_date, a.service_id as service_id, b.service_name as service_name, a.dest_address as dest_address, count(message_id) as message_count FROM tbl_inbound_messages a, tbl_services b WHERE a.service_id = b.service_id AND a.created_on>:start_date AND a.created_on<=:end_date GROUP BY a.service_id, a.dest_address ORDER BY message_count DESC";
		}
		
		$this->logger->debug(
				'{class_mame}|{method_name}|{service_id}|queries|{query_total}|{query}|bind_parameters:{bind_params}',
				array(
					'class_mame'=>__CLASS__,
					'method_name'=>__FUNCTION__,
					'query'=>$sql,
					'query_total'=>$sql_total,
					'bind_params'=>json_encode($parameters)
				)
			);
		
		// add some logic to handle exceptions in this script
		$row_count=0; 
		$total_records=0;
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
			//get total records
			$query = $database->prepare($sql_total);	
			if ($query->execute($parameters)) {
				$total_records = $query->rowCount();
			} else {	
				$this->logger->error(
					'{class_mame}|{method_name}|{service_id}|error executing the query|{error}|{query}|bind_parameters:{bind_params}',
					array(
						'class_mame'=>__CLASS__,
						'method_name'=>__FUNCTION__,
						'error'=>$database->errorCode(),
						'query'=>$sql_total,
						'bind_params'=>json_encode($parameters)
					)
				);
				return  array('result' => 5, 'resultDesc' => 'Error executing a query.'); 
			}
			
			//get records
			$query = $database->prepare($sql);	
			if ($query->execute($parameters)) {
				$messages = $query->fetchAll();
				$row_count = $query->rowCount();
				if ($row_count > 0)  {	
					return array('result'=>0, 'resultDesc'=>'Records retrieved successfully.', '_recordsRetrieved' => $row_count, '_totalRecords' => $total_records,  'messages'=>$messages);
				} else { // IMPORTANT to display not configured services
					return array('result'=>0, 'resultDesc'=>'Records retrieved successfully.', '_recordsRetrieved' => $row_count, '_totalRecords' => $total_records,  'messages'=>$messages);
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
     * generateInboxReport
	 * 
	 * @param string $start_date start time to be used in filter condition 
	 * @param string $end_date end time to be used in the filter condition 
	 * @param string $service_id service id used to filter, default is '' - sql all services 
	 * @param string $batch_id batch id '' - sql all services 
	 * @param int $start_index the limit used in sql, default is 0
	 * @param int $order the order by clause, default DESC
	 *
	 * @return array containing sql result and result data
     */
	public function generateOutboxReport($start_date, $end_date, $service_id='',  $batch_id='', $order='ASC')
	{
		$sql_total = 'SELECT message_id FROM tbl_outbound_messages a WHERE a.created_on>:start_date AND a.created_on<=:end_date ';
		$sql = 'SELECT a.service_id as service_id, b.service_name as service_name, a.sender_address as sender_address, a.batch_id as batch_id, a.status as status,  count(message_id) as message_count FROM tbl_outbound_messages a, tbl_services b WHERE a.service_id = b.service_id AND a.created_on>:start_date AND a.created_on<=:end_date';
		$parameters = array(':start_date' => $start_date, ':end_date' => $end_date); //bind parameters
		
		if (isset($service_id) && !empty($service_id)) { //query report for one service
			$sql_total .= ' AND a.service_id = :service_id ';
			$sql .= ' AND a.service_id = :service_id ';
			$parameters[':service_id']=$service_id;
		} 
		if (isset($batch_id) && !empty($batch_id)) { //query report for one service
			$sql_total .= ' AND a.batch_id = :batch_id ';
			$sql .= ' AND a.batch_id = :batch_id ';
			$parameters[':batch_id']=$batch_id;
		} 
		$sql.=' GROUP BY a.service_id, a.sender_address, a.batch_id, a.status ORDER BY b.service_name,  a.batch_id, a.status '.$order;
		
		$this->logger->debug(
				'{class_mame}|{method_name}|{service_id}|queries|{query_total}|{query}|bind_parameters:{bind_params}',
				array(
					'class_mame'=>__CLASS__,
					'method_name'=>__FUNCTION__,
					'query'=>$sql,
					'query_total'=>$sql_total,
					'bind_params'=>json_encode($parameters)
				)
			);
		
		// add some logic to handle exceptions in this script
		$row_count=0; 
		$total_records=0;
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
			//get total records
			$query = $database->prepare($sql_total);	
			if ($query->execute($parameters)) {
				$total_records = $query->rowCount();
			} else {	
				$this->logger->error(
					'{class_mame}|{method_name}|{service_id}|error executing the query|{error}|{query}|bind_parameters:{bind_params}',
					array(
						'class_mame'=>__CLASS__,
						'method_name'=>__FUNCTION__,
						'error'=>$database->errorCode(),
						'query'=>$sql_total,
						'bind_params'=>json_encode($parameters)
					)
				);
				return  array('result' => 5, 'resultDesc' => 'Error executing a query.'); 
			}
			
			//get records
			$query = $database->prepare($sql);	
			if ($query->execute($parameters)) {
				$messages = $query->fetchAll();
				$row_count = $query->rowCount();
				if ($row_count > 0)  {	
					return array('result'=>0, 'resultDesc'=>'Records retrieved successfully.', '_recordsRetrieved' => $row_count, '_totalRecords' => $total_records,  'messages'=>$messages);
				} else { // IMPORTANT to display not configured services
					return array('result'=>0, 'resultDesc'=>'Records retrieved successfully.', '_recordsRetrieved' => $row_count, '_totalRecords' => $total_records,  'messages'=>$messages);
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
     * generateInboxReport
	 * 
	 * @param string $start_date start time to be used in filter condition 
	 * @param string $end_date end time to be used in the filter condition 
	 * @param string $service_id service id used to filter, default is '' - sql all services 
	 * @param string $batch_id batch id '' - sql all services 
	 * @param int $start_index the limit used in sql, default is 0
	 * @param int $order the order by clause, default DESC
	 *
	 * @return array containing sql result and result data
     */
	public function generateSubscriptionsReport($start_date, $end_date, $service_id='',  $product_id='',  $update_type='', $order='ASC')
	{
		$sql_total = 'SELECT id FROM tbl_subscription_messages a WHERE a.created_on>:start_date AND a.created_on<=:end_date ';
		$sql = 'SELECT a.service_id as service_id, b.service_name as service_name, a.product_id as product_id, a.update_type as update_type, a.update_desc as update_desc, count(a.id) as message_count FROM tbl_subscription_messages a, tbl_services b  WHERE a.service_id = b.service_id AND a.created_on>:start_date AND a.created_on<=:end_date';
		$parameters = array(':start_date' => $start_date, ':end_date' => $end_date); //bind parameters
		
		if (isset($service_id) && !empty($service_id)) { //query report for one service
			$sql_total .= ' AND a.service_id = :service_id ';
			$sql .= ' AND a.service_id = :service_id ';
			$parameters[':service_id']=$service_id;
		} 
		if (isset($product_id) && !empty($product_id)) { //query report for one service
			$sql_total .= ' AND a.product_id = :product_id ';
			$sql .= ' AND a.product_id = :product_id ';
			$parameters[':product_id']=$product_id;
		} 
		if (isset($update_type) && !empty($update_type)) { //query report for one service
			$sql_total .= ' AND a.update_type = :update_type ';
			$sql .= ' AND a.update_type = :update_type ';
			$parameters[':update_type']=$update_type;
		} 
		$sql.=' GROUP BY a.service_id, a.product_id, a.update_type ORDER BY b.service_id,  a.product_id '.$order;
		
		$this->logger->debug(
				'{class_mame}|{method_name}|{service_id}|queries|{query_total}|{query}|bind_parameters:{bind_params}',
				array(
					'class_mame'=>__CLASS__,
					'method_name'=>__FUNCTION__,
					'query'=>$sql,
					'query_total'=>$sql_total,
					'bind_params'=>json_encode($parameters)
				)
			);
		
		// add some logic to handle exceptions in this script
		$row_count=0; 
		$total_records=0;
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
			//get total records
			$query = $database->prepare($sql_total);	
			if ($query->execute($parameters)) {
				$total_records = $query->rowCount();
			} else {	
				$this->logger->error(
					'{class_mame}|{method_name}|{service_id}|error executing the query|{error}|{query}|bind_parameters:{bind_params}',
					array(
						'class_mame'=>__CLASS__,
						'method_name'=>__FUNCTION__,
						'error'=>$database->errorCode(),
						'query'=>$sql_total,
						'bind_params'=>json_encode($parameters)
					)
				);
				return  array('result' => 5, 'resultDesc' => 'Error executing a query.'); 
			}
			
			//get records
			$query = $database->prepare($sql);	
			if ($query->execute($parameters)) {
				$messages = $query->fetchAll();
				$row_count = $query->rowCount();
				if ($row_count > 0)  {	
					return array('result'=>0, 'resultDesc'=>'Records retrieved successfully.', '_recordsRetrieved' => $row_count, '_totalRecords' => $total_records,  'messages'=>$messages);
				} else { // IMPORTANT to display not configured services
					return array('result'=>0, 'resultDesc'=>'Records retrieved successfully.', '_recordsRetrieved' => $row_count, '_totalRecords' => $total_records,  'messages'=>$messages);
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
	
	public function generateDeliveryReceiptsReport($start_date, $end_date, $delivery_status='', $order='DESC')
	{
		$sql_total = 'SELECT id FROM tbl_delivery_receipts a WHERE a.created_on>:start_date AND a.created_on<=:end_date ';
		$sql = 'SELECT date(created_on) as calendar_date, delivery_status, count(id) as message_count FROM tbl_delivery_receipts WHERE created_on>:start_date AND created_on<=:end_date '; //query for retrieving actual records
		$parameters = array(':start_date' => $start_date, ':end_date' => $end_date); //bind parameters
		
		if (isset($delivery_status) && !empty($delivery_status)) { //query report for one service
			$sql_total .= ' AND delivery_status = :delivery_status ';
			$sql .= ' AND delivery_status = :delivery_status ';
			$parameters[':delivery_status']=$delivery_status;
		}
		
		$sql.=' GROUP BY calendar_date, delivery_status ORDER BY calendar_date '.$order.' LIMIT 0,30';
		
		$this->logger->debug(
				'{class_mame}|{method_name}|{service_id}|queries|{query_total}|{query}|bind_parameters:{bind_params}',
				array(
					'class_mame'=>__CLASS__,
					'method_name'=>__FUNCTION__,
					'query'=>$sql,
					'query_total'=>$sql_total,
					'bind_params'=>json_encode($parameters)
				)
			);
		
		// add some logic to handle exceptions in this script
		$row_count=0; 
		$total_records=0;
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
			//get total records
			$query = $database->prepare($sql_total);	
			if ($query->execute($parameters)) {
				$total_records = $query->rowCount();
			} else {	
				$this->logger->error(
					'{class_mame}|{method_name}|{service_id}|error executing the query|{error}|{query}|bind_parameters:{bind_params}',
					array(
						'class_mame'=>__CLASS__,
						'method_name'=>__FUNCTION__,
						'error'=>$database->errorCode(),
						'query'=>$sql_total,
						'bind_params'=>json_encode($parameters)
					)
				);
				return  array('result' => 5, 'resultDesc' => 'Error executing a query.'); 
			}
			
			//get records
			$query = $database->prepare($sql);	
			if ($query->execute($parameters)) {
				$messages = $query->fetchAll();
				$row_count = $query->rowCount();
				if ($row_count > 0)  {	
					return array('result'=>0, 'resultDesc'=>'Records retrieved successfully.', '_recordsRetrieved' => $row_count, '_totalRecords' => $total_records,  'messages'=>$messages);
				} else { // IMPORTANT to display not configured services
					return array('result'=>0, 'resultDesc'=>'Records retrieved successfully.', '_recordsRetrieved' => $row_count, '_totalRecords' => $total_records,  'messages'=>$messages);
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
