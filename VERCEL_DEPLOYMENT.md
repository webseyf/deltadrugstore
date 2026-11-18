# Deploying Delta Drug Store ERP on Vercel

This application is configured for deployment to **Vercel** using the `vercel-php` serverless runtime.

---

## 1. External MySQL Database Setup
Because Vercel is a serverless platform without a built-in database, you need an external MySQL host (any of the following works):
- **Free/Cloud MySQL Providers**: [Aiven](https://aiven.io), [TiDB Cloud](https://tidbcloud.com), [Railway](https://railway.app), [Clever Cloud](https://clever-cloud.com)
- **Or Remote cPanel / VPS MySQL**: Allow remote connections to your cPanel MySQL in cPanel > *Remote MySQL*.

### Database Initialization:
Import the provided [schema.sql](schema.sql) into your database using phpMyAdmin, DBeaver, or MySQL CLI:
```bash
mysql -h <host> -u <user> -p <database_name> < schema.sql
```
*(This creates all tables including the distributed serverless `sessions` table).*

---

## 2. Environment Variables in Vercel
Go to **Vercel Dashboard** > **Your Project** > **Settings** > **Environment Variables** and add:

| Key | Description | Example |
| :--- | :--- | :--- |
| `DB_HOST` | Database Hostname / IP | `mysql-xxxx.aivencloud.com` |
| `DB_PORT` | Port (optional, default 3306) | `3306` |
| `DB_NAME` | Database Name | `delta_erp` |
| `DB_USER` | Database Username | `admin` |
| `DB_PASS` | Database Password | `your_secure_password` |

> **Alternative**: You can provide a single connection string:
> `DATABASE_URL` = `mysql://user:password@host:port/dbname`

Optional variables:
- `APP_NAME`: `DELTA DRUG STORE ERP`
- `CURRENCY`: `ETB`
- `APP_TIMEZONE`: `Africa/Addis_Ababa`

---

## 3. Deploy to Vercel

### Option A: GitHub (Recommended)
1. Push this project folder to a GitHub repository:
   ```bash
   git init
   git add .
   git commit -m "Prepare Delta ERP for Vercel"
   git branch -M main
   git remote add origin <your-repo-url>
   git push -u origin main
   ```
2. In Vercel, click **Add New** > **Project** and import your repository.
3. Make sure the Environment Variables from Step 2 are entered.
4. Click **Deploy**.

### Option B: Vercel CLI
```bash
npx vercel
```
Follow the interactive prompts to link and deploy.

---

## 4. Initial Setup & First Login
1. Once deployed, open:  
   `https://your-app.vercel.app/install.php`
2. Create your administrator account (password must be at least 12 characters).
3. Once completed, visit `https://your-app.vercel.app/login.php` and sign in.
4. `install.php` will automatically lock itself once the administrator is created.

