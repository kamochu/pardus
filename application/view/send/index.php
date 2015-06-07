<?php

/* Log the message here and delete below comments */ 
// echo "Send sms request processing\n";
// echo "result: ".$this->result."\n";
// echo "resultDesc: ".$this->resultDesc."\n";
//echo "data: ";
// print_r($this->data);

//result_code|result_desc|ssg_ref_id|sdp_ref_id

$result_code = '';
$result_desc = '';
$ssg_ref_id = '';
$sdp_ref_id = '';

if(isset($this->data['status'])) $result_code = $this->data['status'];
if(isset($this->data['status_desc'])) $result_desc = $this->data['status_desc'];
if(isset($this->data['_lastInsertID'])) $ssg_ref_id = $this->data['_lastInsertID'];
if(isset($this->data['send_ref_id'])) $sdp_ref_id = $this->data['send_ref_id'];

echo $result_code.'|'.$result_desc.'|'.$ssg_ref_id.'|'.$sdp_ref_id;

?>