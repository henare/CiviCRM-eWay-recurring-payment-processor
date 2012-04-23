<?php

// Heavily customised version of original CiviCRM email processing. A lot of the code is probably defunct.

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.0                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2011
 * $Id$
 *
 */

define( 'EMAIL_ACTIVITY_TYPE_ID', null  );
define( 'MAIL_BATCH_SIZE'       , 50    );

/** 
 *When runing script from cli :
 * 1. By default script is being used for civimail processing.
 * eg : nice -19 php bin/EwayEmailProcessor.php -u<login> -p<password> -s<sites(or default)>
 *
 * 2. Pass "activities" as argument to use script for 'Email To Activity Processing'.
 * eg : nice -19 php bin/EwayEmailProcessor.php -u<login> -p<password> -s<sites(or default)> activities 
 *
 */

class EwayEmailProcessor {
    
    /**
     * Process the default mailbox (ie. that is used by civiMail for the bounce)
     *
     * @return void
     */
    static function processBounces() {
        require_once 'CRM/Core/DAO/MailSettings.php';
        $dao = new CRM_Core_DAO_MailSettings;
        $dao->domain_id = CRM_Core_Config::domainID( );
        $dao->is_default = true;
        $dao->find( );

        while ( $dao->fetch() ) {
            EwayEmailProcessor::_process(true,$dao);
        }
    }

    /**
     * Delete old files from a given directory (recursively)
     *
     * @param string $dir  directory to cleanup
     * @param int    $age  files older than this many seconds will be deleted (default: 60 days)
     * @return void
     */
    static function cleanupDir($dir, $age = 5184000)
    {
        // return early if we can’t read/write the dir
        if (!is_writable($dir) or !is_readable($dir) or !is_dir($dir)) return;

        foreach (scandir($dir) as $file) {

            // don’t go up the directory stack and skip new files/dirs
            if ($file == '.' or $file == '..')           continue;
            if (filemtime("$dir/$file") > time() - $age) continue;

            // it’s an old file/dir, so delete/recurse
            is_dir("$dir/$file") ? self::cleanupDir("$dir/$file", $age) : unlink("$dir/$file");
        }
    }

    /**
     * Process the mailboxes that aren't default (ie. that aren't used by civiMail for the bounce)
     *
     * @return void
     */
    static function processActivities() {
        require_once 'CRM/Core/DAO/MailSettings.php';
        $dao = new CRM_Core_DAO_MailSettings;
        $dao->domain_id = CRM_Core_Config::domainID( );
        $dao->is_default = false;
        $dao->find( );

        while ( $dao->fetch() ) {
            EwayEmailProcessor::_process(false,$dao);
        }
    }

    /**
     * Process the mailbox for all the settings from civicrm_mail_settings
     *
     * @param string $civiMail  if true, processing is done in CiviMail context, or Activities otherwise.
     * @return void
     */
    static function process( $civiMail = true ) {
        require_once 'CRM/Core/DAO/MailSettings.php';
        $dao = new CRM_Core_DAO_MailSettings;
        $dao->domain_id = CRM_Core_Config::domainID( );
        $dao->find( );

        while ( $dao->fetch() ) {
            EwayEmailProcessor::_process($civiMail,$dao);
        }
    }
    
    static function getEwayEmail()
    {
        $dao = new CRM_Core_DAO_MailSettings;
        $dao->domain_id =  CRM_Core_Config::domainID( );
        //$name ? $dao->name = $name : $dao->is_default = 1;
        //if (!$dao->find(true)) throw new Exception("Could not find entry named $name in civicrm_mail_settings");

		/* Change all these settings */
        require_once 'CRM/Mailing/MailStore/Imap.php';
        
        // Currently manually entering mailbox settings. In the long run this should be settable in GUI, but that would involve many other changes to CiviCRM first…
        return new CRM_Mailing_MailStore_Imap('email server address','email server user name','email server password', (bool) 1, NULL);
    }

    static function _process ($civiMail,$dao) {

		// 0 = activities; 1 = bounce;
		$usedfor = $dao->is_default;
		
		require_once 'CRM/Core/OptionGroup.php';
        $emailActivityTypeId = 
            ( defined('EMAIL_ACTIVITY_TYPE_ID') && EMAIL_ACTIVITY_TYPE_ID )  ? 
            EMAIL_ACTIVITY_TYPE_ID : CRM_Core_OptionGroup::getValue( 'activity_type', 
                                                                     'Inbound Email', 
                                                                     'name' );
        if ( ! $emailActivityTypeId ) {
            CRM_Core_Error::fatal( ts( 'Could not find a valid Activity Type ID for Inbound Email' ) );
        }

        $config = CRM_Core_Config::singleton();
        $verpSeperator = preg_quote( $config->verpSeparator );
        $twoDigitStringMin = $verpSeperator . '(\d+)' . $verpSeperator . '(\d+)';
        $twoDigitString    = $twoDigitStringMin . $verpSeperator;
        $threeDigitString  = $twoDigitString . '(\d+)' . $verpSeperator;

        // FIXME: legacy regexen to handle CiviCRM 2.1 address patterns, with domain id and possible VERP part
        $commonRegex = '/^' . preg_quote($dao->localpart) . '(b|bounce|c|confirm|o|optOut|r|reply|re|e|resubscribe|u|unsubscribe)' . $threeDigitString . '([0-9a-f]{16})(-.*)?@' . preg_quote($dao->domain) . '$/';
        $subscrRegex = '/^' . preg_quote($dao->localpart) . '(s|subscribe)' . $twoDigitStringMin . '@' . preg_quote($dao->domain) . '$/';

        // a common-for-all-actions regex to handle CiviCRM 2.2 address patterns
        $regex = '/^' . preg_quote($dao->localpart) . '(b|c|e|o|r|u)' . $twoDigitString . '([0-9a-f]{16})@' . preg_quote($dao->domain) . '$/';

        // a tighter regex for finding bounce info in soft bounces’ mail bodies
        $rpRegex = '/Return-Path: ' . preg_quote($dao->localpart) . '(b)' . $twoDigitString . '([0-9a-f]{16})@' . preg_quote($dao->domain) . '/';

        // retrieve the emails
        require_once 'CRM/Mailing/MailStore.php';
        // Using custom function to connect to eWay email box
		try {
        	$store = EwayEmailProcessor::getEwayEmail();
        } catch ( Exception $e ) {
            $message  = ts( 'Could not connect to MailStore' ) . '<p>';
            $message .= ts( 'Error message: ' );
            $message .= '<pre>' . $e->getMessage( ) . '</pre><p>';
            CRM_Core_Error::fatal( $message );
        }

        civicrm_api_include('mailer', false, 2);
        require_once 'CRM/Utils/Hook.php';

        // process fifty at a time, CRM-4002

        while ($mails = $store->fetchNext(MAIL_BATCH_SIZE)) {
            foreach ($mails as $key => $mail) {

		// If email subject is approved
        if(ereg('APPROVED', $mail->subject, $approvedMatch) > 0)
		{
			require_once 'CRM/Utils/Mail/Incoming.php';
			$mailParams = CRM_Utils_Mail_Incoming::parseMailingObject( $mail );
			
			// Build up a bunch of parameters and variables we may need
			$dateToProcess = strstr($mailParams['body'], 'Sent') ;									
			$strToProcess = strstr($mailParams['body'], 'Name') ;
			$amountToProcess = strstr($mailParams['body'], 'Purchase') ;
			$transactionToProcess = strstr($mailParams['body'], 'eWAY Transaction No') ;
			$invoiceToProcess = strstr($transactionToProcess, 'Invoice Ref#') ;

			$paymentDate = date('Ymd',strtotime(rtrim($mailParams['date']))) ;
			// Do we need to add time?
									
			preg_match('/Name *: *(.*)/', $strToProcess, $nameMatches);
			$paymentName = rtrim($nameMatches[1]) ;
			
			preg_match('/Address *: *(.*)/', $strToProcess, $addressMatches);
			$paymentAddress = rtrim($addressMatches[1]) ;
			
			preg_match('/eWAY Transaction No *: *(.*)/', $transactionToProcess, $transactionMatches);
			$paymentTransaction = rtrim($transactionMatches[1]) ;             		
			
			preg_match('/Purchase \/ Payment *: *(.*)/', $amountToProcess, $amountMatches);
			$paymentAmount = rtrim(str_replace("AUD$","",$amountMatches[1])) ;
								
			$names = explode(" ", $paymentName) ;
			
			preg_match('/Invoice Ref# *: *(.*)/', $invoiceToProcess, $invoiceMatches);
			$paymentInvoice = rtrim($invoiceMatches[1]) ;  

			$params = array(
				'invoice_id' => $paymentInvoice, //no (r) here
				'version' => 3,
			);
			
			$result = civicrm_api('contribution','get',$params) ;

			//require_once 'CRM/Core/Payment/eWayRecurring.php';
			//$eWayRecurring = new CRM_Core_Payment_eWAYRecurring( );
			//$eWayRecurring->processRecur($result,$paymentDate,$paymentTransaction);
			
			/* None of the below should be here, but keep getting server error*/

			// Populate contribution id and values from result above
			$cid = $result['id'] ;
			$values = $result['values'][$cid];

			// If a result is found
			if ($result['count'] == 1)
			{
				// CLIENT SPECIFIC, this checks to see if this is a one off payment or a recurring payment, the 'custom_140' is how client was previously marking recurring payments, so we need to keep checking for the foreseeable future.
				if($values['contribution_recur_id'] != NULL || $values['custom_140'] == 1)
				{ 
					// Populate the api call
					$contributionParams = array( 
						'contact_id' => $values['contact_id'],
						'receive_date' => $paymentDate,
						'total_amount' => $values['total_amount'],
						'currency' => $values['currency'],
						'contribution_type_id' => $values['contribution_type_id'],
						'contribution_recur_id' => $values['contribution_recur_id'],
						'payment_instrument_id' => 1,
						'trxn_id' => $paymentTransaction,
						'receipt_date' => date('Ymd'),
						'thankyou_date' => date('Ymd'),
						'source' => $values['contribution_source'],
						'contribution_status_id' => $values['contribution_status_id'],
						'version' => 2,
						'custom_140' => $values['custom_140'], // CLIENT SPECIFIC
					);
					// Error utilising v3 API, may be fixed after applying updates 
					// Create new contribution
					$newContribution = civicrm_api('contribution','create',$contributionParams) ;
				}
				else
				// If this is one off payment, which shouldn't ever happen, but there are a few rogue receipts coming through.
				{
					// Populate api call
					$updateParams = array(
						'version' => 3,
						'id' => $result['id'],
						'receipt_date' => date('Ymd'),
						'thankyou_date' => date('Ymd'),
						'contribution_status_id' => 1,
					);
					// Update contributions
					$updateResult = civicrm_api('contribution','update',$updateParams);	
			
					// Now to send an email		
					require_once 'CRM/Core/BAO/MessageTemplates.php' ;
					$params = array(
						'id' => 51 // CLIENT SPECIFIC, matches relevant message template
					);
					
					$emailAddress = civicrm_api('contact','get',array('contact_id' => $values['contact_id'],'version' => 3)) ; // Get email address from contact record
					$receiptEmail = CRM_Core_BAO_MessageTemplates::retrieve($params,$emailAddress['values'][$values['contact_id']]) ; // Retrieve and populate email template
			
					// Generate and create activity		
					require_once 'CRM/Activity/BAO/Activity.php';
					
					$contacts = array('id' => $values['contact_id']);
					// CLIENT SPECIFIC, replace manual tokens in email template
					$body = str_replace('{$formValues.date}', $result['values'][$result['id']]['receive_date'], $receiptEmail->msg_html);
					$body = str_replace('{$formValues.invoice_id}', $result['values'][$result['id']]['invoice_id'], $body);
					$body = str_replace('{$formValues.total_amount}', '$'.$result['values'][$result['id']]['total_amount'], $body);
					
					$contactIDs = array('id' => $values['contact_id']) ;
					// Populate email to be sent		
					$sendEmail = CRM_Activity_BAO_Activity::sendEmail(
						$emailAddress['values'],
						$receiptEmail->msg_subject,
						$receiptEmail->msg_text,
						$body,
						$emailAddress['email'],
						xxx, // CLIENT SPECIFIC, we have to match activity to a contact, so this is Suzanne
						'from email address',// CLIENT SPECIFIC, manual setting of from email address
						null,
						'cc email address',// CLIENT SPECIFIC, manual setting of cc addess
						null,
						$contactIDs
					);
					
				}			
		}
		// If no record is found, create a new one
 		else
 		{		
 			// Populate API call
			$contributionParams = array( 
				'contact_id' => $values['contact_id'],
			  	'receive_date' => $paymentDate,
			  	'total_amount' => $values['total_amount'],
			  	'currency' => $values['currency'],
			  	'contribution_type_id' => $values['contribution_type_id'],
			  	'contribution_recur_id' => $values['contribution_recur_id'],
				'payment_instrument_id' => 1,
				'trxn_id' => $paymentTransaction,
			  	'receipt_date' => date('Ymd'),
				'thankyou_date' => date('Ymd'),
			  	'source' => $values['contribution_source'],
			  	'contribution_status_id' => $values['contribution_status_id'],
			  	'version' => 2,
			);
			// Error utilising v3 API, may be fixed after applying updates 
			// Create contribution
			$newContribution = civicrm_api('contribution','create',$contributionParams) ;
		}
	}
    $store->markProcessed($key);
}              
   
$store->expunge();   // CRM-7356 – used by IMAP only
}
}

}

// bootstrap the environment and run the processor
// you can run this program either from an apache command, or from the cli
if ( php_sapi_name() == "cli" ) {
    require_once ("bin/cli.php");
    $cli=new civicrm_cli ();
    //if it doesn't die, it's authenticated 
    //log the execution of script
    CRM_Core_Error::debug_log_message( 'EwayEmailProcessor.php from the cli');
    require_once 'CRM/Core/Lock.php';
    $lock = new CRM_Core_Lock('EwayEmailProcessor');
    
    if (!$lock->isAcquired()) {
        throw new Exception('Could not acquire lock, another EwayEmailProcessor process is running');
    }

    // check if the script is being used for civimail processing or email to 
    // activity processing.
    if ( isset( $cli->args[0] ) && $cli->args[0] == "activities" ) {
        EwayEmailProcessor::processActivities();
    } else {
        EwayEmailProcessor::processBounces();
    }
    $lock->release();
} else {
    session_start();
    require_once '../../../civicrm.config.php';
    require_once 'CRM/Core/Config.php';
    $config = CRM_Core_Config::singleton();
    CRM_Utils_System::authenticateScript(true);

    //log the execution of script
    CRM_Core_Error::debug_log_message( 'EwayEmailProcessor.php');

    require_once 'CRM/Core/Lock.php';
    $lock = new CRM_Core_Lock('EwayEmailProcessor');

    if (! $lock->isAcquired()) {
        throw new Exception('Could not acquire lock, another EwayEmailProcessor process is running');
    }

    // try to unset any time limits
    if ( ! ini_get('safe_mode') ) {
        set_time_limit(0);
    }
        
    // cleanup directories with old mail files (if they exist): CRM-4452
    EwayEmailProcessor::cleanupDir($config->customFileUploadDir . DIRECTORY_SEPARATOR . 'CiviMail.ignored');
    EwayEmailProcessor::cleanupDir($config->customFileUploadDir . DIRECTORY_SEPARATOR . 'CiviMail.processed');
    
    // check if the script is being used for civimail processing or email to 
    // activity processing.
    $isCiviMail = CRM_Utils_Array::value( 'emailtoactivity', $_REQUEST ) ? false : true;
    EwayEmailProcessor::process($isCiviMail);

    $lock->release();
}
