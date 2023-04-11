export const fields = (): void => {
  /* Hide custom NL fields by default when country not NL */
  const billingCountry = $('#billing_country').val();
  const shippingCountry = $('#shipping_country').val();

  if (billingCountry !== 'GB') {
    $('#billing_eori_field').hide();
  }

  if (shippingCountry !== 'GB') {
    $('#shipping_eori_field').hide();
  }

  const required = ` <abbr class="required" title="${woocommerce_params.i18n_required_text}">*</abbr>`;

  $('body')
    .on('country_to_state_changing', (event, country, wrapper) => {
      const form = wrapper;
      const $eorifield = form.find('#billing_eori_field, #shipping_eori_field');

      if (country === 'UK') {
        $eorifield.show();

        /* Mark required fields */
        if ($eorifield.find('label abbr').size() === 0) {
          $eorifield.find('label').append(required);
        }

        /* Add validation required classes */
        $eorifield.addClass('validate-required');
      } else {
        /* Hide custom NL fields */
        $eorifield.hide();

        /* Unmark required fields */
        $eorifield.find('label abbr').remove();

        /* Remove validation required classes */
        $eorifield.removeClass('validate-required');
      }
    })

    .on('init_checkout', () => {
      $('#billing_country, #shipping_country, .country_to_state').trigger('change');
    });

  /* Update on page load */
  if (woocommerce_params.is_checkout === 1) {
    $('body').trigger('init_checkout');
  }
};
