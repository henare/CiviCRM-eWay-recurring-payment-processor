<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.3                                                |
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

class TokenGatewayResponse
{
    // Create Customer values
    public $UpdateCustomerResult;

    // Process Payment values
    public $ewayTrxnError;
    public $ewayTrxnStatus;
    public $ewayTrxnNumber;
    public $ewayReturnAmount;
    public $ewayAuthCode;

    function processCreateCustomerResponse($xml)
    {
        $this->UpdateCustomerResult = $this->GetNodeValue('UpdateCustomerResult', $xml);
    }

    function processProcessPaymentResponse($xml)
    {
        $this->ewayTrxnError = $this->GetNodeValue('ewayTrxnError', $xml);
        $this->ewayTrxnStatus = $this->GetNodeValue('ewayTrxnStatus', $xml);
        $this->ewayTrxnNumber = $this->GetNodeValue('ewayTrxnNumber', $xml);
        $this->ewayReturnAmount = $this->GetNodeValue('ewayReturnAmount', $xml);
        $this->ewayAuthCode = $this->GetNodeValue('ewayAuthCode', $xml);
    }

    /************************************************************************
    * Simple function to use in place of the 'simplexml_load_string' call.
    *
    * It returns the NodeValue for a given NodeName
    * or returns and empty string.
    *
    * The 'simplexml_load_string' was removed as it was causing major issues
    * with Drupal V5.7 / CiviCRM 1.9 installtion's Home page.
    * Filling the Home page with "Warning: session_start() [function.session-start]: Node no longer exists in ..." messages
    ************************************************************************/
    function GetNodeValue($NodeName, &$strXML)
    {
        $OpeningNodeName = "<" . $NodeName . ">";
        $ClosingNodeName = "</" . $NodeName . ">";

        $pos1 = stripos($strXML, $OpeningNodeName);
        $pos2 = stripos($strXML, $ClosingNodeName);

        if ( ($pos1 === false) || ($pos2 === false) ) {
            return "";
        }

        $pos1 += strlen($OpeningNodeName);
        $len   = $pos2 - $pos1;

        $return = substr($strXML, $pos1, $len);

        return ($return);
    }
}
