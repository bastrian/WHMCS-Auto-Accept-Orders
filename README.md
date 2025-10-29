# Changelog – Jetserver Auto Accept Orders

All notable changes to this project will be documented in this file.

---

## [3.1.0] – 2025-10-29
### Added
- Converted legacy hook script into a full WHMCS **addon module**
- Added **admin dashboard page** with:
  - Recent auto-accepted order log
  - “Clear Log” button
  - Debug Mode toggle
- Added **category** metadata (Automation) for better WHMCS UI integration
- Added built-in database table (`jetserver_autoaccept_log`) for persistent logs

### Changed
- Switched from manual configuration inside PHP file to WHMCS Addon Module configuration UI
- Replaced `apiuser`-based API calls with **native WHMCS internal context**
- Updated codebase to **Capsule ORM** for database operations
- Modernized all hook and API usage for WHMCS 8.13.x / PHP 8.1+
- Improved error handling and debug logging
- Added optional payment-method filter

---

## [3.0.0] – 2025-10-25
### Added
- Reimplemented Auto Accept logic using native `InvoicePaid` hook
- Added configuration fields for setup, registrar, email, and payment filters

### Changed
- Removed dependency on direct database queries and legacy WHMCS 5.x syntax

---

## [1.0.1] – 2016-05-10
### Added
- Initial release as WHMCS **hook file**
- Customizable settings block for:
  - Auto setup
  - Domain automation
  - Welcome emails
  - Paid-only orders
  - Payment method filtering

---

## [1.0.0] – 2015-12-01
### Added
- First public release by **Jetserver Web Hosting**

---

## Authors
- **Original:** Idan Ben-Ezra, Jetserver Web Hosting  
- **Modernization:** Bastrian
