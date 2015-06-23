<?php
namespace Ssg\Controller;

use Ssg\Core\Controller;
use Ssg\Core\Config;
use Ssg\Core\Pagination;
use Ssg\Model\ReportModel;
use Ssg\Core\Request;
use Ssg\Core\Auth;
use Psr\Log\LoggerInterface;


class ReportsController extends Controller
{
    /**
     * Construct this object by extending the basic Controller class
     */
    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
		
		// this entire controller should only be visible/usable by logged in users, so we put authentication-check here
		Auth::checkAuthentication();
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
		//get request data
		$service_id = Request::get('service_id');
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
		
		$model=new ReportModel($this->logger);
		$result = $model->generateInboxReport($start_date.' 00:00:00', $end_date.' 23:59:59',$service_id);
		//add result
		$data['result'] = $result; 
	
		$this->View->render('reports/inbox', $data);
		
		//log the event
		$this->logger->info(
			'{class_mame}|{method_name}|result|{result}|{result_desc}',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'result'=>$result['result'],
				'result_desc'=>$result['resultDesc'],
			)
		);	
    }
	
	
	/**
     * Handles what happens when user moves to URL/service/all. This returns all servives in the system. 
     */
    public function outbox()
    {
		//get request data
		$service_id = Request::get('service_id');
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
		
		$model=new ReportModel($this->logger);
		$result = $model->generateOutboxReport($start_date.' 00:00:00', $end_date.' 23:59:59', $service_id, $batch_id);
		//add result
		$data['result'] = $result; 
	
		$this->View->render('reports/outbox', $data);
		
		//log the event
		$this->logger->info(
			'{class_mame}|{method_name}|result|{result}|{result_desc}',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'result'=>$result['result'],
				'result_desc'=>$result['resultDesc'],
			)
		);	
    }
	
	/**
     * Handles what happens when user moves to URL/service/all. This returns all servives in the system. 
     */
    public function subscriptions()
    {
		//get request data
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
			'service_id' =>  $service_id,
			'product_id' =>  $product_id,
			'update_type' =>  $update_type,
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
		
		$model=new ReportModel($this->logger);
		$result = $model->generateSubscriptionsReport($start_date.' 00:00:00', $end_date.' 23:59:59', $service_id, $product_id, $update_type);
		//add result
		$data['result'] = $result; 
	
		$this->View->render('reports/subscriptions', $data);
		
		//log the event
		$this->logger->info(
			'{class_mame}|{method_name}|result|{result}|{result_desc}',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'result'=>$result['result'],
				'result_desc'=>$result['resultDesc'],
			)
		);	
    }
	
	
	/**
     * Handles what happens when user moves to URL/service/all. This returns all servives in the system. 
     */
    public function delvryrcpts()
    {
		//get request data
		$delivery_status = Request::get('delivery_status');
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
			'delivery_status' =>  $delivery_status,
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
		$model=new ReportModel($this->logger);
		$result = $model->generateDeliveryReceiptsReport($start_date.' 00:00:00', $end_date.' 23:59:59', $delivery_status);
		//add result
		$data['result'] = $result; 
		$this->View->render('reports/delvryrcpts', $data);
		//log the event
		$this->logger->info(
			'{class_mame}|{method_name}|result|{result}|{result_desc}',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'result'=>$result['result'],
				'result_desc'=>$result['resultDesc'],
			)
		);	
    }
	
}
