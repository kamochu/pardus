<?php
namespace Ssg\Controller;

use Ssg\Core\Controller;
use Ssg\Core\Config;
use Ssg\Core\Pagination;
use Ssg\Model\MessageModel;
use Ssg\Core\Request;
use Ssg\Core\Auth;
use Ssg\Core\PDF;
use Ssg\Core\Session;
use Psr\Log\LoggerInterface;


class MessagesController extends Controller
{
    /**
     * Construct this object by extending the basic Controller class
     */
    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
		
		// this entire controller should only be visible/usable by logged in users, so we put authentication-check here
		Auth::checkAuthentication();
		
		//check the IP whitelist
		Auth::checkIPAuthentication();
    }
	
    /**
     * Handles what happens when user moves to URL/delivery
     */
    public function index()
    {		
		//bad request error
		header('HTTP/1.0 400 Bad Request');
		$this->View->renderWithoutHeaderAndFooter('error/httperror500',array("error"=>$resultData['result']." - ".$resultData['resultDesc']));	
    }
	 
	/**
     * Handles what happens when user moves to URL/service/all. This returns all servives in the system. 
     */
    public function inbox()
    {
		/*
			Initialize the request data
		*/
		//get request data
		$service_id = Request::get('service_id');
		$sender_address = Request::get('sender_address');
		$dest_address = Request::get('dest_address');
		$start_date = Request::get('start_date');
		$end_date = Request::get('end_date');
		$page = (null !== Request::get('page')) ? ((int) Request::get('page')) : 1; //page - default is 1
		$rpp = (int)Config::get('RECORDS_PER_PAGE'); //records per page
		$start_record = ( (int) ( ($page-1) * $rpp) ); // start record 
		
		//set default start date - 1 month ago
		if (!isset($start_date) || $start_date == '') {
			$date = date_create(date('Y-m-d'));
			date_sub($date, date_interval_create_from_date_string('1 months'));
			$start_date = date_format($date, 'Y-m-d');
		}
		
		//set default end date  - current day
		if (!isset($end_date) || $end_date == '') {
			$end_date = date('Y-m-d');
		}
		
		//request data to be used in calling the model
		$data = array(
			'service_id' =>  $service_id,
			'sender_address' =>  $sender_address,
			'dest_address' =>  $dest_address,
			'start_date' =>  $start_date,
			'end_date' =>  $end_date
		);
		
		//log the event
		$this->logger->debug(
			'{class_mame}|{method_name}|request|request-data:{data}',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'data'=> json_encode($data)
			)
		);
		
		//call the model 
		$model=new MessageModel($this->logger);
		$result = $model->getInboundMessages($start_date.' 00:00:00', $end_date.' 23:59:59', $sender_address, $dest_address, $service_id, $start_record, Config::get('RECORDS_PER_PAGE'));
		//add result
		$data['result'] = $result; 
		
		
		//add some pagination logic here
		$total_records = isset($result['_totalRecords']) ? $result['_totalRecords'] : 0;
		$pagination = (new Pagination());
		$pagination->setCurrent($page);
		$pagination->setTotal($total_records);
		$markup = $pagination->parse();
		$data['markup'] = $markup; 
		$this->View->render('messages/inbox', $data);
		
		//log the event
		$this->logger->info(
			'{class_mame}|{method_name}|result|result:{result}|result_desc:{result_desc}',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'result'=>$result['result'],
				'result_desc'=>$result['resultDesc']
			)
		);	
    }
	
	/**
     * Handles what happens when user moves to URL/service/all. This returns all servives in the system. 
     */
    public function inbox_pdf()
    {
		/*
			Initialize the request data
		*/
		//get request data
		$service_id = Request::get('service_id');
		$sender_address = Request::get('sender_address');
		$dest_address = Request::get('dest_address');
		$start_date = Request::get('start_date');
		$end_date = Request::get('end_date');
		
		//set default start date - 1 month ago
		if (!isset($start_date) || $start_date == '') {
			$date = date_create(date('Y-m-d'));
			date_sub($date, date_interval_create_from_date_string('1 months'));
			$start_date = date_format($date, 'Y-m-d');
		}
		
		//set default end date  - current day
		if (!isset($end_date) || $end_date == '') {
			$end_date = date('Y-m-d');
		}
		
		//request data to be used in calling the model
		$data = array(
			'service_id' =>  $service_id,
			'sender_address' =>  $sender_address,
			'dest_address' =>  $dest_address,
			'start_date' =>  $start_date,
			'end_date' =>  $end_date
		);
		
		//log the event
		$this->logger->debug(
			'{class_mame}|{method_name}|request|request-data:{data}',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'data'=> json_encode($data)
			)
		);
		
		//call the model 
		$model=new MessageModel($this->logger);
		$result = $model->getInboundMessages($start_date.' 00:00:00', $end_date.' 23:59:59', $sender_address, $dest_address, $service_id, 0, Config::get('MAX_RECORDS_PDF'));
		//add result
		$data['result'] = $result; 
		
		//pdf data preparation
		$title= "Inbound Messages (Inbox) Extract - Service ID: $service_id, Sender: $sender_address, Code:$dest_address, Start Date: $start_date, End Date: $end_date";
		$headers = array('#', 'Sender', 'Code', 'Service ID', 'Link ID', 'Message', 'Processing Time');
		$sizes = 		array(10, 25, 15, 30, 35, 133, 30);
		$max_sizes = 	array( 8, 20, 10, 25, 30, 100, 20);
		$data = array();
		$filename = __FUNCTION__.'_'.Session::get('user_name').'_'.date('YmdHis').'.pdf';
		
		$i=0 ;
		foreach ($result['messages'] as $message) {
			$data[$i] = array(
							$message->message_id, 
							$message->sender_address, 
							$message->dest_address, 
							$message->service_id, 
							$message->link_id, 
							$message->message, 
							$message->created_on
						);
			$i++;
		}
		
		$pdf = new PDF($title, $data, $headers, $sizes, $max_sizes);
		$pdf->Output($filename,'I');
		
		//log the event
		$this->logger->info(
			'{class_mame}|{method_name}|result|result:{result}|result_desc:{result_desc}',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'result'=>$result['result'],
				'result_desc'=>$result['resultDesc']
			)
		);	
    }
	
	
	/**
     * Handles what happens when user moves to URL/service/all. This returns all servives in the system. 
     */
    public function outbox()
    {
		/*
			Initialize the request data
		*/
		//get request data
		$service_id = Request::get('service_id');
		$sender_address = Request::get('sender_address');
		$dest_address = Request::get('dest_address');
		$batch_id = Request::get('batch_id');
		$start_date = Request::get('start_date');
		$end_date = Request::get('end_date');
		$page = (null !== Request::get('page')) ? ((int) Request::get('page')) : 1; //page - default is 1
		$rpp = (int)Config::get('RECORDS_PER_PAGE'); //records per page
		$start_record = ( (int) ( ($page-1) * $rpp) ); // start record 
		
		//set default start date - 1 month ago
		if (!isset($start_date) || $start_date == '') {
			$date = date_create(date('Y-m-d'));
			date_sub($date, date_interval_create_from_date_string('1 months'));
			$start_date = date_format($date, 'Y-m-d');
		}
		
		//set default end date  - current day
		if (!isset($end_date) || $end_date == '') {
			$end_date = date('Y-m-d');
		}
		
		
		//request data to be used in calling the model
		$data = array(
			'service_id' =>  $service_id,
			'sender_address' =>  $sender_address,
			'dest_address' =>  $dest_address,
			'batch_id' =>  $batch_id,
			'start_date' =>  $start_date,
			'end_date' =>  $end_date
		);
		
		//log the event
		$this->logger->debug(
			'{class_mame}|{method_name}|request|request-data:{data}',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'data'=> json_encode($data)
			)
		);
		
		//call the model 
		$model=new MessageModel($this->logger);
		$result = $model->getOutboundMessages($start_date.' 00:00:00', $end_date.' 23:59:59', $dest_address, $sender_address, $service_id, $batch_id, $start_record, Config::get('RECORDS_PER_PAGE'));
		//add result
		$data['result'] = $result; 
		
		//add some pagination logic
		$total_records = isset($result['_totalRecords']) ? $result['_totalRecords'] : 0;
		$pagination = (new Pagination());
		$pagination->setCurrent($page);
		$pagination->setTotal($total_records);
		$markup = $pagination->parse();
		$data['markup'] = $markup; 
		$this->View->render('messages/outbox', $data);

		
		//log the event
		$this->logger->info(
			'{class_mame}|{method_name}|result|{result}|result_desc:{result_desc}',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'result'=>$result['result'],
				'result_desc'=>$result['resultDesc']
			)
		);	
    }
	
	
	
	public function outbox_pdf()
    {
		/*
			Initialize the request data
		*/
		//get request data
		$service_id = Request::get('service_id');
		$sender_address = Request::get('sender_address');
		$dest_address = Request::get('dest_address');
		$batch_id = Request::get('batch_id');
		$start_date = Request::get('start_date');
		$end_date = Request::get('end_date');
		
		//set default start date - 1 month ago
		if (!isset($start_date) || $start_date == '') {
			$date = date_create(date('Y-m-d'));
			date_sub($date, date_interval_create_from_date_string('1 months'));
			$start_date = date_format($date, 'Y-m-d');
		}
		
		//set default end date  - current day
		if (!isset($end_date) || $end_date == '') {
			$end_date = date('Y-m-d');
		}
		
		
		//request data to be used in calling the model
		$data = array(
			'service_id' =>  $service_id,
			'sender_address' =>  $sender_address,
			'dest_address' =>  $dest_address,
			'batch_id' =>  $batch_id,
			'start_date' =>  $start_date,
			'end_date' =>  $end_date
		);
		
		//log the event
		$this->logger->debug(
			'{class_mame}|{method_name}|request|request-data:{data}',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'data'=> json_encode($data)
			)
		);
		
		//call the model 
		$model=new MessageModel($this->logger);
		$result = $model->getOutboundMessages($start_date.' 00:00:00', $end_date.' 23:59:59', $dest_address, $sender_address, $service_id, $batch_id, 0, Config::get('MAX_RECORDS_PDF'));
		//add result
		$data['result'] = $result; 
		
		//print_r($result);
		
		$title= "Outbound Messages (Outbox) Extract - Service ID: $service_id, Code: $sender_address, Recipient:$dest_address, Batch ID: $batch_id, Start Date: $start_date, End Date: $end_date";
		$headers = array('#', 'Code', 'Recipient', 'Service ID', 'Batch ID', 'Message', 'Send Status', 'Delivery Status', 'Processing Time');
		$sizes = 		array(10, 15, 25, 30, 15, 93, 30, 30, 30);
		$max_sizes = 	array( 8, 10, 20, 25, 10, 70, 20, 20, 20);
		$data = array();
		$filename = __FUNCTION__.'_'.Session::get('user_name').'_'.date('YmdHis').'.pdf';
		
		$i=0 ;
		foreach ($result['messages'] as $message) {
			$data[$i] = array(
							$message->message_id, 
							$message->sender_address, 
							$message->dest_address, 
							$message->service_id, 
							$message->batch_id, 
							$message->message, 
							$message->status_desc, 
							$message->delivery_status, 
							$message->created_on
						);
			$i++;
		}
		
		$pdf = new PDF($title, $data, $headers, $sizes, $max_sizes);
		$pdf->Output($filename,'I');

		
		//log the event
		$this->logger->info(
			'{class_mame}|{method_name}|result|{result}|result_desc:{result_desc}',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'result'=>$result['result'],
				'result_desc'=>$result['resultDesc']
			)
		);	
    }
	
	
	/**
     * Handles what happens when user moves to URL/service/all. This returns all servives in the system. 
     */
    public function delvryrcpts()
    {
		/*
			Initialize the request data
		*/
		//get request data
		$dest_address = Request::get('dest_address');
		$correlator = Request::get('correlator');
		$start_date = Request::get('start_date');
		$end_date = Request::get('end_date');
		$page = (null !== Request::get('page')) ? ((int) Request::get('page')) : 1; //page - default is 1
		$rpp = (int)Config::get('RECORDS_PER_PAGE'); //records per page
		$start_record = ( (int) ( ($page-1) * $rpp) ); // start record 
		
		//set default start date - 1 month ago
		if (!isset($start_date) || $start_date == '') {
			$date = date_create(date('Y-m-d'));
			date_sub($date, date_interval_create_from_date_string('1 months'));
			$start_date = date_format($date, 'Y-m-d');
		}
		
		//set default end date  - current day
		if (!isset($end_date) || $end_date == '') {
			$end_date = date('Y-m-d');
		}
		
		
		//request data to be used in calling the model
		$data = array(
			'dest_address' =>  $dest_address,
			'correlator' => $correlator,
			'start_date' =>  $start_date,
			'end_date' =>  $end_date
		);
		
		//log the event
		$this->logger->debug(
			'{class_mame}|{method_name}|request|request-data:{data}',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'data'=> json_encode($data)
			)
		);
		
		//call the model - getDeliveryMessages($start_date, $end_date, $subscriber_id='', $correlator='', $start_index=0, $limit=10, $order='DESC')
		$model=new MessageModel($this->logger);
		$result = $model->getDeliveryMessages($start_date.' 00:00:00', $end_date.' 23:59:59', $dest_address, $correlator, $start_record, Config::get('RECORDS_PER_PAGE'));
		//add result
		$data['result'] = $result; 
		
		//add some pagination logic
		$total_records = isset($result['_totalRecords']) ? $result['_totalRecords'] : 0;
		$pagination = (new Pagination());
		$pagination->setCurrent($page);
		$pagination->setTotal($total_records);
		$markup = $pagination->parse();
		$data['markup'] = $markup; 
		$this->View->render('messages/delvryrcpts', $data);

		
		//log the event
		$this->logger->info(
			'{class_mame}|{method_name}|result|{result}|result_desc:{result_desc}',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'result'=>$result['result'],
				'result_desc'=>$result['resultDesc']
			)
		);	
    }
	
	
	 public function delvryrcpts_pdf()
    {
		/*
			Initialize the request data
		*/
		//get request data
		$dest_address = Request::get('dest_address');
		$correlator = Request::get('correlator');
		$start_date = Request::get('start_date');
		$end_date = Request::get('end_date');
		
		//set default start date - 1 month ago
		if (!isset($start_date) || $start_date == '') {
			$date = date_create(date('Y-m-d'));
			date_sub($date, date_interval_create_from_date_string('1 months'));
			$start_date = date_format($date, 'Y-m-d');
		}
		
		//set default end date  - current day
		if (!isset($end_date) || $end_date == '') {
			$end_date = date('Y-m-d');
		}
		
		
		//request data to be used in calling the model
		$data = array(
			'dest_address' =>  $dest_address,
			'correlator' => $correlator,
			'start_date' =>  $start_date,
			'end_date' =>  $end_date
		);
		
		//log the event
		$this->logger->debug(
			'{class_mame}|{method_name}|request|request-data:{data}',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'data'=> json_encode($data)
			)
		);
		
		//call the model - getDeliveryMessages($start_date, $end_date, $subscriber_id='', $correlator='', $start_index=0, $limit=10, $order='DESC')
		$model=new MessageModel($this->logger);
		$result = $model->getDeliveryMessages($start_date.' 00:00:00', $end_date.' 23:59:59', $dest_address, $correlator, 0, Config::get('MAX_RECORDS_PDF'));
		//add result
		$data['result'] = $result; 
		
		//print_r($result);
		
		$title= "Delivery Receipts Extract - Recipient:$dest_address, Correlator: $correlator, Start Date: $start_date, End Date: $end_date";
		$headers = array('#', 'Recipient', 'Correlator', 'Delivery Status', 'SDP Timestamp', 'Trace Unique ID', 'Processing Time');
		$sizes = 		array(15, 40, 40, 40, 45, 58, 40);
		$max_sizes = 	array(10, 28, 28, 28, 30, 40, 25);
		$data = array();
		$filename = __FUNCTION__.'_'.Session::get('user_name').'_'.date('YmdHis').'.pdf';
		
		$i=0 ;
		foreach ($result['messages'] as $message) {
			$data[$i] = array(
							$message->id, 
							$message->dest_address, 
							$message->correlator, 
							$message->delivery_status, 
							$message->time_stamp, 
							$message->trace_unique_id, 
							$message->created_on
						);
			$i++;
		}
		
		$pdf = new PDF($title, $data, $headers, $sizes, $max_sizes);
		$pdf->Output($filename,'I');

		
		//log the event
		$this->logger->info(
			'{class_mame}|{method_name}|result|{result}|result_desc:{result_desc}',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'result'=>$result['result'],
				'result_desc'=>$result['resultDesc']
			)
		);	
    }
	
	
	/**
     * Handles what happens when user moves to URL/service/all. This returns all servives in the system. 
     */
    public function subscriptions()
    {
		/*
			Initialize the request data
		*/
		//get request data
		$subscriber_id = Request::get('subscriber_id');
		$service_id = Request::get('service_id');
		$product_id = Request::get('product_id');
		$update_type = Request::get('update_type');
		$start_date = Request::get('start_date');
		$end_date = Request::get('end_date');
		$page = (null !== Request::get('page')) ? ((int) Request::get('page')) : 1; //page - default is 1
		$rpp = (int)Config::get('RECORDS_PER_PAGE'); //records per page
		$start_record = ( (int) ( ($page-1) * $rpp) ); // start record 
		
		//set default start date - 1 month ago
		if (!isset($start_date) || $start_date == '') {
			$date = date_create(date('Y-m-d'));
			date_sub($date, date_interval_create_from_date_string('1 months'));
			$start_date = date_format($date, 'Y-m-d');
		}
		
		//set default end date  - current day
		if (!isset($end_date) || $end_date == '') {
			$end_date = date('Y-m-d');
		}
		
		
		//request data to be used in calling the model
		$data = array(
			'subscriber_id' =>  $subscriber_id,
			'service_id' => $service_id,
			'product_id' => $product_id,
			'update_type' => $update_type,
			'start_date' =>  $start_date,
			'end_date' =>  $end_date
		);
		
		//log the event
		$this->logger->debug(
			'{class_mame}|{method_name}|request|request-data:{data}',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'data'=> json_encode($data)
			)
		);
		
		//call the model
		$model=new MessageModel($this->logger);
		$result = $model->getSubscriptionMessages($start_date.' 00:00:00', $end_date.' 23:59:59', $subscriber_id, $service_id, $product_id, $update_type, $start_record, Config::get('RECORDS_PER_PAGE'));
		//add result
		$data['result'] = $result; 
		
		//add some pagination logic here
		$total_records = isset($result['_totalRecords']) ? $result['_totalRecords'] : 0;
		$pagination = (new Pagination());
		$pagination->setCurrent($page);
		$pagination->setTotal($total_records);
		$markup = $pagination->parse();
		$data['markup'] = $markup; 
		$this->View->render('messages/subscriptions', $data);

		//log the event
		$this->logger->info(
			'{class_mame}|{method_name}|result|{result}|result_desc:{result_desc}',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'result'=>$result['result'],
				'result_desc'=>$result['resultDesc']
			)
		);	
    }
	
	public function subscriptions_pdf()
    {
		/*
			Initialize the request data
		*/
		//get request data
		$subscriber_id = Request::get('subscriber_id');
		$service_id = Request::get('service_id');
		$product_id = Request::get('product_id');
		$update_type = Request::get('update_type');
		$start_date = Request::get('start_date');
		$end_date = Request::get('end_date');
		
		//set default start date - 1 month ago
		if (!isset($start_date) || $start_date == '') {
			$date = date_create(date('Y-m-d'));
			date_sub($date, date_interval_create_from_date_string('1 months'));
			$start_date = date_format($date, 'Y-m-d');
		}
		
		//set default end date  - current day
		if (!isset($end_date) || $end_date == '') {
			$end_date = date('Y-m-d');
		}
		
		
		//request data to be used in calling the model
		$data = array(
			'subscriber_id' =>  $subscriber_id,
			'service_id' => $service_id,
			'product_id' => $product_id,
			'update_type' => $update_type,
			'start_date' =>  $start_date,
			'end_date' =>  $end_date
		);
		
		//log the event
		$this->logger->debug(
			'{class_mame}|{method_name}|request|request-data:{data}',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'data'=> json_encode($data)
			)
		);
		
		//call the model
		$model=new MessageModel($this->logger);
		$result = $model->getSubscriptionMessages($start_date.' 00:00:00', $end_date.' 23:59:59', $subscriber_id, $service_id, $product_id, $update_type, 0, Config::get('MAX_RECORDS_PDF'));
		//add result
		$data['result'] = $result; 
		
		//print_r($result);
		
		//request data to be used in calling the model
		$data = array(
			'subscriber_id' =>  $subscriber_id,
			'service_id' => $service_id,
			'product_id' => $product_id,
			'update_type' => $update_type,
			'start_date' =>  $start_date,
			'end_date' =>  $end_date
		);
		
		$title= "Subscription Requests Extract - Subscriber: $subscriber_id, Service ID: $service_id, Product ID: $product_id, Update Type: $update_type, Start Date: $start_date, End Date: $end_date";
		$headers = array('#', 'Subscriber', 'Service ID', 'Product ID', 'Update Type', 'Effective Time', 'Expiry Time', 'Processing Time');
		$sizes = 		array(15, 43, 40, 40, 35, 35, 35, 35); 
		$max_sizes = 	array(10, 30, 28, 28, 30, 25, 25, 25);
		$data = array();
		$filename = __FUNCTION__.'_'.Session::get('user_name').'_'.date('YmdHis').'.pdf';
		
		$i=0 ;
		foreach ($result['messages'] as $message) {
			$data[$i] = array(
							$message->id, 
							$message->subscriber_id, 
							$message->service_id, 
							$message->product_id, 
							$message->update_desc, 
							$message->effective_time, 
							$message->expiry_time, 
							$message->created_on
						);
			$i++;
		}
	
		
		$pdf = new PDF($title, $data, $headers, $sizes, $max_sizes);
		$pdf->Output($filename,'I');

		//log the event
		$this->logger->info(
			'{class_mame}|{method_name}|result|{result}|result_desc:{result_desc}',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'result'=>$result['result'],
				'result_desc'=>$result['resultDesc']
			)
		);	
    }
}
