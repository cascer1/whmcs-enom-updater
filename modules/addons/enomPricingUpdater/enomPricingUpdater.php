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

function enomPricingUpdater_config() {
  $configarray = array(
    "name" => "eNom domain pricing updater",
    "description" => "Automatically update domain pricing based on eNom pricing",
    "version" => "1.0.0",
    "author" => "Duco Hosting",
    "fields" => array(
      "username" => array(
        "FriendlyName" => "Account login ID",
        "Type" => "text",
        "Size" => "50",
        "Description" => "Your eNom account username"
      ),
      "apikey" => array(
        "FriendlyName" => "API key",
        "Type" => "text",
        "Size" => "50",
        "Description" => "eNom API key. Get one <a href='https://www.enom.com/apitokens/'>here</a>"
      ),
      "profit" => array(
        "FriendlyName" => "Profit Margin",
        "Type" => "text",
        "Description" => "Profit margin in percent (%) to increase wholesale price by. e.g:" . round(entering, 2) . " 50 sets the price for a domain to 150% of your cost."
      ),
      "multiDiscount" => array(
        "FriendlyName" => "Multi-year discount",
        "Type" => "text",
        "Description" => "Percentage discount to apply for multi-year registrations and renewals. e.g:" . round(Entering, 2) . " 5 will decrease the profit margin by 5 percentage points for 2-year transers, 10 percentage points for 3-year transfers, etc."
      ),
      "cron" => array(
        "FriendlyName" => "Enable cron mode",
        "Type" => "yesno",
        "Description" => "Automatically run for all extensions during the daily cron job"
      ),
      "testmode" => array(
        "FriendlyName" => "Test Mode",
        "Type" => "yesno",
        "Description" => "Enable test mode, makes module verbose and disables database saving"
      )
    )
  );
  return $configarray;
}

function enomPricingUpdater_activate() {
  try {
    return array('status'=>'success','description'=>'The module has been activated');
  } catch (Exception $e) {
    return array('status'=>'error', 'description' => $e->getMessage());
  }
}

function enomPricingUpdater_deactivate() {
  try {
    return array('status'=>'success','description'=>'The module has been deactivated');
  } catch(Exception $e) {
    return array('status'=>'error','description'=>$e->getMessage());
  }
}

function enomPricingUpdater_output($vars) {
  try {
    echo "<form method='post'>";
    echo "<input type='hidden' name='enomAction' value='updateAll' />";
    echo "<button type='submit' class='btn btn-success'>Update all TLDs</button> (This may take a while)";
    echo "</form>";
    echo "<hr>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='enomAction' value='updateSome' />";
    echo "<input type='text' name='tlds' placeholder='.com,.net,.info'/> TLDs to update, comma separated<br>";
    echo "<button type='submit' class='btn btn-info'>Update specific TLDs</button>";
    echo "</form>";

    if(isset($_POST['enomAction'])) {
      switch($_POST['enomAction']) {
        case 'updateAll':
        enomPricingUpdater_process();
        break;
        case 'updateSome':
        enomPricingUpdater_processSome($_POST['tlds']);
        break;
        default:
        break;
      }
    }
  } catch(Exception $ex) {
    echo "<strong>Whoops!</strong><br><pre>{$ex->getMessage()}</pre>";
  }
}

function enomPricingUpdater_processSome($tlds) {
  $extensions = explode(',', $tlds);
  $parsed = array();
  foreach($extensions as $extension) {
    $extension = trim($extension);
    if(!enomPricingUpdater_startsWith($extension, '.')) {
      $extension = '.' . $extension;
    }
    array_push($parsed, $extension);
  }

  enomPricingUpdater_process($parsed);
}

function enomPricingUpdater_startsWith($haystack, $needle)
{
  $length = strlen($needle);
  return (substr($haystack, 0, $length) === $needle);
}

function enomPricingUpdater_process($extensions) {
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
  if(!isset($extensions)) {
    $domains = Capsule::table('tbldomainpricing')
    ->select('extension', 'id')
    ->get();
  } else {
    $domains = Capsule::table('tbldomainpricing')
    ->select('extension', 'id')
    ->whereIn('extension', $extensions)
    ->get();
  }

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

    if($testmode) echo "<strong>$tld</strong><br>\n";

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

    if($testmode) {
      echo "<strong>eNom Wholesale prices (in default currency)</strong><br>";
      echo "Register 1 year:" . round($registerDef, 2) . " per year<br>";
      echo "Register 2 years:" . round($registerDef2, 2) . " per year<br>";
      echo "Register 3 years:" . round($registerDef3, 2) . " per year<br>";
      echo "Renew 1 year:" . round($renewDef, 2) . " per year<br>";
      echo "Renew 2 years:" . round($renewDef2, 2) . " per year<br>";
      echo "Renew 3 years:" . round($renewDef3, 2) . " per year<br>";
      echo "Transfer:" . round($transferDef, 2) . " <br>";
    }

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

    if($testmode) {
      echo "<strong>Sale prices (In default currency)</strong><br>";
      echo "Register 1 year: " . round($register, 2) . " per year<br>";
      echo "Register 2 years: " . round($register2, 2) . " per 2 years<br>";
      echo "Register 3 years: " . round($register3, 2) . " per 3 years<br>";
      echo "Renew 1 year: " . round($renew, 2) . " per year<br>";
      echo "Renew 2 years: " . round($renew2, 2) . " per 2 years<br>";
      echo "Renew 3 years: " . round($renew3, 2) . " per 3 years<br>";
      echo "Transfer: " . round($transfer, 2) . "<br><br>";
    }

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
  echo "<br><strong>The following domain extensions have been updated</strong>:<br>" . implode(", ", $tlds);
}

?>
