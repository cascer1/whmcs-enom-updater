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

require_once("../vendor/autoload.php");

/**
 * UPGRADE TO 2.1.0 ALPHA 2
 *
 * * Internal tables now reference tbldomainpricing for easier deletion
 */

require_once("upgradeFunctions.php");


// STEP 0: Remove temporary tables of previous upgrade
Capsule::schema()->dropIfExists('mod_enomupdater_prices_new');
Capsule::schema()->dropIfExists('mod_enomupdater_enomprices_new');
Capsule::schema()->dropIfExists('mod_enomupdater_promos_new');
Capsule::schema()->dropIfExists('mod_enomupdater_extensions_new');

// STEP 1: Create temporary table for extensions
try {
    Capsule::schema()->create('mod_enomupdater_extensions_new', function (Illuminate\Database\Schema\Blueprint $table) {
        $table->engine = 'InnoDB';
        $table->integer('relid', 10)->references('id')->on('tbldomainpricing')->onDelete('cascade');
        $table->string('extension');//->unique();
        $table->string('group')->default('none');
//        $table->primary(['relid']);
//        $table->foreign('relid')->references('id')->on('tbldomainpricing')->onDelete('cascade');
    });
} catch (\Illuminate\Database\QueryException $ex) {
    Capsule::schema()->dropIfExists('mod_enomupdater_extensions_new');
    echo "<pre>";
    print_r([
        "SQL" => $ex->getSql(),
        "message" => $ex->getMessage(),
        "code" => $ex->getCode()
    ]);
    echo "</pre>";
    throw new Exception("There was an error upgrading to version 2.1.0 Alpha 2 in step 1. Please contact the module developer.", 1, $ex);
}

// STEP 2: Get WHMCS domain ID's (relid)

$whmcsDomainsDB = Capsule::table('tbldomainpricing')->get();
$whmcsDomains = [];

foreach ($whmcsDomainsDB as $whmcsDomain) {
    $whmcsDomains[$whmcsDomain->extension] = $whmcsDomain->id;
}

// STEP 3: Copy extensions to new format

$localPrices = Capsule::table('mod_enomupdater_extensions')->get();

foreach ($localPrices as $d) {
    $id = $whmcsDomains[$d->extension];
    $extension = $d->extension;
    $group = $d->group;

    $domain = [
        'relid' => $id,
        'extension' => $extension,
        'group' => $group
    ];

    Capsule::table('mod_enomupdater_extensions_new')->insert($domain);
}

// STEP 4: Validate new extensions table

compareTableRowCount("mod_enomupdater_extensions", "mod_enomupdater_extensions_new");

// STEP 5: Remove old extensions table

Capsule::schema()->dropIfExists('mod_enomupdater_extensions');

// STEP 6: Rename extensions table

Capsule::schema()->rename('mod_enomupdater_extensions_new', 'mod_enomupdater_extensions');

// STEP 7: Create temporary tables for prices, promos and enomprices

try {
    Capsule::schema()->create('mod_enomupdater_prices_new', function (Illuminate\Database\Schema\Blueprint $table) {
        $table->engine = 'InnoDB';
        $table->integer('relid', 10)->references('relid')->on('mod_enomupdater_extensions')->onDelete('cascade');
        $table->integer('currency', 10)->references('id')->on('tblcurrencies')->onDelete('cascade');
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
//        $table->foreign('relid')->references('relid')->on('mod_enomupdater_extensions')->onDelete('cascade');
//        $table->foreign('currency')->references('id')->on('tblcurrencies')->onDelete('cascade');
//        $table->primary(['relid', 'currency', 'type']);
    });

    Capsule::schema()->create('mod_enomupdater_enomprices_new', function (Illuminate\Database\Schema\Blueprint $table) {
        $table->engine = 'InnoDB';
        $table->integer('relid', 10);
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
        $table->foreign('relid')->references('relid')->on('mod_enomupdater_extensions')->onDelete('cascade');
        $table->primary(['relid', 'type']);
    });

    Capsule::schema()->create('mod_enomupdater_promos_new', function (Illuminate\Database\Schema\Blueprint $table) {
        $table->engine = 'InnoDB';
        $table->integer('relid', 10);
        $table->enum('type', ['domainregister', 'domainrenew', 'domaintransfer']);
        $table->smallInteger('years');
        $table->decimal('price', 10, 2);
        $table->date('expires');
        $table->foreign('relid')->references('relid')->on('mod_enomupdater_extensions')->onDelete('cascade');
        $table->unique(['relid', 'type', 'years']);
    });
} catch (\Illuminate\Database\QueryException $ex) {
    Capsule::schema()->dropIfExists('mod_enomupdater_prices_new');
    Capsule::schema()->dropIfExists('mod_enomupdater_enomprices_new');
    Capsule::schema()->dropIfExists('mod_enomupdater_promos_new');
    echo "<pre>";
    print_r([
        "SQL" => $ex->getSql(),
        "message" => $ex->getMessage(),
        "code" => $ex->getCode()
    ]);
    echo "</pre>";
    throw new Exception("There was an error upgrading to version 2.1.0 Alpha 2 in step 7. Please contact the module developer.", 7, $ex);
}

// STEP 8: Store eNom prices in new format

$localPrices = Capsule::table('mod_enomupdater_enomprices')->get();

foreach ($localPrices as $d) {
    $id = $whmcsDomains[$d->extension];

    $domain = [
        'relid' => $id,
        'type' => $d->type,
        'one' => $d->one,
        'two' => $d->two,
        'three' => $d->three,
        'four' => $d->four,
        'five' => $d->five,
        'six' => $d->six,
        'seven' => $d->seven,
        'eight' => $d->eight,
        'nine' => $d->nine,
        'ten' => $d->ten
    ];

    Capsule::table('mod_enomupdater_enomprices_new')->insert($domain);
}

// STEP 9: Store promos in new format

$localPromos = Capsule::table('mod_enomupdater_promos')->get();

foreach ($localPromos as $p) {
    $id = $whmcsDomains[$p->extension];

    $domain = [
        'relid' => $id,
        'type' => $p->type,
        'years' => $p->years,
        'price' => $p->price,
        'expires' => $p->expires
    ];

    Capsule::table('mod_enomupdater_promos_new')->insert($domain);
}

// STEP 10: Validate new enomprices and promos tables

compareTableRowCount("mod_enomupdater_enomprices", "mod_enomupdater_enomprices_new");
compareTableRowCount("mod_enomupdater_promos", "mod_enomupdater_promos_new");

// STEP 11: Remove old tables

Capsule::schema()->dropIfExists('mod_enomupdater_prices');
Capsule::schema()->dropIfExists('mod_enomupdater_promos');
Capsule::schema()->dropIfExists('mod_enomupdater_enomprices');

// STEP 12: Rename new tables

Capsule::schema()->rename('mod_enomupdater_prices_new', 'mod_enomupdater_prices');
Capsule::schema()->rename('mod_enomupdater_promos_new', 'mod_enomupdater_promos');
Capsule::schema()->rename('mod_enomupdater_enomprices_new', 'mod_enomupdater_enomprices');

// STEP 13:
