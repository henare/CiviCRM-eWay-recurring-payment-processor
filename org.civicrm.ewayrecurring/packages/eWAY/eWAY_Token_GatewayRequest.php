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
                <eWAYCustomerID>$this->eWayCustomerID</eWAYCustomerID>
                <Username>$this->eWayUsername</Username>
                <Password>$this->eWayPassword</Password>
            </eWAYHeader>
        </soap:Header>
        <soap:Body>
            <CreateCustomer xmlns=\"http://www.eway.com.au/gateway/managedPayment\">
                <Title>$this->Title</Title>
                <FirstName>$this->FirstName</FirstName>
                <LastName>$this->LastName</LastName>
                <Address>$this->Address</Address>
                <Suburb>$this->Suburb</Suburb>
                <State>$this->State</State>
                <Company>$this->Company</Company>
                <PostCode>$this->PostCode</PostCode>
                <Country>$this->Country</Country>
                <Email>$this->Email</Email>
                <Fax>$this->Fax</Fax>
                <Phone>$this->Phone</Phone>
                <Mobile>$this->Mobile</Mobile>
                <CustomerRef>$this->CustomerRef</CustomerRef>
                <JobDesc>$this->JobDesc</JobDesc>
                <Comments>$this->Comments</Comments>
                <URL>$this->URL<URL>
                <CCNumber>$this->CCNumber</CCNumber>
                <CCNameOnCard>$this->CCNameOnCard</CCNameOnCard>
                <CCExpiryMonth>$this->CCExpiryMonth</CCExpiryMonth>
                <CCExpiryYear>$this->CCExpiryYear</CCExpiryYear>
            </CreateCustomer>
        </soap:Body>
        ";

        return $xml;
    }

    public function processPaymentXML() {
        $xml = "
        <soap:Header>
            <eWAYHeader xmlns=\"http://www.eway.com.au/gateway/managedPayment\">
                <eWAYCustomerID>$this->eWayCustomerID</eWAYCustomerID>
                <Username>$this->eWayUsername</Username>
                <Password>$this->eWayPassword</Password>
            </eWAYHeader>
        </soap:Header>
        <soap:Body>
            <ProcessPayment xmlns=\"https://www.eway.com.au/gateway/managedpayment\">
                <managedCustomerID>$this->managedCustomerID</managedCustomerID>
                <amount>$this->amount</amount>
                <invoiceReference>$this->invoiceReference</invoiceReference>
                <invoiceDescription>$this->invoiceDescription</invoiceDescription>
            </ProcessPayment>
        </soap:Body>
        ";

        return $xml;
    }
}
