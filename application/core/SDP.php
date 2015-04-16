<?php
/*
* The class has untility functions that act as SDP client and perform various basic functions
* like send sms, reqister end point and stop end point, etc
*/
class SDP{
	
   /*
	* generatePassword - generates password used in sending requests to SDP
	*
	* @param string sp_timestamp a string reprenting the timestamp of sending the message
	* @return string generated password
	*/
	
	public static function generatePassword($sp_timestamp='')
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
	* @parameters
	* @param string $kmp_spid sp id from SDP
	* @param string $kmp_sppwd sp password generated for this request 
	* @param string $kmp_service_id service id
	* @param string $kmp_timestamp the send timestamp 
	* @param array or string $kmp_recipients destination address(es)
	* @param string $kmp_correlator correlator
	* @param string $kmp_code sender address (short code)
	* @param string $kmp_message message to be sent
	*
	* @return
	* Associative array with: ResultCode, ResultDesc, and ResultDetails 
	* ResultDetails - for sucessful code (0), the value is an array and ResultDetails['result'] gives the request identifier that can be used in querying delivery status.
	*/
	public static function sendSms($kmp_service_id, $kmp_recipients,$kmp_correlator,$kmp_code,$kmp_message,$kmp_linkid=''){
		
		$kmp_spid=Config::get('SP_ID'); // sp id from configuration file
		$kmp_timestamp=date("YmdHis"); //current timestamp
		$kmp_sppwd=SDP::generatePassword($kmp_timestamp); // password to be passed to SDP
	
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
			return array("ResultCode"=>"4","ResultDesc"=>"Recipient(s) empty.","ResultDetails"=>"No recipient address(es) specified."); 
		}
		 
		$bodyxml.='</v2:RequestSOAPHeader></soapenv:Header><soapenv:Body><loc:sendSms>';
		
		//specify the address of the recipient
		$count=count($kmp_recipients);
		if($count == 1){ //one recipient
			$bodyxml.='<loc:addresses>'.$kmp_recipients.'</loc:addresses>';
		}
		else if($count >  Config::get('SEND_SMS_MAXIMUM_RECIPIENTS')){ //too many recipients
			return array("ResultCode"=>"5","ResultDesc"=>"Too many recipients.","ResultDetails"=>"The number of recipients exceeds the maximum number."); 
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
		
		//check for fault and return
		if ($client->fault) {
		  return array("ResultCode"=>"1","ResultDesc"=>"SOAP Fault","ResultDetails"=>$result, "request"=>$bodyxml);
		}
		
		// check for errors and return
		$err = $client->getError();
		if ($err) {
			return array("ResultCode"=>"2","ResultDesc"=>"Error","ResultDetails"=>$err, "request"=>$bodyxml);
		}
		else{
			//check for fault code
			if(isset($result['faultcode'])){
				return array("ResultCode"=>"3","ResultDesc"=>"Fault - ".$result['faultcode'],"ResultDetails"=>$result['faultstring'],"request"=>$bodyxml);
			}
			//return success
			return array("ResultCode"=>"0","ResultDesc"=>"Operation Successful.","ResultDetails"=>$result, "request"=>$bodyxml);
		}
	} //end of sendSms method
	
	
	/*
	* @method: getSmsDeliveryStatus - method to get SMS Delivery Status
	* Supports getting delivery status using either requestIdentifier alone or both requestIdentifier and MSISDN (address)
	*
	* @parameters
	* @param string $kmp_spid
	* $spPassword
	* $serviceId
	* $timeStamp
	* $requestIdentifier
	* $msisdn - optional 
	*
	* @return
	* Associative array with: ResultCode, ResultDesc, and ResultDetails 
	* ResultDetails - contains additional information. For successful result code (0). The aray will contain delivery status of all the messages sent and referenced by the same $kmp_request_identifier
	*/
	public static function getSmsDeliveryStatus($kmp_service_id, $kmp_request_identifier, $kmp_msisdn=''){
		
		$kmp_spid=Config::get('SP_ID'); // sp id from configuration file
		$kmp_timestamp=date("YmdHis"); //current timestamp
		$kmp_sppwd=SDP::generatePassword($kmp_timestamp); // password to be passed to SDP
		
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
			return array("ResultCode"=>"6","ResultDesc"=>"Missing Request Identifier.","ResultDetails"=>"Request Identifier Parameter is empty");
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
		  return array("ResultCode"=>"1","ResultDesc"=>"SOAP Fault","ResultDetails"=>$result, "xml"=>$bodyxml);
		}
		
		// check for errors and return
		$err = $client->getError();
		if ($err) {
			return array("ResultCode"=>"2","ResultDesc"=>"Error","ResultDetails"=>$err, "xml"=>$bodyxml);
		}
		else{
			//check for fault code
			if(isset($result['faultcode'])){
				return array("ResultCode"=>"3","ResultDesc"=>"Fault - ".$result['faultcode'],"ResultDetails"=>$result['faultstring'], "xml"=>$bodyxml);
			}
			//return success
			return array("ResultCode"=>"0","ResultDesc"=>"Operation Successful.","ResultDetails"=>$result, "xml"=>$bodyxml);
		}
	}
	
	
	/*
	* @method writeToFile - Utility function to write file into disk for logging purposes
	* @parameter
	* $file - path/to/file
	* $data - the data to be written into the file
	*
	* @return - none
	*/
	function writeToFile($file,$data){
		if (file_exists($file)){
			file_put_contents($file,  $data, FILE_APPEND);
		}
		else{
			file_put_contents($file, $data);
		}
	}
	
} //end of class

?>