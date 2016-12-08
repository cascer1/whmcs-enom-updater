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

$GLOBALS['enomTerms'] = [
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
];

$GLOBALS['enomModes'] = [
    'domainregister' => 10,
    'domainrenew' => 16,
    'domaintransfer' => 19
];

$GLOBALS['numberNames'] = [
    1 => 'one',
    2 => 'two',
    3 => 'three',
    4 => 'four',
    5 => 'five',
    6 => 'six',
    7 => 'seven',
    8 => 'eight',
    9 => 'nine',
    10 => 'ten'
];

/**
 * @return array
 */
function enomPricingUpdater_config()
{
    $configarray = [
        "name" => "eNom domain pricing updater",
        "description" => "Automatically update domain pricing based on eNom pricing",
        "version" => "1.1.0-beta4",
        "author" => "Duco Hosting",
        "fields" => [
            "username" => [
                "FriendlyName" => "Account login ID",
                "Type" => "text",
                "Size" => "50",
                "Description" => "Your eNom account username"
            ],
            "apikey" => [
                "FriendlyName" => "API key",
                "Type" => "password",
                "Size" => "50",
                "Description" => "eNom API key. Get one <a href='https://www.enom.com/apitokens/'>here</a>"
            ],
            "profit" => [
                "FriendlyName" => "Profit Margin",
                "Type" => "text",
                "Description" => "Profit margin in percent (%) to increase wholesale price by. e.g: entering 50 sets the price for a domain to 150% of your cost."
            ],
            "multiDiscount" => [
                "FriendlyName" => "Multi-year discount",
                "Type" => "text",
                "Description" => "Percentage discount to apply for multi-year registrations and renewals. e.g: entering 5 will decrease the profit margin by 5 percentage points for 2-year transers, 10 percentage points for 3-year transfers, etc."
            ],
            "rounding" => [
                "FriendlyName" => "Price rouding",
                "Type" => "text",
                "Description" => "Amount of cents to round sale prices to. e.g: entering 25 will results in prices like 7.00, 7.25, 7.50 or 7.75."
            ],
            "minPrice" => [
                "FriendlyName" => "Minimal price",
                "Type" => "text",
                "Description" => "The addon will never price domains lower than this amount, including domains that are on sale"
            ],
            "cron" => [
                "FriendlyName" => "Enable cron mode",
                "Type" => "yesno",
                "Description" => "Automatically run for all extensions during the daily cron job"
            ],
            "priceUpdateRate" => [
                "FriendlyName" => "Update eNom prices",
                "Type" => "dropdown",
                "Options" => "Daily,Weekly,Monthly,Never",
                "Description" => "When to automatically update eNom wholesale prices during cron job.",
                "Default" => "Monthly"
            ],
            "priceUpdateDay" => [
                "FriendlyName" => "eNom price update day",
                "Type" => "dropdown",
                "Options" => "Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday",
                "Description" => "When Update eNom prices is set to weekly, the update will be performed on this weekday",
                "Default" => "Sunday"
            ],
            "testmode" => [
                "FriendlyName" => "Test Mode",
                "Type" => "yesno",
                "Description" => "Enable test mode, disables database saving"
            ],
            "debug" => [
                "FriendlyName" => "Debug Mode",
                "Type" => "yesno",
                "Description" => "Enable debug mode, makes the module more verbose. Make sure to enable Module logging to view output."
            ]
        ]
    ];
    return $configarray;
}

/**
 * @return array
 */
function enomPricingUpdater_activate()
{
    try {
        // Store regular prices without sales
        Capsule::schema()->create('mod_enomupdater_prices', function (Illuminate\Database\Schema\Blueprint $table) {
            // https://laravel.com/docs/4.2/schema
            $table->integer('relid')->references('id')->on('tbldomainpricing')->onDelete('cascade')->unique();
            $table->integer('currency')->references('id')->on('tblcurrencies')->onDelete('cascade');
            $table->enum('type', ['domainregister', 'domainrenew', 'domaintransfer']);
            $table->decimal('msetupfee', 10, 2)->nullable();
            $table->decimal('qsetupfee', 10, 2)->nullable();
            $table->decimal('ssetupfee', 10, 2)->nullable();
            $table->decimal('asetupfee', 10, 2)->nullable();
            $table->decimal('bsetupfee', 10, 2)->nullable();
            $table->decimal('monthly', 10, 2)->nullable();
            $table->decimal('quarterly', 10, 2)->nullable();
            $table->decimal('semiannually', 10, 2)->nullable();
            $table->decimal('annually', 10, 2)->nullable();
            $table->decimal('biennially', 10, 2)->nullable();
        });

        // Store eNom wholesale prices
        Capsule::schema()->create('mod_enomupdater_enomprices', function (Illuminate\Database\Schema\Blueprint $table) {
            // https://laravel.com/docs/4.2/schema
            $table->string('extension')->references('extension')->on('tbldomainpricing')->onDelete('cascade');
            $table->enum('type', ['domainregister', 'domainrenew', 'domaintransfer']);
            $table->decimal('one', 10, 2)->nullable();
            $table->decimal('two', 10, 2)->nullable();
            $table->decimal('three', 10, 2)->nullable();
            $table->decimal('four', 10, 2)->nullable();
            $table->decimal('five', 10, 2)->nullable();
            $table->decimal('six', 10, 2)->nullable();
            $table->decimal('seven', 10, 2)->nullable();
            $table->decimal('eight', 10, 2)->nullable();
            $table->decimal('nine', 10, 2)->nullable();
            $table->decimal('ten', 10, 2)->nullable();
            $table->primary(['extension', 'type']);
        });

        Capsule::schema()->create('mod_enomupdater_sales', function (Illuminate\Database\Schema\Blueprint $table) {
            $table->string('extension')->references('extension')->on('tbldomainpricing')->onDelete('cascade');
            $table->enum('type', ['domainregister', 'domainrenew', 'domaintransfer']);
            $table->smallInteger('years');
            $table->decimal('price', 10, 2);
            $table->date('expires');
            $table->primary(['extension', 'type', 'years']);
        });

        Capsule::schema()->create('mod_enomupdater_extensions', function (Illuminate\Database\Schema\Blueprint $table) {
            $table->string('extension')->references('extension')->on('tbldomainpricing')->onDelete('cascade');
            $table->string('group')->default('none');
            $table->primary(['extension']);
        });

        return ['status' => 'success', 'description' => 'The module has been activated'];
    } catch (Exception $e) {
        Capsule::schema()->dropIfExists('mod_enomupdater_prices');
        Capsule::schema()->dropIfExists('mod_enomupdater_enomprices');
        Capsule::schema()->dropIfExists('mod_enomupdater_sales');
        Capsule::schema()->dropIfExists('mod_enomupdater_extensions');
        return ['status' => 'error', 'description' => $e->getMessage()];
    }
}

/**
 * @return array
 */
function enomPricingUpdater_deactivate()
{
    try {
        Capsule::schema()->dropIfExists('mod_enomupdater_prices');
        Capsule::schema()->dropIfExists('mod_enomupdater_enomprices');
        Capsule::schema()->dropIfExists('mod_enomupdater_sales');
        Capsule::schema()->dropIfExists('mod_enomupdater_extensions');
        return ['status' => 'success', 'description' => 'The module has been deactivated'];
    } catch (Exception $e) {
        return ['status' => 'error', 'description' => $e->getMessage()];
    }
}

/**
 * Upgrade module to most recent version
 * Executed whenever version stored in database is different than that defined in _config()
 * @param $vars array containing ['version']. This is the previously installed version
 */
function enomPricingUpdater_upgrade($vars)
{
    $version = $vars['version'];

    // Update to version 1.1.0-beta2, adding eNom wholesale prices to database
    if (version_compare($version, '1.1.0-beta2') == -1) {
        Capsule::schema()->create('mod_enomupdater_enomprices', function (Illuminate\Database\Schema\Blueprint $table) {
            // https://laravel.com/docs/4.2/schema
            $table->renameColumn('salePrice', 'regPrice');
            $table->dropColumn('processed');
            $table->decimal('traPrice', 5, 2)->nullable();
        });
    }

    // Add support for transfer sales
    if (version_compare($version, '1.1.0-beta4') == -1) {
        Capsule::schema()->rename('mod_enomupdater_prices', 'mod_enomupdater_enomprices');

        Capsule::schema()->table('mod_enomupdater_extensions', function (Illuminate\Database\Schema\Blueprint $table) {
            $table->dropColumn('sale');
            $table->dropColumn('salePrice');
            $table->dropColumn('saleEnd');
            $table->dropColumn('processed');
            $table->string('group')->default('none');
            $table->primary(['extension']);
        });

        Capsule::schema()->create('mod_enomupdater_prices', function (Illuminate\Database\Schema\Blueprint $table) {
            // https://laravel.com/docs/4.2/schema
            $table->integer('relid')->references('id')->on('tbldomainpricing')->onDelete('cascade')->unique();
            $table->integer('currency')->references('id')->on('tblcurrencies')->onDelete('cascade');
            $table->enum('type', ['domainregister', 'domainrenew', 'domaintransfer']);
            $table->decimal('msetupfee', 10, 2)->nullable();
            $table->decimal('qsetupfee', 10, 2)->nullable();
            $table->decimal('ssetupfee', 10, 2)->nullable();
            $table->decimal('asetupfee', 10, 2)->nullable();
            $table->decimal('bsetupfee', 10, 2)->nullable();
            $table->decimal('monthly', 10, 2)->nullable();
            $table->decimal('quarterly', 10, 2)->nullable();
            $table->decimal('semiannually', 10, 2)->nullable();
            $table->decimal('annually', 10, 2)->nullable();
            $table->decimal('biennially', 10, 2)->nullable();
        });

        Capsule::schema()->create('mod_enomupdater_sales', function (Illuminate\Database\Schema\Blueprint $table) {
            $table->string('extension')->references('extension')->on('tbldomainpricing')->onDelete('cascade');
            $table->enum('type', ['domainregister', 'domainrenew', 'domaintransfer']);
            $table->smallInteger('years');
            $table->decimal('price', 10, 2);
            $table->date('expires');
            $table->primary(['extension', 'type', 'years']);
        });
    }
}

/**
 * @param $vars
 */
function enomPricingUpdater_output($vars)
{
    try {
        if (isset($_POST['enomAction'])) {
            switch ($_POST['enomAction']) {
                case 'updateAll':
                    enomPricingUpdater_process(null);
                    enomPricingUpdater_applySales();
                    break;
                case 'updateSome':
                    enomPricingUpdater_processSome($_POST['tlds']);
                    enomPricingUpdater_applySales();
                    break;
                case 'updateDomainList':
                    enomPricingUpdater_updateDomainList();
                    break;
                case 'setPromo':
                    enomPricingUpdater_setPromo($_POST);
                    break;
                case 'updateSales':
                    enomPricingUpdater_applySales();
                    break;
                case 'checkSales':
                    enomPricingUpdater_checkSales();
                    break;
                case 'checkUpdates':
                    enomPricingUpdater_checkUpdates();
                    break;
                case 'fetchEnomPrices':
                    enomPricingUpdater_fetchEnomPrices();
                    break;
                default:
                    break;
            }
        }
    } catch (Exception $ex) {

        $hiddenData = [substr($vars['apikey'], 10), substr($vars['username'], 5)];

        logModuleCall('eNom pricing updater', 'Action: ' . $_POST['enomAction'], print_r(['vars' => $vars, 'post' => $_POST], true), $ex->getMessage(), '', $hiddenData);
        echo "<strong>Whoops!</strong><br><pre>{$ex->getMessage()}</pre>";
    }

    try {
        /* TODO: Update this to show a promo creation form and table of existing promos
         *
         * Form should contain fields:
         * numSalePrice - decimal number input (2 decimals)
         * numSaleYears - number input [1 .. 10]
         * datSaleEnd - date input
         * radSaleType - radio buttons ['domainregister', 'domainrenew', 'domaintransfer']
         * selSaleDomain - dropdown domain selection
         *
         * Get current sales form mod_enomupdater_sales
         */
        // Get list of configured domains
        $domains = Capsule::table('mod_enomupdater_extensions')->get();
        $addon_dir = substr(__DIR__, strlen($_SERVER['DOCUMENT_ROOT']));
        $quote = '"';

        echo "<script src='$addon_dir/functions.js' type='text/javascript'></script>";

        echo "<div class='row'>";
        echo "<div class='col-md-3 pull-md-left'>";
        echo "<h4>Actions</h4>";

        echo "<form method='post'>";
        echo "<input type='hidden' name='enomAction' value='fetchEnomPrices' />";
        echo "<button type='submit' class='btn btn-warning'>Fetch eNom prices</button> (This may take a while)";
        echo "</form>";
        echo "<hr>";

        echo "<form method='post'>";
        echo "<input type='hidden' name='enomAction' value='fetchEnomPrices' />";
        echo "<input type='text' name='tlds' placeholder='.com,.net,.info'/><br>";
        echo "<button type='submit' class='btn btn-info'>Fetch some eNom prices</button>";
        echo "</form>";
        echo "<hr>";

        echo "<form method='post'>";
        echo "<input type='hidden' name='enomAction' value='updateAll' />";
        echo "<button type='submit' class='btn btn-success'>Update all TLDs</button>";
        echo "</form>";
        echo "<hr>";

        echo "<form method='post'>";
        echo "<input type='hidden' name='enomAction' value='updateSome' />";
        echo "<input type='text' name='tlds' placeholder='.com,.net,.info'/><br>";
        echo "<button type='submit' class='btn btn-info'>Update specific TLDs</button>";
        echo "</form>";
        echo "<hr>";

        echo "<form method='post'>";
        echo "<input type='hidden' name='enomAction' value='updateSales' />";
        echo "<button type='submit' class='btn btn-success'>Apply Sale prices</button>";
        echo "</form>";
        echo "<hr>";

        echo "<form method='post'>";
        echo "<input type='hidden' name='enomAction' value='checkSales' />";
        echo "<button type='submit' class='btn btn-info'>Remove expired sales</button>";
        echo "</form>";
        echo "<hr>";

        echo "<form method='post'>";
        echo "<input type='hidden' name='enomAction' value='updateDomainList' />";
        echo "<button type='submit' class='btn btn-info'>Update internal domain list</button> <br>Run this when you add or remove TLDs that you sell.";
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
        echo "<input type='hidden' name='enomAction' value='setPromo' />";
        echo "<h2>Create Sale</h2>";
        echo "<table class='table table-list'>";
        echo "<thead><tr><th>Extension</th><th>Sale</th><th>Registration Price (&dollar; USD)</th><th>Transfer Price (&dollar; USD)</th><th>Sale End</th></tr></thead>";
        echo "<tbody>";
        foreach ($domains as $domain) {
            $tld = ltrim($domain->extension, '.');
            echo "<tr>";
            echo "<td>{$domain->extension}</td>";
            echo "<td>
      <input type='checkbox' id='chkSaleEnabled$tld' name='chkSaleEnabled$tld' onchange='toggleDomainSale($quote$tld$quote)' value='on'";
            if ($domain->sale == 1) echo "checked='checked'";
            echo "></td>";

            echo "<td id='tdRegPrice$tld'>
      <input type='number' min='0.00' step='0.01' id='numRegPrice$tld' name='numRegPrice$tld' value='{$domain->salePrice}'";
            if ($domain->sale != 1) echo "style='display: none'";
            echo "></td>";

            echo "<td id='tdTraPrice$tld'>
      <input type='number' min='0.00' step='0.01' id='numTraPrice$tld' name='numTraPrice$tld' value='{$domain->salePrice}'";
            if ($domain->sale != 1) echo "style='display: none'";
            echo "></td>";

            echo "<td id='tdSaleEnd$tld'>
      <input type='date' id='datSaleEnd$tld' name='datSaleEnd$tld' value='{$domain->saleEnd}'";
            if ($domain->sale != 1) echo "style='display: none'";
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
    } catch (Exception $ex) {
        echo "<strong>Whoops!</strong><br><pre>{$ex->getMessage()}</pre>";
    }
}

/**
 * Create a new sale entry
 * @param $post array containing ['selSaleDomain', 'radSaleType', 'numSaleYears', 'numSalePrice', 'datSaleEnd']
 */
function enomPricingUpdater_setPromo($post)
{
    $tld = $post['selSaleDomain'];
    $tldjs = ltrim($tld, '.');
    $domainCount = Capsule::table('mod_enomupdater_extensions')->where('extension', $tld)->count();

    $whmcsDomain = Capsule::table('tbldomainpricing')->where('extension', $tld);

    if ($domainCount == 1) {
        $type = $post['radSaleType' . $tldjs];
        $years = $post['numSaleYears' . $tldjs];
        $price = $post['numSalePrice' . $tldjs];
        $end = $post['datSaleEnd' . $tldjs];

        $existingSale = Capsule::table('mod_enomupdater_sales')
            ->where('extension', $tld)
            ->where('type', $type)
            ->where('years', $years)
            ->count();

        if ($existingSale == 1) {
            // Update existing sale
            Capsule::table('mod_enomupdater_sales')
                ->where('extension', $tld)
                ->where('type', $type)
                ->where('years', $years)
                ->update(['expires' => $end, 'price' => $price]);
        } else {
            Capsule::table('mod_enomupdater_sales')->insert([
                'extension' => $tld,
                'type' => $type,
                'years' => $years,
                'expires' => $end,
                'price' => $price
            ]);
        }
        $whmcsDomain->update(['group' => 'sale']);
    }
}

/**
 * Process only the specified TLDs
 * @param $tlds array() of TLDs (may contain leading dots)
 */
function enomPricingUpdater_processSome($tlds)
{
    $extensions = explode(',', $tlds);
    $parsed = [];
    foreach ($extensions as $extension) {
        $extension = trim($extension);
        if (!enomPricingUpdater_startsWith($extension, '.')) {
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
 * @return bool
 */
function enomPricingUpdater_startsWith($haystack, $needle)
{
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
}

/**
 * Synchronize internal list of TLDs with TLDs configured in WHMCS
 */
function enomPricingUpdater_updateDomainList()
{
    // Add new domains from WHMCS to module table
    $existing = Capsule::table('mod_enomupdater_extensions')->lists('extension');
    $extensions = Capsule::table('tbldomainpricing')->whereNotIn('extension', $existing)->get();

    foreach ($extensions as $ext) {
        Capsule::table('mod_enomupdater_extensions')->insert(
            ['extension' => $ext->extension, 'group' => $ext->group]
        );
    }

    // Remove extensions from table if they are not present in WHMCS
    $all = Capsule::table('tbldomainpricing')->lists('extension');
    Capsule::table('mod_enomupdater_extensions')->whereNotIn('extension', $all)->delete();
}

/**
 * Update prices for domains on sale
 */
function enomPricingUpdater_applySales()
{
    $testmode = (Capsule::table('tbladdonmodules')
            ->where([['module', 'enomPricingUpdater'], ['setting', 'testmode']])
            ->first()->value == 'on');

    $debug = (Capsule::table('tbladdonmodules')
            ->where([['module', 'enomPricingUpdater'], ['setting', 'debug']])
            ->first()->value == 'on');

    $profit = Capsule::table('tbladdonmodules')
        ->where([['module', 'enomPricingUpdater'], ['setting', 'profit']])
        ->first()->value;

    $minPrice = Capsule::table('tbladdonmodules')
        ->where([['module', 'enomPricingUpdater'], ['setting', 'minPrice']])
        ->first()->value;

    $rounding = 100 / (Capsule::table('tbladdonmodules')
            ->where([['module', 'enomPricingUpdater'], ['setting', 'rounding']])
            ->first()->value);

    if (!isset($rounding) || $rounding < 0 || $rounding > 100 || !is_numeric($rounding)) $rounding = 4;
    if (!isset($profit) || !is_numeric($profit)) $profit = 50;
    if (!isset($minPrice) || $minPrice < 0 || !is_numeric($minPrice)) $minPrice = 0.01;

    $domains = Capsule::table('tbldomainpricing')->where('group', 'sale')->get();

    $rates = enomPricingUpdater_getRates();

    foreach ($domains as $domain) {
        // Get sale price in default currency
        $saleFee = Capsule::table('mod_enomupdater_extensions')->where('extension', $domain->extension)
                ->first()->salePrice / $rates['USD']->rate;

        $salePrice = $saleFee * (1 + $profit / 100);

        foreach ($rates as $rate) {
            $price = (floor($salePrice * $rate->rate * $rounding)) / $rounding;
            if ($price < $minPrice) $price = $minPrice;

            // Update database, only execute if not running in testmode
            if (!$testmode) {
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
 * Convert a list of current prices to a list of enabled terms
 * @param $currentPrices object(msetupfee,qsetupfee,ssetupfee...)
 * @return array containing enabled terms for type, like $arr['domainregister'][1,2,3,4]
 */
function enomPricingUpdater_getEnabledTerms($currentPrices)
{
    $returned = [];

    //TODO: Make this more flexible instead of hardcoding all terms
    // foreach($GLOBALS['enomTerms'] as $year => $name) {
    //   if($currentPrices->$name > 0) array_push($returned, $year);
    // }

    if ($currentPrices->msetupfee > 0) array_push($returned, 1);
    if ($currentPrices->qsetupfee > 0) array_push($returned, 2);
    if ($currentPrices->ssetupfee > 0) array_push($returned, 3);
    if ($currentPrices->asetupfee > 0) array_push($returned, 4);
    if ($currentPrices->bsetupfee > 0) array_push($returned, 5);
    if ($currentPrices->monthly > 0) array_push($returned, 6);
    if ($currentPrices->quarterly > 0) array_push($returned, 7);
    if ($currentPrices->semiannually > 0) array_push($returned, 8);
    if ($currentPrices->annually > 0) array_push($returned, 9);
    if ($currentPrices->biennially > 0) array_push($returned, 10);

    return $returned;
}

/**
 * Get a list of enabled purchase modes for a domain
 * @param $domain Illuminate\Database\Query\Builder result of Capsule query for single domain
 * @return array magic
 */
function enomPricingUpdater_getEnabledModes($domain)
{
    $enabledModes = [
        'domainregister' => [],
        'domainrenew' => [],
        'domaintransfer' => []
    ];

    $currentRegistrationPrices = Capsule::table('tblpricing')
        ->where('relid', $domain->id)
        ->where('currency', 1)
        ->where('type', 'domainregister')
        ->select('msetupfee', 'qsetupfee', 'ssetupfee', 'asetupfee', 'bsetupfee', 'monthly', 'quarterly', 'semiannually', 'annually', 'biennially')
        ->first();

    $currentRenewalPrices = Capsule::table('tblpricing')
        ->where('relid', $domain->id)
        ->where('currency', 1)
        ->where('type', 'domainrenew')
        ->select('msetupfee', 'qsetupfee', 'ssetupfee', 'asetupfee', 'bsetupfee', 'monthly', 'quarterly', 'semiannually', 'annually', 'biennially')
        ->first();

    $currentTransferPrices = Capsule::table('tblpricing')
        ->where('relid', $domain->id)
        ->where('currency', 1)
        ->where('type', 'domaintransfer')
        ->select('msetupfee', 'qsetupfee', 'ssetupfee', 'asetupfee', 'bsetupfee', 'monthly', 'quarterly', 'semiannually', 'annually', 'biennially')
        ->first();

    $enabledModes['domainregister'] = enomPricingUpdater_getEnabledTerms($currentRegistrationPrices);
    $enabledModes['domainrenew'] = enomPricingUpdater_getEnabledTerms($currentRenewalPrices);
    $enabledModes['domaintransfer'] = enomPricingUpdater_getEnabledTerms($currentTransferPrices);

    return $enabledModes;
}

/**
 * Update prices for all (or certain) domains
 * @param $extensions array() of TLDs to update. If NULL, update all TLDs
 */
function enomPricingUpdater_process($extensions)
{
    logModuleCall('eNom pricing updater', 'process', print_r($extensions, true), '', '', '');
    $username = Capsule::table('tbladdonmodules')
        ->where([['module', 'enomPricingUpdater'], ['setting', 'username']])
        ->first()->value;

    $apiKey = Capsule::table('tbladdonmodules')
        ->where([['module', 'enomPricingUpdater'], ['setting', 'apikey']])
        ->first()->value;

    $testmode = (Capsule::table('tbladdonmodules')
            ->where([['module', 'enomPricingUpdater'], ['setting', 'testmode']])
            ->first()->value == 'on');

    $debug = (Capsule::table('tbladdonmodules')
            ->where([['module', 'enomPricingUpdater'], ['setting', 'debug']])
            ->first()->value == 'on');

    $profit = Capsule::table('tbladdonmodules')
        ->where([['module', 'enomPricingUpdater'], ['setting', 'profit']])
        ->first()->value;

    $discount = Capsule::table('tbladdonmodules')
        ->where([['module', 'enomPricingUpdater'], ['setting', 'multiDiscount']])
        ->first()->value;

    $minPrice = Capsule::table('tbladdonmodules')
        ->where([['module', 'enomPricingUpdater'], ['setting', 'minPrice']])
        ->first()->value;

    $rounding = 100 / (Capsule::table('tbladdonmodules')
            ->where([['module', 'enomPricingUpdater'], ['setting', 'rounding']])
            ->first()->value);

    if (!isset($rounding) || $rounding < 0 || $rounding > 100 || !is_numeric($rounding)) $rounding = 4;
    if (!isset($profit) || !is_numeric($profit)) $profit = 50;
    if (!isset($minPrice) || $minPrice < 0 || !is_numeric($minPrice)) $minPrice = 0.01;

    // Get available domains from WHMCS
    $domains = Capsule::table('tbldomainpricing');
    if (isset($extensions)) $domains->whereIn('extension', $extensions);
    $domains = $domains->get();

    $rates = enomPricingUpdater_getRates();

    $logData = [
        'domains' => $domains,
        'rates' => $rates,
        'testmode' => $testmode,
        'minPrice' => $minPrice,
        'discount' => $discount,
        'rounding' => $rounding,
        'username' => $username,
        'apiKey' => $apiKey
    ];

    $hidden = [substr($username, 5), substr($apiKey, 10)];

    logModuleCall('eNom Pricing Updater', 'process domains', print_r($logData, true), '', '', $hidden);

    enomPricingUpdater_processRegularDomains($domains, $rates, $testmode, $debug, $profit, $minPrice, $discount, $rounding, $username, $apiKey);

}

/**
 * Get an array of all exchange rates in the system
 * Rates are relative to default currency
 * @return array [code] => rate
 */
function enomPricingUpdater_getRates()
{
    // Save exchange rates for easy access
    $dbrates = Capsule::table('tblcurrencies')->get();
    $rates = [];

    foreach ($dbrates as $rate) {
        $rates[$rate->code] = $rate;
    }

    return $rates;
}

/**
 * Check for current wholesale price from eNom and update WHMCS prices accordingly
 * @param $domains array() of TLDs to update
 * @param $rates array() of exchange rates
 * @param $testmode boolean testmode is enabled
 * @param $debug boolean enable debug mode
 * @param $profit integer profit margin. 50 = 50%
 * @param $minPrice
 * @param $discount integer discount percentage per year for multi-year registrations and renewals
 * @param $rounding
 * @param $username string eNom API username
 * @param $apiKey string eNom API access key
 */
function enomPricingUpdater_processRegularDomains($domains, $rates, $testmode, $debug, $profit, $minPrice, $discount, $rounding, $username, $apiKey)
{
    $tlds = [];

    // PE_GetProductPrice <-- Just get the price
    // PE_GetResellerPrice <-- Get price and status

    // Loop through all domains in WHMCS
    foreach ($domains as $domain) {
        $enabledModes = enomPricingUpdater_getEnabledModes($domain);


        $enomPrices = [
            'domainregister' => [],
            'domainrenew' => [],
            'domaintransfer' => []
        ];

        array_push($tlds, $domain->extension);

        foreach ($enabledModes as $mode => $years) {
            foreach ($years as $year) {
                $enomPrices[$mode][$year] = enomPricingUpdater_getStoredPrice($domain->extension, $mode, $year);
            }
        }

        $newPrices = enomPricingUpdater_calculateSalePrices($enomPrices, $profit, $discount);

        // Save new prices for all exchange rates
        foreach ($rates as $rate) {
            $salePrices = [];

            foreach ($newPrices as $type => $years) {
                $salePrices[$type] = [];
                foreach ($years as $year => $price) {
                    $term = $GLOBALS['enomTerms'][$year];
                    if ($price == -1) {
                        $salePrices[$type][$term] = -1;
                        continue;
                    }

                    $price = (floor($price * $rate->rate * $rounding)) / $rounding;
                    if ($price < $minPrice) $price = $minPrice;
                    $salePrices[$type][$term] = $price;
                }
            }


            if ($debug) {
                $logData = [
                    'domain' => $domain,
                    'currency' => $rate,
                    'newPrices' => $newPrices,
                    'testmode' => $testmode,
                    'minPrice' => $minPrice,
                    'discount' => $discount,
                    'rounding' => $rounding,
                    'username' => $username,
                    'apiKey' => $apiKey
                ];

                $hidden = [substr($username, 5), substr($apiKey, 10)];

                logModuleCall('eNom pricing updater', "DEBUG: Update pricing for {$domain->extension} in {$rate->code}", print_r($logData, true), $salePrices, print_r($salePrices, true), $hidden);
            }

            // Update database, only execute if not running in testmode
            if (!$testmode) {
                if (isset($salePrices['domainregister'])) {
                    Capsule::table('tblpricing')
                        ->where('relid', $domain->id)
                        ->where('type', 'domainregister')
                        ->where('currency', $rate->id)
                        ->update($salePrices['domainregister']);

                    Capsule::table('mod_enomupdater_prices')
                        ->where('relid', $domain->id)
                        ->where('type', 'domainregister')
                        ->where('currency', $rate->id)
                        ->update($salePrices['domainregister']);
                }

                if (isset($salePrices['domainrenew'])) {
                    Capsule::table('tblpricing')
                        ->where('relid', $domain->id)
                        ->where('type', 'domainrenew')
                        ->where('currency', $rate->id)
                        ->update($salePrices['domainrenew']);

                    Capsule::table('mod_enomupdater_prices')
                        ->where('relid', $domain->id)
                        ->where('type', 'domainrenew')
                        ->where('currency', $rate->id)
                        ->update($salePrices['domainrenew']);
                }

                if (isset($salePrices['domaintransfer'])) {
                    Capsule::table('tblpricing')
                        ->where('relid', $domain->id)
                        ->where('type', 'domaintransfer')
                        ->where('currency', $rate->id)
                        ->update($salePrices['domaintransfer']);

                    Capsule::table('mod_enomupdater_prices')
                        ->where('relid', $domain->id)
                        ->where('type', 'domaintransfer')
                        ->where('currency', $rate->id)
                        ->update($salePrices['domaintransfer']);
                }
            }

        }
    }
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
 * @param $discount integer discount percentage per year for multi-year registrations
 * @return array() of $arr[type][years]. e.g: $arr['domainregister'][1] = 7.50. Prices in default currency
 */
function enomPricingUpdater_calculateSalePrices($enomPrices, $profit, $discount)
{
    $rates = enomPricingUpdater_getRates();
    $returned = [];

    foreach ($enomPrices as $type => $durations) {
        foreach ($durations as $duration => $price) {
            $returned[$type][$duration] = $price * (1 + $profit / 100 - $discount * ($duration - 1) / 100) * $duration;
            $returned[$type][$duration] /= $rates['USD'];
            if ($price == -1 || $price == null) $returned[$type][$duration] = -1;
        }
    }

    return $returned;
}

/**
 * Get the stored wholesale price for a domain/mode/year combo
 * @param $extension string domain TLD
 * @param $mode string ['domainregister', 'domainrenew', 'domaintransfer']
 * @param $years integer between 1 and 10
 * @return float wholesale price
 */
function enomPricingUpdater_getStoredPrice($extension, $mode, $years)
{
    $price = Capsule::table('mod_enomupdater_enomprices')
        ->where('extension', $extension)
        ->where('type', $mode)
        ->pluck($GLOBALS['numberNames'][$years])[0];

    return $price;
}

/**
 * Update mod_enomupdater_enomprices table with current eNom wholesale prices
 */
function enomPricingUpdater_fetchEnomPrices()
{
    $parsed = [];

    if (isset($_POST['tlds'])) {
        $extensions = explode(',', $_POST['tlds']);
        foreach ($extensions as $extension) {
            $extension = trim($extension);
            if (!enomPricingUpdater_startsWith($extension, '.')) {
                $extension = '.' . $extension;
            }
            array_push($parsed, $extension);
        }
    }


    $username = Capsule::table('tbladdonmodules')
        ->where([['module', 'enomPricingUpdater'], ['setting', 'username']])
        ->first()->value;

    $apiKey = Capsule::table('tbladdonmodules')
        ->where([['module', 'enomPricingUpdater'], ['setting', 'apikey']])
        ->first()->value;

    $domains = Capsule::table('tbldomainpricing');
    if (count($parsed) > 0) $domains = $domains->whereIn('extension', $parsed);
    $domains = $domains->get();


    if (count($parsed) > 0) {
        // Only clear pricing for domains we want to update
        Capsule::table('mod_enomupdater_enomprices')->whereIn('extension', $parsed)->delete();
    } else {
        // Empty entire pricing database
        Capsule::table('mod_enomupdater_enomprices')->truncate();
    }

    foreach ($domains as $domain) {
        $enomPrices = [
            'domainregister' => [],
            'domainrenew' => [],
            'domaintransfer' => []
        ];

        $tld = ltrim($domain->extension, '.');

        foreach ($GLOBALS['enomModes'] as $mode => $foo) {
            $generalInfo = enomPricingUpdater_getEnomPrice(['tld' => $tld, 'type' => $foo, 'years' => 1], $username, $apiKey, true);
            $minPeriod = intval($generalInfo->MinPeriod);
            $maxPeriod = intval($generalInfo->MaxPeriod);

            if (!is_numeric($minPeriod) || $minPeriod < 1) $minPeriod = 1;
            if (!is_numeric($maxPeriod) || $maxPeriod > 10) $maxPeriod = 10;

            for ($i = $minPeriod; $i <= $maxPeriod; $i++) {
                $result = enomPricingUpdater_getEnomPrice(['tld' => $tld, 'type' => $foo, 'years' => $i], $username, $apiKey, false);
                $enomPrices[$mode][$i] = $result;
            }
        }

        foreach ($enomPrices as $type => $years) {
            Capsule::table('mod_enomupdater_enomprices')->insert([
                'extension' => $domain->extension,
                'type' => $type
            ]);

            foreach ($years as $year => $price) {
                Capsule::table('mod_enomupdater_enomprices')
                    ->where('extension', $domain->extension)
                    ->where('type', $type)
                    ->update([$GLOBALS['numberNames'][$year] => $price]);
            }
        }
    }

}

/**
 * Get eNom price for certain product configuration
 * @param $settings array() containing product settings. ['tld', 'years', 'type']
 * @param $username string eNom API username
 * @param $apiKey string eNom API access key
 * @param $getResult boolean return complete API response
 * @return float|SimpleXMLElement eNom price for specified purchase
 */
function enomPricingUpdater_getEnomPrice($settings, $username, $apiKey, $getResult = false)
{
    $endpoint = "https://reseller.enom.com/interface.asp";
    $urlBase = "$endpoint?uid=$username&pw=$apiKey&command=PE_GetProductPrice&ResponseType=xml";

    $requestUrl = "$urlBase&tld={$settings['tld']}&ProductType={$settings['type']}&Years={$settings['years']}";

    $requestResult = simplexml_load_file($requestUrl);

    if (!$getResult) return $requestResult->productprice->price;

    else return $requestResult;
}

/**
 * Checks sales for expiration dates and disables them once they expire
 */
function enomPricingUpdater_checkSales()
{
    //TODO: Reset all entries in tbldomainpricing to their original group from mod_enomupdater_extensions before applying sale labels
    //      To ensure nothing is incorrectly marked as sale.
    try {
        $expired = Capsule::table('mod_enomupdater_sales')->where('expires', '<', Capsule::RAW('CURRENT_TIMESTAMP'))->lists('extension');
        $domains = Capsule::table('mod_enomupdater_extensions')->whereIn('extension', $expired)->get();

        foreach ($domains as $domain) {
            Capsule::table('tbldomainpricing')->where('extension', $domain->extension)->update(['group', $domain->group]);
        }

        Capsule::table('mod_enomupdater_sales')->where('expires', '<', Capsule::RAW('CURRENT_TIMESTAMP'))->delete();

        $logData = [
            'expired' => $expired
        ];

        logModuleCall('eNom pricing updater', 'CheckSales', print_r($logData, true), '', '', '');
    } catch (Exception $ex) {
        logModuleCall('eNom pricing updater', 'CheckSales Error', '', $ex->getMessage(), '', '');
    }
}

/**
 * Check to see if a new version is available on GitHub
 * Only checks for stable releases.
 * Displays a message with download link if new version is found
 */
function enomPricingUpdater_checkUpdates()
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/repos/ducohosting/whmcs-enom-updater/releases/latest');
    curl_setopt($ch, CURLOPT_USERAGENT, 'WHMCS eNom pricing update module by Duco Hosting');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = json_decode(curl_exec($ch));
    curl_close($ch);

    $latestVersion = ltrim($response->tag_name, 'v');
    $currentVersion = Capsule::table('tbladdonmodules')->where([['module', 'enomPricingUpdater'], ['setting', 'version']])->first()->value;

    logModuleCall('eNom pricing updater', 'Update check', $currentVersion, print_r($response, true), '', '');

    // first > last --> 1
    // first = last --> 0
    // first < last --> -1

    $result = version_compare($currentVersion, $latestVersion);

    if ($result == -1) {
        echo "<strong>Update available!</strong><br>";
        echo "Installed version: <strong>$currentVersion</strong><br>";
        echo "Latest version: <strong>$latestVersion</strong><br><br>";
        echo "Download the latest version <a href='{$response->assets->browser_download_url}'>HERE</a>";
    } else {
        echo "<strong>You are running the latest version</strong><br>";
    }
}
