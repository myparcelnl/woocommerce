import {BILLING_ID, SHIPPING_ID} from './init';

// export const initialize = (): void => {
//     // Get the forms
//     const forms = getForms();

//     // Watch for changes to the "is-editing" class on the forms
//     forms.forEach((form) => {
//         // Check if already editing
//         if (form.classList.contains('is-editing')) {
//             // Initialize the address widget

//             return;
//         }
//         const observer = new MutationObserver((mutations) => {
//             mutations.forEach((mutation) => {
//                 if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
//                     const target = mutation.target as HTMLElement;
//                     if (target.classList.contains('is-editing')) {
//                         // Initialize the address widget
//                         initialize();
//                     }
//                 }
//             });
//         });

//         observer.observe(form, { attributes: true });
//     }
//     );
// };
export const waitForElement = () => {
  // Watch 'data-block-name=woocommerce/checkout-shipping-address-block' and 'data-block-name=woocommerce/checkout-billing-address-block' and log a message when removed
  //   return new Promise((resolve) => {
  const observer = new MutationObserver((mutationList, observer) => {
    for (const mutation of mutationList) {
      console.log({mutation});
    }
  });
  const element = document.querySelector('[data-block-name="woocommerce/checkout-billing-address-block"]');
  console.log(element);

  if (element) {
    observer.observe(element);
  }
  //   });
};

export const getForms = (): Element[] => {
  const forms = [];
  forms.push(document.querySelector(`#billing`));
  forms.push(document.querySelector(`#shipping`));

  return forms.filter((form) => form !== null);
};

export const createPlaceholders = (): void => {
  waitForElement();
  const forms = getForms();

  if (!forms.length) {
    console.warn('No wrappers found for the address widget, was the address block removed from the blocks?');
  }

  console.log(forms);

  forms.forEach((form) => {
    const element = document.createElement('div');
    element.id = form.id === 'billing' ? BILLING_ID : SHIPPING_ID;
    form.appendChild(element);
  });
};
