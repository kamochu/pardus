<?php

class TestController extends Controller
{
    /**
     * Construct this object by extending the basic Controller class
     */
    public function __construct()
    {
        parent::__construct();
    }
	 
	 /**
     * Handles what happens when user moves to URL/test/index
     */
	 public function index()
	 {
		//print_r(ServiceModel::getAllServices());
		
		//$response = ServiceModel::getService('6013992000001494');
		//echo "Query sevrice\n\n\n";
		//print_r($response);
		
		/*$service = $response['service'];
		$service->short_code = '29333';
		$service->criteria = 'Love';
		$service->correlator = '20150417172519';
		$service->service_name = 'Service name after update';
		
		print_r($service);
		$response2 = ServiceModel::updateService(json_decode(json_encode($service),true));
		echo "Update sevrice\n\n\n";
		print_r($response2);
		*/
		
		echo "\n\n\nDELETE SERVICE:\n\n";
		print_r(ServiceModel::deleteService('6013992000001494'));
		
		
		echo "\n\n\nADD SERVICE:\n\n";
		print_r(ServiceModel::addService(array('service_id' => '6013992000001494', 'service_name' => 'Test service', 'service_type'=>1, 'short_code' => '29678', 'criteria' => '', 'service_endpoint' => 'http://192.168.0.16/pardus/notify/sms/', 'delivery_notification_endpoint' => 'http://192.168.0.16/pardus/delivery/receipt/',  'interface_name' => 'notifySmsReception', 'correlator' => '34234234', 'status' => 0,'last_updated_by' => '2')));
				
		
		//render the view and pass the raw post data
        $this->View->renderWithoutHeaderAndFooter('test/index',array('test'=>'This is a test page.'));
	 }
	
}
