<?php
namespace Ssg\Core;

use Psr\Log\LoggerInterface;

/*
* The class has Utility functions on SDP to access services services on; sendSms, 
* reqister and de-register service end point, getSmsDeliveryStatus, etc
* This class requires nusoap_client class (see application core folder)
* 
* Note: This class needs to be updated to help log nusoap debug information 
*/
class SDP{
	
   /*
	* generatePassword - generates password used in sending requests to SDP
	*
	* @param string sp_timestamp a string reprenting the timestamp of sending the message
	* @return string generated password
	*/
	
	private static function generatePassword($sp_timestamp='')
	{
		//check whether the timestamp is set
		if(!isset($sp_timestamp) || empty($sp_timestamp)) 
		{
			$sp_timestamp=date("YmdHis"); //current timestamp
		}
		return md5(Config::get('SP_ID').Config::get('SP_PASSWORD').$sp_timestamp); // password to be passed to SDP
	}

	/*
	* sendSms - method to Send SMS
	* The interface supports sending SMS to one or more recipient(s) in a single request
	* Maximum number of recipients set in SEND_SMS_MAXIMUM_RECIPIENTS in configuration file
	*
	* @param string $kmp_spid sp id from SDP
	* @param string $kmp_sppwd sp password generated for this request 
	* @param string $kmp_service_id service id
	* @param string $kmp_timestamp the send timestamp 
	* @param array or string $kmp_recipients destination address(es)
	* @param string $kmp_correlator correlator
	* @param string $kmp_code sender address (short code)
	* @param string $kmp_message message to be sent
	*
	* @return array associative array with: ResultCode, ResultDesc, and ResultDetails, ResultDetails and XML sent
	*/
	public static function sendSms(LoggerInterface $logger, $kmp_service_id, $kmp_recipients,$kmp_correlator,$kmp_code,$kmp_message,$kmp_linkid=''){
		
		$kmp_spid=Config::get('SP_ID'); // sp id from configuration file
		$kmp_timestamp=date("YmdHis"); //current timestamp
		$kmp_sppwd=self::generatePassword($kmp_timestamp); // password to be passed to SDP
	
		//construct the SOAP request to be sent to the SDP server
		$bodyxml = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:v2="http://www.huawei.com.cn/schema/common/v2_1" xmlns:loc="http://www.csapi.org/schema/parlayx/sms/send/v2_2/local"> <soapenv:Header> <v2:RequestSOAPHeader><spId>'.$kmp_spid.'</spId><spPassword>'.$kmp_sppwd.'</spPassword><serviceId>'.$kmp_service_id.'</serviceId><timeStamp>'.$kmp_timestamp.'</timeStamp>';
					
		if(!empty($kmp_linkid)){
			$bodyxml.='<v2:linkid>'.$kmp_linkid.'</v2:linkid>';
		}
		
		 //Check whether the recipient is empty or isset
		if(isset($kmp_recipients)){
			//if the recipient is one number, we include the OA and FA parameter in the SOAP header
			if(count($kmp_recipients)==1){	 
				$bodyxml.='<v2:OA>'.$kmp_recipients.'</v2:OA><v2:FA>'.$kmp_recipients.'</v2:FA>';
			}
		}
		else{
			return array('ResultCode'=>"4",'ResultDesc'=>"Recipient(s) empty.",'ResultDetails'=>"No recipient address(es) specified."); 
		}
		 
		$bodyxml.='</v2:RequestSOAPHeader></soapenv:Header><soapenv:Body><loc:sendSms>';
		
		//specify the address of the recipient
		$count=count($kmp_recipients);
		if($count == 1){ //one recipient
			$bodyxml.='<loc:addresses>'.$kmp_recipients.'</loc:addresses>';
		}
		else if($count >  Config::get('SEND_SMS_MAXIMUM_RECIPIENTS')){ //too many recipients
			return array('ResultCode'=>"5",'ResultDesc'=>"Too many recipients.",'ResultDetails'=>"The number of recipients exceeds the maximum number."); 
		}
		else{ //more than one recipients
			foreach ($kmp_recipients as $misdn){
				$bodyxml.='<loc:addresses>'.$misdn.'</loc:addresses>';
			}
		}
		
		//specify the last part of the soap request
		$bodyxml.=	'<loc:senderName>'.$kmp_code.'</loc:senderName><loc:message>'.$kmp_message.'</loc:message>';
					
		//include receiptRequest part in the message
		if( Config::get('SEND_SMS_DEFAULT_DELIVERY_NOTIFICATION_FLAG') == 1){
			$bodyxml.=	'<loc:receiptRequest><endpoint>'.Config::get('SEND_SMS_DEFAULT_DELIVERY_NOTIFICATION_ENDPOINT').'</endpoint><interfaceName>SmsNotification</interfaceName><correlator>'.$kmp_correlator.'</correlator></loc:receiptRequest>';
		}
		
		$bodyxml.=	'</loc:sendSms></soapenv:Body></soapenv:Envelope>';
		
					
		//Create the nusoap client and set the parameters, endpoint specified in the client_inc.php
		$client = new nusoap_client(Config::get('SEND_SMS_DEFAULT_SERVICE_ENDPOINT'),true);	
		$bsoapaction = "";
		$client->soap_defencoding = 'utf-8';
		$client->useHTTPPersistentConnection();
		
		//Send the soap request to the server
		$result = $client->send($bodyxml, $bsoapaction);
		
		//log the send request
		$logger->debug(
			"{class_mame}|{method_name}|{service_id}|send-sms|{endpoint}|{soapaction}|{request}",
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'service_id'=>$kmp_service_id, 
				'endpoint'=>Config::get('SEND_SMS_DEFAULT_SERVICE_ENDPOINT'),
				'soapaction'=>$bsoapaction,
				'request'=>$bodyxml
			)
		);
		
		//check for fault and return
		if ($client->fault) {
		  return array('ResultCode'=>"1",'ResultDesc'=>'SOAP Fault','ResultDetails'=>$result, "request"=>$bodyxml);
		}
		
		// check for errors and return
		$err = $client->getError();
		if ($err) {
			return array('ResultCode'=>"2",'ResultDesc'=>'Error','ResultDetails'=>$err, "request"=>$bodyxml);
		}
		else{
			//check for fault code
			if(isset($result['faultcode'])){
				return array('ResultCode'=>"3",'ResultDesc'=>'Fault - '.$result['faultcode'],'ResultDetails'=>$result['faultstring'],"request"=>$bodyxml);
			}
			//return success
			return array('ResultCode'=>"0",'ResultDesc'=>"Operation Successful.",'ResultDetails'=>$result, "request"=>$bodyxml);
		}
	} //end of sendSms method
	
	
	/*
	* @method: getSmsDeliveryStatus - method to get SMS Delivery Status
	* Supports getting delivery status using either requestIdentifier alone or both requestIdentifier and MSISDN (address)
	*
	* @param string $kmp_service_id service id
	* @param string $kmp_request_identifier request identifier returned as a response to sendSms interface
	* @param string $kmp_msisdn optional the subscriber number of the recipient of the message sent via sendSms interface
	*
	* @return array associative array with: ResultCode, ResultDesc, and ResultDetails, ResultDetails and XML sent
	*/
	public static function getSmsDeliveryStatus($kmp_service_id, $kmp_request_identifier, $kmp_msisdn=''){
		
		$kmp_spid=Config::get('SP_ID'); // sp id from configuration file
		$kmp_timestamp=date("YmdHis"); //current timestamp
		$kmp_sppwd=self::generatePassword($kmp_timestamp); // password to be passed to SDP
		
		//setup the initial part of the body xml
		$bodyxml='<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:v2="http://www.huawei.com.cn/schema/common/v2_1" xmlns:loc="http://www.csapi.org/schema/parlayx/sms/send/v2_2/local"><soapenv:Header><v2:RequestSOAPHeader><v2:spId>'.$kmp_spid.'</v2:spId><v2:spPassword>'.$kmp_sppwd.'</v2:spPassword><v2:serviceId>'.$kmp_service_id.'</v2:serviceId> <v2:timeStamp>'.$kmp_timestamp.'</v2:timeStamp>';
		
		//populate the MSISDN	
		if(!empty($kmp_msisdn)){
			$bodyxml.='<v2:OA>'.$kmp_msisdn.'</v2:OA><v2:FA>'.$kmp_msisdn.'</v2:FA>';
		}
		
		//append the last part of the body xml 
		$bodyxml.='</v2:RequestSOAPHeader></soapenv:Header><soapenv:Body><loc:getSmsDeliveryStatus><loc:requestIdentifier>'.$kmp_request_identifier.'</loc:requestIdentifier></loc:getSmsDeliveryStatus></soapenv:Body></soapenv:Envelope>';
		
		//requestIdentifier is empty
		if(empty($kmp_request_identifier)){
			return array('ResultCode'=>"6",'ResultDesc'=>"Missing Request Identifier.",'ResultDetails'=>"Request Identifier Parameter is empty");
		}
		
		//Create the nusoap client and set the parameters, endpoint specified in the client_inc.php
		$client = new nusoap_client(Config::get('GET_DELIVERY_STATUS_DEFAULT_SERVICE_ENDPOINT'),true);	
		$bsoapaction = "";
		$client->soap_defencoding = 'utf-8';
		$client->useHTTPPersistentConnection();
		
		//Send the soap request to the server
		$result = $client->send($bodyxml, $bsoapaction);
		
		//check for fault and return
		if ($client->fault) {
		  return array('ResultCode'=>"1",'ResultDesc'=>'SOAP Fault','ResultDetails'=>$result, 'xml'=>$bodyxml);
		}
		
		// check for errors and return
		$err = $client->getError();
		if ($err) {
			return array('ResultCode'=>"2",'ResultDesc'=>'Error','ResultDetails'=>$err, 'xml'=>$bodyxml);
		}
		else{
			//check for fault code
			if(isset($result['faultcode'])){
				return array('ResultCode'=>"3",'ResultDesc'=>'Fault - '.$result['faultcode'],'ResultDetails'=>$result['faultstring'], 'xml'=>$bodyxml);
			}
			//return success
			return array('ResultCode'=>"0",'ResultDesc'=>"Operation Successful.",'ResultDetails'=>$result, 'xml'=>$bodyxml);
		}
	}
	
	
	/*
	* startSmsNotification - method to send the startSmsNotification request to the SDP server for SMS notify
	* The interface is used to register the end point that should receive SMS (notifySmsReception)
	* 
	* @param string $kmp_service_id service id
	* @param string $kmp_notify_endpoint the service endpoint that will receive SMS (notifySmsReception interface)
	* @param string $kmp_correlator correlator
	* @param string $kmp_code smsServiceActivationNumber or the short code
	* @param string $kmp_criteria the criteria for receiving the notification 
	*
	* @return array associative array with: ResultCode, ResultDesc, and ResultDetails, ResultDetails and XML sent
	*/
	public static function startSmsNotification(LoggerInterface $logger,$kmp_service_id,$kmp_notify_endpoint,$kmp_correlator,$kmp_code,$kmp_criteria=''){
	
		$kmp_spid=Config::get('SP_ID'); // sp id from configuration file
		$kmp_timestamp=date("YmdHis"); //current timestamp
		$kmp_sppwd=self::generatePassword($kmp_timestamp); // password to be passed to SDP
		
		$bodyxml = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:v2="http://www.huawei.com.cn/schema/common/v2_1" xmlns:loc="http://www.csapi.org/schema/parlayx/sms/notification_manager/v2_3/local"><soapenv:Header><RequestSOAPHeader xmlns="http://www.huawei.com.cn/schema/common/v2_1"><spId>'.$kmp_spid.'</spId><spPassword>'.$kmp_sppwd.'</spPassword><serviceId>'.$kmp_service_id.'</serviceId><timeStamp>'.$kmp_timestamp.'</timeStamp></RequestSOAPHeader></soapenv:Header><soapenv:Body><loc:startSmsNotification><loc:reference><endpoint>'.$kmp_notify_endpoint.'</endpoint><interfaceName>startSmsNotification</interfaceName><correlator>'.$kmp_correlator.'</correlator></loc:reference><loc:smsServiceActivationNumber>'.$kmp_code.'</loc:smsServiceActivationNumber><loc:criteria>'.$kmp_criteria.'</loc:criteria></loc:startSmsNotification></soapenv:Body></soapenv:Envelope>';
		
		
		//create the client
		$client = new nusoap_client(Config::get('SMS_NOTIFICATION_MANAGER_ENDPOINT'),true);	
		$bsoapaction = "";
		$client->soap_defencoding = 'utf-8';
		$client->useHTTPPersistentConnection();
		
		//log the send request
		$logger->debug(
			"{class_mame}|{method_name}|{service_id}|sending start sms notification|{endpoint}|{soapaction}|{request}",
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'service_id'=>$kmp_service_id,
				'endpoint'=>Config::get('SMS_NOTIFICATION_MANAGER_ENDPOINT'),
				'soapaction'=>$bsoapaction,
				'request'=>$bodyxml
			)
		);
		
		//send the request to the server
		$result = $client->send($bodyxml, $bsoapaction);

		//check for fault and return
		if ($client->fault) {
		  return array('ResultCode'=>1,'ResultDesc'=>'SOAP Fault','ResultDetails'=>$result, 'xml' => $bodyxml, 'endpoint'=>Config::get('SMS_NOTIFICATION_MANAGER_ENDPOINT'));
		}
		
		// check for errors and return
		$err = $client->getError();
		if ($err) {
			return array('ResultCode'=>2,'ResultDesc'=>'Error','ResultDetails'=>$err, 'xml' => $bodyxml, 'endpoint'=>Config::get('SMS_NOTIFICATION_MANAGER_ENDPOINT'));
		}
		else{
			//check for fault code
			if(isset($result['faultcode'])){
				return array('ResultCode'=>'3','ResultDesc'=>'Fault - '.$result['faultcode'],'ResultDetails'=>$result['faultstring'], 'xml' => $bodyxml, 'endpoint'=>Config::get('SMS_NOTIFICATION_MANAGER_ENDPOINT'));
			}
			//return success
			return array('ResultCode'=>0,'ResultDesc'=>'Operation Successful.','ResultDetails'=>$result, 'xml' => $bodyxml, 'endpoint'=>Config::get('SMS_NOTIFICATION_MANAGER_ENDPOINT'));
		}
	}
	
	
   /*
	* startSmsNotification - method to send the startSmsNotification request to the SDP server for SMS notify
	* The interface is used to register the end point that should receive SMS (notifySmsReception)
	*
	* @param string $kmp_service_id service id
	* @param string $kmp_correlator correlator
	*
	* @return array associative array with: ResultCode, ResultDesc, and ResultDetails, ResultDetails
	*/
	public static function stopSmsNotification(LoggerInterface $logger, $kmp_service_id, $kmp_correlator)
	{
		$kmp_spid=Config::get('SP_ID'); // sp id from configuration file
		$kmp_timestamp=date("YmdHis"); //current timestamp
		$kmp_sppwd=self::generatePassword($kmp_timestamp); // password to be passed to SDP
		
		$bodyxml = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:v2="http://www.huawei.com.cn/schema/common/v2_1" xmlns:loc="http://www.csapi.org/schema/parlayx/sms/notification_manager/v2_3/local"><soapenv:Header><v2:RequestSOAPHeader><spId>'.$kmp_spid.'</spId><spPassword>'.$kmp_sppwd.'</spPassword><serviceId>'.$kmp_service_id.'</serviceId><timeStamp>'.$kmp_timestamp.'</timeStamp></v2:RequestSOAPHeader></soapenv:Header><soapenv:Body><loc:stopSmsNotification><correlator>'.$kmp_correlator.'</correlator></loc:stopSmsNotification></soapenv:Body></soapenv:Envelope>';
		
		//create the client
		$client = new nusoap_client(Config::get('SMS_NOTIFICATION_MANAGER_ENDPOINT'),true);	
		$bsoapaction = "";
		$client->soap_defencoding = 'utf-8';
		$client->useHTTPPersistentConnection();
		
		//send the request to the server
		$result = $client->send($bodyxml, $bsoapaction);
		
		//log the send request
		$logger->debug(
			"{class_mame}|{method_name}|{service_id}|sending stop sms notification|{endpoint}|{soapaction}|{request}",
			array(
				'class_mame'=>__CLASS__,
				'method_name'=>__FUNCTION__,
				'service_id'=>$kmp_service_id, 
				'endpoint'=>Config::get('SMS_NOTIFICATION_MANAGER_ENDPOINT'),
				'soapaction'=>$bsoapaction,
				'request'=>$bodyxml
			)
		);
		
		//check for fault and return
		if ($client->fault) {
		  return array('ResultCode'=>1,'ResultDesc'=>'SOAP Fault','ResultDetails'=>$result, 'xml' => $bodyxml, 'endpoint'=>Config::get('SMS_NOTIFICATION_MANAGER_ENDPOINT'));
		}
		
		// check for errors and return
		$err = $client->getError();
		if ($err) {
			return array('ResultCode'=>2,'ResultDesc'=>'Error','ResultDetails'=>$err, 'xml' => $bodyxml, 'endpoint'=>Config::get('SMS_NOTIFICATION_MANAGER_ENDPOINT'));
		}
		else{
			//check for fault code
			if(isset($result['faultcode'])){
				return array('ResultCode'=>'3','ResultDesc'=>'Fault - '.$result['faultcode'],'ResultDetails'=>$result['faultstring'], 'xml' => $bodyxml, 'endpoint'=>Config::get('SMS_NOTIFICATION_MANAGER_ENDPOINT'));
			}
			//return success
			return array('ResultCode'=>0,'ResultDesc'=>'Operation Successful.','ResultDetails'=>$result, 'xml' => $bodyxml, 'endpoint'=>Config::get('SMS_NOTIFICATION_MANAGER_ENDPOINT'));
		}
	}
} //end of class

?>