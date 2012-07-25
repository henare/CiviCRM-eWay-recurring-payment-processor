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

// Initialise CiviCRM
chdir(CIVICRM_DIRECTORY);
require 'civicrm.config.php';
require 'CRM/Core/Config.php';
$config = CRM_Core_Config::singleton();

require_once 'api/api.php';
require_once 'CRM/Contribute/BAO/ContributionRecur.php';
require_once 'CRM/Contribute/BAO/Contribution.php';
require_once 'CRM/Core/BAO/PaymentProcessor.php';
require_once 'CRM/Utils/Date.php';

// Get pending contributions
$pending_contributions = get_pending_recurring_contributions();

// Create eWay token client
$payment_processor = CRM_Core_BAO_PaymentProcessor::getPayment(PAYMENT_PROCESSOR_ID, 'live');
$token_client = eway_token_client(
    $payment_processor['url_recur'],
    $payment_processor['subject'],
    $payment_processor['user_name'],
    $payment_processor['password']
);

echo "Processing " . count($pending_contributions) . " pending contributions\n";
foreach ($pending_contributions as $pending_contribution) {
    // Process payment
    echo "Processing payment for pending contribution ID: " . $pending_contribution['contribution']['id'] . "\n";
    $amount_in_cents = str_replace('.', '', $pending_contribution['contribution']['total_amount']);
    $result = process_eway_payment(
        $token_client,
        $pending_contribution['contribution_recur']->processor_id,
        $amount_in_cents,
        $pending_contribution['contribution']['invoice_id'],
        $pending_contribution['contribution']['contribution_source']
    );

    // Bail if the transaction fails
    if ($result->ewayTrxnStatus != 'True') {
        echo 'ERROR: Failed to process transaction for managed customer: ' . $pending_contribution['contribution_recur']->processor_id . "\n";
        echo 'eWay response: ' . $result->ewayTrxnError . "\n";
        continue;
    }
    echo "Successfully processed payment for pending contribution ID: " . $pending_contribution['contribution']['id'] . "\n";

    // Send receipt
    send_receipt_email($pending_contribution['contribution_recur']->id);

    // Mark contribution as complete
    complete_contribution($pending_contribution['contribution']['id']);

    $pending_contribution['contribution_recur']->next_sched_contribution = CRM_Utils_Date::isoToMysql(date('Y-m-d 00:00:00', strtotime("+1 month")));
    $pending_contribution['contribution_recur']->save();
}

// Process today's scheduled contributions
$scheduled_contributions = get_scheduled_contributions();

echo "Processing " . count($scheduled_contributions) . " scheduled contributions\n";
foreach ($scheduled_contributions as $contribution) {
    // Process payment
    $amount_in_cents = str_replace('.', '', $contribution->amount);
    $result = process_eway_payment(
        $token_client,
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

    // Create contribution record
    $new_contribution_record = new CRM_Contribute_BAO_Contribution();
    $new_contribution_record->contact_id = $contribution->contact_id;
    $new_contribution_record->receive_date = CRM_Utils_Date::isoToMysql(date('Y-m-d 00:00:00'));
    $new_contribution_record->total_amount = $contribution->amount;
    $new_contribution_record->contribution_recur_id = $contribution->id;
    $new_contribution_record->contribution_status_id = 1; // TODO: Remove hardcoded hack
    $new_contribution_record->contribution_type_id = $contribution->contribution_type_id;
    $new_contribution_record->save();

    // Send receipt
    send_receipt_email($new_contribution_record->id);

    $contribution->next_sched_contribution = CRM_Utils_Date::isoToMysql(date('Y-m-d 00:00:00', strtotime("+1 month")));
    $contribution->save();
}

/**
 * get_pending_recurring_contributions
 *
 * Gets recurring contributions that are in a pending state.
 * These are for newly created recurring contributions and should
 * generally be processed the same day they're created. These do not
 * include the regularly processed recurring transactions.
 *
 * @return array An array of associative arrays containing contribution arrays & contribtion_recur objects
 */
function get_pending_recurring_contributions()
{
    // Get pending contributions
    $params = array(
        'version' => 3,
        // TODO: Statuses are customisable so this configuration should be read from the DB
        'contribution_status_id' => PENDING_CONTRIBUTION_STATUS_ID
    );
    $pending_contributions = civicrm_api('contribution', 'get', $params);

    $result = array();

    foreach ($pending_contributions['values'] as $contribution) {
        // Only process those with recurring contribution records
        if ($contribution['contribution_recur_id']) {
            // Find the recurring contribution record for this contribution
            // TODO: Use the API when it has support for getting recurring contributions
            $recurring = new CRM_Contribute_BAO_ContributionRecur();
            $recurring->id = $contribution['contribution_recur_id'];

            // Only process records that have a recurring record with
            // a processor ID, i.e. an eWay token
            if ($recurring->find(true) && $recurring->processor_id) {
                // TODO: Return the same type of results
                // This is a bit nasty, contribution is an array and
                // contribution_recur is an object
                $result[] = array(
                    'contribution' => $contribution,
                    'contribution_recur' => $recurring
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
 * @return array An array of contribtion_recur objects
 */
function get_scheduled_contributions()
{
    $scheduled_today = new CRM_Contribute_BAO_ContributionRecur();
    $scheduled_today->whereAdd("`next_sched_contribution` = '" . date('Y-m-d 00:00:00') . "'");
    // Don't get cancelled contributions
    $scheduled_today->whereAdd("`contribution_status_id` != 3");
    // Or test transactions
    $scheduled_today->whereAdd("`is_test` != 1");
    $scheduled_today->find();

    $scheduled_contributions = array();

    while ($scheduled_today->fetch()) {
        // Check that there's no existing contribution record for today
        $contribution = new CRM_Contribute_BAO_Contribution();
        $contribution->contribution_recur_id = $scheduled_today->id;
        $contribution->whereAdd("`receive_date` = '" . date('Y-m-d 00:00:00') . "'");

        if ($contribution->find() == 0) {
            $scheduled_contributions[] = $scheduled_today;
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
    $soap_client = new SoapClient($gateway_url);

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
    $paymentinfo = array(
        'managedCustomerID' => $managed_customer_id,
        'amount' => $amount_in_cents,
        'InvoiceReference' => $invoice_reference,
        'InvoiceDescription' => $invoice_description
    );

    $result = $soap_client->ProcessPayment($paymentinfo);
    $eway_response = $result->ewayResponse;

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
    $contribution->contribution_status_id = 2;
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
    echo "TODO: Send email for contribution ID: $contribution_id";

    return false;
}
