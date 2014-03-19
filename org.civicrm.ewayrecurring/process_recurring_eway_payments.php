<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM                                                            |
 +--------------------------------------------------------------------+
 | Copyright Henare Degan (C) 2012                                    |
 +--------------------------------------------------------------------+
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007.                                       |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

// TODO: Remove hacky hardcoded constants
// The full path to your CiviCRM directory
define('CIVICRM_DIRECTORY', '/srv/www/localhost/wordpress/wp-content/plugins/civicrm/civicrm');
// The ID for contributions in a pending status
define('PENDING_CONTRIBUTION_STATUS_ID', 2);
// The ID of your CiviCRM eWay recurring payment processor
define('PAYMENT_PROCESSOR_ID', 1);
define('RECEIPT_SUBJECT_TITLE', 'Monthly Donation');

// Initialise CiviCRM
chdir(CIVICRM_DIRECTORY);
require 'civicrm.config.php';
require 'CRM/Core/Config.php';
$config = CRM_Core_Config::singleton();

require_once 'api/api.php';
require_once 'CRM/Contribute/BAO/ContributionRecur.php';
require_once 'CRM/Contribute/BAO/Contribution.php';
require_once 'CRM/Financial/BAO/PaymentProcessor.php';
require_once 'CRM/Utils/Date.php';
require_once 'CRM/Core/BAO/MessageTemplates.php';
require_once 'CRM/Contact/BAO/Contact/Location.php';
require_once 'CRM/Core/BAO/Domain.php';

// Create eWay token clients
$live_payment_processor = CRM_Financial_BAO_PaymentProcessor::getPayment(PAYMENT_PROCESSOR_ID, 'live');
$live_token_client = eway_token_client(
    $live_payment_processor['url_recur'],
    $live_payment_processor['subject'],
    $live_payment_processor['user_name'],
    $live_payment_processor['password']
);
$test_payment_processor = CRM_Financial_BAO_PaymentProcessor::getPayment(PAYMENT_PROCESSOR_ID, 'test');
$test_token_client = eway_token_client(
    $test_payment_processor['url_recur'],
    $test_payment_processor['subject'],
    $test_payment_processor['user_name'],
    $test_payment_processor['password']
);

// Get pending contributions
$pending_contributions = get_pending_recurring_contributions();

echo "Processing " . count($pending_contributions) . " pending contributions\n";
foreach ($pending_contributions as $pending_contribution) {
    // Process payment
    echo "Processing payment for pending contribution ID: " . $pending_contribution['contribution']->id . "\n";
    $amount_in_cents = str_replace('.', '', $pending_contribution['contribution']->total_amount);
    $result = process_eway_payment(
        ($pending_contribution['contribution']->is_test ? $test_token_client : $live_token_client),
        $pending_contribution['contribution_recur']->processor_id,
        $amount_in_cents,
        $pending_contribution['contribution']->invoice_id,
        $pending_contribution['contribution']->source
    );

    // Bail if the transaction fails
    if ($result->ewayTrxnStatus != 'True') {
        echo 'ERROR: Failed to process transaction for managed customer: ' . $pending_contribution['contribution_recur']->processor_id . "\n";
        echo 'eWay response: ' . $result->ewayTrxnError . "\n";
        continue;
    }
    echo "Successfully processed payment for pending contribution ID: " . $pending_contribution['contribution']->id . "\n";

    echo "Sending receipt\n";
    send_receipt_email($pending_contribution['contribution']->id);

    echo "Marking contribution as complete\n";
    complete_contribution($pending_contribution['contribution']->id);

    echo "Updating recurring contribution\n";
    $pending_contribution['contribution_recur']->next_sched_contribution = CRM_Utils_Date::isoToMysql(date('Y-m-d 00:00:00', strtotime("+1 month")));

	//add additional info
	$pending_contribution['contribution_recur']->start_date = CRM_Utils_Date::isoToMysql(date('Y-m-d H:i:s'));
	$pending_contribution['contribution_recur']->create_date = CRM_Utils_Date::isoToMysql(date('Y-m-d H:i:s'));
	$pending_contribution['contribution_recur']->modified_date = CRM_Utils_Date::isoToMysql(date('Y-m-d H:i:s'));
	
	//change status. it's is not pending anymore and becomes In Progress
	$pending_contribution['contribution_recur']->contribution_status_id = 5;

    $pending_contribution['contribution_recur']->save();
    echo "Finished processing contribution ID: " . $pending_contribution['contribution']->id . "\n";
}

// Process today's scheduled contributions
$scheduled_contributions = get_scheduled_contributions();

echo "Processing " . count($scheduled_contributions) . " scheduled contributions\n";
foreach ($scheduled_contributions as $contribution) {
    // Process payment
    echo "Processing payment for scheduled recurring contribution ID: " . $contribution->id . "\n";
    $amount_in_cents = str_replace('.', '', $contribution->amount);
    $result = process_eway_payment(
        ($contribution->is_test ? $test_token_client : $live_token_client),
        $contribution->processor_id,
        $amount_in_cents,
        $contribution->invoice_id,
        ''
    );

    // Bail if the transaction fails
    if ($result->ewayTrxnStatus != 'True') {
        echo 'ERROR: Failed to process transaction for managed customer: ' . $contribution->processor_id;
        echo 'eWay response: ' . $result->ewayTrxnError;
        // TODO: Mark transaction as failed
        continue;
    }
    echo "Successfully processed payment for scheduled recurring contribution ID: " . $contribution->id . "\n";

	$past_contribution = get_first_contribution_from_recurring($contribution->id);

    echo "Creating contribution record\n";
    $new_contribution_record = new CRM_Contribute_BAO_Contribution();
    $new_contribution_record->contact_id = $contribution->contact_id;
    $new_contribution_record->receive_date = CRM_Utils_Date::isoToMysql(date('Y-m-d H:i:s'));
    $new_contribution_record->total_amount = $contribution->amount;
    $new_contribution_record->contribution_recur_id = $contribution->id;
    $new_contribution_record->contribution_status_id = 1; // TODO: Remove hardcoded hack
    $new_contribution_record->financial_type_id = $contribution->financial_type_id;
	$new_contribution_record->currency = $contribution->currency;
	//copy info from previous contribution belonging to the same recurring contrib
	if($past_contribution!=null){
		$new_contribution_record->contribution_page_id = $past_contribution->contribution_page_id;
		$new_contribution_record->payment_instrument_id = $past_contribution->payment_instrument_id;
		$new_contribution_record->source = $past_contribution->source;
		$new_contribution_record->address_id = $past_contribution->address_id;
	}
    $new_contribution_record->save();

    echo "Sending receipt\n";
    send_receipt_email($new_contribution_record->id);

    echo "Updating recurring contribution\n";
    $contribution->next_sched_contribution = CRM_Utils_Date::isoToMysql(date('Y-m-d 00:00:00', strtotime("+1 month")));
    $contribution->modified_date = CRM_Utils_Date::isoToMysql(date('Y-m-d H:i:s')); // update modified date
    $contribution->create_date = CRM_Utils_Date::isoToMysql($contribution->create_date); // so that it does not get erased
    $contribution->start_date = CRM_Utils_Date::isoToMysql($contribution->start_date); // so that it does not get erased
    $contribution->save();
    echo "Finished processing recurring contribution ID: " . $contribution->id . "\n";
}


/**
 * get_first_contribution_from_recurring
 *
 * find the latest contribution belonging to the recurring contribution so that we
 * can extract some info for cloning, like source etc
 *
 * @return a contribution object
 */
function get_first_contribution_from_recurring($recur_id)
{
    $contributions = new CRM_Contribute_BAO_Contribution();
    $contributions->whereAdd("`contribution_recur_id` = " . $recur_id);
    $contributions->find();

 	$result = array();

    while ($contributions->fetch()) {
		echo "Found first contribution for this reccurring with ID:".$contributions->id."\n";
		return clone($contributions);//return the first found. It should not matter
	}
}

/**
 * get_pending_recurring_contributions
 *
 * Gets recurring contributions that are in a pending state.
 * These are for newly created recurring contributions and should
 * generally be processed the same day they're created. These do not
 * include the regularly processed recurring transactions.
 *
 * @return array An array of associative arrays containing contribution & contribution_recur objects
 */
function get_pending_recurring_contributions()
{
    // Get pending contributions
    $pending_contributions = new CRM_Contribute_BAO_Contribution();
    $pending_contributions->whereAdd("`contribution_status_id` = " . PENDING_CONTRIBUTION_STATUS_ID);
    $pending_contributions->find();

    $result = array();

    while ($pending_contributions->fetch()) {
        // Only process those with recurring contribution records
        if ($pending_contributions->contribution_recur_id) {
            // Find the recurring contribution record for this contribution
            $recurring = new CRM_Contribute_BAO_ContributionRecur();
            $recurring->id = $pending_contributions->contribution_recur_id;

            // Only process records that have a recurring record with
            // a processor ID, i.e. an eWay token
            if ($recurring->find(true) && $recurring->processor_id) {
                $result[] = array(
                    'contribution' => clone($pending_contributions),
                    'contribution_recur' => clone($recurring)
                );
            }
        }
    }
    return $result;
}

/**
 * get_scheduled_contributions
 *
 * Gets recurring contributions that are scheduled to be processed today
 *
 * @return array An array of contribution_recur objects
 */
function get_scheduled_contributions()
{
    $scheduled_today = new CRM_Contribute_BAO_ContributionRecur();
    $scheduled_today->whereAdd("`next_sched_contribution` <= '" . date('Y-m-d 00:00:00') . "'");
    // Don't get cancelled contributions
    $scheduled_today->whereAdd("`contribution_status_id` != 3");
    $scheduled_today->find();

    $scheduled_contributions = array();

    while ($scheduled_today->fetch()) {
	
        // Check that there's no existing contribution record for today
        $contribution = new CRM_Contribute_BAO_Contribution();
        $contribution->contribution_recur_id = $scheduled_today->id;
        $contribution->whereAdd("`receive_date` = '" . date('Y-m-d 00:00:00') . "'");

        if ($contribution->find() == 0) {
            $scheduled_contributions[] = clone($scheduled_today);
        }else{
            echo "WARNING: Attempted to reprocess recurring contribution ID " . $scheduled_today->id .  ". Skipping and updating recurring contribution\n";
            $scheduled_today->next_sched_contribution = CRM_Utils_Date::isoToMysql(date('Y-m-d 00:00:00', strtotime("+1 month")));
            $scheduled_today->update();
        }
    }

    return $scheduled_contributions;
}

/**
 * eway_token_client
 *
 * Creates an eWay SOAP client to the eWay token API
 *
 * @param string $gateway_url URL of the gateway to connect to (could be the test or live gateway)
 * @param string $eway_customer_id Your eWay customer ID
 * @param string $username Your eWay business centre username
 * @param string $password Your eWay business centre password
 * @return object A SOAP client to the eWay token API
 */
function eway_token_client($gateway_url, $eway_customer_id, $username, $password)
{
    $soap_client = new SoapClient($gateway_url, array('trace' => 1));

    // Set up SOAP headers
    $headers = array(
        'eWAYCustomerID' => $eway_customer_id,
        'Username'       => $username,
        'Password'       => $password
    );
    $header = new SoapHeader('https://www.eway.com.au/gateway/managedpayment', 'eWAYHeader', $headers);
    $soap_client->__setSoapHeaders($header);

    return $soap_client;
}

/**
 * process_eway_payment
 *
 * Processes an eWay token payment
 *
 * @param object $soap_client An eWay SOAP client set up and ready to go
 * @param string $managed_customer_id The eWay token ID for the credit card you want to process
 * @param string $amount_in_cents The amount in cents to charge the customer
 * @param string $invoice_reference InvoiceReference to send to eWay
 * @param string $invoice_description InvoiceDescription to send to eWay
 * @throws SoapFault exceptions
 * @return object eWay response object
 */
function process_eway_payment($soap_client, $managed_customer_id, $amount_in_cents, $invoice_reference, $invoice_description)
{
	//PHP bug: https://bugs.php.net/bug.php?id=49669. issue with value greater than 2147483647.
	settype($managed_customer_id,"float");
	
    $paymentinfo = array(
        'managedCustomerID' => $managed_customer_id,
        'amount' => $amount_in_cents,
        'InvoiceReference' => $invoice_reference,
        'InvoiceDescription' => $invoice_description
    );

	//soap call to Eway with error handling
	try{
  	$result = $soap_client->ProcessPayment($paymentinfo);
  	$eway_response = $result->ewayResponse;

	} catch (Exception $e) {
  		echo 'Caught exception: ',  $e->getMessage(), "\n";
		//echo "LAST SOAP REQUEST:\n" . $soap_client->__getLastRequest() . "\n";
	}

    return $eway_response;
}

/**
 * complete_contribution
 *
 * Marks a contribution as complete
 *
 * @param string $contribution_id The ID of the contribution to mark as complete
 * @return object The contribution object
 */
function complete_contribution($contribution_id)
{
    // Mark the contribution as complete
    $contribution = new CRM_Contribute_BAO_Contribution();
    $contribution->id = $contribution_id;
    $contribution->find(true);
    $contribution->contribution_status_id = 1;
    $contribution->receive_date = CRM_Utils_Date::isoToMysql(date('Y-m-d H:i:s'));

    return $contribution->save();
}

/**
 * send_receipt_email
 *
 * Sends a receipt for a contribution
 *
 * @param string $contribution_id The ID of the contribution to mark as complete
 * @return bool Success or failure
 */
function send_receipt_email($contribution_id)
{
    $contribution = new CRM_Contribute_BAO_Contribution();
    $contribution->id = $contribution_id;
    $contribution->find(true);

    list($name, $email) = CRM_Contact_BAO_Contact_Location::getEmailDetails($contribution->contact_id);

    $domainValues     = CRM_Core_BAO_Domain::getNameAndEmail();
    $receiptFrom      = "$domainValues[0] <$domainValues[1]>";
    $receiptFromEmail = $domainValues[1];

    $params = array(
        'groupName' => 'msg_tpl_workflow_contribution',
        'valueName' => 'contribution_online_receipt',
        'contactId' => $contribution->contact_id,
        'tplParams' => array(
            'contributeMode' => 'directIPN', // Tells the person to contact us for cancellations
            'receiptFromEmail' => $receiptFromEmail,
            'amount' => $contribution->total_amount,
            'title' => RECEIPT_SUBJECT_TITLE,
            'is_recur' => true,
            'billingName' => $name,
            'email' => $email
        ),
        'from' => $receiptFrom,
        'toName' => $name,
        'toEmail' => $email,
        'isTest' => $contribution->is_test
    );

    list($sent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplates::sendTemplate($params);

    return $sent;
}
