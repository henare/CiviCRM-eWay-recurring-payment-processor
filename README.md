These settings need to be added to the 'civicrm.settings.php' file, I will figure out how to change this in the future.

    define('PENDING_CONTRIBUTION_STATUS_ID', 2);
    // Typically 2, but may be different depending on your setup

    define('PAYMENT_PROCESSOR_ID', 9);
    // The ID of your CiviCRM eWay recurring payment processor

    define('RECEIPT_SUBJECT_TITLE', 'Monthly Donation');
    // Every period a receipt will get sent, what should it's subject be
