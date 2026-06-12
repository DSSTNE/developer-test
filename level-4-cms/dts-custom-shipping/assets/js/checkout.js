(function ($) {
  'use strict';

  function loadWarehouses(cityRef) {
    var $branch = $('#dts_delivery_branch_ref');
    if (!$branch.length) return;

    $branch.prop('disabled', true).html('<option value="">' + dtsShipping.selectBranch + '</option>');

    if (!cityRef) return;

    $.get(dtsShipping.ajaxUrl, {
      action: 'dts_np_warehouses',
      nonce: dtsShipping.nonce,
      city_ref: cityRef
    }).done(function (res) {
      if (!res.success || !Array.isArray(res.data)) return;
      res.data.forEach(function (item) {
        $branch.append($('<option>', { value: item.ref, text: item.name }));
      });
    }).always(function () {
      $branch.prop('disabled', false);
    });
  }

  $(document.body).on('change', '#dts_delivery_city_ref', function () {
    var $opt = $(this).find('option:selected');
    $('#dts_delivery_city').val($opt.text());
    loadWarehouses($(this).val());
  });

  $(document.body).on('change', '#dts_delivery_branch_ref', function () {
    var $opt = $(this).find('option:selected');
    $('#dts_delivery_branch').val($opt.text());
  });

  $(document.body).on('updated_checkout', function () {
    var cityRef = $('#dts_delivery_city_ref').val();
    if (cityRef) {
      loadWarehouses(cityRef);
    }
  });
})(jQuery);
