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
if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

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
        "Description" => "Profit margin in percent (%) to increase wholesale price by. e.g: entering 50 sets the price for a domain to 150% of your cost."
      ),
      "multiDiscount" => array(
        "FriendlyName" => "Multi-year discount",
        "Type" => "text",
        "Description" => "Percentage discount to apply for multi-year registrations and renewals. e.g: entering 5 will decrease the profit margin by 5 percentage points for 2-year transers, 10 percentage points for 3-year transfers, etc."
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
    Capsule::schema()->create('mod_enomupdater_extensions', function ($table) {
      // https://laravel.com/docs/4.2/schema
      $table->string('extension')->references('extension')->on('tbldomainpricing')->onDelete('cascade')->unique();
      $table->boolean('sale')->default(false);
      $table->decimal('salePrice', 5, 2)->nullable();
      $table->date('saleEnd')->nullable();
      $table->boolean('processed')->default(false);
    });
    return array('status'=>'success','description'=>'The module has been activated');
  } catch (Exception $e) {
    Capsule::schema()->dropIfExists('mod_enomupdater_extensions');
    return array('status'=>'error', 'description' => $e->getMessage());
  }
}

function enomPricingUpdater_deactivate() {
  try {
    Capsule::schema()->dropIfExists('mod_enomupdater_extensions');
    return array('status'=>'success','description'=>'The module has been deactivated');
  } catch(Exception $e) {
    return array('status'=>'error','description'=>$e->getMessage());
  }
}

function enomPricingUpdater_output($vars) {
  try {
    if(isset($_POST['enomAction'])) {
      switch($_POST['enomAction']) {
        case 'updateAll':
        enomPricingUpdater_process(null);
        enomPricingUpdater_updateSales();
        break;
        case 'updateSome':
        enomPricingUpdater_processSome($_POST['tlds']);
        enomPricingUpdater_updateSales();
        break;
        case 'updateDomainList':
        enomPricingUpdater_updateDomainList();
        break;
        case 'setPromos':
        enomPricingUpdater_setPromos($_POST);
        break;
        case 'updateSales':
        enomPricingUpdater_updateSales();
        break;
        case 'checkSales':
        enomPricingUpdater_checkSales();
        break;
        case 'checkUpdates':
        enomPricingUpdater_checkUpdates();
        break;
        default:
        break;
      }
    }
  } catch(Exception $ex) {
    logModuleCall('eNom pricing updater', 'Action: ' . $_POST['enomAction'], json_encode(array('vars' => $vars, 'post' => $_POST)), $ex->getMessage(), '', '');
    echo "<strong>Whoops!</strong><br><pre>{$ex->getMessage()}</pre>";
  }

  try {
    // Get list of configured domains
    $domains = Capsule::table('mod_enomupdater_extensions')->get();
    $addon_dir = substr(__DIR__, strlen($_SERVER['DOCUMENT_ROOT']));
    $quote = '"';

    echo "<script src='{$addon_dir}/functions.js' type='text/javascript'></script>";

    echo "<div class='row'>";
    echo "<div class='col-md-3 pull-md-left'>";
    echo "<h4>Actions</h4>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='enomAction' value='updateAll' />";
    echo "<button type='submit' class='btn btn-success'>Update all TLDs</button> (This may take a while)";
    echo "</form>";
    echo "<hr>";

    echo "<form method='post'>";
    echo "<input type='hidden' name='enomAction' value='updateSales' />";
    echo "<button type='submit' class='btn btn-success'>Apply Sale prices</button>";
    echo "</form>";
    echo "<hr>";

    echo "<form method='post'>";
    echo "<input type='hidden' name='enomAction' value='updateSome' />";
    echo "<input type='text' name='tlds' placeholder='.com,.net,.info'/><br>";
    echo "<button type='submit' class='btn btn-info'>Update specific TLDs</button>";
    echo "</form>";
    echo "<hr>";

    echo "<form method='post'>";
    echo "<input type='hidden' name='enomAction' value='updateDomainList' />";
    echo "<button type='submit' class='btn btn-info'>Update internal domain list</button> <br>Run this when you add or remove TLDs that you sell.";
    echo "</form>";
    echo "<hr>";

    echo "<form method='post'>";
    echo "<input type='hidden' name='enomAction' value='checkSales' />";
    echo "<button type='submit' class='btn btn-info'>Remove expired sales</button>";
    echo "</form>";
    echo "<hr>";

    echo "<form method='post'>";
    echo "<input type='hidden' name='enomAction' value='checkUpdates' />";
    echo "<button type='submit' class='btn btn-info'>Check for updates</button>";
    echo "</form>";

    echo "</div>"; // col
    echo "<div class='col-md-9 pull-md-right'>";
    echo "<h4>Promotions</h4>";
    echo "<div class='table-container clearfix'>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='enomAction' value='setPromos' />";
    echo "<table class='table table-list'>";
    echo "<thead><tr><th>Extension</th><th>Sale</th><th>Sale Price (&dollar; USD)</th><th>Sale End</th></tr></thead>";
    echo "<tbody>";
    foreach($domains as $domain) {
      $tld = ltrim($domain->extension, '.');
      echo "<tr>";
      echo "<td>{$domain->extension}</td>";
      echo "<td>
      <input type='checkbox' id='chkSaleEnabled$tld' name='chkSaleEnabled$tld' onchange='toggleDomainSale($quote$tld$quote)' value='on'";
      if($domain->sale == 1) echo "checked='checked'";
      echo "></td>";

      echo "<td id='tdSalePrice$tld'>
      <input type='number' min='0.00' step='0.01' id='numSalePrice$tld' name='numSalePrice$tld' value='{$domain->salePrice}'";
      if($domain->sale != 1) echo "style='display: none'";
      echo "></td>";

      echo "<td id='tdSaleEnd$tld'>
      <input type='date' id='datSaleEnd$tld' name='datSaleEnd$tld' value='{$domain->saleEnd}'";
      if($domain->sale != 1) echo "style='display: none'";
      echo "></td>";

      echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";
    echo "<button class='btn btn-primary' type='submit'>Save</button>";
    echo "</form>";
    echo "</div>"; // table-container
    echo "</div>"; // col
    echo "</div>"; // row
  } catch(Exception $ex) {
    echo "<strong>Whoops!</strong><br><pre>{$ex->getMessage()}</pre>";
  }
}

/**
*  Change promo settings for domains
* Domains that are currently on sale will not have their prices overwritten with eNom prices.
*/
function enomPricingUpdater_setPromos($post) {
  $domains = Capsule::table('mod_enomupdater_extensions')->lists('extension');

  foreach($domains as $domain) {
    $updated = Capsule::table('mod_enomupdater_extensions')->where('extension', $domain);
    $updated_whmcs = Capsule::table('tbldomainpricing')->where('extension', $domain);

    $tld = ltrim($domain, '.');
    $sale = isset($post['chkSaleEnabled' . $tld]);
    if($sale) {
      $salePrice = $post['numSalePrice' . $tld];
      $saleEnd = $post['datSaleEnd' . $tld];
      $updated->update(array('sale' => true, 'salePrice' => $salePrice, 'saleEnd' => $saleEnd));
      $updated_whmcs->update(array('group' => 'sale'));
    } else {
      $updated->update(array('sale' => false, 'salePrice' => null, 'saleEnd' => null));
      $updated_whmcs->update(array('group' => 'none'));
    }
  }
}

/**
* Process only the specified TLDs
* @param $tlds array() of TLDs (may contain leading dots)
*/
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

/**
* Check if string starts with certain substring
* @param $haystack string to search in
* @param $needle string to search for
*/
function enomPricingUpdater_startsWith($haystack, $needle) {
  $length = strlen($needle);
  return (substr($haystack, 0, $length) === $needle);
}

/**
* Synchronize internal list of TLDs with TLDs configured in WHMCS
*/
function enomPricingUpdater_updateDomainList() {
  // Add new domains from WHMCS to module table
  $existing = Capsule::table('mod_enomupdater_extensions')->lists('extension');
  $extensions = Capsule::table('tbldomainpricing')->whereNotIn('extension', $existing)->lists('extension');

  foreach($extensions as $ext) {
    Capsule::table('mod_enomupdater_extensions')->insert(
      array('extension' => $ext, 'sale' => false)
    );
  }

  // Remove extensions from table if they are not present in WHMCS
  $all = Capsule::table('tbldomainpricing')->lists('extension');
  Capsule::table('mod_enomupdater_extensions')->whereNotIn('extension', $all)->delete();
}

/**
* Update prices for domains on sale
*/
function enomPricingUpdater_updateSales() {
  $testmode = (Capsule::table('tbladdonmodules')
  ->where([['module', 'enomPricingUpdater'],['setting', 'testmode']])
  ->first()->value == 'on');

  $profit = Capsule::table('tbladdonmodules')
  ->where([['module', 'enomPricingUpdater'],['setting', 'profit']])
  ->first()->value;

  $domains = Capsule::table('tbldomainpricing')->where('group', 'sale')->get();

  $dbrates = Capsule::table('tblcurrencies')->get();
  $rates = [];

  foreach($dbrates as $rate) {
    $rates[$rate->code] = $rate;
  }

  foreach($domains as $domain) {
    // Get sale price in default currency
    $saleFee = Capsule::table('mod_enomupdater_extensions')->where('extension', $domain->extension)
    ->first()->salePrice/$rates['USD']->rate;

    $salePrice = $saleFee * (1 + $profit/100);

    foreach($rates as $rate) {
      $price = (floor($salePrice * $rate->rate * 10))/10;

      // Update database, only execute if not running in testmode
      if(!$testmode) {
        Capsule::table('tblpricing')
        ->where('relid', $domain->id)
        ->where('type', 'domainregister')
        ->where('currency', $rate->id)
        ->update(['msetupfee' => $price]);
      }

    }

  }

}

/**
* Update prices for all (or certain) domains
* @param $extensions array() of TLDs to update. If NULL, update all TLDs
*/
function enomPricingUpdater_process($extensions) {
  logModuleCall('eNom pricing updater', 'process', json_encode($extensions), '', '', '');
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
  $domains = Capsule::table('tbldomainpricing');
  if(isset($extensions)) $domains->whereIn('extension', $extensions);
  $domains = $domains->get();

  // Save exchange rates for easy access
  $dbrates = Capsule::table('tblcurrencies')->get();
  $rates = [];

  foreach($dbrates as $rate) {
    $rates[$rate->code] = $rate;
  }

  enomPricingUpdater_processRegularDomains($domains, $rates, $testmode, $profit, $discount, $username, $apiKey);

}

/**
* Check for current wholesale price from eNom and update WHMCS prices accordingly
* @param $domains array() of TLDs to update
* @param $rates array() of exchange rates
* @param $testmode boolean testmode is enabled
* @param $profit integer profit margin. 50 = 50%
* @param $discount integer discount percentage per year for multi-year registrations and renewals
* @param $username eNom API username
* @param $apiKey eNom API access key
*/
function enomPricingUpdater_processRegularDomains($domains, $rates, $testmode, $profit, $discount, $username, $apiKey) {
  $tlds = [];

  // PE_GetProductPrice <-- Just get the price
  // PE_GetResellerPrice <-- Get price and status

  $enabledModes = array(
    'domainregister' => array(1,2,3),
    'domainrenew' => array(1,2,3),
    'domaintransfer' => array(1)
  );

  // eNom api product types
  $enomModes = array(
    'domainregister' => 10,
    'domainrenew' => 16,
    'domaintransfer' => 19
  );

  // How are different durations stored in the database?
  $terms = array(
    1 => 'msetupfee',
    2 => 'qsetupfee',
    3 => 'ssetupfee',
    4 => 'asetupfee',
    5 => 'bsetupfee',
    6 => 'monthly',
    7 => 'quarterly',
    8 => 'semiannually',
    9 => 'annually',
    10 => 'biennially'
  );

  // Loop through all domains in WHMCS
  foreach($domains as $domain) {
    // $enabledModes = array(
    //   'domainregister' => array();
    //   'domainrenew' => array();
    //   'domaintransfer' => array(1);
    // )

    $currentRegistrationPrices = Capsule::table('tblpricing')
    ->where('relid', $domain->id)
    ->where('type', 'domainregister')
    ->where('currency', 1)
    ->select('msetupfee', 'qsetupfee', 'ssetupfee', 'asetupfee', 'bsetupfee', 'monthly', 'quarterly', 'semiannually', 'annually', 'biennially')
    ->first();

    $currentRenewalPrices = Capsule::table('tblpricing')
    ->where('relid', $domain->id)
    ->where('type', 'domainrenew')
    ->where('currency', 1)
    ->select('msetupfee', 'qsetupfee', 'ssetupfee', 'asetupfee', 'bsetupfee', 'monthly', 'quarterly', 'semiannually', 'annually', 'biennially')
    ->first();

    //TODO: Set enabled modes based on $currentRegistrationPrices and $currentRenewalPrices


    $enomPrices = array(
      'domainregister' => array(),
      'domainrenew' => array(),
      'domaintransfer' => array()
    );
    array_push($tlds, $domain->extension);
    $tld = ltrim($domain->extension, '.');

    foreach($enabledModes as $mode => $years) {
      foreach($years as $year) {
        $enomPrices[$mode][$year] = getEnomPrice(array('tld' => $tld, 'type' => $enomModes[$mode], 'years' => $year), $rates, $username, $apiKey);
      }
    }

    $newPrices = enomPricingUpdater_calculateSalePrices($enomPrices, $profit);

    // Save new prices for all exchange rates
    foreach($rates as $rate) {
      $salePrices = array();

      foreach($newPrices as $type => $years) {
        $salePrices[$type] = array();
        foreach($years as $year => $price) {
          $term = $terms[$year];
          $salePrices[$type][$term] = (floor($price * $rate->rate * 10))/10;
        }
      }

      // Update database, only execute if not running in testmode
      if(!$testmode) {
        Capsule::table('tblpricing')
        ->where('relid', $domain->id)
        ->where('type', 'domainregister')
        ->where('currency', $rate->id)
        ->update($salePrices['domainregister']);

        Capsule::table('tblpricing')
        ->where('relid', $domain->id)
        ->where('type', 'domainrenew')
        ->where('currency', $rate->id)
        ->update($salePrices['domainrenew']);

        Capsule::table('tblpricing')
        ->where('relid', $domain->id)
        ->where('type', 'domaintransfer')
        ->where('currency', $rate->id)
        ->update($salePrices['domaintransfer']);
      }

    }
  }
  echo "<br><strong>The following domain extensions have been updated</strong>:<br>" . implode(", ", $tlds);
  echo "<hr>";
}

/**
* Calculate price to charge in WHMCS using wholesale price and profit margin
* @param $enomPrices array() multidimensional array containing wholesale prices.
*        ['domainregister'] => array ( 1 => 7.25   // Wholesale price (per year) for 1 year registration
*                                      2 => 7.25), // Wholesale price (per year) for 2 year registration
*        ['domainrenew'] =>    array ( 1 => 7.25   // Wholesale price (per year) for 1 year renewal
*                                      2 => 7.25), // Wholesale price (per year) for 2 year renewal
*        ['domaintransfer'] => array ( 1 => 7.25)  // Wholesale price for domain transfers
* @param $profit integer profit margin. 50 = 50%
*/
function enomPricingUpdater_calculateSalePrices($enomPrices, $profit) {
  $returned = array();

  foreach($enomPrices as $type => $durations) {
    foreach($durations as $duration => $price) {
      $returned[$type][$duration] = $price * (1 + $profit/100 - $discount*($duration - 1)/100) * $duration;
    }
  }

  return $returned;
}

/**
* Get eNom price for certain product configuration
* @param $settings array() containing product settings. ['tld', 'years', 'type']
* @param $rates array() of exchange rates
* @param $username string eNom API username
* @param $apiKey string eNom API access key
*/
function getEnomPrice($settings, $rates, $username, $apiKey) {
  $endpoint = "https://reseller.enom.com/interface.asp";
  $urlBase = "$endpoint?uid=$username&pw=$apiKey&command=PE_GetProductPrice&ResponseType=xml";

  // Registration endpoints
  $requestUrl  = "$urlBase&tld={$settings['tld']}&ProductType={$settings['type']}&Years={$settings['years']}";

  $requestResult  = simplexml_load_file($requestUrl);
  return $requestResult->productprice->price/$rates['USD']->rate;
}

/**
* Checks sales for expiration dates and disables them once they expire
*/
function enomPricingUpdater_checkSales($vars) {
  try {
    $expired = Capsule::table('mod_enomupdater_extensions')
    ->where('saleEnd', '<', Capsule::RAW('CURRENT_TIMESTAMP'))->lists('extension');

    Capsule::table('tbldomainpricing')->whereIn('extension', $expired)->update(['group' => 'none']);

    Capsule::table('mod_enomupdater_extensions')->whereIn('extension', $expired)
    ->update(['salePrice' => null, 'saleEnd' => null, 'sale' => false]);
    logModuleCall('eNom pricing updater', 'CheckSales', '', '', '', '');
  } catch(Exception $ex) {
    logModuleCall('eNom pricing updater', 'CheckSales Error', '', $ex->getMessage(), '', '');
  }
}

function enomPricingUpdater_hookProcessAll($vars) {
  enomPricingUpdater_process();
  logModuleCall('eNom pricing updater', 'Cron: hookProcessAll', '', '', '', '');
}

function enomPricingUpdater_checkUpdates() {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/repos/ducohosting/whmcs-enom-updater/releases/latest');
  curl_setopt($ch, CURLOPT_USERAGENT,'WHMCS eNom pricing update module by Duco Hosting');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
  $response = json_decode(curl_exec($ch));
  curl_close($ch);

  $latestVersion = ltrim($response->tag_name, 'v');
  $currentVersion = Capsule::table('tbladdonmodules')->where([['module', 'enomPricingUpdater'], ['setting', 'version']])->first()->value;

  // first > last --> 1
  // first = last --> 0
  // first < last --> -1

  $result = version_compare($currentVersion, $latestVersion);

  if($result == -1) {
    echo "<strong>Update available!</strong><br>";
    echo "Installed version: <strong>$currentVersion</strong><br>";
    echo "Latest version: <strong>$latestVersion</strong><br><br>";
    echo "Download the latest version <a href='{$response->zipball_url}'>HERE</a>";
  } else {
    echo "<strong>You are running the latest version</strong><br>";
  }
}

?>
