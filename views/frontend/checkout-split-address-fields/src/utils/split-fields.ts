export const splitFields = (): void => {
  /* Hide custom NL fields by default when country not NL */
  const billingCountry = $('#billing_country').val();
  const shippingCountry = $('#shipping_country').val();

  if (billingCountry !== 'NL') {
    $('#billing_street_name_field').hide();
    $('#billing_house_number_field').hide();
    $('#billing_house_number_suffix_field').hide();
  }

  if (shippingCountry !== 'NL') {
    $('#shipping_street_name_field').hide();
    $('#shipping_house_number_field').hide();
    $('#shipping_house_number_suffix_field').hide();
  }

  /* Localisation */
  const localeJson = woocommerce_params.locale.replace(/&quot;/g, '"');
  const locale = JSON.parse(localeJson);
  const required = ` <abbr class="required" title="${woocommerce_params.i18n_required_text}">*</abbr>`;

  $('body')
    .on('country_to_state_changing', (event, country, wrapper) => {
      let thisLocale = {};
      const form = wrapper;
      const $postcodefield = form.find('#billing_postcode_field, #shipping_postcode_field');
      const $cityfield = form.find('#billing_city_field, #shipping_city_field');
      const $address1field = form.find('#billing_address_1_field, #shipping_address_1_field');
      const $address2field = form.find('#billing_address_2_field, #shipping_address_2_field');
      const $streetfield = form.find('#billing_street_name_field, #shipping_street_name_field');
      const $numberfield = form.find('#billing_house_number_field, #shipping_house_number_field');
      const $suffixfield = form.find('#billing_house_number_suffix_field, #shipping_house_number_suffix_field');

      if (country === 'NL') {
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
        if ($streetfield.find('label abbr').size() === 0) {
          $streetfield.find('label').append(required);
        }

        if ($numberfield.find('label abbr').size() === 0) {
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

      if (typeof locale[country] === 'undefined') {
        thisLocale = locale.default;
      } else {
        thisLocale = locale[country];
      }

      /* Handle locale fields */
      const localeFields = {
        address_1: '#billing_address_1_field, #shipping_address_1_field',
        address_2: '#billing_address_2_field, #shipping_address_2_field',
      };

      $.each(localeFields, (key, value): void => {
        const field = form.find(value);

        if (thisLocale[key]) {
          if (thisLocale[key].label) {
            field.find('label').html(thisLocale[key].label);
          }

          if (thisLocale[key].placeholder) {
            field.find('input').attr('placeholder', thisLocale[key].placeholder);
          }

          field.find('label abbr').remove();

          if (typeof thisLocale[key].required == 'undefined' && locale.default[key].required == true) {
            field.find('label').append(required);
          } else if (thisLocale[key].required == true) {
            field.find('label').append(required);
          }

          if (key !== 'state') {
            if (thisLocale[key].hidden == true) {
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

          if (
            key !== 'state' &&
            (typeof locale.default[key].hidden == 'undefined' || locale.default[key].hidden == false)
          ) {
            field.show();
          }
        }
      });
    })

    .on('init_checkout', () => {
      $('#billing_country, #shipping_country, .country_to_state').trigger('change');
    });

  /* Update on page load */
  if (woocommerce_params.is_checkout === 1) {
    $('body').trigger('init_checkout');
  }
};
