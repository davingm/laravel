# davingm/laravel

<a href="https://github.com/davingm/laravel"><img width="830" src="./docs/public/image.png" alt="davingm/laravel framework banner"></a>

<p align="center">
  <a href="https://deepwiki.com/davingm/laravel"><img src="./docs/public/deepwiki.svg" alt="Ask DeepWiki"></a>
  <a href="https://packagist.org/packages/davingm/laravel"><img src="https://img.shields.io/packagist/dt/davingm/laravel" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/davingm/laravel"><img src="https://img.shields.io/packagist/v/davingm/laravel" alt="Latest Stable Version"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/source%20license-AGPL--3.0--only-blue" alt="Source license: AGPL-3.0-only"></a>
  <a href="https://www.php.net/"><img src="https://img.shields.io/badge/PHP-8.3%2B-777BB4?style=flat&logo=php&logoColor=white" alt="PHP 8.3 or later"></a>
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-13-18181B?style=flat&logo=laravel&logoColor=FF2D20" alt="Laravel 13"></a>
</p>

An early-stage Laravel framework project by [davingm](https://github.com/davingm). It combines a Laravel application with file-based Blade pages, framework generators, and a small server-rendered frontend toolchain.

> [!WARNING]
> **This project is very new and still evolving.** APIs, conventions, tooling, and documentation may change between releases. Do not assume backward compatibility or production readiness; evaluate it in an isolated environment, pin the version you test, and verify that it meets your needs before relying on it.

## Try the published starter

Create a new application from the published Composer project:

```bash
composer create-project davingm/laravel my-app
cd my-app
```

The starter materializes the application from the package and installs the Composer runtime plugin that generates the ignored `.laravel/` directory. Follow the [installation guide](docs/guidness/02-instalasi.md) for requirements and troubleshooting.

## Develop this repository

This is a monorepo: the framework runtime and its Composer plugin are maintained separately from the Laravel application used to exercise them.

| Path | Purpose |
| --- | --- |
| `packages/laravel/src/` | Composer plugin that installs the framework runtime |
| `packages/laravel/.laravel/` | Editable framework runtime source |
| `playground/` | Laravel application used for development and tests |
| `playground/.laravel/` | Generated runtime copy; do not edit directly |
| `docs/` | User guides, developer documentation, and clearly marked design notes |

### Isolated setup with Dev Containers

1. Install Docker Desktop or another Docker-compatible engine, Visual Studio Code, and its Dev Containers extension.
2. Clone this repository and open its root folder in VS Code.
3. Run **Dev Containers: Reopen in Container** from the Command Palette. The container installs dependencies and prepares `playground/` on first creation.
4. In the container terminal:

   ```bash
   cd playground
   composer run dev
   ```

The development server uses port `8000`. The [Dev Container configuration](.devcontainer/devcontainer.json) is also the reproducible PHP/Composer/Node environment used for local development.

### Setup on your own machine

Requirements: PHP `^8.3`, Composer 2, Node.js and npm. From the repository root:

```bash
cd playground
composer run setup
composer run dev
```

Run `composer run setup` once for a fresh checkout; it installs dependencies, prepares local environment/database files, generates an application key, runs migrations, and builds frontend assets. Do not rerun setup over an application whose local key or database you need to preserve.

### Tests and frontend build

Run these from `playground/`:

```bash
php artisan test --compact
npm run build
```

The GitHub Actions workflow also runs the test suite and frontend build, then checks the application through Apache. See [contribution guidelines](CONTRIBUTING.md) for focused checks and pull request expectations.

## Documentation and community

- [Documentation index](docs/README.md)
- [Framework guides](docs/guidness/01-pengenalan.md)
- [Developing the framework](docs/dev/develop-framework-in-playground.md)
- [Contributing](CONTRIBUTING.md)
- [Code of Conduct](CODE_OF_CONDUCT.md)
- [Security policy](SECURITY.md)
- [Runtime package release guide](docs/dev/publish-runtime-package.md)

The `docs/discussion/` folder contains historical or exploratory material, not authoritative implementation instructions. Verify proposals against the current source.

## License

The current project source is licensed under the [GNU Affero General Public License v3.0](LICENSE) (`AGPL-3.0-only`). Third-party dependencies and materials retain their respective licenses. Earlier published releases may carry different, historical license metadata; changing the source branch does not change the license attached to an existing release.

Packagist derives metadata from each tag and branch's own `composer.json`; changing `main` does not rewrite metadata for existing releases or branches. After a tag or branch is changed or removed, Packagist and Composer may still show cached metadata. Refresh the package on Packagist and run `composer clear-cache` locally before diagnosing a mismatch. Check the exact version or branch you install if its registry metadata matters to you.
