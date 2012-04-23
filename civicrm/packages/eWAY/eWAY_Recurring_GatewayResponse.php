


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

/**************************************************************************************************************************
 * Licensed to CiviCRM under the Academic Free License version 3.0
 * Written & Contributed by Dolphin Software P/L - March 2008 
 *
 * 'eWAY_GatewayResponse.php' - Loosley based on the standard supplied eWay sample code 'GatewayResponse.php'
 *
 * The 'simplexml_load_string' has been removed as it was causing major issues
 * with Drupal V5.7 / CiviCRM 1.9 installtion's Home page. 
 * Filling the Home page with "Warning: session_start() [function.session-start]: Node no longer exists in ..." messages
 *
 * Found web reference indicating 'simplexml_load_string' was a probable cause.
 * As soon as 'simplexml_load_string' was removed the problem fixed itself.
 *
 * Additionally the '$txStatus' var has been set as a string rather than a boolean.
 * This is because the returned $params['trxn_result_code'] is in fact a string and not a boolean.
 **************************************************************************************************************************/
 
class RecurGatewayResponse
{
	var $txResult    = "";

	var $txErrorSeverity   = "";
	var $txErrorDetails            = "";

	function GatewayResponse()
	{
	   // Empty Constructor
    }
   
	function ProcessResponse($Xml)
	{
#####################################################################################
#                                                                                   #
#      $xtr = simplexml_load_string($Xml) or die ("Unable to load XML string!");    #
#                                                                                   #
#      $this->txError             = $xtr->ewayTrxnError;                            #
#      $this->txStatus            = $xtr->ewayTrxnStatus;                           #
#      $this->txTransactionNumber = $xtr->ewayTrxnNumber;                           #
#      $this->txOption1           = $xtr->ewayTrxnOption1;                          #
#      $this->txOption2           = $xtr->ewayTrxnOption2;                          #
#      $this->txOption3           = $xtr->ewayTrxnOption3;                          #
#      $this->txAmount            = $xtr->ewayReturnAmount;                         #
#      $this->txAuthCode          = $xtr->ewayAuthCode;                             #
#      $this->txInvoiceReference  = $xtr->ewayTrxnReference;                        #
#                                                                                   #
#####################################################################################

      $this->txResult            = self::GetNodeValue("Result", $Xml);

      $this->txErrorSeverity             = self::GetNodeValue("ErrorSeverity", $Xml);
      $this->txErrorDetails = self::GetNodeValue("ErrorDetails", $Xml);
               $this->txRebillCustomerID = self::GetNodeValue("RebillCustomerID", $Xml);
   
            $this->txRebillID = self::GetNodeValue("RebillID", $Xml);


   }
   
   
   /************************************************************************
   * Simple function to use in place of the 'simplexml_load_string' call.
   * 
   * It returns the NodeValue for a given NodeName
   * or returns and empty string.
   ************************************************************************/
   function GetNodeValue($NodeName, &$strXML)
   {
      $OpeningNodeName = "<" . $NodeName . ">";
      $ClosingNodeName = "</" . $NodeName . ">";
      
      $pos1 = stripos($strXML, $OpeningNodeName);
      $pos2 = stripos($strXML, $ClosingNodeName);
      
      if ( ($pos1 === false) || ($pos2 === false) )
         return "";
         
      $pos1 += strlen($OpeningNodeName);
      $len   = $pos2 - $pos1;

      $return = substr($strXML, $pos1, $len);                                       
      
      return ($return);
   }
   

   function ErrorSeverity()
   {
      return $this->txErrorSeverity; 
   }

   function Status() 
   {
      return $this->txResult; 
   }

   function ErrorDetails() 
   {
      return $this->txErrorDetails; 
   }
   
      function RebillID() 
   {
      return $this->txRebillID; 
   }
   
      function RebillCustomerID() 
   {
      return $this->txRebillCustomerID; 
   }

  
}

?>

