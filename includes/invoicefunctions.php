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
 * Validate that invoice exists, or end execution
 * @param $invoiceID string Invoice ID
 * @param $gateway string Gateway name
 * @returns string|null Invoice ID is valid, halts execution if not
 * @see http://docs.whmcs.com/Gateway_Module_Developer_Docs#Callbacks
 */
function checkCbInvoiceID($invoiceID, $gateway) {
    // STUB
    return $invoiceID . $gateway;
}

/**
 * STUB FUNCTION for PHPStorm code completion
 * Check if transaction already exists in database
 * @param $transactionID string Transaction ID
 * @see http://docs.whmcs.com/Gateway_Module_Developer_Docs#Callbacks
 */
function checkCbTransID($transactionID) {
    // STUB
}

/**
 * STUB FUNCTION for PHPStorm code completion
 * @param $gateway string Gateway name
 * @param $post array POST data
 * @param $status string transaction status
 */
function logTransaction($gateway, $post, $status) {
    // STUB
}

/**
 * STUB FUNCTION for PHPStorm code completion
 * @param $invoiceId string Invoice ID
 * @param $transactionId string Gateway transaction ID
 * @param $amount float Paid amount
 * @param $fee float Transaction fee
 * @param $gatewaymodule string name of Gateway module
 */
function addInvoicePayment($invoiceId, $transactionId, $amount, $fee, $gatewaymodule) {
    // STUB
}

