<?php

/* This is to be removed once the log files have been put in place */
// echo "Subscription request processing\n";
// echo "result: ".$this->result."\n";
//echo "resultDesc: ".$this->resultDesc."\n";
// echo "data: ";
// print_r($this->data);

echo '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
xmlns:loc="http://www.csapi.org/schema/parlayx/data/sync/v1_0/local"><soapenv:Header/><soapenv:Body><loc:syncOrderRelationResponse><loc:result>0</loc:result><loc:resultDescription>OK</loc:resultDescription></loc:syncOrderRelationResponse></soapenv:Body></soapenv:Envelope>'; 

?>