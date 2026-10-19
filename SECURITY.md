# Security Policy

## Supported Versions

This project does not currently publish a formal security-support window for individual release lines. When reporting an issue, include the exact package version or commit and affected component. Maintainers will assess affected versions and remediation scope as part of triage; do not assume every historical release receives fixes.

---

## Reporting a Vulnerability

**Do not report security vulnerabilities through public GitHub Issues, Pull Requests, or discussions.**

If you discover a security vulnerability, please report it through one of the following channels:

- **GitHub Security Advisories:** Use the ["Report a vulnerability"](https://github.com/davingm/laravel/security/advisories/new) button on the Security tab of this repository. This is the preferred channel.
- **Maintainer contact:** If private vulnerability reporting is unavailable, contact the maintainer through the private contact options on the [GitHub profile](https://github.com/davingm). The Composer manifest does not publish a security email address.

### What to include in your report

To help us triage and resolve the issue quickly, please include as much of the following as possible:

- A clear description of the vulnerability and its potential impact
- The affected component (e.g., PageRouter, Frontend payload, CLI, a specific Artisan command)
- Steps to reproduce the issue or a proof-of-concept
- Any suggested mitigations or patches you have identified
- Your contact information if you wish to be credited

---

## Response Process

The maintainer will review a report, assess its impact, and coordinate a fix and disclosure where appropriate. Response and release timing depend on severity and maintainer availability; no fixed response-time SLA is currently offered.

Please observe **responsible disclosure** and avoid public disclosure until a fix is released or a disclosure timeline has been agreed with the maintainer.

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

When appropriate and feasible, a security fix may be accompanied by a GitHub Security Advisory describing affected versions and the resolution. Reporter credit will be given only with their consent.
