# Contributing to davingm/laravel

Thank you for considering contributing to this project. This document outlines the process and expectations for contributing.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [How to Contribute](#how-to-contribute)
- [Development Setup](#development-setup)
- [Coding Standards](#coding-standards)
- [Commit Messages](#commit-messages)
- [Pull Request Process](#pull-request-process)
- [Reporting Bugs](#reporting-bugs)
- [Feature Requests](#feature-requests)

---

## Code of Conduct

This project adheres to the [Code of Conduct](CODE_OF_CONDUCT.md). By participating, you agree to uphold these standards. Please report unacceptable behavior to the maintainers.

---

## Getting Started

Before contributing, please:

1. Read the [reference documentation](docs/reference.md) to understand how the framework works.
2. Search existing [issues](https://github.com/davingm/laravel/issues) and [pull requests](https://github.com/davingm/laravel/pulls) to avoid duplicating effort.
3. Open an issue before starting work on a significant change. This allows the maintainers to discuss the direction before implementation begins.

---

## How to Contribute

### Types of contributions welcome

- Bug fixes
- Documentation improvements
- Performance improvements
- New features that align with the project's conventions
- Test coverage improvements

### Types of contributions that require prior discussion

- Changes to the public API surface (CLI commands, Artisan commands, Blade directives)
- Changes to the PageRouter file-system routing conventions
- Changes to the Frontend payload structure
- New dependencies

---

## Development Setup

**Requirements:**

| Dependency | Version |
|---|---|
| PHP | >= 8.3 |
| Composer | >= 2.x |
| Node.js | >= 18.0.0 |

**Setup:**

```bash
git clone https://github.com/davingm/laravel.git
cd laravel
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
```

**Start development environment:**

```bash
artisan dev
```

This starts the Laravel server, queue worker, and Vite dev server in a single terminal.

---

## Coding Standards

### PHP

- Follow PSR-12 code style.
- All code is formatted with [Laravel Pint](https://laravel.com/docs/pint). Run it before committing:

```bash
vendor/bin/pint --dirty
```

- Use PHP 8.x constructor property promotion and match expressions where appropriate.
- All public methods must have explicit return type declarations and typed parameters.
- Use PHPDoc blocks for complex logic, not inline comments.

### Blade

- Follow the structure of existing pages in `resources/views/pages/`.
- New pages created manually (without `make:page`) should include a `@section('seo')` block following the standard template.
- Partial files used as `@include` targets must be prefixed with `_` (e.g., `_form.blade.php`).

### JavaScript

- Keep `resources/js/app.js` minimal. It is intentionally vanilla JavaScript with no framework dependency.
- Do not introduce npm dependencies without prior discussion.

### CSS

- Write vanilla CSS. Do not add utility-first frameworks unless the project adopts one explicitly.
- Follow the existing naming conventions in `resources/css/app.css`.

---

## Commit Messages

Use the [Conventional Commits](https://www.conventionalcommits.org/) specification:

```
<type>(<scope>): <short summary>
```

**Types:**

| Type | When to use |
|---|---|
| `feat` | A new feature |
| `fix` | A bug fix |
| `docs` | Documentation changes only |
| `refactor` | Code change that is neither a bug fix nor a new feature |
| `test` | Adding or updating tests |
| `chore` | Build process, tooling, or dependency changes |
| `perf` | Performance improvements |

**Examples:**

```
feat(make:page): add --force flag to overwrite existing pages
fix(page-router): normalise Windows backslashes in path resolution
docs(reference): document exclude pattern behaviour
test(page-router): add coverage for wildcard exclude patterns
```

---

## Pull Request Process

1. Fork the repository and create a branch from `main`.
2. Name your branch descriptively: `fix/page-router-windows-paths`, `feat/make-page-force-flag`.
3. Make your changes with appropriate test coverage.
4. Ensure all tests pass:

```bash
php artisan test
```

5. Run Pint to format your code:

```bash
vendor/bin/pint --dirty
```

6. Update documentation if you are changing behaviour or adding features.
7. Open a pull request against `main` with a clear title and description.
8. Reference any related issues using `Closes #123` in the PR description.
9. A maintainer will review your pull request. Be prepared for feedback and revisions.

Pull requests that do not have tests, break existing tests, or fail the Pint check will not be merged until those issues are resolved.

---

## Reporting Bugs

Use the [GitHub Issues](https://github.com/davingm/laravel/issues) tracker. Before filing a report, search existing issues to see if it has already been reported.

A good bug report includes:

- A clear and descriptive title
- The PHP, Composer, and Node.js versions you are using
- The exact steps to reproduce the issue
- The expected behaviour and what you observed instead
- Relevant output, error messages, or stack traces

For security vulnerabilities, do **not** open a public issue. See [SECURITY.md](SECURITY.md).

---

## Feature Requests

Open a [GitHub Issue](https://github.com/davingm/laravel/issues) with the label `enhancement`. Describe:

- The problem you are trying to solve
- The proposed solution
- Alternatives you have considered

Features that significantly expand the scope or add dependencies will require broader discussion before implementation.

```bash
git status

git add .
git commit -m "feat: here"

git tag v1.<major>.0<version>
git push origin v1.<major>.<version>

git push origin main --tags
```