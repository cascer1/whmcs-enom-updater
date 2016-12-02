# WHMCS eNom price sync
This module automatically gets your current pricing levels from eNom and updates your WHMCS domain prices accordingly.  
It supports registration and renewal periods of up to 3 years, but this can be easily extended if required.

You can set up your desired profit margin as a percentage, which will be taken into account when calculating the final domain prices.

## Installation
Upload `modules/addons/enomPricingUpdater` to `whmcs/modules/addons/` (be sure to create a new directory for it)

## Usage
This module supports a manual and automatic mode.

The manual mode can be executed by visiting the addon page in the WHMCS admin area (addonmodules.php?module=enomPricingUpdater). On this page you can choose to update all domain extensions, or just a specific set of extensions.

The automatic mode runs after every daily cron job, but before the database backup. This mode only runs if it's enabled in the module settings.

**IMPORTANT:** You MUST have USD configured as one of the currencies in WHMCS, this is used for conversion from eNom prices. The adodn module has not been tested without this currency configured.

## Features

* Supports automatic and manual mode
* Can update all domains at once, or only specific domains
* Supports all currencies defined in WHMCS
* Automatically acquires current domain pricing from eNom
* Supports registrations and renewals for up to 10 years
* Automatically disables registration/renewal terms not supported by eNom (e.g: .CO domains can only be registered/renewed for up to 5 years at a time)
* Supports domain promotions: You can enter a promo price and expiry and the module will make sure to set the correct pricing and restore pricing after expiry

## Compatibility
This addon module has been tested on WHMCS version 7.1.0 RC 1 using PHP 7. compatibility with other WHMCS or PHP versions is not guaranteed.

## Contributing
Please read [CONTRIBUTING.md](https://github.com/ducohosting/whmcs-enom-updater/blob/master/.github/CONTRIBUTING.md)

## License

WHMCS eNom price sync addon module  
Copyright &copy; 2016  Duco Hosting  

This program is free software: you can redistribute it and/or modify  
it under the terms of the GNU General Public License as published by  
the Free Software Foundation, either version 3 of the License, or  
(at your option) any later version.  

This program is distributed in the hope that it will be useful,  
but WITHOUT ANY WARRANTY; without even the implied warranty of  
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the  
GNU General Public License for more details.  

You should have received a copy of the GNU General Public License  
along with this program.  If not, see <http://www.gnu.org/licenses/>.  
