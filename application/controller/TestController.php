<?php
namespace Ssg\Controller;

use Ssg\Core\Controller;
use Ssg\Core\Pagination;
use Ssg\Model\MessageModel;
use Ssg\Model\ServiceModel;
use Psr\Log\LoggerInterface;

class TestController extends Controller
{
   /**
     * Construct this object by extending the basic Controller class
     */
    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
    }
	 
	 /**
     * Handles what happens when user moves to URL/test/index
     */
	 public function index()
	 {
		 //test pagination class 
		 $page = isset($_GET['page']) ? ((int) $_GET['page']) : 1;
		 
		// instantiate; set current page; set number of records
		$pagination = (new Pagination());
		$pagination->setCurrent($page);
		$pagination->setTotal(200);
		
		$markup = $pagination->parse();
		
		echo $markup."\n\n\n";
		 
		echo "\n\n\nGET ALL SERVICES:\n\n";
		$service_model= new ServiceModel($this->logger);
		print_r($service_model->getServices());
		
		$response = $service_model->getService('6013992000001494');
		echo "\n\n\nQUERY SERVICE\n\n";
		print_r($response);
		
		$service = $response['service'];
		$service->short_code = '29333';
		$service->criteria = 'Love';
		$service->correlator = '20150417172519';
		$service->service_name = 'Service name after update';
		$response = $service_model->updateService(json_decode(json_encode($service),true));
		echo "\n\n\nUPDATE SERVICE\n\n";
		print_r($response);
		
		
		
		//print_r($service);
		//$response2 = ServiceModel::getAllServices();
		//echo "All Services\n\n\n";
		//print_r(ServiceModel::getAllServices());
		
		
		echo "\n\n\nDELETE SERVICE:\n\n";
		print_r($service_model->deleteService('6013992000001495'));
		
		//echo "\n\n\nADD SERVICE:\n\n";
		//print_r($service_model->addService(array('service_id' => '6013992000001495', 'service_name' => 'Test service', 'service_type'=>1, 'short_code' => '29678', 'criteria' => '', 'service_endpoint' => 'http://192.168.0.16/pardus/notify/sms/', 'delivery_notification_endpoint' => 'http://192.168.0.16/pardus/delivery/receipt/',  'interface_name' => 'notifySmsReception', 'correlator' => '34234234', 'status' => 0,'last_updated_by' => '2')));
		
		$model=new MessageModel($this->logger);
		echo "\n\n\nGET INBOUND MESSAGES:\n\n"; 
		//print_r(MessageModel:: getInboundMessages('2015-04-21 08:38:36', ''2015-04-21 08:38:39'', $subscriber_id='',  $short_code='', $service_id='', $start_index=0, $limit=10));
		print_r($model->getInboundMessages('2015-04-21 08:38:36', '2016-04-21 08:38:37', '', '', '', 0, 2));
		
		echo "\nGET OUTBOUND MESSAGES:\n\n"; 
		//print_r(MessageModel:: getInboundMessages('2015-04-21 08:38:36', ''2015-04-21 08:38:39'', $subscriber_id='',  $short_code='', $service_id='', $start_index=0, $limit=10));
		print_r($model->getOutboundMessages('2015-04-21 08:38:52', '2016-04-21 08:38:59', '', '', '', '', 0, 2));
		
				
		echo "\nGET SUBSCRIPTION MESSAGES:\n\n"; 
		//$start_date, $end_date, $subscriber_id='', $service_id='', $product_id='', $update_type='', $start_index=0, $limit=10
		print_r($model->getSubscriptionMessages('2015-04-15 17:00:04', '2016-04-15 17:29:43','','','','',0,2));
		
		echo "\nGET DELIVERY MESSAGES:\n\n"; 
		//$start_date, $end_date, $subscriber_id='', $correlator='', $start_index=0, $limit=10
		print_r($model->getDeliveryMessages('2015-04-20 15:00:54', '2016-04-20 15:15:54','','',0,2));
		
		//render the view and pass the raw post data
        $this->View->renderWithoutHeaderAndFooter('test/index',array('test'=>'This is a test page.'));
	 }	
}
