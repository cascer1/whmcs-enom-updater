/*
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

//noinspection JSUnusedGlobalSymbols
function toggleDomainSale(tld) {
    var enabled = $('#chkSaleEnabled' + tld).is(':checked');
    var datEndSale = $('#datSaleEnd' + tld);
    var numRegPrice = $('#numRegPrice' + tld);
    var numTraPrice = $('#numTraPrice' + tld);

    if (enabled === true) {
        datEndSale.show();
        numRegPrice.show();
        numTraPrice.show();
    } else {
        datEndSale.hide();
        numRegPrice.hide();
        numTraPrice.hide();

        datEndSale.val(null);
        numRegPrice.val(null);
        numTraPrice.val(null);
    }
}
