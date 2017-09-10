<?php
/**
 *
 *                  WHMCS eNom price sync addon module
 *                   Copyright (C) 2017  Duco Hosting
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
        "version" => "2.1.0-alpha4",
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
                "Description" => "When to automatically update eNom wholesale prices during cron job. (not yet implemented)",
                "Default" => "Monthly"
            ],
            "priceUpdateDay" => [
                "FriendlyName" => "eNom price update day",
                "Type" => "dropdown",
                "Options" => "Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday",
                "Description" => "When Update eNom prices is set to weekly, the update will be performed on this weekday. (not yet implemented)",
                "Default" => "Sunday"
            ],
            "onlyEnom" => [
                "FriendlyName" => "Only update eNom domains",
                "Type" => "yesno",
                "Description" => "Only process domains that are set to auto register with eNom"
            ],
            "checkBeta" => [
                "FriendlyName" => "Check for beta releases",
                "Type" => "yesno",
                "Description" => "When using the update checker to check for new versions, also consider pre-release versions"
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
            $table->integer('relid')->references('id')->on('tbldomainpricing')->onDelete('cascade');
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
            $table->primary(['relid', 'currency', 'type']);
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

        Capsule::schema()->create('mod_enomupdater_promos', function (Illuminate\Database\Schema\Blueprint $table) {
            $table->string('extension')->references('extension')->on('tbldomainpricing')->onDelete('cascade');
            $table->integer('relid')->references('id')->on('tbldomainpricing')->onDelete('cascade');
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
        Capsule::schema()->dropIfExists('mod_enomupdater_promos');
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
        Capsule::schema()->dropIfExists('mod_enomupdater_promos');
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
//    if (version_compare($version, '1.1.0-beta2') == -1) {
//        Capsule::schema()->create('mod_enomupdater_enomprices', function (Illuminate\Database\Schema\Blueprint $table) {
//            // https://laravel.com/docs/4.2/schema
//            $table->renameColumn('salePrice', 'regPrice');
//            $table->dropColumn('processed');
//            $table->decimal('traPrice', 5, 2)->nullable();
//        });
//    }
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
                    enomPricingUpdater_applyPromos();
                    $enomPricingUpdater_actionCompleted = 1;
                    $enomPricingUpdater_actionMessage = 'Finished applying all TLD pricing';
                    break;
                case 'updateSome':
                    enomPricingUpdater_processSome($_POST['tlds']);
                    enomPricingUpdater_applyPromos();
                    $enomPricingUpdater_actionCompleted = 1;
                    $enomPricingUpdater_actionMessage = 'Finished applying some TLD pricing';
                    break;
                case 'updateDomainList':
                    enomPricingUpdater_updateDomainList();
                    $enomPricingUpdater_actionCompleted = 1;
                    $enomPricingUpdater_actionMessage = 'Finished updating the internal eNom domain list';
                    break;
                case 'addPromo':
                    enomPricingUpdater_addPromo($_POST);
                    $enomPricingUpdater_actionCompleted = 1;
                    $enomPricingUpdater_actionMessage = 'Added promotion successfully';
                    break;
                case 'deletePromo':
                    enomPricingUpdater_deletePromo($_POST);
                    $enomPricingUpdater_actionCompleted = 1;
                    $enomPricingUpdater_actionMessage = 'Deleted promotion successfully';
                    break;
                case 'updatePromos':
                    enomPricingUpdater_applyPromos();
                    $enomPricingUpdater_actionCompleted = 1;
                    $enomPricingUpdater_actionMessage = 'Finished applying domain promotion pricing';
                    break;
                case 'scheckPromos':
                    enomPricingUpdater_checkPromos();
                    $enomPricingUpdater_actionCompleted = 1;
                    $enomPricingUpdater_actionMessage = 'Finished removing expired domain promotions';
                    break;
                case 'checkUpdates':
                    enomPricingUpdater_checkUpdates();
                    $enomPricingUpdater_actionCompleted = 1;
                    $enomPricingUpdater_actionMessage = 'Check for updates completed';
                    break;
                case 'fetchEnomPrices':
                    enomPricingUpdater_fetchEnomPrices();
                    $enomPricingUpdater_actionCompleted = 1;
                    $enomPricingUpdater_actionMessage = 'Finished fetching eNom pricing';
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
        /*
         * Form should contain fields:
         * numSalePrice - decimal number input (2 decimals)
         * numSaleYears - number input [1 .. 10]
         * datSaleEnd - date input
         * radSaleType - radio buttons ['domainregister', 'domainrenew', 'domaintransfer']
         * selSaleDomain - dropdown domain selection
         *
         * Get current sales form mod_enomupdater_promos
         */
        // Get list of configured domains
        $domains = Capsule::table('mod_enomupdater_extensions')->orderBy('extension', 'asc')->get();
        $promos = Capsule::table('mod_enomupdater_promos')->orderBy('extension', 'asc')->get();
        $version = enomPricingUpdater_getSetting('version');
        $domainOptions = "";
        $promoRows = "";

        foreach ($domains as $domain) {
            $domainOptions .= "<option value='{$domain->extension}'>{$domain->extension}</option>";
        }

        foreach ($promos as $promo) {
            switch ($promo->type) {
                case 'domainregister':
                default:
                    $type = 'Registrations';
                    break;
                case 'domainrenew':
                    $type = 'Renewals';
                    break;
                case 'domaintransfer':
                    $type = 'Transfers';
                    break;
            }
            $promoRows .= <<<EOL
                <tr>
                    <td>$promo->extension</td>
                    <td>$type</td>
                    <td>$promo->years</td>
                    <td>$promo->price</td>
                    <td>$promo->expires</td>
                    <td>
                        <form method='post' onsubmit="return confirm('Are you sure you want to end this promotion now?\\nYou will need to apply promo prices for this to take effect');">
                            <input type='hidden' name='enomAction' value='deletePromo'>
                            <input type='hidden' name='domain' value='$promo->extension'>
                            <input type='hidden' name='years' value='$promo->years'>
                            <input type='hidden' name='type' value='$promo->type'>
                            <button type="submit" class="fabutton" data-toggle="tooltip" data-placement="top" title="Delete"><i class="fa fa-trash"></i></button>
                        </form>
                    </td>
                </tr>            
EOL;
        }
        
if(isset($enomPricingUpdater_actionCompleted) && $enomPricingUpdater_actionCompleted == 1){
echo <<<END
<div class="infobox"><strong><span class="title">Action Completed!</span></strong><br>$enomPricingUpdater_actionMessage</div>
END;
}

        echo /** @lang HTML */
        
        <<<EOL
<style>
    .fabutton {
        background: none;
        padding: 0;
        border: none;
    }
</style>

<p>To update your eNom pricing, we first recommend selecting the desired option to fetch eNom prices, then calculating the TLD pricing, using the options below. Please use the Promo Actions section to update your TLD pricing after creating or remove promotions.</p>

<div class='row'>
    <div class='col-md-3 pull-md-left'>
        <div class="panel panel-default">
            <div class="panel-heading">eNom Actions</div>
            <div class="panel-body">
                <form method='post'>
                    <input type='hidden' name='enomAction' value='fetchEnomPrices' />
                    <button type='submit' class='btn btn-warning'>Fetch eNom prices</button> (This may take a while)
                </form>
                <hr>
                <form method='post'>
                    <input type='hidden' name='enomAction' value='fetchEnomPrices' />
                    <input type='text' name='tlds' placeholder='.com,.net,.info'/><br>
                    <button type='submit' class='btn btn-info'>Fetch some eNom prices</button>
                </form>
            </div>
        </div>
        
        <div class="panel panel-default">
            <div class="panel-heading">WHMCS TLD Actions</div>
            <div class="panel-body">
                <form method='post'>
                    <input type='hidden' name='enomAction' value='updateAll' />
                    <button type='submit' class='btn btn-success'>Calculate all TLD prices</button>
                </form>
                <hr>
                <form method='post'>
                    <input type='hidden' name='enomAction' value='updateSome' />
                    <input type='text' name='tlds' placeholder='.com,.net,.info'/><br>
                    <button type='submit' class='btn btn-info'>Calculate specific TLD prices</button>
                </form>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">Promo Actions</div>
            <div class="panel-body">
                <form method='post'>
                    <input type='hidden' name='enomAction' value='updatePromos' />
                    <button type='submit' class='btn btn-success'>Apply Promotion Pricing</button>
                </form>
                <hr>
                <form method='post'>
                    <input type='hidden' name='enomAction' value='scheckPromos' />
                    <button type='submit' class='btn btn-info'>Remove Expired Promotions</button>
                </form>
            </div>
        </div>
        
        <div class="panel panel-default">
            <div class="panel-heading">Module Actions</div>
            <div class="panel-body">
                <form method='post'>
                    <input type='hidden' name='enomAction' value='updateDomainList' />
                    <button type='submit' class='btn btn-info'>Update internal domain list</button> <br>Run this when you add or remove TLDs that you sell.
                </form>
            </div>
        </div>
    </div>
        
    <div class="col-md-9 pull-md-right">
        <div class='row'>
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">Create Promotion</div>
                    <div class="panel-body">
                        <div class="row">
                            <form method='post'>
                                <input type='hidden' name='enomAction' value='addPromo'>
                                <div class='col-sm-6'>
                                    <table class="table">
                                        <tr>
                                            <td>Domain</td>
                                            <td>
                                                <select name='selPromoDomain' id='selPromoDomain'>
                                                    <option value="0" selected disabled>Choose a domain</option>
                                                        $domainOptions
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Type</td>
                                            <td>
                                                <label><input type='radio' name='radPromoType' value='domainregister' checked> Registrations</label><br>
                                                <label><input type='radio' name='radPromoType' value='domainrenew'> Renewals</label><br>
                                                <label><input type='radio' name='radPromoType' value='domaintransfer'> Transfers</label><br>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div class='col-sm-6'>
                                    <table class="table">
                                        <tr>
                                            <td><label for='datPromoEnd'>End Date</label></td>
                                            <td><input type='date' name='datPromoEnd' placeholder='Promotion end date' id='datPromoEnd' required></td>
                                        </tr>
                                        <tr>
                                            <td><label for='numPromoYears'>Years</label></td>
                                            <td><input type='number' name='numPromoYears' min='1' max='10' id='numPromoYears' value="1" required></td>
                                        </tr>
                                        <tr>
                                            <td><label for='numPromoPrice'>Wholesale Price <br>(&dollar; USD)</label><br>
                                            This is the total price, not the price per year!</td>
                                            <td><input type='number' name='numPromoPrice' min='0.01' max='99999999.99' step='0.01' id='numPromoPrice' required></td>
                                        </tr>
                                    </table>
                                    <button class='btn btn-primary' type='submit'>Create Promo</button>
                                </div>
                            </form>
                        </div> <!-- ROW -->
                    </div> <!-- PANEL BODY -->
                </div> <!-- PANEL -->
            </div> <!-- COL MD 12 -->
            <div class='col-md-12'>
                <div class="panel panel-default">
                    <div class="panel-heading">Active Promotions</div>
                    <div class="panel-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Extension</th>
                                    <th>Type</th>
                                    <th>Years</th>
                                    <th>Price</th>
                                    <th>Expiry</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                $promoRows
                            </tbody>
                        </table>
                    </div> <!-- PANEL BODY -->
                </div> <!-- PANEL -->
            </div> <!-- COL MD 12 -->
        </div> <!-- ROW -->
    </div> <!-- COL MD 9 -->
</div> <!-- ROW -->
EOL;

    } catch (Exception $ex) {
        echo "<strong>Whoops!</strong><br><pre>{$ex->getMessage()}</pre>";
    }
}

/**
 * Create a new sale entry
 * @param $post array containing ['numPromoPrice', 'numPromoYears', 'selPromoDomain', 'datPromoEnd', 'radPromoType']
 */
function enomPricingUpdater_addPromo($post)
{
    $type = $post['radPromoType'];
    $price = $post['numPromoPrice'];
    $years = $post['numPromoYears'];
    $end = $post['datPromoEnd'];

    $validTypes = ['domainregister', 'domaintransfer', 'domainrenew'];
    $today = date("Y-m-d H:i:s");
    $date = "2010-01-21 00:00:00";

    // Check that expiry date is in the future
    if (strtotime($end) < time()) $err = "The promotion must expire in the future";

    // Check that price is positive
    if (!is_numeric($price) || $price <= 0.00) $err = "The price must be a number higher than 0.00";

    // Check that type is valid
    if (!in_array($type, $validTypes)) $err = "The type must be either registration, transfer or renewal";

    // Check term is valid
    if (!is_numeric($years) || $years < 1 || $years > 10) $err = "The amount of years must be [1 <= x <= 10]";


    $tld = $post['selPromoDomain'];
    $domainCount = Capsule::table('mod_enomupdater_extensions')->where('extension', $tld)->count();
    $whmcsDomainCount = Capsule::table('tbldomainpricing')->where('extension', $tld)->count();


    $whmcsDomain = Capsule::table('tbldomainpricing')->where('extension', $tld);

    if ($domainCount == 1 && $whmcsDomainCount == 1 && !isset($err)) {
        $relid = Capsule::table('tbldomainpricing')->where('extension', $tld)->first()->id;
        $existingSale = Capsule::table('mod_enomupdater_promos')
            ->where('extension', $tld)
            ->where('type', $type)
            ->where('years', $years)
            ->count();

        if ($existingSale == 1) {
            // Update existing sale
            Capsule::table('mod_enomupdater_promos')
                ->where('extension', $tld)
                ->where('type', $type)
                ->where('years', $years)
                ->update(['expires' => $end, 'price' => $price]);
        } else {
            Capsule::table('mod_enomupdater_promos')->insert([
                'extension' => $tld,
                'type' => $type,
                'years' => $years,
                'expires' => $end,
                'price' => $price,
                'relid' => $relid
            ]);
        }
        $whmcsDomain->update(['group' => 'sale']);
        logModuleCall('enom pricing updater', 'add promotion', print_r($post, true), '', '', []);
    } else {
        $err = "This domain does not exist";
    }

    if (isset($err)) {
        echo $err;
        logModuleCall('enom pricing updater', 'ERROR: add promotion', print_r($post, true), $err, '', []);
    }
}

/**
 * Delete a promotion from mod_enomupdater_promos
 * @param $post array['domain', 'type', 'years']
 */
function enomPricingUpdater_deletePromo($post)
{
    $domain = $post['domain'];
    $type = $post['type'];
    $years = $post['years'];

    $oldGroup = Capsule::table('mod_enomupdater_extensions')->where('extension', $domain)->first()->group;

    Capsule::table('mod_enomupdater_promos')
        ->where('extension', $domain)
        ->where('type', $type)
        ->where('years', $years)
        ->delete();

    // Reset domain to old group in WHMCS
    $promoCount = Capsule::table('mod_enomupdater_promos')->where('extension', $domain)->count();
    if ($promoCount == 0) Capsule::table('tbldomainpricing')->where('extension', $domain)->update(['group' => $oldGroup]);

    logModuleCall('enom pricing updater', 'delete promotion', print_r($post, true), '', '', []);
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

    $onlyEnom = (enomPricingUpdater_getSetting('onlyEnom') == 'on');

    $extensions = Capsule::table('tbldomainpricing')->whereNotIn('extension', $existing);
    if ($onlyEnom) $extensions = $extensions->where('autoreg', 'enom');
    $extensions = $extensions->get();

    $newGroups = Capsule::table('tbldomainpricing')->where('group', '!=', 'sale')->get();
    $currencies = Capsule::table('tblcurrencies')->get();

    foreach ($extensions as $ext) {
        Capsule::table('mod_enomupdater_extensions')->insert(
            ['extension' => $ext->extension, 'group' => $ext->group]
        );

        // Insert extension in mod_enomupdater_prices to keep track of regular (non promo) prices
        foreach ($currencies as $currency) {
            Capsule::table('mod_enomupdater_prices')->insert([
                'relid' => $ext->id,
                'currency' => $currency->id,
                'type' => 'domainregister'
            ]);

            Capsule::table('mod_enomupdater_prices')->insert([
                'relid' => $ext->id,
                'currency' => $currency->id,
                'type' => 'domainrenew'
            ]);

            Capsule::table('mod_enomupdater_prices')->insert([
                'relid' => $ext->id,
                'currency' => $currency->id,
                'type' => 'domaintransfer'
            ]);
        }
    }

    foreach ($newGroups as $ext) {
        Capsule::table('mod_enomupdater_extensions')->where('extension', $ext->extension)->update(['group' => $ext->group]);
    }

    // Remove extensions from table if they are not present in WHMCS
    $all = Capsule::table('tbldomainpricing');
    if ($onlyEnom) $all = $all->where('autoreg', 'enom');
    $all = $all->lists('extension');

    $allIds = Capsule::table('tbldomainpricing');
    if ($onlyEnom) $allIds = $allIds->where('autoreg', 'enom');
    $allIds = $allIds->lists('extension');

    Capsule::table('mod_enomupdater_extensions')->whereNotIn('extension', $all)->delete();
    Capsule::table('mod_enomupdater_prices')->whereNotIn('relid', $allIds)->delete();
}

/**
 * Update prices for domains on sale
 */
function enomPricingUpdater_applyPromos()
{
    $testmode = (Capsule::table('tbladdonmodules')
            ->where([['module', 'enomPricingUpdater'], ['setting', 'testmode']])
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

    $domains = Capsule::table('mod_enomupdater_promos')->get();
    $rates = enomPricingUpdater_getRates();

    foreach ($domains as $domain) {
        $type = $domain->type;
        $years = $domain->years;
        $wholesalePrice = $domain->price;

        $priceDefault = $wholesalePrice / $rates['USD']->rate;

        $salePrice = ($wholesalePrice * (1 + $profit / 100)) / $rates['USD']->rate;


        foreach ($rates as $rate) {
            $price = (floor($salePrice * $rate->rate * $rounding)) / $rounding;
            if ($price < $minPrice) $price = $minPrice;

            $logData = [
                'Type' => $type,
                'Years' => $years,
                'Wholesale' => $wholesalePrice,
                'DefaultPrice' => $priceDefault,
                'SalePrice' => $price,
                'Currency' => $rate->code,
                'testmode' => $testmode
            ];

            logModuleCall('eNom pricing updater', 'apply sales', print_r($domain, true), $logData, '', []);

            // Update database, only execute if not running in testmode
            if (!$testmode) {
                Capsule::table('tblpricing')
                    ->where('relid', $domain->relid)
                    ->where('type', $type)
                    ->where('currency', $rate->id)
                    ->update([$GLOBALS['enomTerms'][$years] => $price]);
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

    if ($currentPrices->msetupfee >= 0) array_push($returned, 1);
    if ($currentPrices->qsetupfee >= 0) array_push($returned, 2);
    if ($currentPrices->ssetupfee >= 0) array_push($returned, 3);
    if ($currentPrices->asetupfee >= 0) array_push($returned, 4);
    if ($currentPrices->bsetupfee >= 0) array_push($returned, 5);
    if ($currentPrices->monthly >= 0) array_push($returned, 6);
    if ($currentPrices->quarterly >= 0) array_push($returned, 7);
    if ($currentPrices->semiannually >= 0) array_push($returned, 8);
    if ($currentPrices->annually >= 0) array_push($returned, 9);
    if ($currentPrices->biennially >= 0) array_push($returned, 10);

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
    logModuleCall('eNom pricing updater', 'process', print_r($extensions, true), '', '', []);

    $username = enomPricingUpdater_getSetting('username');
    $apiKey = enomPricingUpdater_getSetting('apikey');
    $testmode = (enomPricingUpdater_getSetting('testmode') == 'on');
    $debug = (enomPricingUpdater_getSetting('debug') == 'on');
    $profit = enomPricingUpdater_getSetting('profit');
    $discount = enomPricingUpdater_getSetting('multiDiscount');
    $minPrice = enomPricingUpdater_getSetting('minPrice');
    $rounding = 100 / (enomPricingUpdater_getSetting('rounding'));
    $onlyEnom = (enomPricingUpdater_getSetting('onlyEnom') == 'on');

    // Convert input to numeric
    $rounding = preg_replace("/[^0-9.]/", "", $rounding);
    $discount = preg_replace("/[^0-9.]/", "", $discount);
    $minPrice = preg_replace("/[^0-9.]/", "", $minPrice);

    if (!isset($rounding) || $rounding < 0 || $rounding > 100 || !is_numeric($rounding)) $rounding = 4;
    if (!isset($profit) || !is_numeric($profit)) $profit = 50;
    if (!isset($minPrice) || $minPrice < 0 || !is_numeric($minPrice)) $minPrice = 0.01;

    // Update internal domain list
    enomPricingUpdater_updateDomainList();

    // Get available domains from WHMCS
    $domains = Capsule::table('tbldomainpricing');
    if (isset($extensions)) $domains->whereIn('extension', $extensions);
    if ($onlyEnom) $domains = $domains->where('autoreg', 'enom');

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
            $returned[$type][$duration] = (($price * (1 + $profit / 100 - $discount * ($duration - 1) / 100))  / $rates['USD']->rate) * $duration;
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

    if (enomPricingUpdater_getSetting('debug')) logModuleCall('enomPricingUpdater', 'Fetch prices', print_r($parsed, true), '', '', []);


    $username = Capsule::table('tbladdonmodules')
        ->where([['module', 'enomPricingUpdater'], ['setting', 'username']])
        ->first()->value;

    $apiKey = Capsule::table('tbladdonmodules')
        ->where([['module', 'enomPricingUpdater'], ['setting', 'apikey']])
        ->first()->value;

    $domains = Capsule::table('tbldomainpricing');
    if (count($parsed) > 0) $domains = $domains->whereIn('extension', $parsed);
    $domains = $domains->get();

    if (enomPricingUpdater_getSetting('debug')) logModuleCall('enomPricingUpdater', 'fetch prices', print_r($domains, true), '', '', []);


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
            $args = ['tld' => $tld, 'type' => $foo, 'years' => 1];
            $generalInfo = enomPricingUpdater_getEnomPrice($args, $username, $apiKey, true);

            if (enomPricingUpdater_getSetting('debug')) logModuleCall('enomPricingUpdater', 'fetch eNom price', print_r($args, true), print_r($generalInfo, true), '', []);

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

    $requestResult = enomPricingUpdater_performRequest($requestUrl, $apiKey);

    if (enomPricingUpdater_getSetting('debug')) logModuleCall('enomPricingUpdater', 'getEnomPrice_requestResult', $requestUrl, print_r($requestResult, true), '', [$apiKey]);

    if (!$requestResult) return false;


    if (!$getResult) return $requestResult->productprice->price;

    else return $requestResult;
}


//<interface-response>
//    <productprice>
//        <price>9.45</price>
//        <productenabled>True</productenabled>
//    </productprice>
//    <Command>PE_GETPRODUCTPRICE</Command>
//    <APIType>API</APIType>
//    <Language>eng</Language>
//    <ErrCount>0</ErrCount>
//    <ResponseCount>0</ResponseCount>
//    <MinPeriod>1</MinPeriod>
//    <MaxPeriod>10</MaxPeriod>
//    <Server>SJL0VWAPI05</Server>
//    <Site>eNom</Site>
//    <IsLockable>True</IsLockable>
//    <IsRealTimeTLD>True</IsRealTimeTLD>
//    <TimeDifference>+08.00</TimeDifference>
//    <ExecTime>0.000</ExecTime>
//    <Done>true</Done>
//    <TrackingKey>b57a7bd0-7283-44a0-8d47-d801895ffeb8</TrackingKey>
//    <RequestDateTime>6/16/2017 12:08:27 PM</RequestDateTime>
//    <debug><![CDATA[]]></debug>
//</interface-response>


/**
 * @param $url String URL to request
 * @param $apiKey String eNom API key
 * @return SimpleXMLElement
 * @throws Exception on curl error
 */
function enomPricingUpdater_performRequest($url, $apiKey)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, false);

    $xml = curl_exec($ch);

    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (enomPricingUpdater_getSetting('debug')) logModuleCall('enomPricingUpdater', 'cURL: fetch prices', $url, print_r($xml, true), '', [$apiKey]);

    if ($xml === false) {
        throw new Exception(curl_error($ch));
    }

    if ($http_status != 200) {
        throw new Exception("HTTP status code while fetching prices: $http_status");
    }


    return simplexml_load_string($xml);
}

/**
 * Checks sales for expiration dates and disables them once they expire
 */
function enomPricingUpdater_checkPromos()
{
    try {
        $expired = Capsule::table('mod_enomupdater_promos')->where('expires', '<', Capsule::RAW('CURRENT_TIMESTAMP'))->get();

        foreach ($expired as $domain) {
            $arr = ['domain' => $domain->extension, 'type' => $domain->type, 'years' => $domain->years];
            enomPricingUpdater_deletePromo($arr);
        }

        $logData = [
            'expired' => $expired
        ];

        if (count($expired) > 0) logModuleCall('eNom pricing updater', 'checkPromos', '', print_r($logData, true), '', []);
    } catch (Exception $ex) {
        logModuleCall('eNom pricing updater', 'checkPromos Error', '', $ex->getMessage(), '', []);
    }
}

/**
 * Check to see if a new version is available on GitHub
 * Only checks for stable releases.
 * Displays a message with download link if new version is found
 * @deprecated still uses old GitHub repository
 */
function enomPricingUpdater_checkUpdates()
{
    $checkBeta = (enomPricingUpdater_getSetting('checkBeta') == 'on');

    $url = $checkBeta
        ? 'https://api.github.com/repos/ducohosting/whmcs-enom-updater/releases'
        : 'https://api.github.com/repos/ducohosting/whmcs-enom-updater/releases/latest';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, 'WHMCS eNom pricing update module by Duco Hosting');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = json_decode(curl_exec($ch));
    curl_close($ch);

    if ($checkBeta) {
        $response = $response[0];
    }
    $latestVersion = ltrim($response->tag_name, 'v');
    $currentVersion = enomPricingUpdater_getSetting('version');

    logModuleCall('eNom pricing updater', 'Update check', $currentVersion, print_r($response, true), '', []);

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

/**
 * Get a list of all module settings
 * @return array|static[] settings
 */
function enomPricingUpdater_getSettings()
{
    return Capsule::table('tbladdonmodules')->where('module', 'enomPricingUpdater')->get();
}

/**
 * Get a specific module setting
 * @param $setting string setting name
 * @return string setting value
 */
function enomPricingUpdater_getSetting($setting)
{
    return Capsule::table('tbladdonmodules')->where('module', 'enomPricingUpdater')->where('setting', $setting)->first()->value;
}
