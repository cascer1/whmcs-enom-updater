<?php
/**
 *
 *                  WHMCS eNom price sync addon module
 *                   Copyright (C) 2016  Duco Hosting
 *
 *        This program is free software: you can redistribute it and/or modify
 *        it under the terms of the GNU General Public License as published by
 *        the Free Software Foundation, either version 3 of the License, or
 *        (at your option) any later version.
 *
 *        This program is distributed in the hope that it will be useful,
 *        but WITHOUT ANY WARRANTY; without even the implied warranty of
 *        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *        GNU General Public License for more details.
 *
 *        You should have received a copy of the GNU General Public License
 *        along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


/**
 * STUB FUNCTION for PHPStorm code completion
 * Log module action to WHMCS module log
 * @param $module string Module name of calling module
 * @param $action string Description of performed action
 * @param $request string Request data or module input
 * @param $response string response data or output
 * @param $processedData string processed respone, for readability
 * @param $replaceVars array of strings that should be censored in output
 * @see http://docs.whmcs.com/Provisioning_Module_Developer_Docs#Module_Logging
 */
function LogModuleCall($module, $action, $request, $response, $processedData, $replaceVars)
{
    // STUB
}

/** @noinspection PhpUnusedParameterInspection */

/**
 * STUB FUNCTION for PHPStorm code completion
 * Get WHMCS gateway parameters
 * @param $gateway string Name of gateway
 * @return array Gateway parameters
 */
function getGatewayVariables($gateway) {
    // STUB
    return [];
}

/** @noinspection PhpUnusedParameterInspection */

/**
 * STUB FUNCTION for PHPStorm code completion
 * Perform local WHMCS API call
 * @param $action string API action to perform
 * @param $params array variables
 * @param $user string Admin user to execute as
 * @return object|null API result
 * @see http://docs.whmcs.com/API:Internal_API
 */
function localAPI($action, $params, $user) {
    // STUB
    return new stdClass();
}



