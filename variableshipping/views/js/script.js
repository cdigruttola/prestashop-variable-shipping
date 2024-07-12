/**
 * Copyright since 2024 Carmine Di Gruttola
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    cdigruttola <c.digruttola@hotmail.it>
 *  @copyright Copyright since 2007 Carmine Di Gruttola
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *
 *
 */
function customPrice() {
  var carrier = $('#delivery-option-select option:selected').val();
  var obj_custom_price = $('input[name=custom_price]');
  var total = $('.js-total-shipping-tax-inc');
  if (carrier == variableshipping_carrier_id) {
    obj_custom_price.closest('.form-group').show(100);
    total.closest('.form-group').hide(100);
  } else {
    obj_custom_price.closest('.form-group').hide(100);
    total.closest('.form-group').show(100);
  }
}

$(document).ready(function () {
  var select = $('#delivery-option-select');

  var observer = new MutationObserver(function (mutations) {
    mutations.forEach(function (mutation) {
      if (mutation.type === 'childList') {
        customPrice();
      }
    });
  });

  var config = {
    childList: true,
    subtree: true,
  };

  observer.observe(select[0], config);
  observer.disconnect();
  observer.observe(select[0], config);

  select.change(function (e) {
    e.preventDefault();
    customPrice();
  });

  $('#create-order-button').click(function (e) {
    var carrier = $('#delivery-option-select option:selected').val();
    var obj_custom_price = $('input[name=custom_price]');
    if (carrier == variableshipping_carrier_id) {
      obj_custom_price.val();
    }
  });
});
