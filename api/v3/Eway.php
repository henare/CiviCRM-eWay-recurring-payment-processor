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
/**
 * eWayProcess API call
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */


function civicrm_api3_eway_process($params) {

    // TODO: Remove hacky hardcoded constants
    // The full path to your CiviCRM directory
    //define('CIVICRM_DIRECTORY', '/Library/WebServer/Documents/civisandbox/sites/default');
    // The ID for contributions in a pending status
    define('PENDING_CONTRIBUTION_STATUS_ID', 2);
    // The ID of your CiviCRM eWay recurring payment processor
    define('PAYMENT_PROCESSOR_ID', 9);
    define('RECEIPT_SUBJECT_TITLE', 'Monthly Donation');

    // Initialise CiviCRM
//    chdir(CIVICRM_DIRECTORY);
  //  require 'civicrm.settings.php';
    //require 'CRM/Core/Config.php';
    //$config = CRM_Core_Config::singleton(); // Needed?

// Any of the below needed?
// Seemingly so?
/*
    require_once 'CRM/Contribute/BAO/ContributionRecur.php';
    require_once 'CRM/Contribute/BAO/Contribution.php';
    require_once 'CRM/Financial/BAO/PaymentProcessor.php';
    require_once 'CRM/Utils/Date.php';
    require_once 'CRM/Core/BAO/MessageTemplates.php';
    require_once 'CRM/Contact/BAO/Contact/Location.php';
    require_once 'CRM/Core/BAO/Domain.php';
    */

    require_once 'nusoap.php';

    // Create eWay token clients
    $live_payment_processor = CRM_Core_BAO_PaymentProcessor::getPayment(PAYMENT_PROCESSOR_ID, 'live');
    $live_token_client = eway_token_client(
        $live_payment_processor['url_recur'],
        $live_payment_processor['subject'],
        $live_payment_processor['user_name'],
        $live_payment_processor['password']
    );

    $test_payment_processor = CRM_Core_BAO_PaymentProcessor::getPayment(PAYMENT_PROCESSOR_ID, 'test');
    $test_token_client = eway_token_client(
        $test_payment_processor['url_recur'],
        $test_payment_processor['subject'],
        $test_payment_processor['user_name'],
        $test_payment_processor['password']
    );

    // Get pending contributions
    $pending_contributions = get_pending_recurring_contributions();

    $messages = "Processing " . count($pending_contributions) . " pending contributions\n";
    foreach ($pending_contributions as $pending_contribution) {
        // Process payment
        $messages .=  "Processing payment for pending contribution ID: " . $pending_contribution['contribution']->id . "\n";
        $amount_in_cents = str_replace('.', '', $pending_contribution['contribution']->total_amount);
        $result = process_eway_payment(
            ($pending_contribution['contribution']->is_test ? $test_token_client : $live_token_client),
            $pending_contribution['contribution_recur']->is_test ? '9876543211000' : $pending_contribution['contribution_recur']->processor_id,
            $amount_in_cents,
            $pending_contribution['contribution']->invoice_id,
            $pending_contribution['contribution']->source
        );

        // Bail if the transaction fails
        if ($result['ewayTrxnStatus'] != 'True') {
            $messages .=  'ERROR: Failed to process transaction for managed customer: ' . $pending_contribution['contribution_recur']->processor_id . "\n";
            $messages .=  'eWay response: ' . $result['ewayTrxnError'] . "\n";
            continue;
        }
        $messages .=  "Successfully processed payment for pending contribution ID: " . $pending_contribution['contribution']->id . "\n";

        $messages .=  "Sending receipt\n";
        send_receipt_email($pending_contribution['contribution']->id);

        $messages .=  "Marking contribution as complete\n";
        complete_contribution($pending_contribution['contribution']->id);

        $messages .=  "Updating recurring contribution\n";
        $pending_contribution['contribution_recur']->next_sched_contribution = CRM_Utils_Date::isoToMysql(date('Y-m-d 00:00:00', strtotime("+1 month")));
        $pending_contribution['contribution_recur']->save();
        $messages .=  "Finished processing contribution ID: " . $pending_contribution['contribution']->id . "\n";
    }

    // Process today's scheduled contributions
    $scheduled_contributions = get_scheduled_contributions();

    $messages .=  "Processing " . count($scheduled_contributions) . " scheduled contributions\n";
    foreach ($scheduled_contributions as $contribution) {
        // Process payment
        $messages .=  "Processing payment for scheduled recurring contribution ID: " . $contribution->id . "\n";
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
            $messages .=  'ERROR: Failed to process transaction for managed customer: ' . $contribution->processor_id;
            $messages .=  'eWay response: ' . $result->ewayTrxnError;
            // TODO: Mark transaction as failed
            continue;
        }
        $messages .=  "Successfully processed payment for scheduled recurring contribution ID: " . $contribution->id . "\n";

        $messages .=  "Creating contribution record\n";
        $new_contribution_record = new CRM_Contribute_BAO_Contribution();
        $new_contribution_record->contact_id = $contribution->contact_id;
        $new_contribution_record->receive_date = CRM_Utils_Date::isoToMysql(date('Y-m-d 00:00:00'));
        $new_contribution_record->total_amount = $contribution->amount;
        $new_contribution_record->contribution_recur_id = $contribution->id;
        $new_contribution_record->contribution_status_id = 1; // TODO: Remove hardcoded hack
        $new_contribution_record->financial_type_id = $contribution->financial_type_id;
        $new_contribution_record->save();

        $messages .=  "Sending receipt\n";
        send_receipt_email($new_contribution_record->id);

        $messages .=  "Updating recurring contribution\n";
        $contribution->next_sched_contribution = CRM_Utils_Date::isoToMysql(date('Y-m-d 00:00:00', strtotime("+1 month")));
        $contribution->save();
        $messages .=  "Finished processing recurring contribution ID: " . $contribution->id . "\n";
    }
          return civicrm_api3_create_success( $messages );

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
            $messages .=  "WARNING: Attempted to reprocess recurring contribution ID " . $scheduled_today->id .  ". Skipping and updating recurring contribution\n";
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
    var_dump($gateway_url);
    //$soap_client = new SoapClient($gateway_url);
        $soap_client = new nusoap_client($gateway_url, false);

    // Set up SOAP headers
    $headers = array(
        'eWAYCustomerID' => $eway_customer_id,
        'Username'       => $username,
        'Password'       => $password
    );

    $soap_client->namespaces['man'] = 'https://www.eway.com.au/gateway/managedpayment';
    // set SOAP header
    $headers = "<man:eWAYHeader><man:eWAYCustomerID>" . $headers['eWAYCustomerID'] . "</man:eWAYCustomerID><man:Username>" . $headers['Username'] . "</man:Username><man:Password>" . $headers['Password'] . "</man:Password></man:eWAYHeader>";
    $soap_client->setHeaders($headers);

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
    $paymentinfo = array(
        'man:managedCustomerID' => $managed_customer_id,
        'man:amount' => $amount_in_cents,
        'man:InvoiceReference' => $invoice_reference,
        'man:InvoiceDescription' => $invoice_description
    );
    $soapaction = 'https://www.eway.com.au/gateway/managedpayment/ProcessPayment';

    $result = $soap_client->call('man:ProcessPayment', $paymentinfo, '', $soapaction);

    return $result;
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
