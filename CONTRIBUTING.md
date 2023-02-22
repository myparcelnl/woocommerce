# Contributing

## Prerequisites

- [Docker]

## Steps

### Build and run the image

```shell
docker build -t myparcelnl/woocommerce:dev --target=dev .
docker run --rm myparcelnl/woocommerce:dev
```

This will install Node and Composer dependencies and build the plugin. The compiled plugins will be available as folders and zip files in the `dist` folder.

### Make your changes

- Please try to conform to our existing code style.
- For frontend code, we use [ESLint] to format the code. Make sure it's enabled in your editor.

### Test your changes

**Easiest method**

This is only sufficient if you're running WordPress locally and your source directory is inside your `wp-content` folder. If this is not the case, continue to the next section.

Run this after every change:

```shell
docker run -it --rm -v $(pwd):/app myparcelnl/woocommerce
```

Or run this to monitor your changes and rebuild automatically:

```shell
docker run -it --rm -v $(pwd):/app myparcelnl/woocommerce yarn serve
```

**If your WordPress instance is hosted somewhere else**

Build zip files:

```shell
yarn build
```

Then upload this file on the plugins page of your WordPress website to install the plugin.

You can also upload the plugin folder manually.

> Note: We don't recommend uploading the whole source folder to your website's `wp-content` folder, but it does work. A better solution is to extract the created .zip file and upload its contents to your website.

### Make a pull request

- Make sure your code is formatted correctly.
- Make sure your code is tested.
- Make sure your code is documented if necessary.
- Conform to [conventional commits] standards.

[conventional commits]: https://www.conventionalcommits.org/
[docker]: https://www.docker.com/
[eslint]: https://eslint.org/
