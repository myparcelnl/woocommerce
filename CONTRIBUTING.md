# Contributing

## Prerequisites

- [Docker]
- [Volta] or [Node]

> If you don't want to use Volta, make sure to use the Node version in the `volta.node` key in [./package.json].

## Steps

### Install dependencies

Install composer dependencies with Docker:

```shell
docker compose up php
```

Install yarn dependencies:

```shell
yarn
```

### Build the plugin

```shell
yarn build
```

This will build the plugin and output a version for each platform to the `dist` folder.

### Make your changes

### In case of random error:

If you encounter a random error when loading the plugin, try running the following command:

```shell
yarn pdk-builder translations
```

Follow our [Developer Guide for contributing to MyParcel repositories].

### Test your changes

#### Automated tests

You should always run the automated tests.

To run the frontend tests:

```shell
yarn test:run
```

To run the PHP tests:

```shell
docker compose run php composer test
```

These will also be run automatically on GitHub when you create a pull request and must pass before your changes can be merged.

Update snapshots:

To run the PHP tests:

```shell
docker compose run php composer test:snapshots
```

#### Manual testing

##### Using a local WordPress instance

This is only sufficient if you're running WordPress locally and your source directory is inside your `wp-content/plugins` folder. If this is not the case, continue to [the next section](#using-a-remote-wordpress-instance).

Run this after every change:

```shell
yarn build
```

Or run this to monitor your changes and rebuild automatically:

```shell
yarn watch
```

##### Using a remote WordPress instance

Build plugin files:

```shell
yarn build
```

The folder structure should look like this:

```
dist
├── myparcelbe
└── myparcelnl
```

Now zip the plugin folder you want to use, then upload the zip file on the plugins page of your WordPress website to install it.

You can also upload the plugin folder manually using FTP.

[Developer Guide for contributing to MyParcel repositories ]: https://github.com/myparcelnl/developer/blob/main/DEVELOPERS.md#developer-guide-for-contributing-to-myparcel-repositories
[conventional commits]: https://www.conventionalcommits.org/
[docker]: https://www.docker.com/
[volta]: https://volta.sh/
[node]: https://nodejs.org/
