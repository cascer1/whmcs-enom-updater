<?php
// * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
// *                                                                               *
// *                WHMCS eNom price sync addon module                             *
// *                 Copyright (C) 2016  Duco Hosting                              *
// *                                                                               *
// *      This program is free software: you can redistribute it and/or modify     *
// *      it under the terms of the GNU General Public License as published by     *
// *      the Free Software Foundation, either version 3 of the License, or        *
// *      (at your option) any later version.                                      *
// *                                                                               *
// *      This program is distributed in the hope that it will be useful,          *
// *      but WITHOUT ANY WARRANTY; without even the implied warranty of           *
// *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            *
// *      GNU General Public License for more details.                             *
// *                                                                               *
// *      You should have received a copy of the GNU General Public License        *
// *      along with this program.  If not, see <http://www.gnu.org/licenses/>.    *
// *                                                                               *
// * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
use Illuminate\Database\Capsule\Manager as Capsule;

/**
* Add hook function call.
*
* @param string $hookPoint The hook point to call
* @param integer $priority The priority for the given hook function
* @param string|function Function name to call or anonymous function.
*
* @return Depends on hook function point.
*/
add_hook('DailyCronJob', 1, function ($vars)
{
  $cron = (Capsule::table('tbladdonmodules')
  ->where([['module', 'enomPricingUpdater'],['setting', 'cron']])
  ->first()->value == 'on');
  if(!$cron) return;

  $username = Capsule::table('tbladdonmodules')
  ->where([['module', 'enomPricingUpdater'],['setting', 'username']])
  ->first()->value;

  $apiKey = Capsule::table('tbladdonmodules')
  ->where([['module', 'enomPricingUpdater'],['setting', 'apikey']])
  ->first()->value;

  $testmode = (Capsule::table('tbladdonmodules')
  ->where([['module', 'enomPricingUpdater'],['setting', 'testmode']])
  ->first()->value == 'on');

  $profit = Capsule::table('tbladdonmodules')
  ->where([['module', 'enomPricingUpdater'],['setting', 'profit']])
  ->first()->value;


  // Get available domains from WHMCS
  $domains = Capsule::table('tbldomainpricing')->select('extension', 'id')->get();

  // Save exchange rates for easy access
  $dbrates = Capsule::table('tblcurrencies')->get();
  $rates = [];
  $tlds = [];

  foreach($dbrates as $rate) {
    $rates[$rate->code] = $rate;
  }

  // ProductType
  //    10 = register
  //    16 = renew
  //    19 = transfer

  // PE_GetProductPrice <-- Just get the price
  // PE_GetResellerPrice <-- Get price and status

  // Loop through all domains in WHMCS
  foreach($domains as $domain) {
    $username = Capsule::table('tbladdonmodules')
    ->where([['module', 'enomPricingUpdater'],['setting', 'username']])
    ->first()->value;

    $apiKey = Capsule::table('tbladdonmodules')
    ->where([['module', 'enomPricingUpdater'],['setting', 'apikey']])
    ->first()->value;

    $testmode = (Capsule::table('tbladdonmodules')
    ->where([['module', 'enomPricingUpdater'],['setting', 'testmode']])
    ->first()->value == 'on');

    $profit = Capsule::table('tbladdonmodules')
    ->where([['module', 'enomPricingUpdater'],['setting', 'profit']])
    ->first()->value;

    $discount = Capsule::table('tbladdonmodules')
    ->where([['module', 'enomPricingUpdater'],['setting', 'multiDiscount']])
    ->first()->value;

    if(!isset($discount)) $discount = 0;

    // Get available domains from WHMCS
    $domains = Capsule::table('tbldomainpricing')
    ->select('extension', 'id')
    ->get();

    // Save exchange rates for easy access
    $dbrates = Capsule::table('tblcurrencies')->get();
    $rates = [];
    $tlds = [];

    foreach($dbrates as $rate) {
      $rates[$rate->code] = $rate;
    }

    // ProductType
    //    10 = register
    //    16 = renew
    //    19 = transfer

    // PE_GetProductPrice <-- Just get the price
    // PE_GetResellerPrice <-- Get price and status

    // Loop through all domains in WHMCS
    foreach($domains as $domain) {
      array_push($tlds, $domain->extension);
      $tld = ltrim($domain->extension, '.');

      $endpoint = "https://reseller.enom.com/interface.asp";

      $urlBase = "$endpoint?uid=$username&pw=$apiKey&command=PE_GetProductPrice&ResponseType=xml&tld=$tld";

      // Registration endpoints
      $urlRegister  = "$urlBase&ProductType=10&Years=1";
      $urlRegister2 = "$urlBase&ProductType=10&Years=2";
      $urlRegister3 = "$urlBase&ProductType=10&Years=3";

      // Renewal endpoints
      $urlRenew     = "$urlBase&ProductType=16&Years=1";
      $urlRenew2    = "$urlBase&ProductType=16&Years=2";
      $urlRenew3    = "$urlBase&ProductType=16&Years=3";

      // Transfer endpoint
      $urlTransfer  = "$urlBase&ProductType=19&Years=1";

      // Load API responses
      $xmlRegister  = simplexml_load_file($urlRegister);
      $xmlRegister2 = simplexml_load_file($urlRegister2);
      $xmlRegister3 = simplexml_load_file($urlRegister3);
      $xmlRenew     = simplexml_load_file($urlRenew);
      $xmlRenew2    = simplexml_load_file($urlRenew2);
      $xmlRenew3    = simplexml_load_file($urlRenew3);
      $xmlTransfer  = simplexml_load_file($urlTransfer);

      // eNom wholesale prices      Registration
      $registerUsd  = $xmlRegister->productprice->price;
      $registerUsd2 = $xmlRegister2->productprice->price;
      $registerUsd3 = $xmlRegister3->productprice->price;

      // eNom wholesale prices      Renewal
      $renewUsd     = $xmlRenew->productprice->price;
      $renewUsd2    = $xmlRenew2->productprice->price;
      $renewUsd3    = $xmlRenew3->productprice->price;

      // eNom wholesale prices      Transfer
      $transferUsd  = $xmlTransfer->productprice->price;

      // Convert eNom prices to default currency
      $registerDef  = $registerUsd/$rates['USD']->rate;
      $registerDef2 = $registerUsd2/$rates['USD']->rate;
      $registerDef3 = $registerUsd3/$rates['USD']->rate;
      $renewDef     = $renewUsd/$rates['USD']->rate;
      $renewDef2    = $renewUsd2/$rates['USD']->rate;
      $renewDef3    = $renewUsd3/$rates['USD']->rate;
      $transferOrg  = $transferUsd/$rates['USD']->rate;

      // Calculate sale prices      Registration
      $register  = $registerDef  * (1 + $profit/100);
      $register2 = $registerDef2 * (1 + $profit/100 - $discount/100) * 2;
      $register3 = $registerDef3 * (1 + $profit/100 - $discount*2/100) * 3;

      // Calculate sale prices      Renewal
      $renew  = $renewDef  * (1+ $profit/100);
      $renew2 = $renewDef2 * (1 + $profit/100 - $discount/100) * 2;
      $renew3 = $renewDef3 * (1 + $profit/100 - $discount*2/100) * 3;

      // Calculate sale prices      Transfer
      $transfer = $transferOrg * (1+ $profit/100);

      // Save new prices for all exchange rates
      foreach($rates as $rate) {
        $reg  = (floor($register  * $rate->rate * 4))/4;
        $reg2 = (floor($register2 * $rate->rate * 4))/4;
        $reg3 = (floor($register3 * $rate->rate * 4))/4;

        $ren  = (floor($renew  * $rate->rate * 4))/4;
        $ren2 = (floor($renew2 * $rate->rate * 4))/4;
        $ren3 = (floor($renew3 * $rate->rate * 4))/4;

        $tra  = (floor($transfer * $rate->rate * 4))/4;

        // Update database, only execute if not running in testmode
        if(!$testmode) {
          Capsule::table('tblpricing')
          ->where('relid', $domain->id)
          ->where('type', 'domainregister')
          ->where('currency', $rate->id)
          ->update(['msetupfee' => $reg, 'qsetupfee' => $reg2, 'ssetupfee' => $reg3]);

          Capsule::table('tblpricing')
          ->where('relid', $domain->id)
          ->where('type', 'domainrenew')
          ->where('currency', $rate->id)
          ->update(['msetupfee' => $ren, 'qsetupfee' => $ren2, 'ssetupfee' => $ren3]);

          Capsule::table('tblpricing')
          ->where('relid', $domain->id)
          ->where('type', 'domaintransfer')
          ->where('currency', $rate->id)
          ->update(['msetupfee' => $tra]);
        }
      }
    }
  }
});



?>
