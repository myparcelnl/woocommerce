
# WooCommerce MyParcel
Welcome to the WooCommerce MyParcel repository on GitHub. Here you can browse the source, look at open issues and keep track of development.

This WooCommerce extension allows you to export your orders to the MyParcel [Backoffice]. Single orders exports as well as batch exports are possible.

> :warning: **Note**: A MyParcel API key is required for this plugin. You can [generate one](https://myparcelnl.github.io/woocommerce/#2_A:~:text=API%20instellingen) in your [Backoffice].

* [Main features](#main-features)
* [Manual](#manual)
* [Installation](#installation)
* [Contributing](#contributing)
    * [Making JavaScript or CSS changes](#making-javascript-or-css-changes)

## Main features
- [Delivery options] integrated in your checkout
- Export your WooCommerce orders to MyParcel with a simple click, single orders or in batch
- Print shipping labels directly (PDF)
- Create multiple shipments for the same order
- Choose your package type (Parcel, digital stamp, mailbox package)
- Define default MyParcel shipping options (signature, only recipient, insurance, etc.)
- Modify the MyParcel shipping options per order before exporting
- Optional separate street name and house number in checkout for more precise address data
- View the status of the shipment in the order details page
- Add Track & Trace URL to the order confirmation email

## Manual
[Plugin Manual](https://myparcel.github.io/woocommerce)

## Installation
You can download the .zip file of the latest release from here: [Latest] ![GitHub release](https://img.shields.io/github/v/release/myparcelnl/woocommerce)

Or install it on your website from the [WordPress plugin repository].

## Contributing
- Clone or download the source code
- If you're planning to change JavaScript or CSS code, see below section for details.
- Make your changes
- Create a pull request!

### Making JavaScript or CSS changes
1. Set up Node if you haven't already: https://nodejs.org/
2. Install npm packages
    ```shell script
    $ npm i
    ```
2. Make your changes
   * Optional: Run the following command to rebuild assets on every code change:
   ```shell script
   $ gulp watch
   ```
   > :warning:️ **Note**: When building with `gulp watch` assets are **not** run through Babel. This type of build will not be compatible with older browsers.
                                                                                                               
3. Test your changes
    * Locally, if the source directory is inside your `<wordpress>/wp-content` folder.
        ```shell script
        $ gulp
        ```
    * By uploading a `.zip` file

        This builds all assets and puts all necessary files into `woocommerce-myparcel.zip`. Upload this file to your WordPress website to install the plugin.
        ```shell script
        $ gulp zip
        ```
    * By uploading the source folder
        
        We don't recommend uploading the whole source folder to where your website is hosted, but it does work. Run the following command and copy the whole plugin folder to your website's `wp-content` folder.
        ```shell script
        $ gulp
        ```
        A better solution is to follow the instructions for installation using a `.zip` file, then extracting the zip yourself and uploading its contents to your website's `wp-content` folder.

[Delivery options]: https://github.com/myparcelnl/delivery-options
[Backoffice]: https://backoffice.myparcel.nl/
[Latest]: https://github.com/myparcelnl/woocommerce/releases/latest
[WordPress plugin repository]: https://wordpress.org/plugins/woocommerce-myparcel/
