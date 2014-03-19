CiviCRM eWay Token Recurring Payment Processor Extension
========================================================

[![Project Status](http://stillmaintained.com/henare/CiviCRM-eWay-recurring-payment-processor.png)](http://stillmaintained.com/henare/CiviCRM-eWay-recurring-payment-processor)

Installing
----------

* Copy the `org.civicrm.ewayrecurring` directory to your CiviCRM extension directory (which is located in a configurable location, see Administer > System Settings > Directories)
* Install the extension from the Administer > System Settings > Manage Extensions administration page in CiviCRM
* Now [set up a payment processor](http://wiki.civicrm.org/confluence/display/CRMDOC/Payment+Processors#PaymentProcessors-SetupandConfiguration) and contribution page like you would normally

Additional set up
-----------------

The `process_recurring_eway_payments.php` file is intended to be run daily from the command line. It processes pending transactions and recurring transactions that are due to be processed.

It is not ready to run out of the box and needs further customisation (see issue #3).

Background
----------
Most recurring payment processors in CiviCRM work as follows:

1. A user signs up for a recurring contribution
2. The payment processor sets up a recurring contribution with the payment gateway
3. The gateway handles the scheduling of payments. Whenever a payment is processed the gateway emails a receipt to the customer and pings CiviCRM to add a contribution (this is called Instant Payment Notification or IPN)
4. The customer can manage their payment directly with the gateway so if they cancel, CiviCRM just never sees another contribution

In early 2012 Chris Ward contributed the first eWay recurring payment processor which works similar to the above steps. The only difference being that eWay does not provide IPNs so the payment processor was set to request receipts be sent to a special mailbox where they were then parsed by a cron script. This is available on the [recurring-payments](https://github.com/henare/CiviCRM-eWay-recurring-payment-processor/tree/recurring-payments) branch.

In mid 2012 Henare Degan modified this payment processor to use [eWay's Token Payment API](http://www.eway.com.au/developers/api/token.html). It works as follows:

1. A user signs up for a recurring contribution
2. The payment processor sets up an eWay customer using the Token API
3. A cron script runs each day that: processes pending transactions (i.e. people that have signed up since the script last ran) and processes scheduled transactions (i.e. a recurring payment that needs to happen that day). It does this by using the Token API to process a payment and recording it as a contribution in CiviCRM

### So which one should I use?

That's up to you, both need a bit more work to set up.

The Recurring API version has a bunch of hardcoded values that you need to set and a mailbox to set up to process receipts.

The Token API version's cron script does not process generic recurring payments (it assumes monthly recurring) currently and it also has a few hardcoded values that need setting.
