<?php
namespace Ssg\Controller;

use Ssg\Core\Controller;
use Ssg\Model\NotifyModel;
use Psr\Log\LoggerInterface;

class NotifyController extends Controller
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
		//change the content type to be used in the response
		header("Content-type: text/xml;charset=utf-8");
		
		 //receive the post data
		$raw_post_data = file_get_contents("php://input");
		
		//log the event
		$this->logger->debug(
			'{class_mame}|{method_name}|raw-request-data|{request_data}',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'request_data'=>$raw_post_data
				)
		);		
		
		//process the data
		$model = new NotifyModel($this->logger);
		$resultData = $model->process($raw_post_data);
		
		//prepare logging data
		$data='';
		if (isset($resultData['data'])) {
			$data = $resultData['data'];
			if (is_array($data)) {
				$data= json_encode($data);
			}
		}
		//log the event		
		$this->logger->info(
			'{class_mame}|{method_name}|process-sms-result|{result}|{result_desc}|{data}',
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'result'=>$resultData['result'],
				'result_desc'=>$resultData['resultDesc'],
				'data'=>$data
			)
		);
		
		// successful processing
		if ($resultData['result'] == 0) {
			//render the view and pass the raw post data
        	$this->View->renderWithoutHeaderAndFooter('notify/index',$resultData);
		} else if( $resultData['result'] == 11) {
			// decoding failure //bad request error
			header('HTTP/1.0 400 Bad Request');
		 	$this->View->renderWithoutHeaderAndFooter('error/httperror500',array("error"=>$resultData['result']." - ".$resultData['resultDesc']));	
		} else {
			//processing error
			header('HTTP/1.0 500 Internal Server Error');
		 	$this->View->renderWithoutHeaderAndFooter('error/httperror500',array("error"=>$resultData['result']." - ".$resultData['resultDesc']));
		}
	 }
	
}
