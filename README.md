This is very much BETA right now, go ahead and use it, but I make no guarantees about anything, however, you will be helping me test things :)

These settings need to be added to the 'civicrm.settings.php' file, I will figure out how to change this in the future.

define('PENDING_CONTRIBUTION_STATUS_ID', 2);
// Typically 2, but may be different depending on your setup

define('COMPLETE_CONTRIBUTION_STATUS_ID', 1);
// Typically 1, but may be different depending on your setup

define('CANCELLED_CONTRIBUTION_STATUS_ID', 3);
// Typically 3, but may be different depending on your setup


define('PAYMENT_PROCESSOR_ID', 9);
// The ID of your CiviCRM eWay recurring payment processor

define('RECEIPT_SUBJECT_TITLE', 'Monthly Donation');
// Every period a receipt will get sent, what should it's subject be

This extension has quite a history, this documentation will be fleshed out in the future, but it wouldn't have been possible without the efforts and backing of : Voiceless, Community Builders, The Australasian Tuberous Sclerosis Society, Henare Degan and RIGPA.
