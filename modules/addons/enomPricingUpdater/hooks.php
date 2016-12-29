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

use Illuminate\Database\Capsule\Manager as Capsule;

// Remove expired promos
add_hook('DailyCronJob', 6, function () {
    $cronEnabled = (Capsule::table('tbladdonmodules')->where('module', 'enomPricingUpdater')->where('setting', 'cron')->first()->value == 'on');
    if(!$cronEnabled) return;

    if(!function_exists("enomPricingUpdater_checkPromos")) {
        require_once(__DIR__ . '/enomPricingUpdater.php');
    }

    enomPricingUpdater_checkPromos();
});

// Calculate sale prices
add_hook('DailyCronJob', 7, function () {
    $cronEnabled = (Capsule::table('tbladdonmodules')->where('module', 'enomPricingUpdater')->where('setting', 'cron')->first()->value == 'on');
    if(!$cronEnabled) return;

    if(!function_exists("enomPricingUpdater_process")) {
        require_once(__DIR__ . '/enomPricingUpdater.php');
    }

    enomPricingUpdater_process(null);
});

// Apply promo prices
add_hook('DailyCronJob', 8, function () {
    $cronEnabled = (Capsule::table('tbladdonmodules')->where('module', 'enomPricingUpdater')->where('setting', 'cron')->first()->value == 'on');
    if(!$cronEnabled) return;

    if(!function_exists("enomPricingUpdater_applyPromos")) {
        require_once(__DIR__ . '/enomPricingUpdater.php');
    }

    enomPricingUpdater_applyPromos();
});

