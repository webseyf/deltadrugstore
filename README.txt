DELTA DRUG STORE ERP - cPanel edition
=====================================

REQUIREMENTS
- PHP 8.1 or newer (8.2 recommended)
- MySQL 5.7+ or MariaDB 10.4+
- PHP extensions: PDO, pdo_mysql, mbstring
- cPanel hosting with SSL enabled

INSTALLATION
1. In cPanel > MySQL Database Wizard, create a database and database user.
   Add the user to the database with ALL PRIVILEGES.
2. Open phpMyAdmin, select the new empty database, and import schema.sql.
3. Rename config.example.php to config.php.
4. Edit DB_NAME, DB_USER and DB_PASS in config.php with your cPanel values.
   In cPanel the database/user names often include your cPanel account prefix.
5. Upload the project files and folders into public_html (or a subdomain document root).
6. Visit https://your-domain.example/install.php and create an admin with a strong
   password of at least 12 characters.
7. DELETE install.php immediately after setup.
8. Visit https://your-domain.example/login.php and sign in.
9. Confirm SSL/HTTPS works before entering real business data.

MODULES
Dashboard, product inventory, stock adjustment history, sales/POS, receipt printing,
purchases/stock receiving, suppliers, customers, expenses, management reports,
user management, branch-aware access, audit log records.

IMPORTANT
- This package creates a NEW clean database. It does not automatically import the
  data from the old small pharmacy_dashboard ZIP or the Claude Artifact report.
- The old ZIP's sales table contained only a total and no sale-item details. Its
  historical product-level sales/profit cannot be reconstructed from that schema.
- Your Claude Artifact report showed current stock and a sales summary. Those values
  can be entered as opening balances after confirming each product's SKU, unit,
  cost price, selling price, batch and expiry date.
- This is an operational inventory/sales management system, not a complete statutory
  accounting or Ethiopian tax-compliance package.
- Back up the database regularly in cPanel > Backup or phpMyAdmin Export.
- Never share your cPanel password or database password in chat.
