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
    public $CustomerTitle;
    public $CustomerFirstName;
    public $CustomerLastName;
    public $CustomerAddress;
    public $CustomerSuburb;
    public $CustomerState;
    public $CustomerCompany;
    public $CustomerPostCode;
    public $CustomerCountry;
    public $CustomerEmail;
    public $CustomerFax;
    public $CustomerPhone;
    public $CustomerMobile;
    public $CustomerRef;
    public $CustomerJobDesc;
    public $CustomerComments;
    public $CustomerURL;
    public $CustomerCCNumber;
    public $CustomerCCNameOnCard;
    public $CustomerCCExpiryMonth;
    public $CustomerCCExpiryYear;

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
            <Title>$CustomerTitle</Title>
            <FirstName>$CustomerFirstName</FirstName>
            <LastName>$CustomerLastName</LastName>
            <Address>$CustomerAddress</Address>
            <Suburb>$CustomerSuburb</Suburb>
            <State>$CustomerState</State>
            <Company>$CustomerCompany</Company>
            <PostCode>$CustomerPostCode</PostCode>
            <Country>$CustomerCountry</Country>
            <Email>$CustomerEmail</Email>
            <Fax>$CustomerFax</Fax>
            <Phone>$CustomerPhone</Phone>
            <Mobile>$CustomerMobile</Mobile>
            <CustomerRef>$CustomerRef</CustomerRef>
            <JobDesc>$CustomerJobDesc</JobDesc>
            <Comments>$CustomerComments</Comments>
            <URL>$CustomerURL<URL>
            <CCNumber>$CustomerCCNumber</CCNumber>
            <CCNameOnCard>$CustomerCCNameOnCard</CCNameOnCard>
            <CCExpiryMonth>$CustomerCCExpiryMonth</CCExpiryMonth>
            <CCExpiryYear>$CustomerCCExpiryYear</CCExpiryYear>
            </CreateCustomer>
        </soap:Body>
        ";

        return $xml;
    }
}
