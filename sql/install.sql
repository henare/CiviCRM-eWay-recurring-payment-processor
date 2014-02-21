INSERT INTO `civicrm_payment_processor_type` (
	`name`, 		`title`, 		`description`,
	`is_active`,	`is_default`,	`user_name_label`,	`password_label`,
	`signature_label`,	`subject_label`,	`class_name`,
	`url_site_default`, 					`url_api_default`,
	`url_recur_default`,
	`url_button_default`,
	`url_site_test_default`,
	`url_api_test_default`,
	`url_recur_test_default`,
	`url_button_test_default`,	`billing_mode`,	`is_recur`,	`payment_type`)
VALUES (
	'ewayrecurring',	'eWAY (Recurring)',	'Recurring payments payment processor for eWAY',
	1,		0, 		'Username',		'Password',
	NULL,			'Customer Id',		'com.chrischinchilla.ewayrecurring',
	'https://www.eway.com.au/gateway_cvn/xmlpayment.asp',	NULL,
	'https://www.eway.com.au/gateway/ManagedPaymentService/managedCreditCardPayment.asmx?WSDL',
	NULL,
	'https://www.eway.com.au/gateway_cvn/xmltest/testpage.asp',
	NULL,
	'https://www.eway.com.au/gateway/ManagedPaymentService/test/managedcreditcardpayment.asmx?WSDL',
	NULL,				1,		1,		1);
