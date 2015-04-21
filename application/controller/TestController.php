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
		
		//echo "\n\n\nDELETE SERVICE:\n\n";
		//print_r(ServiceModel::deleteService('6013992000001494'));
		
		//echo "\n\n\nADD SERVICE:\n\n";
		//print_r(ServiceModel::addService(array('service_id' => '6013992000001494', 'service_name' => 'Test service', 'service_type'=>1, 'short_code' => '29678', 'criteria' => '', 'service_endpoint' => 'http://192.168.0.16/pardus/notify/sms/', 'delivery_notification_endpoint' => 'http://192.168.0.16/pardus/delivery/receipt/',  'interface_name' => 'notifySmsReception', 'correlator' => '34234234', 'status' => 0,'last_updated_by' => '2')));
		
		//echo "\n\n\nGET INBOUND MESSAGES:\n\n"; 
		//print_r(MessageModel:: getInboundMessages('2015-04-21 08:38:36', ''2015-04-21 08:38:39'', $subscriber_id='',  $short_code='', $service_id='', $start_index=0, $limit=10));
		//print_r(MessageModel:: getInboundMessages('2015-04-21 08:38:36', '2015-04-21 08:38:37', '', '', '', 0, 10));
		
		//echo "\nGET OUTBOUND MESSAGES:\n\n"; 
		//print_r(MessageModel:: getInboundMessages('2015-04-21 08:38:36', ''2015-04-21 08:38:39'', $subscriber_id='',  $short_code='', $service_id='', $start_index=0, $limit=10));
		//print_r(MessageModel:: getOutboundMessages('2015-04-21 08:38:52', '2015-04-21 08:38:59', 'tel:72220150421081626', '29208', '6013992000001491', '2015042108', 2, 100));
		
				
		//echo "\nGET SUBSCRIPTION MESSAGES:\n\n"; 
		//$start_date, $end_date, $subscriber_id='', $service_id='', $product_id='', $update_type='', $start_index=0, $limit=10
		//print_r(MessageModel:: getSubscriptionMessages('2015-04-15 17:00:04', '2015-04-15 17:29:43','2547212148487809','6013992000001442','MDSP2000052892',1,7,3));
		
		echo "\nGET DELIVERY MESSAGES:\n\n"; 
		//$start_date, $end_date, $subscriber_id='', $correlator='', $start_index=0, $limit=10
		print_r(MessageModel:: getDeliveryMessages('2015-04-20 15:00:54', '2015-04-20 15:15:54','tel:72220150417152903','1220150417152903000000',0,1));
		
		//render the view and pass the raw post data
        $this->View->renderWithoutHeaderAndFooter('test/index',array('test'=>'This is a test page.'));
	 }
	
}
