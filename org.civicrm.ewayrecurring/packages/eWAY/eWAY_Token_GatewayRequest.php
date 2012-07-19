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
    public $ewayCustomerID;
    public $CustomerRef;
    public $CustomerTitle;
    public $CustomerFirstName;
    public $CustomerLastName;
    public $CustomerCompany;
    public $CustomerJobDesc;
    public $CustomerEmail;
    public $CustomerAddress;
    public $CustomerSuburb;
    public $CustomerState;
    public $CustomerPostCode;
    public $CustomerCountry;
    public $CustomerPhone1;
    public $CustomerPhone2;
    public $CustomerFax;
    public $CustomerURL;
    public $CustomerComments;
    public $RebillInvRef;
    public $RebillInvDesc;
    public $RebillCCName;
    public $RebillCCNumber;
    public $RebillCCExpMonth;
    public $RebillCCExpYear;
    public $RebillInitAmt;
    public $RebillInitDate;
    public $RebillRecurAmt;
    public $RebillStartDate;
    public $RebillInterval;
    public $RebillIntervalType;
    public $RebillEndDate;

    function TokenGatewayRequest()
    {
        // Empty Constructor
    }

    public function ToXML()
    {
        $xmlRebill = new DomDocument('1.0');

        $nodeRoot = $xmlRebill->CreateElement('RebillUpload');
        $nodeRoot = $xmlRebill->appendChild($nodeRoot);

        $nodeNewRebill = $xmlRebill->createElement('NewRebill');
        $nodeNewRebill = $nodeRoot->appendChild($nodeNewRebill);

        $nodeCustomer = $xmlRebill->createElement('eWayCustomerID');
        $nodeCustomer = $nodeNewRebill->appendChild($nodeCustomer);

        $value = $xmlRebill->createTextNode($this->ewayCustomerID);
        $value = $nodeCustomer->appendChild($value);

        //Customer
        $nodeCustomer = $xmlRebill->createElement('Customer');
        $nodeCustomer = $nodeNewRebill->appendChild($nodeCustomer);

        $nodeCustomerDetails = $xmlRebill->createElement('CustomerRef');
        $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

        $value = $xmlRebill->createTextNode($this->CustomerRef);
        $value = $nodeCustomerDetails->appendChild($value);

        $nodeCustomerDetails = $xmlRebill->createElement('CustomerTitle');
        $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

        $value = $xmlRebill->createTextNode($this->CustomerTitle);
        $value = $nodeCustomerDetails->appendChild($value);

        $nodeCustomerDetails = $xmlRebill->createElement('CustomerFirstName');
        $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

        $value = $xmlRebill->createTextNode($this->CustomerFirstName);
        $value = $nodeCustomerDetails->appendChild($value);

        $nodeCustomerDetails = $xmlRebill->createElement('CustomerLastName');
        $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

        $value = $xmlRebill->createTextNode($this->CustomerLastName);
        $value = $nodeCustomerDetails->appendChild($value);

        $nodeCustomerDetails = $xmlRebill->createElement('CustomerCompany');
        $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

        $value = $xmlRebill->createTextNode($this->CustomerCompany);
        $value = $nodeCustomerDetails->appendChild($value);

        $nodeCustomerDetails = $xmlRebill->createElement('CustomerJobDesc');
        $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

        $value = $xmlRebill->createTextNode($this->CustomerJobDesc);
        $value = $nodeCustomerDetails->appendChild($value);

        $nodeCustomerDetails = $xmlRebill->createElement('CustomerEmail');
        $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

        $value = $xmlRebill->createTextNode($this->CustomerEmail);
        $value = $nodeCustomerDetails->appendChild($value);

        $nodeCustomerDetails = $xmlRebill->createElement('CustomerAddress');
        $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

        $value = $xmlRebill->createTextNode($this->CustomerAddress);
        $value = $nodeCustomerDetails->appendChild($value);

        $nodeCustomerDetails = $xmlRebill->createElement('CustomerSuburb');
        $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

        $value = $xmlRebill->createTextNode($this->CustomerSuburb);
        $value = $nodeCustomerDetails->appendChild($value);

        $nodeCustomerDetails = $xmlRebill->createElement('CustomerState');
        $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

        $value = $xmlRebill->createTextNode($this->CustomerState);
        $value = $nodeCustomerDetails->appendChild($value);

        $nodeCustomerDetails = $xmlRebill->createElement('CustomerPostCode');
        $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

        $value = $xmlRebill->createTextNode($this->CustomerPostCode);
        $value = $nodeCustomerDetails->appendChild($value);

        $nodeCustomerDetails = $xmlRebill->createElement('CustomerCountry');
        $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

        $value = $xmlRebill->createTextNode($this->CustomerCountry);
        $value = $nodeCustomerDetails->appendChild($value);

        $nodeCustomerDetails = $xmlRebill->createElement('CustomerPhone1');
        $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

        $value = $xmlRebill->createTextNode($this->CustomerPhone1);
        $value = $nodeCustomerDetails->appendChild($value);

        $nodeCustomerDetails = $xmlRebill->createElement('CustomerPhone2');
        $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

        $value = $xmlRebill->createTextNode($this->CustomerPhone2);
        $value = $nodeCustomerDetails->appendChild($value);

        $nodeCustomerDetails = $xmlRebill->createElement('CustomerFax');
        $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

        $value = $xmlRebill->createTextNode($this->CustomerFax);
        $value = $nodeCustomerDetails->appendChild($value);

        $nodeCustomerDetails = $xmlRebill->createElement('CustomerURL');
        $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

        $value = $xmlRebill->createTextNode($this->CustomerURL);
        $value = $nodeCustomerDetails->appendChild($value);

        $nodeCustomerDetails = $xmlRebill->createElement('CustomerComments');
        $nodeCustomerDetails = $nodeCustomer->appendChild($nodeCustomerDetails);

        $value = $xmlRebill->createTextNode($this->CustomerComments);
        $value = $nodeCustomerDetails->appendChild($value);

        //Rebill Events
        $nodeRebillEvent = $xmlRebill->createElement('RebillEvent');
        $nodeRebillEvent = $nodeNewRebill->appendChild($nodeRebillEvent);

        $nodeRebillDetails = $xmlRebill->createElement('RebillInvRef');
        $nodeRebillDetails = $nodeRebillEvent->appendChild($nodeRebillDetails);

        $value = $xmlRebill->createTextNode($this->RebillInvRef);
        $value = $nodeRebillDetails->AppendChild($value);

        $nodeRebillDetails = $xmlRebill->createElement('RebillInvDesc');
        $nodeRebillDetails = $nodeRebillEvent->appendChild($nodeRebillDetails);

        $value = $xmlRebill->createTextNode($this->RebillInvDesc);
        $value = $nodeRebillDetails->AppendChild($value);

        $nodeRebillDetails = $xmlRebill->createElement('RebillCCName');
        $nodeRebillDetails = $nodeRebillEvent->appendChild($nodeRebillDetails);

        $value = $xmlRebill->createTextNode($this->RebillCCName);
        $value = $nodeRebillDetails->AppendChild($value);

        $nodeRebillDetails = $xmlRebill->createElement('RebillCCNumber');
        $nodeRebillDetails = $nodeRebillEvent->appendChild($nodeRebillDetails);

        $value = $xmlRebill->createTextNode($this->RebillCCNumber);
        $value = $nodeRebillDetails->AppendChild($value);

        $nodeRebillDetails = $xmlRebill->createElement('RebillCCExpMonth');
        $nodeRebillDetails = $nodeRebillEvent->appendChild($nodeRebillDetails);

        $value = $xmlRebill->createTextNode($this->RebillCCExpMonth);
        $value = $nodeRebillDetails->AppendChild($value);

        $nodeRebillDetails = $xmlRebill->createElement('RebillCCExpYear');
        $nodeRebillDetails = $nodeRebillEvent->appendChild($nodeRebillDetails);

        $value = $xmlRebill->createTextNode($this->RebillCCExpYear);
        $value = $nodeRebillDetails->AppendChild($value);

        $nodeRebillDetails = $xmlRebill->createElement('RebillInitAmt');
        $nodeRebillDetails = $nodeRebillEvent->appendChild($nodeRebillDetails);

        $value = $xmlRebill->createTextNode($this->RebillInitAmt);
        $value = $nodeRebillDetails->AppendChild($value);

        $nodeRebillDetails = $xmlRebill->createElement('RebillInitDate');
        $nodeRebillDetails = $nodeRebillEvent->appendChild($nodeRebillDetails);

        $value = $xmlRebill->createTextNode($this->RebillInitDate);
        $value = $nodeRebillDetails->AppendChild($value);

        $nodeRebillDetails = $xmlRebill->createElement('RebillRecurAmt');
        $nodeRebillDetails = $nodeRebillEvent->appendChild($nodeRebillDetails);

        $value = $xmlRebill->createTextNode($this->RebillRecurAmt);
        $value = $nodeRebillDetails->AppendChild($value);

        $nodeRebillDetails = $xmlRebill->createElement('RebillStartDate');
        $nodeRebillDetails = $nodeRebillEvent->appendChild($nodeRebillDetails);

        $value = $xmlRebill->createTextNode($this->RebillStartDate);
        $value = $nodeRebillDetails->AppendChild($value);

        $nodeRebillDetails = $xmlRebill->createElement('RebillInterval');
        $nodeRebillDetails = $nodeRebillEvent->appendChild($nodeRebillDetails);

        $value = $xmlRebill->createTextNode($this->RebillInterval);
        $value = $nodeRebillDetails->AppendChild($value);

        $nodeRebillDetails = $xmlRebill->createElement('RebillIntervalType');
        $nodeRebillDetails = $nodeRebillEvent->appendChild($nodeRebillDetails);

        $value = $xmlRebill->createTextNode($this->RebillIntervalType);
        $value = $nodeRebillDetails->AppendChild($value);

        $nodeRebillDetails = $xmlRebill->createElement('RebillEndDate');
        $nodeRebillDetails = $nodeRebillEvent->appendChild($nodeRebillDetails);

        $value = $xmlRebill->createTextNode($this->RebillEndDate);
        $value = $nodeRebillDetails->AppendChild($value);

        $InnerXml = $xmlRebill->saveXML();

        return $InnerXml;
    }
}
