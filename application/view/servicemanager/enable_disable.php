<?php
/* Remove this omce logging has been implemented on the service */
//echo json_encode($this->data)."\n\n";
print_r($this->data);

$result ='';
$result_desc ='';
if(isset($this->data['result']))$result = $this->data['result'];
if(isset($this->data['resultDesc']))$result_desc = $this->data['resultDesc'];

echo $result.'|'.$result_desc; //output result 

?>