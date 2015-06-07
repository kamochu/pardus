<?php
namespace Ssg\Core;
/**
 * Class PardusXMLParser
 * This is a Utility class that is used to parse XML. The data in the XML is populated in an associated array ($parameters) 
 * whose key is the tag name. This array is private and can be accessed using a getter method getParameters()
 * If a tag, without the namespace indicator, is repeated in the request data (without the namespace indicator), the 
 * class adds the value $parameters array and append  an integer value,  starting with 1 incremented by 1, to the tag name.
 * 
 * 
 * This class is inspired by a blog post below.
 * @see http://php-and-symfony.matthiasnoback.nl/2012/04/php-create-an-object-oriented-xml-parser-using-the-built-in-xml_-functions/
 */
class PardusXMLParser
{
    private $parser;
	private $parameters;
	private $repeatedParameters;
	private $currentParam;
	private $currentValue;

	/**
	* This contructor initializes the parser instance and other attributes
	*/ 
    public function __construct($encoding = 'UTF-8')
    {
		//initialize the parser
        $this->parser = xml_parser_create($encoding);

        xml_set_object($this->parser, $this);
        xml_set_element_handler($this->parser, 'startElement', 'endElement');
        xml_set_character_data_handler($this->parser, 'cdata');
		xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);
		xml_parser_set_option($this->parser, XML_OPTION_SKIP_WHITE,true);
		
		//initialize the class variables
		$this->parameters = array();
		$this->repeatedParameters = array();
		$this->currentParam = "";
		$this->currentValue = "";
    }
	
	/**
	* This is a getter method that accesses the private parameters array. 
	* This method should be called after parse method has been called. 
	* @return an array of parameters
	*/
	public function getParameters()
	{
		return $this->parameters;
	}
	
	/**
	* This is a getter method that accesses the private repeated parameters array. 
	* @return array repeated parameters (indicating which tags have been repeated and how many times)
	*/
	public function getRepeatedParametersArray()
	{
		return $this->repeatedParameters;
	}
	
	/**
	* This is a getter method gets XML parser error string
	* @return string  XML parser error string
	*/
	public function getParseError()
	{
		return xml_error_string(xml_get_error_code($this->parser));
	}
	
	/**
	* This is a getter method that gets the line number  where the parsing error was encountered. 
	* @return int line number
	*/
	public function getCurrentLineNumber()
	{		
		return xml_get_current_line_number($this->parser);
	}
	
	
	/**
	* Parses an XML document. The handlers for the configured events are called as many times as necessary.
	* @param string $data chunk of data to parse.
	* @param bool  $is_final if set and TRUE, data is the last piece of data sent in this parse.
	* @return int 1 on success or 0 on failure.
	*/
	public function parse($data, $isFinal)
	{
		 return xml_parse($this->parser, $data, $isFinal );
	}
	
	/**
	* start_element_handler method called whenever a start tag is processed.
	* @param resource $parser a reference to the XML parser calling the handler.
	* @param string $name contains the name of the element for which this handler is called.
	* @param array $attributes an associative array with the element's attributes (if any). Ignored by this class.
	* @see http://php.net/manual/en/function.xml-set-element-handler.php
	*/
	public function startElement($parser, $name, array $attributes)
    {
        $this->currentParam = $name; // copy the tag name into current parameter attribute
    }

	/**
	* Sets the character data handler function for the XML parser parser.
	* @param resource $parser a reference to the XML parser calling the handler.
	* @param string $cdata contains the character data as a string. 
	* @return TRUE on success or FALSE on failure.
	*/
    public function cdata($parser, $cdata)
    {
        $this->currentValue = trim($cdata); // copy the data into the current value
    }

	/**
	* end_element_handler method called whenever a end tag is processed.
	* @param resource $parser a reference to the XML parser calling the handler.
	* @param string $name contains the name of the element for which this handler is called.
	* @see http://php.net/manual/en/function.xml-set-element-handler.php
	*/
    public function endElement($parser, $name)
    {
        //if they are equal, had to delete '&& !empty($this->currentValue)' to ensure that all parameters are addded
		if(strcmp($this->currentParam, $name) == 0 ) {
			/* Sometimes parameter might include the XML namespace e.g.  'loc:serviceID'. The logic below strips off namespace part.*/
			$position = strpos($this->currentParam, ":");
			$param_name = $this->currentParam;
			if ( $position !== false) { // no namespace character
				$param_name = substr($this->currentParam,$position+1); // ignore the ":" - substring from next character after 
			}
			
			//check whether there is a parameter with the same name
			if (isset($this->parameters[$param_name])) {
				$repeated_suffix =1; // default parameter
				//check whether there is a record in the repeated parameters
				if (isset($this->repeatedParameters[$param_name])) {
					$repeated_suffix = $this->repeatedParameters[$param_name]+1; //assign it to the suffix
				}
				
				$this->repeatedParameters[$param_name] = $repeated_suffix; // update the repated parameter counter for that value
				$param_name = $param_name.$repeated_suffix; // append the repeated suffix to the parameter
				$this->parameters[$param_name] = $this->currentValue;
			} else {
				
				if(isset($this->currentValue) && !empty($this->currentValue)) {
					$this->parameters[$param_name] = $this->currentValue;
				}		
			}
		}
    }


	/**
	* Frees the given XML parser.
	* @param resource $parser a reference to the XML parser.
	* @return FALSE if parser does not refer to a valid parser, or else it frees the parser and returns TRUE.
	* @see http://php.net/manual/en/function.xml-parser-free.php
	*/
    public function __destruct()
    {
        if (is_resource($this->parser)) {
            xml_parser_free($this->parser);
        }
    }
	
}