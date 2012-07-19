<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM                                                            |
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

class TokenGatewayRequest
{
    // Header values
    public $eWayCustomerID;
    public $eWayUsername;
    public $eWayPassword;

    // Create Customer values
    public $Title;
    public $FirstName;
    public $LastName;
    public $Address;
    public $Suburb;
    public $State;
    public $Company;
    public $PostCode;
    public $Country;
    public $Email;
    public $Fax;
    public $Phone;
    public $Mobile;
    public $CustomerRef;
    public $JobDesc;
    public $Comments;
    public $URL;
    public $CCNumber;
    public $CCNameOnCard;
    public $CCExpiryMonth;
    public $CCExpiryYear;

    // Process Payment values
    public $managedCustomerID;
    public $amount;
    public $invoiceReference;
    public $invoiceDescription;

    public function createCustomerXML() {
        $xml = "
        <soap:Header>
            <eWAYHeader xmlns=\"http://www.eway.com.au/gateway/managedPayment\">
                <eWAYCustomerID>$eWayCustomerID</eWAYCustomerID>
                <Username>$eWayUsername</Username>
                <Password>$eWayPassword</Password>
            </eWAYHeader>
        </soap:Header>
        <soap:Body>
            <CreateCustomer xmlns=\"http://www.eway.com.au/gateway/managedPayment\">
                <Title>$Title</Title>
                <FirstName>$FirstName</FirstName>
                <LastName>$LastName</LastName>
                <Address>$Address</Address>
                <Suburb>$Suburb</Suburb>
                <State>$State</State>
                <Company>$Company</Company>
                <PostCode>$PostCode</PostCode>
                <Country>$Country</Country>
                <Email>$Email</Email>
                <Fax>$Fax</Fax>
                <Phone>$Phone</Phone>
                <Mobile>$Mobile</Mobile>
                <CustomerRef>$CustomerRef</CustomerRef>
                <JobDesc>$JobDesc</JobDesc>
                <Comments>$Comments</Comments>
                <URL>$URL<URL>
                <CCNumber>$CCNumber</CCNumber>
                <CCNameOnCard>$CCNameOnCard</CCNameOnCard>
                <CCExpiryMonth>$CCExpiryMonth</CCExpiryMonth>
                <CCExpiryYear>$CCExpiryYear</CCExpiryYear>
            </CreateCustomer>
        </soap:Body>
        ";

        return $xml;
    }

    public function processPaymentXML() {
        $xml = "
        <soap:Header>
            <eWAYHeader xmlns=\"http://www.eway.com.au/gateway/managedPayment\">
                <eWAYCustomerID>$eWayCustomerID</eWAYCustomerID>
                <Username>$eWayUsername</Username>
                <Password>$eWayPassword</Password>
            </eWAYHeader>
        </soap:Header>
        <soap:Body>
            <ProcessPayment xmlns=\"https://www.eway.com.au/gateway/managedpayment\">
                <managedCustomerID>$managedCustomerID</managedCustomerID>
                <amount>$amount</amount>
                <invoiceReference>$invoiceReference</invoiceReference>
                <invoiceDescription>$invoiceDescription</invoiceDescription>
            </ProcessPayment>
        </soap:Body>
        ";

        return $xml;
    }
}
