<?php
	$postdata = file_get_contents("php://input");  // get raw HTTP data
	//echo "PIPI".$postdata; // raw post data 
	
	//raw post data - see 
	$data=$postdata;
	
	//global declaration
	global 	$parameters;
	global 	$repeated_parameters;
	global 	$current_param;
	global 	$current_value;
	
	//initialize the global parameters
	$parameters = array();
	$repeated_parameters = array();
	$current_param = "";
	$current_value = "";
	
	/*
	* The method called when a start tag is encountered.
	*/
	function sax_start($sax, $tag, $attr) 
	{
		global $current_param;
		$current_param = $tag; // copy the tag name into current parameter attribute
	}
	
	/*
	* The method called when a end tag is encountered.
	*/
	function sax_end($sax, $tag) 
	{
		global $current_param;
		global $current_value;
		global $parameters;
		global $repeated_parameters;

		//if they are equal
		if(strcmp($current_param, $tag) == 0 && !empty($current_value))
		{
			/* Sometimes parameter might include the namespace e.g.  'loc:serviceID'. The logic below strips off namespace part.*/
			$position = strpos($current_param, ":");
			$param_name = $current_param;
			if( $position !== false) // no namespace character
			{
				$param_name = substr($current_param,$position+1); // ignore the ":" - substring from next character after 
			}
			
			//check whether there is a parameter with the same name
			if(isset($parameters[$param_name])){
				$repeated_suffix =1; // default parameter
				//check whether there is a record in the repeated parameters
				if(isset($repeated_parameters[$param_name]))
				{
					$repeated_suffix = $repeated_parameters[$param_name]+1; //assign it to the suffix
				}
				
				$repeated_parameters[$param_name] = $repeated_suffix; // update the repated parameter counter for that value
				$param_name = $param_name.$repeated_suffix; // append the repeated suffix to the parameter
			}
			
			$parameters[$param_name] = $current_value;
		}
	}
	
	
	/*
	* The method called when data is encountered.
	*/
	function sax_cdata($sax, $data) 
	{
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
	print_r($repeated_parameters);

	
?>