<?php
namespace Ssg\Controller;

use Ssg\Core\Controller;
use Ssg\Model\SendModel;
use Ssg\Core\Request;
use Psr\Log\LoggerInterface;
use Ssg\Core\Auth;

class SendController extends Controller
{
    /**
     * Construct this object by extending the basic Controller class
     */
    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
		
		//check the IP whitelist
		Auth::checkIPAuthentication();
    }
	
	
    /**
     * Handles what happens when user moves to URL/notify
     */
    public function index()
    {
		$this->sms();
    }
	
	/**
     * Handles what happens when user moves to URL/notify/sms
     */
	 public function sms()
	 {
		 //initialize data 
		$data = array();
		$data['service_id'] = '';
		$data['link_id'] = '';
		$data['linked_incoming_msg_id'] = '';
		$data['dest_address'] = '';
		$data['sender_address'] = '';
		$data['correlator'] = '';
		$data['batch_id'] = '';
		$data['message'] = '';
		
		//get the request data - get
		$data['service_id'] = Request::get('service_id',true);
		$data['link_id'] = Request::get('link_id',true);
		$data['linked_incoming_msg_id'] = Request::get('linked_incoming_msg_id',true);
		$data['dest_address'] = Request::get('dest_address',true);
		$data['sender_address'] = Request::get('sender_address',true);
		$data['correlator'] = Request::get('correlator',true);
		$data['message'] = Request::get('message',true);
		$data['batch_id'] = Request::get('batch_id',true);
		
		$this->logger->debug(
			'{class_mame}|{method_name}|send-sms-request|{parameters}',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'parameters'=>implode('|',$data)
			)
		);
		
		//call the sender model to process
		$model = new SendModel($this->logger);
		$resultData = $model->process($data);
		$this->logger->info(
			'{class_mame}|{method_name}|send-sms-result|{parameters}|{result}|{result_desc}|{send_ref_id}|{send_status}|{send_status_desc}',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'parameters'=>implode('|',$data),
				'result'=>$resultData['result'],
				'result_desc'=>$resultData['resultDesc'],
				'send_ref_id'=>$resultData['data']['send_ref_id'],
				'send_status'=>$resultData['data']['status'],
				'send_status_desc'=>$resultData['data']['status_desc']
			)
		);
		
		if($resultData['result'] == 0)  // successful processing
		{
			//render the view and pass the raw post data
        	$this->View->renderWithoutHeaderAndFooter('send/index',$resultData);
		}
		else if( $resultData['result'] == 11) // decoding failure
		{
			//bad request error
			header('HTTP/1.0 400 Bad Request');
		 	$this->View->renderWithoutHeaderAndFooter('error/httperror500',array("error"=>$resultData['result']." - ".$resultData['resultDesc']));	
		}
		else //other errors
		{
			//processing error
			header('HTTP/1.0 500 Internal Server Error');
		 	$this->View->renderWithoutHeaderAndFooter('error/httperror500',array("error"=>$resultData['result']." - ".$resultData['resultDesc']));
		}
	 }
	
}
