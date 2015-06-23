<?php
//echo "Delivery receipt request processing\n";
//echo "result: ".$this->result."\n";
//echo "resultDesc: ".$this->resultDesc."\n";
//echo "data: ";
//print_r($this->data);

echo '<?xml version="1.0" encoding="utf-8" ?><soapenv:Envelope xmlns:soapenv = "http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi = "http://www.w3.org/2001/XMLSchema-instance"><soapenv:Body><ns1:notifySmsDeliveryReceiptResponse xmlns:ns1 = "http://www.csapi.org/schema/parlayx/sms/send/v2_2/local" />  </soapenv:Body></soapenv:Envelope>';

?>