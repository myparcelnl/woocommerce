# Contributing

## Prerequisites

- [Docker]
- [Node] 24.21 or later
- [pnpm] 12.6 or later
- No Volta on your machine. Its `yarn` does not go through corepack, so the `packageManager` field in `package.json`
  does not stop it.

## Steps

### Install dependencies

Install composer dependencies with Docker:

```shell
docker compose up php
```

Install pnpm dependencies:

```shell
pnpm install
```

### Build the plugin

```shell
pnpm build
```

This will build the plugin and output a version for each platform to the `dist` folder.

### Make your changes

### Troubleshooting Plugin Loading Issues

If you run into issues where the plugin settings or other screens do not render, you may resolve these by running the following command:

```shell
pnpm pdk-builder translations
```

Follow our [Developer Guide for contributing to MyParcel repositories].

### Test your changes

#### Automated tests

You should always run the automated tests.

To run the frontend tests:

```shell
pnpm test:run
```

To run the PHP tests:

```shell
docker compose run php composer test
```

These will also be run automatically on GitHub when you create a pull request and must pass before your changes can be merged.

Update snapshots:

```shell
docker compose run php composer test:snapshots
```

#### Manual testing

##### Using a local WordPress instance

This is only sufficient if you're running WordPress locally and your source directory is inside your `wp-content/plugins` folder. If this is not the case, continue to [the next section](#using-a-remote-wordpress-instance).

Run this after every change:

```shell
pnpm build
```

Or run this to monitor your changes and rebuild automatically:

```shell
pnpm watch
```

##### Using a remote WordPress instance

Build plugin files:

```shell
pnpm build
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
[pnpm]: https://pnpm.io/
[node]: https://nodejs.org/
