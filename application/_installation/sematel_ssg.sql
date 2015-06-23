-- phpMyAdmin SQL Dump
-- version 4.1.14
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: May 07, 2015 at 02:11 PM
-- Server version: 5.6.17
-- PHP Version: 5.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `sematel_ssg`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_delivery_receipts`
--

CREATE TABLE IF NOT EXISTS `tbl_delivery_receipts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time_stamp` varchar(30) DEFAULT NULL,
  `sub_req_id` varchar(40) DEFAULT NULL,
  `trace_unique_id` varchar(40) DEFAULT NULL,
  `correlator` varchar(40) DEFAULT NULL,
  `dest_address` varchar(30) DEFAULT NULL,
  `delivery_status` varchar(30) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Delivery receipts processed.' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_inbound_messages`
--

CREATE TABLE IF NOT EXISTS `tbl_inbound_messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Internal message id',
  `service_id` varchar(30) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Service ID from the telco',
  `link_id` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Link id used in sending back sms  (on demand service)',
  `trace_unique_id` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Trace unique id from the telco',
  `correlator` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `message` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Text received in an SMS message',
  `sender_address` varchar(30) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Address sending the SMS message.',
  `dest_address` varchar(30) COLLATE utf8_unicode_ci NOT NULL COMMENT 'smsServiceActivationNumber or short code',
  `date_time` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Time when the message is received by a carrier.',
  `created_on` datetime DEFAULT NULL,
  PRIMARY KEY (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Inbound messages received' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_outbound_messages`
--

CREATE TABLE IF NOT EXISTS `tbl_outbound_messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Message id PK AI',
  `service_id` varchar(30) DEFAULT NULL COMMENT 'Service id',
  `link_id` varchar(40) DEFAULT NULL COMMENT 'Link id for on demand service',
  `linked_incoming_msg_id` int(11) DEFAULT '0' COMMENT 'Incoming message id (on demand service)',
  `dest_address` varchar(30) DEFAULT NULL COMMENT 'Recipient address (mobile number)',
  `sender_address` varchar(30) DEFAULT NULL COMMENT 'Sender address (short code)',
  `correlator` varchar(30) DEFAULT NULL COMMENT 'Correlator used at the time of sending',
  `batch_id` varchar(30) DEFAULT '0' COMMENT 'The batch the outbound message belongs to',
  `message` varchar(255) DEFAULT NULL COMMENT 'Message to be sent',
  `notify_endpoint` varchar(50) DEFAULT NULL COMMENT 'Delivery receipt endpoint',
  `send_timestamp` datetime DEFAULT NULL COMMENT 'Time the message was sent',
  `send_ref_id` varchar(30) DEFAULT NULL COMMENT 'Telco send message reference number',
  `delivery_status` varchar(50) DEFAULT NULL COMMENT 'Delivery status from telco',
  `delivery_timestamp` datetime DEFAULT NULL COMMENT 'The time the delivery receipt is received',
  `delivery_notif_type` tinyint(4) DEFAULT '0' COMMENT 'The mode delivery report was received (1 - manual pull, 2 automatic notification via end point)',
  `delivery_receipt_id` int(11) DEFAULT '0',
  `status` tinyint(4) DEFAULT '1' COMMENT 'Message status 1 - NEW,  2 - SENT, 3 - DELIVERED, 4 - FAILED',
  `status_desc` varchar(200) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `last_updated_on` datetime DEFAULT NULL,
  `last_updated_by` int(11) DEFAULT '0',
  PRIMARY KEY (`message_id`),
  KEY `update_delivery_report_idex` (`dest_address`,`correlator`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Outbound messages' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_services`
--

CREATE TABLE IF NOT EXISTS `tbl_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Internal service id',
  `service_id` varchar(30) DEFAULT NULL COMMENT 'Service id as configured on SDP',
  `service_name` varchar(100) DEFAULT NULL COMMENT 'Display name of the service',
  `service_type` int(11) DEFAULT NULL COMMENT 'Service type as configured in the service type table',
  `short_code` varchar(30) DEFAULT NULL COMMENT 'SmsServiceActivationNumber or short code',
  `criteria` varchar(40) DEFAULT NULL COMMENT 'Criteria used at the time of listening into the service',
  `service_endpoint` varchar(150) DEFAULT NULL COMMENT 'Service URL ',
  `delivery_notification_endpoint` varchar(150) DEFAULT NULL,
  `interface_name` varchar(40) DEFAULT NULL COMMENT 'The interface name',
  `correlator` varchar(30) DEFAULT NULL COMMENT 'Correlator used by the service manager.',
  `status` int(11) DEFAULT '0' COMMENT 'Service status 0 - OFF, 1 - ON',
  `created_on` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_updated_on` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_updated_by` int(11) DEFAULT '0' COMMENT 'User id of the last person to update this record',
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_id_UNIQUE` (`service_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Services table' AUTO_INCREMENT=424 ;

--
-- Dumping data for table `tbl_services`
--

INSERT INTO `tbl_services` (`id`, `service_id`, `service_name`, `service_type`, `short_code`, `criteria`, `service_endpoint`, `delivery_notification_endpoint`, `interface_name`, `correlator`, `status`, `created_on`, `last_updated_on`, `last_updated_by`) VALUES
(1, '6013992000001491', 'On Demand Service', 1, '29000', NULL, 'http://192.168.0.16/pardus/notify/sms/', 'http://192.168.0.16/pardus/delivery/receipt/', 'notifySmsReception', '20150417172518', 1, '2015-04-17 14:33:37', '2015-04-17 20:31:43', 0),
(2, '6013992000001492', 'Bulk SMS Service', 2, '29111', NULL, NULL, 'http://192.168.0.16/pardus/delivery/receipt/', NULL, NULL, 1, '2015-04-17 14:33:37', '2015-04-17 14:33:37', 0),
(3, '6013992000001493', 'Subscription Service', 3, '29111', NULL, 'http://192.168.0.16/pardus/subscription/request/', 'http://192.168.0.16/pardus/delivery/receipt/', NULL, NULL, 1, '2015-04-17 15:22:28', '2015-04-17 15:22:28', 0),
(423, '6013992000001494', 'Test service', 1, '29678', '', 'http://192.168.0.16/pardus/notify/sms/', 'http://192.168.0.16/pardus/delivery/receipt/', 'notifySmsReception', '34234234', 0, '2015-04-21 14:21:24', '2015-04-21 14:21:24', 2);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_service_types`
--

CREATE TABLE IF NOT EXISTS `tbl_service_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL COMMENT 'Display name',
  `description` text COMMENT 'Description of service type',
  `created_on` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_updated_on` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Service Types' AUTO_INCREMENT=4 ;

--
-- Dumping data for table `tbl_service_types`
--

INSERT INTO `tbl_service_types` (`id`, `name`, `description`, `created_on`, `last_updated_on`, `last_updated_by`) VALUES
(1, 'On Demand Service', 'On demand service, customer sends a message to the system and the system processes the message and sends back a response. Service and delivery end point required.', '2015-04-17 14:33:37', '2015-04-17 14:33:37', 0),
(2, 'Bulk Service', 'Send MT SMS to customers without prior subscription request. Delivery endpoint required. ', '2015-04-17 14:33:37', '2015-04-17 14:33:37', 0),
(3, 'Subscription Service', 'Subscription service. Send MT SMS to customers with prior subscription. Delivery endpoint required. ', '2015-04-17 14:33:37', '2015-04-17 14:33:37', 0);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_subscription_messages`
--

CREATE TABLE IF NOT EXISTS `tbl_subscription_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subscriber_id` varchar(30) DEFAULT NULL COMMENT 'The subscriber id (default - mobile subscriber)',
  `sp_id` varchar(10) DEFAULT NULL COMMENT 'SP id from telco',
  `product_id` varchar(40) DEFAULT NULL COMMENT 'Product id from telco',
  `service_id` varchar(40) DEFAULT NULL,
  `service_list` varchar(100) DEFAULT NULL COMMENT 'List of services from telco',
  `update_type` tinyint(4) DEFAULT NULL COMMENT 'Reason type of updating subscription information. Values; 1: Addition 2: Deletion 3: Modification',
  `update_time` varchar(30) DEFAULT NULL COMMENT 'Time stamp in the format of YYYYMMDDhhmmss.',
  `update_desc` varchar(20) DEFAULT NULL COMMENT 'Update description.',
  `effective_time` varchar(30) DEFAULT NULL COMMENT 'Time when the subscription takes effect.',
  `expiry_time` varchar(30) DEFAULT NULL COMMENT 'Expiry time of the subscription.',
  `named_parameters` text COMMENT 'Named parameters in JSON format',
  `created_on` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Subscription request messages processed' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_users`
--

CREATE TABLE IF NOT EXISTS `tbl_users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'auto incrementing user_id of each user, unique index',
  `user_name` varchar(64) COLLATE utf8_unicode_ci NOT NULL COMMENT 'user''s name, unique',
  `user_password_hash` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'user''s password in salted and hashed format',
  `user_email` varchar(64) COLLATE utf8_unicode_ci NOT NULL COMMENT 'user''s email, unique',
  `user_active` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'user''s activation status',
  `user_account_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'user''s account type (basic, premium, etc)',
  `user_has_avatar` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 if user has a local avatar, 0 if not',
  `user_remember_me_token` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'user''s remember-me cookie token',
  `user_creation_timestamp` bigint(20) DEFAULT NULL COMMENT 'timestamp of the creation of user''s account',
  `user_last_login_timestamp` bigint(20) DEFAULT NULL COMMENT 'timestamp of user''s last login',
  `user_failed_logins` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'user''s failed login attempts',
  `user_last_failed_login` int(10) DEFAULT NULL COMMENT 'unix timestamp of last failed login attempt',
  `user_activation_hash` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'user''s email verification hash string',
  `user_password_reset_hash` char(40) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'user''s password reset code',
  `user_password_reset_timestamp` bigint(20) DEFAULT NULL COMMENT 'timestamp of the password reset request',
  `user_provider_type` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_name` (`user_name`),
  UNIQUE KEY `user_email` (`user_email`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='user data' AUTO_INCREMENT=2 ;

--
-- Dumping data for table `tbl_users`
--

INSERT INTO `tbl_users` (`user_id`, `user_name`, `user_password_hash`, `user_email`, `user_active`, `user_account_type`, `user_has_avatar`, `user_remember_me_token`, `user_creation_timestamp`, `user_last_login_timestamp`, `user_failed_logins`, `user_last_failed_login`, `user_activation_hash`, `user_password_reset_hash`, `user_password_reset_timestamp`, `user_provider_type`) VALUES
(1, 'kempes', '$2y$10$OvprunjvKOOhM1h9bzMPs.vuwGIsOqZbw88rzSyGCTJTcE61g5WXi', 'kamochu@gmail.com', 1, 1, 0, 'eff16bd343b70476b10702cb30a4c6a85beb146e3f292f7e0e5ab8abcb7f0c00', 1422205178, 1429514248, 0, NULL, NULL, NULL, NULL, 'DEFAULT');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
