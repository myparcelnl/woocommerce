/**
 * Add listeners to the address fields, remove them before adding new ones if they already exist, then update
 *  shipping method and delivery options if needed.
 *
 * Uses the country field's parent row, because there is no better way to catch the select2 (or selectWoo) events as
 *  we never know when the select is loaded and can't add a normal change event.
 */

export {};

// export const addAddressFieldListeners = (): void => {
//   const fields = [FIELD_COUNTRY, FIELD_POSTCODE, hasSplitAddressFields() ? FIELD_HOUSE_NUMBER : FIELD_ADDRESS];
//
//   const checkout = useCheckoutStore();
//
//   if (checkout.state.addressType) {
//     fields.forEach((field) => {
//       getAddressField(field)?.removeEventListener(EVENT_CHANGE, updateAddress);
//     });
//   }
//
//   fields.forEach((field) => {
//     const resolvedField = getAddressField(field);
//
//     console.log(resolvedField);
//
//     resolvedField?.addEventListener(EVENT_CHANGE, updateAddress);
//   });
// };
