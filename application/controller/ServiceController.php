<?php
namespace Ssg\Controller;

use Ssg\Core\Controller;
use Ssg\Model\ServiceModel;
use Ssg\Core\Request;
use Psr\Log\LoggerInterface;

class ServiceController extends Controller
{
    /**
     * Construct this object by extending the basic Controller class
     */
    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
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
	 public function enable()
	 {
		 //log the event
		$this->logger->debug(
			'{class_mame}|{method_name}|{service_id}|enable service request',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'service_id'=>Request::get('service_id')
			)
		);
		
		$model = new ServiceModel($this->logger);
		$resultData=$model->enable(Request::get('service_id'));
		$data = array('data'=>$resultData);
        $this->View->renderWithoutHeaderAndFooter('servicemanager/enable_disable',$data);

		//log the event
		$this->logger->info(
			'{class_mame}|{method_name}|{service_id}|enable service result|result:{result}|result_desc:{result_desc}',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'service_id'=>Request::get('service_id'),
				'result'=>$resultData['result'],
				'result_desc'=>$resultData['resultDesc']
			)
		);
	 }
	 
	 
	 /**
     * Handles what happens when user moves to URL//ServiceManager/disable
     */
	 public function disable()
	 {
		 //log the event
		$this->logger->debug(
			'{class_mame}|{method_name}|{service_id}|disable service request',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'service_id'=>Request::get('service_id')
			)
		);
		$serviceModel = new ServiceModel($this->logger);
		$resultData=$serviceModel->disable(Request::get('service_id'));
 		$data = array('data'=>$resultData);
        $this->View->renderWithoutHeaderAndFooter('servicemanager/enable_disable',$data);
		
		//log the event
		$this->logger->info(
			'{class_mame}|{method_name}|{service_id}|disable service|result:{result}|result_desc:{result_desc}',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'service_id'=>Request::get('service_id'),
				'result'=>$resultData['result'],
				'result_desc'=>$resultData['resultDesc']
			)
		);
	 }
	
}
