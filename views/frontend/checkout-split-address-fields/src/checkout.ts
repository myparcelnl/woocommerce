/* eslint-disable @typescript-eslint/naming-convention */

jQuery(document).ready(($) => {
  /* Hide custom NL fields by default when country not NL */
  const billingCountry = $('#billing_country').val();
  const shippingCountry = $('#shipping_country').val();

  if (billingCountry != 'NL') {
    $('#billing_street_name_field').hide();
    $('#billing_house_number_field').hide();
    $('#billing_house_number_suffix_field').hide();
  }

  if (shippingCountry != 'NL') {
    $('#shipping_street_name_field').hide();
    $('#shipping_house_number_field').hide();
    $('#shipping_house_number_suffix_field').hide();
  }

  /* Localisation */
  const localeJson = woocommerce_params.locale.replace(/&quot;/g, '"');
  const locale = $.parseJSON(localeJson);
  const required = ` <abbr class="required" title="${woocommerce_params.i18n_required_text}">*</abbr>`;

  $('body')
    /* Handle locale */
    .bind('country_to_state_changing', (event, country, wrapper) => {
      let thislocale;
      const thisform = wrapper;
      const $postcodefield = thisform.find('#billing_postcode_field, #shipping_postcode_field');
      const $cityfield = thisform.find('#billing_city_field, #shipping_city_field');
      const $address1field = thisform.find('#billing_address_1_field, #shipping_address_1_field');
      const $address2field = thisform.find('#billing_address_2_field, #shipping_address_2_field');
      const $streetfield = thisform.find('#billing_street_name_field, #shipping_street_name_field');
      const $numberfield = thisform.find('#billing_house_number_field, #shipping_house_number_field');
      const $suffixfield = thisform.find('#billing_house_number_suffix_field, #shipping_house_number_suffix_field');

      if (country == 'NL') {
        /* show custom NL fields */
        $streetfield.show();
        $numberfield.show();
        $suffixfield.show();

        /* Hide regular address classes */
        $address1field.find('label abbr').remove();
        $address1field.hide();
        $address2field.hide();

        /* Place postcode & city on one line */
        $postcodefield.removeClass('form-row-wide').addClass('form-row-first');
        $cityfield.removeClass('form-row-wide').addClass('form-row-last');

        /* Mark required fields */
        if ($streetfield.find('label abbr').size() == 0) {
          $streetfield.find('label').append(required);
        }

        if ($numberfield.find('label abbr').size() == 0) {
          $numberfield.find('label').append(required);
        }

        /* Add validation required classes */
        $streetfield.addClass('validate-required');
        $numberfield.addClass('validate-required');
      } else {
        /* Hide custom NL fields */
        $streetfield.hide();
        $numberfield.hide();
        $suffixfield.hide();

        /* Unmark required fields */
        $streetfield.find('label abbr').remove();
        $numberfield.find('label abbr').remove();

        /* Remove validation required classes */
        $streetfield.removeClass('validate-required');
        $numberfield.removeClass('validate-required');
      }

      if (typeof locale[country] != 'undefined') {
        thislocale = locale[country];
      } else {
        thislocale = locale.default;
      }

      /* Handle locale fields */
      const locale_fields = {
        address_1: '#billing_address_1_field, #shipping_address_1_field',
        address_2: '#billing_address_2_field, #shipping_address_2_field',
      };

      $.each(locale_fields, function (key, value) {
        const field = thisform.find(value);

        if (thislocale[key]) {
          if (thislocale[key].label) {
            field.find('label').html(thislocale[key].label);
          }

          if (thislocale[key].placeholder) {
            field.find('input').attr('placeholder', thislocale[key].placeholder);
          }

          field.find('label abbr').remove();

          if (typeof thislocale[key].required == 'undefined' && locale.default[key].required == true) {
            field.find('label').append(required);
          } else if (thislocale[key].required == true) {
            field.find('label').append(required);
          }

          if (key !== 'state') {
            if (thislocale[key].hidden == true) {
              field.hide().find('input').val('');
            } else {
              field.show();
            }
          }
        } else if (locale.default[key]) {
          if (locale.default[key].required == true) {
            if (field.find('label abbr').size() == 0) {
              field.find('label').append(required);
            }
          }

          if (key !== 'state' &&
            (typeof locale.default[key].hidden == 'undefined' || locale.default[key].hidden == false)
          ) {
            field.show();
          }
        }
      });
    })

    /* Init trigger */
    .bind('init_checkout', function () {
      $('#billing_country, #shipping_country, .country_to_state').change();
    });

  /* Update on page load */
  if (woocommerce_params.is_checkout == 1) {
    $('body').trigger('init_checkout');
  }
});
