<?php
namespace Ssg\Controller;

use Ssg\Core\Controller;
use Ssg\Core\Config;
use Ssg\Core\Pagination;
use Ssg\Model\MessageModel;
use Ssg\Core\Request;
use Ssg\Core\Auth;
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
}
