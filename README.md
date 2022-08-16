# WooCommerce MyParcel

[![GitHub release](https://img.shields.io/github/v/release/myparcelnl/woocommerce?logo=github)](https://github.com/myparcelnl/woocommerce/releases/latest)
[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/woocommerce-myparcel?logo=wordpress)](https://wordpress.org/plugins/woocommerce-myparcel/)

Welcome to the WooCommerce MyParcel repository on GitHub. Here you can browse
the source, look at open issues and keep
track of development.

This WooCommerce extension allows you to export your orders to the
MyParcel [Backoffice]. Single orders exports as well
as batch exports are possible.

> :warning: **Note**: A MyParcel API key is required for this plugin. You
> can [generate one] in your [Backoffice].

* [Main features](#main-features)
* [Manual](#manual)
* [Installation](#installation)
* [Contributing](#contributing)
    * [Making JavaScript or CSS changes](#making-javascript-or-css-changes)

## Main features

* [Delivery options] integrated in your checkout
* Export your WooCommerce orders to MyParcel with a simple click, single orders
  or in batch
* Print shipping labels directly (PDF)
* Create multiple shipments for the same order
* Choose your package type (Parcel, digital stamp, mailbox package)
* Define default MyParcel shipping options (signature, only recipient,
  insurance, etc.)
* Modify the MyParcel shipping options per order before exporting
* Optional separate street name and house number in checkout for more precise
  address data
* View the status of the shipment in the order details page
* Add Track & Trace URL to the order confirmation email

## Manual

[Plugin Manual]

## Installation

You can download the .zip file of the [latest release].

Or install it on your website from the [WordPress plugin repository].

## Contributing

* Clone or download the source code
* If you're planning to change JavaScript or CSS code, see below section for
  details.
* Make your changes
* Create a pull request!

### Making JavaScript or CSS changes

#### Prerequisites

* [Docker]
* [Node 16]
* [Yarn]

#### Steps

##### Install dependencies

Install npm dependencies

```shell
yarn
```

##### Make your changes

* Please try to conform to our existing code style.

##### Test your changes

###### Easiest method

This is only sufficient if you're running WordPress locally and your source
directory is inside your `wp-content` folder. If this is not the case, continue
to the next section.

Run this after every change:

```shell
yarn build
```

Or run this to monitor your changes and rebuild automatically:

```shell
yarn serve
```

###### If your WordPress instance is hosted somewhere else

First, build all assets and puts all necessary files
into `woocommerce-myparcel.zip`.

```shell
yarn build
```

Then upload this file on the plugins page of your WordPress website to install
the plugin.

You can also upload the plugin folder manually.

> Note: We don't recommend uploading the whole source folder to your website's
> `wp-content` folder, but it does work. A better solution is to extract the
> created .zip file and upload its contents to your website.

[Backoffice]: https://backoffice.myparcel.nl/
[Delivery options]: https://github.com/myparcelnl/delivery-options
[Node 16]: https://nodejs.org/en/
[Plugin manual]: https://developer.myparcel.nl/nl/documentatie/10.woocommerce/
[WordPress plugin repository]: https://wordpress.org/plugins/woocommerce-myparcel/
[Yarn]: https://classic.yarnpkg.com/en/docs/install
[generate one]: https://myparcelnl.github.io/woocommerce/#2_A
[latest release]: https://github.com/myparcelnl/woocommerce/releases/latest
