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
		//receive the post data
		//$raw_post_data = file_get_contents("php://input");
		
		//process the data
		$data['dummy']="data";
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
