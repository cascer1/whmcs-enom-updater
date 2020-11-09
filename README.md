[![Codacy Badge](https://api.codacy.com/project/badge/Grade/cc8a6ea7e3c24735827bcaa416c08ac0)](https://www.codacy.com/app/cas-eliens/whmcs-enom-updater?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=ducohosting/whmcs-enom-updater&amp;utm_campaign=Badge_Grade)

## Archived
This project is no longer actively developed. New issues and pull requests may not be reviewed.

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

**IMPORTANT:** You MUST have USD configured as one of the currencies in WHMCS, this is used for conversion from eNom prices. The addon module has not been tested without this currency configured.

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

MIT License

Copyright (c) 2016 Cas Eliëns

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
