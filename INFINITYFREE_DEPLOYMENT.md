# InfinityFree Deployment Guide — Delta Drug Store ERP

This guide walks you through deploying Delta Drug Store ERP to **InfinityFree** free shared PHP & MySQL hosting.

---

## Important InfinityFree Notes Before You Begin

1. **MySQL Hostname**: Do **NOT** use `localhost`. InfinityFree runs MySQL on dedicated database servers. You must use the host shown in your control panel (e.g. `sql123.infinityfree.com` or `sql200.byetcluster.com`).
2. **Database & User Prefixes**: InfinityFree automatically prefixes all database names and users with your account username (e.g. `epiz_12345678_delta` and `epiz_12345678`).
3. **Web Root Folder**: All website files must be uploaded inside the **`htdocs/`** directory (or `yourdomain.com/htdocs/` if using an addon domain).
4. **phpMyAdmin Import**: Remote MySQL access is disabled on free hosting. You must import `schema.sql` using phpMyAdmin inside the InfinityFree Control Panel.

---

## Step 1: Create the MySQL Database on InfinityFree

1. Log into your [InfinityFree Client Area](https://dash.infinityfree.com).
2. Select your hosting account and open the **Control Panel** (vPanel).
3. Under the **Databases** section, click **MySQL Databases**.
4. Enter a database name (for example, `delta`) and click **Create Database**.
5. Note the following details from that page:
   - **MySQL Hostname** (e.g., `sql108.infinityfree.com`)
   - **MySQL Database Name** (e.g., `epiz_12345678_delta`)
   - **MySQL Username** (e.g., `epiz_12345678`)
   - **MySQL Password** (Found in the Client Area under Account Details > Show Password)

---

## Step 2: Import the Database Schema

1. In the InfinityFree Control Panel, click **phpMyAdmin**.
2. Click **Connect** next to your newly created database.
3. In phpMyAdmin, click the **Import** tab in the top navigation bar.
4. Click **Choose File** and select **`schema.sql`** from your project folder.
5. Click **Import** (or **Go**) at the bottom.
6. Confirm that the 13 tables (`branches`, `users`, `products`, `sales`, etc.) appear on the left.

---

## Step 3: Configure `config.php`

Open `config.php` in a text editor and enter your InfinityFree MySQL details:

```php
const DB_HOST = 'sql108.infinityfree.com'; // Your actual InfinityFree MySQL Hostname
const DB_NAME = 'epiz_12345678_delta';     // Your full database name with prefix
const DB_USER = 'epiz_12345678';           // Your database user with prefix
const DB_PASS = 'your_account_password';   // Your account password
```

Save the file.

---

## Step 4: Upload the Project Files

### Method A: Web File Manager (Monsta FTP)
1. In the InfinityFree Control Panel, click **Online File Manager**.
2. Open the **`htdocs/`** directory.
3. If there are default files (such as `index2.html` or default placeholder files), delete them.
4. Upload all files and folders from the project (or upload `delta-erp-infinityfree.zip` and use the built-in **Extract** tool).

### Method B: FileZilla / Desktop FTP
1. Connect via FTP using the FTP Hostname, Username, and Password shown in your InfinityFree Client Area.
2. Navigate to `/htdocs/`.
3. Drag and drop all files and folders (`assets/`, `includes/`, `config.php`, `index.php`, etc.) into `/htdocs/`.

---

## Step 5: Initial Administrator Setup

1. Open your browser and go to:
   ```text
   http://<your-subdomain>.infinityfreeapp.com/install.php
   ```
2. Enter your administrator details (password must be at least 12 characters).
3. Click **Create administrator**.
4. Once completed, go to the File Manager and **delete `install.php`** for security.
5. Navigate to:
   ```text
   http://<your-subdomain>.infinityfreeapp.com/login.php
   ```
6. Sign in with your new admin credentials.

