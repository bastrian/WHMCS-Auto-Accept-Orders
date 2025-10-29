# Jetserver Auto Accept Orders (for WHMCS 8.13+)

Automatically accepts and provisions orders as soon as their invoices are paid — no more pending orders waiting for manual approval.

This module replaces the old hook-based script with a full WHMCS addon module that integrates natively and securely.  
It requires **no API user** and works entirely within WHMCS’ internal context.

---

## Features

- Automatically accepts orders when invoices are marked paid  
- Works natively — **no admin API user required**  
- Optional automation controls:
  - Auto-provision products/services  
  - Send domains to registrar  
  - Send welcome and registration emails  
- Option to process only fully paid invoices  
- Optional payment-method filtering (accept only PayPal, Stripe, etc.)  
- Built-in **admin dashboard page** with:
  - Recent auto-accepted order log  
  - “Clear Log” button  
  - “Debug Mode” toggle for verbose logging  
- Uses WHMCS’ Capsule ORM and hook system — fully compatible with PHP 8.1 +

---

## Installation

1. Copy the module folder to your WHMCS installation:

modules/addons/jetserver_autoacceptorders/


2. In WHMCS Admin:
- Go to **Setup → Addon Modules**
- Find **Jetserver Auto Accept Orders**
- Click **Activate**

3. Once activated, click **Configure** and set your preferences:
- Auto Setup Products  
- Send to Registrar  
- Send Emails  
- Only Process Paid Invoices  
- Payment Method Filter (comma-separated list)  
- Debug Mode  

4. (Optional) Add a 48×48 px logo:

modules/addons/jetserver_autoacceptorders/logo.png


---

## Log Viewer

After activation, visit  
**Addons → Jetserver Auto Accept Orders**  
to see the log table showing recently auto-accepted orders.  
You can clear entries or enable Debug Mode for detailed event tracing in the WHMCS Activity Log.

---

## Technical Notes

- The module uses WHMCS’ internal `localAPI()` context (no external API credentials).  
- Data is stored in the table `jetserver_autoaccept_log`.  
- Fully compatible with WHMCS 8.13.x and PHP 8.1+.  
- Category: **Automation**

---

## Changelog

**v3.1.0**
- Rewritten as a WHMCS addon module  
- Added admin UI with log viewer and debug mode  
- Removed deprecated `apiuser` configuration  
- Added payment method filter and category metadata  
- Modernized to WHMCS 8.x standards (Capsule ORM, secure hooks)

---

## Author

Originally created by Jetserver Web Hosting
Modernized and extended by Bastrian
