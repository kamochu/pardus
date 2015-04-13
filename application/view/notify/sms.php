<?php
$postdata = file_get_contents("php://input");  // get raw HTTP data
$parser = new PardusXMLParser();
$parser->parse($postdata, true);
print_r($parser->getParameters());
print_r($parser->getRepeatedParametersArray());

?>