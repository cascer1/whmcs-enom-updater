function toggleDomainSale(tld) {
  var enabled = $('#chkSaleEnabled' + tld).is(':checked');

  if(enabled === true) {
    $('#datSaleEnd' + tld).show();
    $('#numSalePrice' + tld).show();
  } else {
    var d = new Date();
    $('#datSaleEnd' + tld).hide();
    $('#numSalePrice' + tld).hide();

    $('#datSaleEnd' + tld).val(null);
    $('#numSalePrice' + tld).val(null);
  }
}
