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

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

// Debug hook
add_hook('DailyCronJob', 5, function () {
    logModuleCall('eNom pricing updater', 'Cron ran', 'Hello, world!', '', '', '');
});

add_hook('DailyCronJob', 6, function () {
    if(function_exists("enomPricingUpdater_checkSales")) {
        require_once(__DIR__ . '/enomPricingUpdater.php');
    }

    enomPricingUpdater_checkSales();
});

add_hook('DailyCronJob', 7, function () {
    if(function_exists("enomPricingUpdater_hookProcessAll")) {
        require_once(__DIR__ . '/enomPricingUpdater.php');
    }

    enomPricingUpdater_hookProcessAll();
});

add_hook('DailyCronJob', 8, function () {
    if(function_exists("enomPricingUpdater_updateSales")) {
        require_once(__DIR__ . '/enomPricingUpdater.php');
    }

    enomPricingUpdater_updateSales();
});

