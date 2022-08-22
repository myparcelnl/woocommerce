# Contributing

1. Clone or download the [source code]
2. If you're planning to change JavaScript or CSS code, see below section for
   details.
3. Make your changes
4. Create a pull request!

## Prerequisites

* [Docker]
* [Node 16]
* [Yarn]

## Steps

### Install dependencies

Install npm dependencies

```shell
yarn
```

### Make your changes

* Please try to conform to our existing code style.

### Test your changes

**Easiest method**

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

**If your WordPress instance is hosted somewhere else**

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

[Docker]: https://www.docker.com/
[Node 16]: https://nodejs.org/en/
[Yarn]: https://classic.yarnpkg.com/en/docs/install
