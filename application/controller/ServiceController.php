<?php
namespace Ssg\Controller;

use Ssg\Core\Controller;
use Ssg\Model\ServiceModel;
use Ssg\Core\Session;
use Ssg\Core\Text;
use Ssg\Core\Request;
use Ssg\Core\Pagination;
use Ssg\Core\Config;
use Ssg\Core\Auth;
use Psr\Log\LoggerInterface;


class ServiceController extends Controller
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
     * Handles what happens when user moves to URL/ServiceManager/enable
     */
	 public function enable($service_id)
	 {
		 //log the event
		$this->logger->debug(
			'{class_mame}|{method_name}|{service_id}|enable-service-request',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'service_id'=>$service_id
			)
		);
		
		$model = new ServiceModel($this->logger);
		$result=$model->enable($service_id);
		$data = array('service_id'=> $service_id, 'result' => $result);
        $this->View->render('servicemanager/enable',$data);

		//log the event
		$this->logger->info(
			'{class_mame}|{method_name}|{service_id}|result|{result}|{result_desc}',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'service_id'=>$service_id,
				'result'=>$result['result'],
				'result_desc'=>$result['resultDesc']
			)
		);
	 }
	 
	 
	 /**
     * Handles what happens when user moves to URL//ServiceManager/disable
     */
	 public function disable($service_id)
	 {
		//log the event
		$this->logger->debug(
			'{class_mame}|{method_name}|{service_id}|disable service request',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'service_id'=>$service_id
			)
		);
		$serviceModel = new ServiceModel($this->logger);
		$result=$serviceModel->disable($service_id);
 		$data = array('service_id'=> $service_id, 'result' => $result);
        $this->View->render('servicemanager/disable',$data);
		
		//log the event
		$this->logger->info(
			'{class_mame}|{method_name}|{service_id}|disable-service|{result}|{result_desc}',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'service_id'=>$service_id,
				'result'=>$result['result'],
				'result_desc'=>$result['resultDesc']
			)
		);
	 }
	 
	 
	/**
     * Handles what happens when user moves to URL/service/all. This returns all servives in the system. 
     */
    public function all()
    {
		/*
			Initialize the request data
		*/
		//get request data
		$service_id = Request::get('service_id');
		$service_type = Request::get('service_type');
		$short_code = Request::get('short_code');
		$page = (null !== Request::get('page')) ? ((int) Request::get('page')) : 1; //page - default is 1
		$rpp = (int)Config::get('RECORDS_PER_PAGE'); //records per page
		$start_record = ( (int) ( ($page-1) * $rpp) ); // start record 
		
		
		//request data to be used in calling the model
		$data = array(
			'service_id' =>  $service_id,
			'service_type' => $service_type,
			'short_code' => $short_code,
			'page' => $page,
			'rpp' =>  $rpp,
			'start_record' =>  $start_record
		);
		
		//log the event
		$this->logger->debug(
			'{class_mame}|{method_name}|{service_id}|request-request|{data}',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'data'=>json_encode($data)
			)
		);
		
		$service_model= new ServiceModel($this->logger);
		$result=$service_model->getServices($service_id, $service_type, $short_code, $start_record, Config::get('RECORDS_PER_PAGE'));
		$data['result'] = $result; 
		
		
		//add some pagination logic here
		$total_records = isset($result['_totalRecords']) ? $result['_totalRecords'] : 0;
		$pagination = (new Pagination());
		$pagination->setCurrent($page);
		$pagination->setTotal($total_records);
		$markup = $pagination->parse();
		$data['markup'] = $markup; 
		
		//success
		if ($result['result'] == 0 ) {
			
			$this->View->render('servicemanager/all',$data);
		} else {
			$this->View->render('error/loaderror',$result['resultDesc']);
		}
		
		//log the event
		$this->logger->debug(
			'{class_mame}|{method_name}|{service_id}|result|{result}|{result_desc}',
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
    public function view($service_id)
    {
		
		$data = array('service_id' => $service_id);
		//log the event
		$this->logger->debug(
			'{class_mame}|{method_name}|{service_id}|request-data',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'service_id'=>$service_id
			)
		);
		
		$service_model= new ServiceModel($this->logger);
		$result=$service_model->getService($service_id);
		$data['result'] = $result; 
		
		//success
		if ($result['result'] == 0 ) {
			
			$this->View->render('servicemanager/view',$data);
		} else {
			$this->View->render('error/loaderror',$result['resultDesc']);
		}
		
		//log the event
		$this->logger->debug(
			'{class_mame}|{method_name}|{service_id}|result|result:{result}|{result_desc}',
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
    public function add()
    {	
		//get request data
		$service_id=Request::post('service_id',true);
		$service_name=Request::post('service_name',true);
		$service_type=Request::post('service_type',true);
		$short_code=Request::post('short_code',true);
		$criteria=Request::post('criteria',true);
		$service_endpoint=Request::post('service_endpoint',true);
		$delivery_notification_endpoint=Request::post('delivery_notification_endpoint',true);
		$interface_name=Request::post('interface_name',true);
		
		$data = array(
			'service_id' => $service_id,
			'service_name' => $service_name,
			'service_type' => $service_type,
			'short_code' => $short_code,
			'criteria' => $criteria,
			'service_endpoint' => $service_endpoint,
			'delivery_notification_endpoint' => $delivery_notification_endpoint,
			'interface_name' => $interface_name
		);
		
		//log the event
		$this->logger->debug(
			'{class_mame}|{method_name}|{service_id}|request-data',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'data'=>json_encode($data)
			)
		);
		
		if(null !== Request::post('action',true)){
			//form submitted, processing to happen below
			$service_model= new ServiceModel($this->logger);
			$result=$service_model->addService($data);
			$data['result'] = $result; 
			
			//success
			if ($result['result'] == 0 ) {
				Session::add('feedback_positive', 'Service created successfully');
			} else {
				Session::add('feedback_negative', 'Service creation failed. Error: '.$result['result'].' - '. $result['resultDesc']);
			}
			
			//log the event
			$this->logger->debug(
				'{class_mame}|{method_name}|{service_id}|result|{result}|{result_desc}',
				array(
					'class_mame'=>__CLASS__,
					'method_name'=>__FUNCTION__,
					'result'=>$result['result'],
					'result_desc'=>$result['resultDesc']
				)
			);
		} 
		$this->View->render('servicemanager/add',$data);	
    }
	
	
	/**
     * Handles what happens when user moves to URL/service/all. This returns all servives in the system. 
     */
    public function edit($service_id)
    {	
		//get request data
		$id=Request::post('id',true);
		$service_name=Request::post('service_name',true);
		$service_type=Request::post('service_type',true);
		$short_code=Request::post('short_code',true);
		$criteria=Request::post('criteria',true);
		$service_endpoint=Request::post('service_endpoint',true);
		$delivery_notification_endpoint=Request::post('delivery_notification_endpoint',true);
		$interface_name=Request::post('interface_name',true);
		
		$data = array(
			'id' => $id,
			'service_id' => $service_id,
			'service_name' => $service_name,
			'service_type' => $service_type,
			'short_code' => $short_code,
			'criteria' => $criteria,
			'service_endpoint' => $service_endpoint,
			'delivery_notification_endpoint' => $delivery_notification_endpoint,
			'interface_name' => $interface_name
		);
		
		//log the event
		$this->logger->debug(
			'{class_mame}|{method_name}|{service_id}|request-data',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'request-data'=>json_encode($data)
			)
		);
		
		if(null !== Request::post('action',true)){
			//form submitted, processing to happen below
			$service_model= new ServiceModel($this->logger);
			$result=$service_model->updateService($data);
			$data['result'] = $result; 
			//success
			if ($result['result'] == 0 ) {
				Session::add('feedback_positive', 'Service updated successfully');
			} else {
				Session::add('feedback_negative', 'Service updating failed. Error: '.$result['result'].' - '. $result['resultDesc']);
			}
			
			//log the event
			$this->logger->info(
				'{class_mame}|{method_name}|{service_id}|edit-service-result|{result}|{result_desc}',
				array(
					'class_mame'=>__CLASS__,
					'method_name'=>__FUNCTION__,
					'result'=>$result['result'],
					'result_desc'=>$result['resultDesc']
				)
			);
		} else {
			//load servive data from windows
			$service_model= new ServiceModel($this->logger);
			$result=$service_model->getService($service_id);
			$data['result'] = $result; 
			//successful loading of service
			if ($result['result'] == 0 ) {
				$data=json_decode(json_encode($result['service']),true);
			} else {
				Session::add('feedback_negative', 'Service '.$service_id.' loading failed. Error: '.$result['result'].' - '. $result['resultDesc']);
			}
			//log the event
			$this->logger->debug(
				'{class_mame}|{method_name}|{service_id}|result|{result}|{result_desc}',
				array(
					'class_mame'=>__CLASS__,
					'method_name'=>__FUNCTION__,
					'result'=>$result['result'],
					'result_desc'=>$result['resultDesc']
				)
			);
		}
		$this->View->render('servicemanager/edit',$data);
			
    }
	
	
	/**
     * Handles what happens when user moves to URL/service/all. This returns all servives in the system. 
     */
    public function delete($service_id)
    {	
		//get request data
		$id='';
		$service_name='';
		$service_type='';
		$short_code='';
		$criteria='';
		$service_endpoint='';
		$delivery_notification_endpoint='';
		$interface_name='';
		
		$data = array(
			'id' => $id,
			'service_id' => $service_id,
			'service_name' => $service_name,
			'service_type' => $service_type,
			'short_code' => $short_code,
			'criteria' => $criteria,
			'service_endpoint' => $service_endpoint,
			'delivery_notification_endpoint' => $delivery_notification_endpoint,
			'interface_name' => $interface_name
		);
		
		//log the event
		$this->logger->debug(
			'{class_mame}|{method_name}|{service_id}|request-data',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'request-data'=>json_encode($data)
			)
		);
		
		if(null !== Request::post('action',true)){
			//form submitted, processing to happen below
			$service_model= new ServiceModel($this->logger);
			$result=$service_model->deleteService($service_id);
			$data['result'] = $result; 
			//success
			if ($result['result'] == 0 ) {
				Session::add('feedback_positive', 'Service deleted successfully');
			} else {
				Session::add('feedback_negative', 'Service deletion failed. Error: '.$result['result'].' - '. $result['resultDesc']);
			}
			
			//log the event
			$this->logger->debug(
				'{class_mame}|{method_name}|{service_id}|edit-service-result|result:{result}|result_desc:{result_desc}',
				array(
					'class_mame'=>__CLASS__,
					'method_name'=>__FUNCTION__,
					'result'=>$result['result'],
					'result_desc'=>$result['resultDesc'],
					'result_desc'=>json_encode($result)
				)
			);
		} else {
			//load servive data from windows
			$service_model= new ServiceModel($this->logger);
			$result=$service_model->getService($service_id);
			$data['result'] = $result; 
			//successful loading of service
			if ($result['result'] == 0 ) {
				$data=json_decode(json_encode($result['service']),true);
			} else {
				Session::add('feedback_negative', 'Service '.$service_id.' loading failed. Error: '.$result['result'].' - '. $result['resultDesc']);
			}
			//log the event
			$this->logger->debug(
				'{class_mame}|{method_name}|{service_id}|result|{result}|{result_desc}',
				array(
					'class_mame'=>__CLASS__,
					'method_name'=>__FUNCTION__,
					'result'=>$result['result'],
					'result_desc'=>$result['resultDesc']
				)
			);
		}
		$this->View->render('servicemanager/delete',$data);
			
    }
	
}
