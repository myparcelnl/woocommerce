# address-widget

This integrates the address widget with the plugin.

It does three things:

1. Hides the vanilla inputs on the checkout page, currently depending on the country and only if the address country is the Netherlands.
2. Loads the address widget script in a placeholder element
3. Syncs any selected address to the vanilla input fields as well as writing the selected address object to a hidden input for processing by PHP
