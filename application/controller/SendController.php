<?php

class SendController extends Controller
{
    /**
     * Construct this object by extending the basic Controller class
     */
    public function __construct()
    {
        parent::__construct();
    }
	
    /**
     * Handles what happens when user moves to URL/notify
     */
    public function index()
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
		
		//call the sender model to process
		$resultData = SendModel::process($data);
		
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
	
	/**
     * Handles what happens when user moves to URL/notify/sms
     */
	 public function sms()
	 {
		 $this->index();
	 }
	
}
