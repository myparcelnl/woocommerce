# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Project Overview

This is the MyParcel for WooCommerce plugin repository, a WordPress plugin that integrates WooCommerce with MyParcel shipping services. The plugin supports both MyParcel NL and BE platforms and provides delivery options, label printing, and order export functionality.

## Development Commands

### Environment Setup
```bash
# Install Composer dependencies with Docker
docker compose up php

# Install JavaScript/TypeScript dependencies
yarn install

# Install dependencies and prepare the development environment
yarn prepare
```

### Building the Plugin
```bash
# Build production assets for all platforms
yarn build

# Build development assets
yarn build:dev

# Build for specific frontend components
yarn build:js:dev:frontend    # Frontend components only
yarn build:js:dev:backend     # Backend admin components only
yarn build:js:dev:blocks      # WooCommerce blocks only

# Watch for changes and rebuild automatically
yarn watch                    # All components
yarn watch:frontend          # Frontend only
yarn watch:backend           # Backend only
yarn watch:blocks           # Blocks only
```

### Testing

#### Frontend Tests
```bash
# Run all frontend tests
yarn test:run

# Run tests with coverage
yarn test:coverage

# Watch mode for tests
yarn test

# Run tests for specific workspace
cd views/[workspace-name] && yarn test
```

#### PHP Tests
```bash
# Run PHP unit tests
docker compose run php composer test

# Run tests with coverage
docker compose run php composer test:coverage

# Update test snapshots
docker compose run php composer test:snapshots
```

### Code Quality

#### Linting and Formatting
```bash
# Lint and format markdown, JSON, YAML, CSS, SCSS files
yarn lint

# JavaScript/TypeScript linting is handled by lint-staged on commit
```

#### Static Analysis
```bash
# Run PHPStan analysis
yarn analyse                 # or composer analyse
docker compose run php composer analyse

# Generate new PHPStan baseline
yarn analyse:generate       # or composer analyse:generate
docker compose run php composer analyse:generate
```

### Development Workflow
```bash
# Full development setup
yarn install
docker compose up php
yarn build:dev

# Start watching for changes during development
yarn watch
```

## High-Level Architecture

### Plugin Structure

The codebase follows a hybrid architecture combining WordPress plugin conventions with modern PHP and JavaScript development practices:

#### PHP Backend (`src/`)
- **PDK Integration**: Built on MyParcel's Platform Development Kit (PDK) for cross-platform compatibility
- **Service Layer**: Services handle WordPress/WooCommerce integrations (`Service/`)
- **Repository Pattern**: Separate repositories for WooCommerce entities (`WooCommerce/Repository/`)
- **Contract Interfaces**: Clear contracts for all major services (`Contract/`)
- **Hook System**: WordPress hook management through dedicated service (`Hooks/`)
- **Migration System**: Database migrations for plugin updates (`Migration/`)
- **Facade Pattern**: Simplified interfaces for complex operations (`Facade/`)

#### Frontend (`views/`)
The frontend is organized as a monorepo with multiple workspaces:

- **Blocks** (`views/blocks/`): WooCommerce Gutenberg blocks
- **Backend** (`views/backend/`): WordPress admin interface components
- **Frontend** (`views/frontend/`): Customer-facing checkout components
- **Shared Configuration** (`views/vite-config/`, `views/webpack-config/`)

#### Key Architectural Patterns

1. **Multi-Platform Support**: Single codebase supports both MyParcel NL and BE through platform-specific builds
2. **Dependency Injection**: PDK container manages service dependencies
3. **Hook-Based Integration**: WordPress hooks are centrally managed through `WordPressHookService`
4. **Event-Driven Architecture**: Plugin lifecycle managed through WordPress action/filter hooks
5. **Modular Frontend**: Each frontend component is a separate workspace with its own build configuration

### Build System

- **NX**: Monorepo management and task orchestration
- **Vite**: Modern frontend build tool for development
- **Webpack**: Production builds for WordPress compatibility
- **PDK Builder**: Custom build system for multi-platform plugin distribution

### Testing Strategy

- **PHP**: Pest framework for unit testing with snapshot testing support
- **JavaScript**: Vitest for modern JavaScript/TypeScript testing
- **Static Analysis**: PHPStan with WordPress-specific rules
- **Multi-Workspace Testing**: Each frontend component has independent test suite

### Development Environment

- **Docker**: PHP development environment with WordPress/WooCommerce stubs
- **Volta**: Node.js version management (Node 20.15.1)
- **Yarn v4**: Package management with workspace support
- **Husky**: Git hooks for code quality enforcement

## Important Notes

### Prerequisites
- Docker (for PHP development)
- Node.js 20.15.1 (managed by Volta)
- Yarn 4.3.1

### Local Development
For local WordPress development, ensure the plugin directory is inside `wp-content/plugins/` and run `yarn build` after changes, or use `yarn watch` for automatic rebuilds.

### Platform Builds
The build system creates separate distributions for MyParcel NL (`woocommerce-myparcel`) and MyParcel BE (`wc-myparcel-belgium`) platforms.

### Third-Party Compatibility
Be aware that the plugin may have limited compatibility with third-party checkout solutions. Always test functionality when integrating with custom checkout implementations.