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
    private $ewayCustomerID;

    private $CustomerRef;

    private $CustomerTitle;

    private $CustomerFirstName;

    private $CustomerLastName;

    private $CustomerCompany;

    private $CustomerJobDesc;

    private $CustomerEmail;

    private $CustomerAddress;

    private $CustomerSuburb;

    private $CustomerState;

    private $CustomerPostCode;

    private $CustomerCountry;

    private $CustomerPhone1;

    private $CustomerPhone2;

    private $CustomerFax;

    private $CustomerURL;

    private $CustomerComments;

    private $RebillInvRef;

    private $RebillInvDesc;

    private $RebillCCName;

    private $RebillCCNumber;

    private $RebillCCExpMonth;

    private $RebillCCExpYear;

    private $RebillInitAmt;

    private $RebillInitDate;

    private $RebillRecurAmt;

    private $RebillStartDate;

    private $RebillInterval;

    private $RebillIntervalType;

    private $RebillEndDate;

    private $ewayURL;

    function TokenGatewayRequest()
    {
        // Empty Constructor
    }


    public function eWAYCustomerID($value)
    {
        $this->ewayCustomerID = $value;
    }

    public function CustomerRef($value)
    {
        $this->CustomerRef = $value;
    }

    public function CustomerTitle($value)
    {
        $this->CustomerTitle = $value;
    }

    public function CustomerFirstName($value)
    {
        $this->CustomerFirstName = $value;
    }

    public function CustomerLastName($value)
    {
        $this->CustomerLastName = $value;
    }

    public function CustomerCompany($value)
    {
        $this->CustomerCompany = $value;
    }

    public function CustomerJobDesc($value)
    {
        $this->CustomerJobDesc = $value;
    }

    public function CustomerEmail($value)
    {
        $this->CustomerEmail = $value;
    }

    public function CustomerAddress($value)
    {
        $this->CustomerAddress = $value;
    }

    public function CustomerSuburb($value)
    {
        $this->CustomerSuburb = $value;
    }

    public function CustomerState($value)
    {
        $this->CustomerState = $value;
    }

    public function CustomerPostCode($value)
    {
        $this->CustomerPostCode = $value;
    }

    public function CustomerCountry($value)
    {
        $this->CustomerCountry = $value;
    }

    public function CustomerPhone1($value)
    {
        $this->CustomerPhone1 = $value;
    }

    public function CustomerPhone2($value)
    {
        $this->CustomerPhone2 = $value;
    }

    public function CustomerFax($value)
    {
        $this->CustomerFax = $value;
    }

    public function CustomerURL($value)
    {
        $this->CustomerURL = $value;
    }

    public function CustomerComments($value)
    {
        $this->CustomerComments = $value;
    }

    public function RebillInvRef($value)
    {
        $this->RebillInvRef = $value;
    }

    public function RebillInvDesc($value)
    {
        $this->RebillInvDesc = $value;
    }

    public function RebillCCName($value)
    {
        $this->RebillCCName = $value;
    }

    public function RebillCCNumber($value)
    {
        $this->RebillCCNumber = $value;
    }

    public function RebillCCExpMonth($value)
    {
        $this->RebillCCExpMonth = $value;
    }

    public function RebillCCExpYear($value)
    {
        $this->RebillCCExpYear = $value;
    }

    public function RebillInitAmt($value)
    {
        $this->RebillInitAmt = $value;
    }

    public function RebillInitDate($value)
    {
        $this->RebillInitDate = $value;
    }

    public function RebillRecurAmt($value)
    {
        $this->RebillRecurAmt = $value;
    }

    public function RebillStartDate($value)
    {
        $this->RebillStartDate = $value;
    }

    public function RebillInterval($value)
    {
        $this->RebillInterval = $value;
    }

    public function RebillIntervalType($value)
    {
        $this->RebillIntervalType = $value;
    }

    public function RebillEndDate($value)
    {
        $this->RebillEndDate = $value;
    }

    public function ewayURL($value)
    {
        $this->ewayURL = $value;
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
