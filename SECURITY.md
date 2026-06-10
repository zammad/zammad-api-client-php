# Security Policy

## Supported Versions

Security fixes are provided for the current stable version of this PHP API client only.
Any older version is not supported and needs to be updated first before reporting security issues.

## Reporting a Vulnerability

If you've found a security vulnerability in Zammad,
please report the vulnerability exclusively via email
to [security@zammad.com](mailto:security@zammad.com).

Please do not combine several independent vulnerabilities,
but send a separate mail for each of them instead.

To send us a secure message, please use [our public key](SECURITY.asc).

We will get back to you as soon as possible and inform
you about the next steps. Accepted vulnerabilities will
be disclosed via patch level release with accompanying
security advisory.

### Reporting Process Overview

- Potential security issues can be reported via [security@zammad.com](mailto:security@zammad.com).
- We evaluate them and provide timely feedback to the reporter.
- There may be security releases created if needed, e.g. [Zammad 6.3.1](https://zammad.com/en/releases/6-3-1).
- We publish security advisories for every acknowledged issue via [GitHub Security Advisories](https://github.com/zammad/zammad/security/advisories).
- After their publication, we request CVE identifiers to be assigned to the advisories.

### Rewards

Every first reporter of a vulnerability may be credited
in the related security advisory.

Zammad does not offer financial compensation through a
security bounty program.

## Security Measures in Development Workflow

### Dependency Management

Dependencies are managed via [Composer](https://getcomposer.org/).
You can check for known security vulnerabilities in dependencies by running:

```bash
composer audit
```
