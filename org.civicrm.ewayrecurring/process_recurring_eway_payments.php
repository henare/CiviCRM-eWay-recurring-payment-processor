<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM                                                            |
 +--------------------------------------------------------------------+
 | Copyright Henare Degan (C) 2012                                    |
 +--------------------------------------------------------------------+
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007.                                       |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

// TODO: Remove hacky hardcoded constants
// The full path to your CiviCRM directory
define('CIVICRM_DIRECTORY', '/srv/www/localhost/wordpress/wp-content/plugins/civicrm/civicrm');
// The ID for contributions in a pending status
define('PENDING_CONTRIBUTION_STATUS_ID', 2);

chdir(CIVICRM_DIRECTORY);
require 'civicrm.config.php';
require 'api/api.php';
require 'CRM/Contribute/DAO/ContributionRecur.php';

/**
 * get_pending_recurring_contributions
 *
 * Gets recurring contributions that are in a pending state.
 * These are for newly created recurring contributions and should
 * generally be processed the same day they're created. These do not
 * include the regularly processed recurring transactions.
 *
 * @return array An array of associative arrays containing contribution arrays & contribtion_recur objects
 */
function get_pending_recurring_contributions()
{
    // Get pending contributions
    $params = array(
        'version' => 3,
        // TODO: Statuses are customisable so this configuration should be read from the DB
        'contribution_status_id' => PENDING_CONTRIBUTION_STATUS_ID
    );
    $pending_contributions = civicrm_api('contribution', 'get', $params);

    $result = array();

    foreach ($pending_contributions['values'] as $contribution) {
        // Only process those with recurring contribution records
        if ($contribution['contribution_recur_id']) {
            // Find the recurring contribution record for this contribution
            $recurring = new CRM_Contribute_DAO_ContributionRecur();
            $recurring->id = $contribution['contribution_recur_id'];

            // Only process records that have a recurring record with
            // a processor ID, i.e. an eWay token
            if ($recurring->find(true) && $recurring->processor_id) {
                // TODO: Return the same type of results
                // This is a bit nasty, contribution is an array and
                // contribution_recur is an object
                $result[] = array(
                    'contribution' => $contribution,
                    'contribution_recur' => $recurring
                );
            }
        }
    }
    return $result;
}



