<?php
/* Insert some logic to log the message before deleting above comments */
// echo "Notify sms request processing\n";
// echo "result: ".$this->result."\n";
// echo "resultDesc: ".$this->resultDesc."\n";
// echo "data: ";
// print_r($this->data);

echo '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:loc="http://www.csapi.org/schema/parlayx/sms/notification/v2_2/local"><soapenv:Header/> <soapenv:Body> <loc:notifySmsReceptionResponse/> </soapenv:Body> </soapenv:Envelope>';

?>