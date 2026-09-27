# Security Policy

## Supported Versions

The following versions of davingm/laravel currently receive security updates:

| Version | Supported |
|---|---|
| Latest (main) | Yes |
| Older releases | No |

This project follows a rolling release model. Only the latest version on the `main` branch is actively maintained. Users are encouraged to always use the most recent release.

---

## Reporting a Vulnerability

**Do not report security vulnerabilities through public GitHub Issues, Pull Requests, or discussions.**

If you discover a security vulnerability, please report it through one of the following channels:

- **GitHub Security Advisories:** Use the ["Report a vulnerability"](https://github.com/davingm/laravel/security/advisories/new) button on the Security tab of this repository. This is the preferred channel.
- **Email:** If GitHub Security Advisories are not available to you, contact the maintainer directly. The contact address is listed in the project's `composer.json` under `authors`.

### What to include in your report

To help us triage and resolve the issue quickly, please include as much of the following as possible:

- A clear description of the vulnerability and its potential impact
- The affected component (e.g., PageRouter, Frontend payload, CLI, a specific Artisan command)
- Steps to reproduce the issue or a proof-of-concept
- Any suggested mitigations or patches you have identified
- Your contact information if you wish to be credited

---

## Response Process

Upon receiving a security report, the maintainer will:

1. Acknowledge receipt of the report within **72 hours**.
2. Assess the severity and scope of the vulnerability.
3. Work on a fix and, where appropriate, coordinate a disclosure timeline with the reporter.
4. Release a patched version and publish a security advisory.

We ask that reporters observe **responsible disclosure** and not publicly disclose the vulnerability until a fix has been released or a mutual disclosure timeline has been agreed upon.

---

## Scope

The following are considered in scope for security reports:

- Remote code execution
- SQL injection or database exposure
- Authentication or authorisation bypass
- Cross-site scripting (XSS) in generated output
- Path traversal in file generation or route resolution
- Exposure of sensitive data through the payload system

The following are generally out of scope:

- Vulnerabilities in Laravel framework itself (report to [Laravel Security](https://laravel.com/docs/contributions#security-vulnerabilities))
- Vulnerabilities in third-party dependencies (report to the respective maintainer)
- Issues requiring physical access to the server
- Social engineering attacks

---

## Disclosure Policy

Once a security fix is released, we will publish a GitHub Security Advisory describing the vulnerability, affected versions, and the resolution. Credit will be given to the reporter unless they request otherwise.
