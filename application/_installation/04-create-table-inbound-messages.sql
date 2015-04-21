CREATE TABLE IF NOT EXISTS `huge`.`inbound_messages` (
  `message_id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'Internal message id',
  `service_id` VARCHAR(30) NOT NULL COMMENT 'Service ID from the telco',
  `link_id` VARCHAR(40) NULL COMMENT 'Link id used in sending back sms  (on demand service)',
  `trace_unique_id` VARCHAR(40) NULL COMMENT 'Trace unique id from the telco',
  `correlator` VARCHAR(40) NULL,
  `message` VARCHAR(255) NOT NULL COMMENT 'Text received in an SMS message',
  `sender_address` VARCHAR(30) NOT NULL COMMENT 'Address sending the SMS message.',
  `dest_address` VARCHAR(30) NOT NULL COMMENT 'smsServiceActivationNumber or short code',
  `created_on` DATETIME NULL,
  `date_time` VARCHAR(30) NULL COMMENT 'Time when the message is received by a carrier.',
  `last_updated_by` DATETIME NULL,
  PRIMARY KEY (`message_id`))
ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT = 'Inbound messages received';
