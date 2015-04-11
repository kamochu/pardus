<?php
	$postdata = file_get_contents("php://input");  // get raw HTTP data
	//echo "PIPI".$postdata; // raw post data 
	
	//raw post data - see 
	$data=$postdata;
	
	//global declaration
	global 	$parameters;
	global 	$current_param;
	global 	$current_value;
	
	//initialize the global parameters
	$parameters = array();
	$current_param = "";
	$current_value = "";
	
	/*
	* The method called when a start tag is encountered.
	*/
	function sax_start($sax, $tag, $attr) {
		global $current_param;
		$current_param = $tag; // copy the tag name into current parameter attribute
	}
	
	/*
	* The method called when a end tag is encountered.
	*/
	function sax_end($sax, $tag) {
		global $current_param;
		global $current_value;
		global $parameters;
		
		//if they are equal
		if(strcmp($current_param, $tag) == 0 && !empty($current_value)){
			$parameters[$current_param] = $current_value;
		}
	}
	
	
	/*
	* The method called when data is encountered.
	*/
	function sax_cdata($sax, $data) {
		global $current_value;
		$current_value = trim($data); // copy the tag name into current parameter attribute
	}
	
	/*
	* Create a parser and set options
	*/
	$sax = xml_parser_create();
	xml_parser_set_option($sax, XML_OPTION_CASE_FOLDING, false);
	xml_parser_set_option($sax, XML_OPTION_SKIP_WHITE,true);
	xml_set_element_handler($sax, 'sax_start','sax_end');
	xml_set_character_data_handler($sax, 'sax_cdata');
	
	/*
	* Parser the $data
	*/
	xml_parse($sax, $data ,true);
	
	/*
	* Free resources
	*/
	xml_parser_free($sax);

	//echo $data;
	
	$data2 ='<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:loc="http://www.csapi.org/schema/parlayx/sms/notification/v2_2/local">
	<soapenv:Header/>
	<soapenv:Body>
		<loc:notifySmsReceptionResponse/>
	</soapenv:Body>
</soapenv:Envelope>';

	echo $data2;
	
	print_r($parameters);

	
?>