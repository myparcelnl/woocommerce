/**
 * Get the highest shipping class by doing a call to WordPress. We're getting it this way and not from the
 *  highest_shipping_class input because that causes some kind of timing issue which makes the delivery options not
 *  show up.
 *
 * @returns {String|null}
 */
export function getHighestShippingClass() {
  let shippingClass = null;

  jQuery.ajax({
    type: 'POST',
    url: wcmp.ajax_url,
    async: false,
    data: {
      action: 'get_highest_shipping_class',
    },
    success(data) {
      shippingClass = data;
    },
  });

  return shippingClass;
}