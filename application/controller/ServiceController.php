<?php

class ServiceController extends Controller
{
    /**
     * Construct this object by extending the basic Controller class
     */
    public function __construct()
    {
        parent::__construct();
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
		$resultData=ServiceManagerModel::enable(Request::get('service_id'));
		print_r($resultData);
		//render the view and pass the raw post data
        $this->View->renderWithoutHeaderAndFooter('servicemanager/enable_disable',$resultData);
	 }
	 
	 
	 /**
     * Handles what happens when user moves to URL//ServiceManager/disable
     */
	 public function disable()
	 {
		 $resultData=ServiceManagerModel::disable(Request::get('service_id'));
		//render the view and pass the raw post data
        $this->View->renderWithoutHeaderAndFooter('servicemanager/enable_disable',$resultData);
	 }
	
}
