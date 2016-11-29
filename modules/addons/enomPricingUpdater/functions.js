//noinspection JSUnusedGlobalSymbols
function toggleDomainSale(tld) {
    var enabled = $('#chkSaleEnabled' + tld).is(':checked');
    var datEndSale = $('#datSaleEnd' + tld);
    var numSalePrice = $('#numSalePrice' + tld);

    if (enabled === true) {
        datEndSale.show();
        numSalePrice.show();
    } else {
        datEndSale.hide();
        numSalePrice.hide();

        datEndSale.val(null);
        numSalePrice.val(null);
    }
}
