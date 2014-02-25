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

 * eWay API call
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_job_eway($params) {

    // TODO: Remove hacky hardcoded constants
    // The title used for receipt messages
    define('RECEIPT_SUBJECT_TITLE', 'Regular Donation');

    $config = CRM_Core_Config::singleton();

    require_once 'api/api.php';
    require_once 'CRM/Contribute/BAO/ContributionRecur.php';
    require_once 'CRM/Contribute/BAO/Contribution.php';
    require_once 'CRM/Contribute/PseudoConstant.php';
    require_once 'CRM/Financial/BAO/PaymentProcessor.php';
    require_once 'CRM/Utils/Date.php';
    require_once 'CRM/Core/BAO/MessageTemplate.php';
    require_once 'CRM/Contact/BAO/Contact/Location.php';
    require_once 'CRM/Core/BAO/Domain.php';
    require_once 'nusoap.php';

    $apiResult = array();

    // Get contribution status values
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');

    // Create eWay token clients
    $eway_token_clients = get_eway_token_clients();

    // Get pending contributions
    $pending_contributions = get_pending_recurring_contributions($eway_token_clients);

    $apiResult[] = "Processing " . count($pending_contributions) . " pending contributions";
    foreach ($pending_contributions as $pending_contribution) {
        // Process payment
        $apiResult[] = "Processing payment for pending contribution ID: " . $pending_contribution['contribution']->id;
        $amount_in_cents = str_replace('.', '', $pending_contribution['contribution']->total_amount);
        $result = process_eway_payment(
            $eway_token_clients[$pending_contribution['contribution_recur']->payment_processor_id],
            $pending_contribution['contribution_recur']->processor_id,
            $amount_in_cents,
            $pending_contribution['contribution']->invoice_id,
            $pending_contribution['contribution']->source
        );

        // Bail if the transaction fails
        if ($result['ewayTrxnStatus'] != 'True') {
            $apiResult[] = 'ERROR: Failed to process transaction for managed customer: ' . $pending_contribution['contribution_recur']->processor_id;
            $apiResult[] = 'eWay response: ' . $result['faultstring'];
            continue;
        }
        $apiResult[] = "Successfully processed payment for pending contribution ID: " . $pending_contribution['contribution']->id;

        $apiResult[] = "Marking contribution as complete";
        $pending_contribution['contribution']->trxn_id = $result->ewayTrxnNumber;
        complete_contribution($pending_contribution['contribution']);

        $apiResult[] = "Sending receipt";
        send_receipt_email($pending_contribution['contribution']->id);

        $apiResult[] = "Updating recurring contribution";
        update_recurring_contribution($pending_contribution['contribution_recur']);
        $apiResult[] = "Finished processing contribution ID: " . $pending_contribution['contribution']->id;
    }

    // Process today's scheduled contributions
    $scheduled_contributions = get_scheduled_contributions($eway_token_clients);

    $apiResult[] = "Processing " . count($scheduled_contributions) . " scheduled contributions";
    foreach ($scheduled_contributions as $contribution) {
        // Process payment
        $apiResult[] = "Processing payment for scheduled recurring contribution ID: " . $contribution->id;
        $amount_in_cents = str_replace('.', '', $contribution->amount);
        $result = process_eway_payment(
            $eway_token_clients[$contribution->payment_processor_id],
            $contribution->processor_id,
            $amount_in_cents,
            $contribution->invoice_id,
            ''
        );

        // Bail if the transaction fails
        if ($result->ewayTrxnStatus != 'True') {
            $apiResult[] = 'ERROR: Failed to process transaction for managed customer: ' . $contribution->processor_id;
            $apiResult[] = 'eWay response: ' . $result->ewayTrxnError;
            // TODO: Mark transaction as failed
            continue;
        }
        $apiResult[] = "Successfully processed payment for scheduled recurring contribution ID: " . $contribution->id;

        $past_contribution = get_first_contribution_from_recurring($contribution->id);

        $apiResult[] = "Creating contribution record";
        $new_contribution_record = new CRM_Contribute_BAO_Contribution();
        $new_contribution_record->contact_id = $contribution->contact_id;
        $new_contribution_record->receive_date = CRM_Utils_Date::isoToMysql(date('Y-m-d H:i:s'));
        $new_contribution_record->total_amount = $contribution->amount;
        $new_contribution_record->non_deductible_amount = $contribution->amount;
        $new_contribution_record->net_amount = $contribution->amount;
        $new_contribution_record->trxn_id = $result->ewayTrxnNumber;
        $new_contribution_record->invoice_id = md5(uniqid(rand(), TRUE));
        $new_contribution_record->contribution_recur_id = $contribution->id;
        $new_contribution_record->contribution_status_id = array_search('Completed', $contributionStatus);
        $new_contribution_record->financial_type_id = $contribution->financial_type_id;
        $new_contribution_record->currency = $contribution->currency;
        //copy info from previous contribution belonging to the same recurring contribution
        if ($past_contribution != null) {
            $new_contribution_record->contribution_page_id = $past_contribution->contribution_page_id;
            $new_contribution_record->payment_instrument_id = $past_contribution->payment_instrument_id;
            $new_contribution_record->source = $past_contribution->source;
            $new_contribution_record->address_id = $past_contribution->address_id;
        }
        $new_contribution_record->save();

        $apiResult[] = "Sending receipt";
        send_receipt_email($new_contribution_record->id);

        $apiResult[] = "Updating recurring contribution";
        update_recurring_contribution($contribution);
        $apiResult[] = "Finished processing recurring contribution ID: " . $contribution->id;
    }

    return civicrm_api3_create_success($apiResult, $params);
}

/**
 * get_eway_token_clients
 * 
 * Find the eWAY recurring payment processors
 * 
 * @return array An associative array of Processor Id => eWAY Token Client
 */
function get_eway_token_clients() {
    $processor = new CRM_Financial_BAO_PaymentProcessor();
    $processor->whereAdd("`class_name` = 'com.chrischinchilla.ewayrecurring'");
    $processor->find();

    $result = array();

    while ($processor->fetch()) {
        $result[$processor->id] = eway_token_client (
                                        $processor->url_recur,
                                        $processor->subject,
                                        $processor->user_name,
                                        $processor->password );
    }

    return $result;
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
    $contributions->orderBy("`id`");
    $contributions->find();

    while ($contributions->fetch()) {
        return clone($contributions);
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
function get_pending_recurring_contributions($eway_token_clients)
{
    if (empty($eway_token_clients)) {
        return array();
    }

    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');

    // Get the Recurring Contributions that are Pending for this Payment Processor
    $recurring = new CRM_Contribute_BAO_ContributionRecur();
    $recurring->whereAdd( "`contribution_status_id` = " . array_search('Pending', $contributionStatus) );
    $recurring->whereAdd( "`payment_processor_id` in (" . implode(', ', array_keys($eway_token_clients)) . ")" );
    $recurring->find();

    $result = array();

    while ( $recurring->fetch() ) {
        // Get the Contribution
        $contribution = new CRM_Contribute_BAO_Contribution();
        $contribution->whereAdd("`contribution_recur_id` = " . $recurring->id);

        if ( $contribution->find(true) ) {
            $result[] = array(
                            'contribution' => clone($contribution),
                            'contribution_recur' => clone($recurring)
                        );
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
function get_scheduled_contributions($eway_token_clients)
{
    if (empty($eway_token_clients)) {
        return array();
    }

	$contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');

    // Get Recurring Contributions that are In Progress and are due to be processed by the eWAY Recurring processor
    $scheduled_today = new CRM_Contribute_BAO_ContributionRecur();
    $scheduled_today->whereAdd("`next_sched_contribution_date` <= '" . date('Y-m-d 00:00:00') . "'");
    $scheduled_today->whereAdd("`contribution_status_id` = " . array_search('In Progress', $contributionStatus));
    $scheduled_today->whereAdd( "`payment_processor_id` in (" . implode(', ', array_keys($eway_token_clients)) . ")" );
    $scheduled_today->find();

    $result = array();

    while ($scheduled_today->fetch()) {
        $result[] = clone($scheduled_today);
    }

    return $result;
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
    // Set up SOAP client
    $soap_client = new nusoap_client($gateway_url, false);
    $soap_client->namespaces['man'] = 'https://www.eway.com.au/gateway/managedpayment';

    // Set up SOAP headers
    $headers = "<man:eWAYHeader><man:eWAYCustomerID>"
             . $eway_customer_id
             . "</man:eWAYCustomerID><man:Username>"
             . $username
             . "</man:Username><man:Password>"
             . $password
             . "</man:Password></man:eWAYHeader>";
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
    // PHP bug: https://bugs.php.net/bug.php?id=49669. issue with value greater than 2147483647.
    settype($managed_customer_id,"float");
	
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
 * @param object $contribution The contribution to mark as complete
 * @return object The contribution object
 */
function complete_contribution($contribution)
{
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');

    // Mark the contribution as complete
    $completed = new CRM_Contribute_BAO_Contribution();
    $completed->id = $contribution->id;
    $completed->find(true);
    $completed->trxn_id = $contribution->trxn_id;
    $completed->contribution_status_id = array_search('Completed', $contributionStatus);
    $completed->receive_date = CRM_Utils_Date::isoToMysql(date('Y-m-d H:i:s'));

    return $completed->save();
}

/**
 * update_recurring_contribution
 *
 * Updates the recurring contribution
 *
 * @param object $current_recur The ID of the recurring contribution
 * @return object The recurring contribution object
 */
function update_recurring_contribution($current_recur)
{
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');

    /*
     * Creating a new recurrence object as the DAO had problems saving unless
     * all the dates were overwritten. Seems easier to create a new object and
     * only update the fields that are needed
     */
    $updated_recur = new CRM_Contribute_BAO_ContributionRecur();
    $updated_recur->id = $current_recur->id;
    $updated_recur->contribution_status_id = array_search('In Progress', $contributionStatus);
    $updated_recur->modified_date = CRM_Utils_Date::isoToMysql(date('Y-m-d H:i:s'));
    
    /*
     * Update the next date to schedule a contribution. 
     * If all installments complete, mark the recurring contribution as complete
     */
    $updated_recur->next_sched_contribution_date = CRM_Utils_Date::isoToMysql(
            date('Y-m-d 00:00:00', 
                    strtotime( '+' . $current_recur->frequency_interval . ' ' . $current_recur->frequency_unit)
            )
    );
    if ( isset($current_recur->installments) && $current_recur->installments > 0 ) {
        $contributions = new CRM_Contribute_BAO_Contribution();
        $contributions->whereAdd("`contribution_recur_id` = " . $current_recur->id);
        $contributions->find();
        if ($contributions->N >= $current_recur->installments) {
            $updated_recur->next_sched_contribution_date = null;
            $updated_recur->contribution_status_id = array_search('Completed', $contributionStatus);
            $updated_recur->end_date = CRM_Utils_Date::isoToMysql(date('Y-m-d 00:00:00'));
        }
    }

    return $updated_recur->save();
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
    
    $page = new CRM_Contribute_BAO_ContributionPage();
    $page->id = $contribution->contribution_page_id;
    $page->find(true);
    if (isset($page->receipt_text)) {
        $receipt_text = $page->receipt_text;
    } else {
        $receipt_text = 'Thank you for your contribution.';
    }

    $params = array(
        'groupName' => 'msg_tpl_workflow_contribution',
        'valueName' => 'contribution_online_receipt',
        'contactId' => $contribution->contact_id,
        'tplParams' => array(
            'contributeMode' => 'directIPN',
            'receiptFromEmail' => $receiptFromEmail,
            'amount' => $contribution->total_amount,
            'title' => RECEIPT_SUBJECT_TITLE,
            'is_recur' => true,
            'billingName' => $name,
            'email' => $email,
            'trxn_id' => $contribution->trxn_id,
            'receive_date' => $contribution->receive_date,
            'is_monetary' => true,
            'receipt_text' => $receipt_text,
        ),
        'from' => $receiptFrom,
        'toName' => $name,
        'toEmail' => $email,
        'isTest' => $contribution->is_test
    );

    $eWayProcessor = CRM_Financial_BAO_PaymentProcessor::getProcessorForEntity
                    ($contribution->contribution_recur_id, 'recur', 'obj');
    if ( $eWayProcessor->isSupported('cancelSubscription') ) {
    	$params['tplParams']['cancelSubscriptionUrl'] =
    	        $eWayProcessor->subscriptionURL($contribution->contribution_recur_id, 'recur');
    }
    if ( $eWayProcessor->isSupported('updateSubscriptionBillingInfo') ) {
    	$params['tplParams']['updateSubscriptionBillingUrl'] =
    	        $eWayProcessor->subscriptionURL($contribution->contribution_recur_id, 'recur', 'billing');
    }
    if ( $eWayProcessor->isSupported('changeSubscriptionAmount') ) {
    	$params['tplParams']['updateSubscriptionUrl'] =
    	        $eWayProcessor->subscriptionURL($contribution->contribution_recur_id, 'recur', 'update');
    }

    list($sent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplate::sendTemplate($params);

    return $sent;
}
