# 🚗 DriveShare — Peer-to-Peer Car Rental System

A full-stack peer-to-peer car rental platform built with **PHP 8.2+ (OOP)**, **PostgreSQL (Supabase)**, **HTML5**, **CSS3**, **Bootstrap 5**, and **Vanilla JavaScript** — deployable for **free** on Render + Supabase.

---

## ✨ Features

| Feature | Description |
|---|---|
| 🔐 **Unified Auth** | Single login/register page with Customer or Owner role selection |
| 🚗 **Car Browsing** | Responsive grid with real-time AJAX filters (type + capacity) |
| 📸 **Multi-Photo Upload** | Drag-and-drop uploader saves images to Supabase Storage |
| 📅 **Smart Booking** | Live JS price calculation (days × rate) before submission |
| ✅ **4-Stage Approval Flow** | pending → owner_approved → customer_confirmed → completed |
| 📱 **WhatsApp Integration** | Auto-generates pre-filled WhatsApp message to owner |
| 📊 **Owner Analytics** | Booking stats, earnings, and rental duration per car |
| 🔒 **Security** | PDO prepared statements, secure cookies, server-side session tokens |

---

## 🗂️ Project Structure

```
khushboo project/
├── index.php                    # Login / Register page
├── car-detail.php               # Car detail + booking form
├── manage-car-photos.php        # Owner: manage car photos
├── schema.sql                   # PostgreSQL schema (run in Supabase)
├── composer.json
├── render.yaml                  # Render.com deploy config
├── .env.example                 # Copy to .env for local dev
│
├── config/
│   └── database.php             # PDO connection + constants
│
├── classes/
│   ├── Auth.php                 # Register, login, session
│   ├── CarModel.php             # Car CRUD + image management
│   ├── BookingModel.php         # Full booking lifecycle
│   └── ImageUploader.php       # Supabase Storage upload via cURL
│
├── includes/
│   ├── middleware.php           # requireAuth() + helpers
│   ├── dashboard_header.php    # Shared topbar + sidebar
│   └── dashboard_footer.php    # Closing tags + JS
│
├── dashboard/
│   ├── customer.php             # Customer dashboard
│   └── owner.php                # Owner dashboard
│
├── api/
│   ├── auth.php                 # POST: login / register / logout
│   ├── cars.php                 # CRUD + image API
│   └── booking.php              # Book / approve / confirm / cancel
│
└── assets/
    └── css/
        ├── auth.css             # Login/register styles
        └── dashboard.css       # Full dashboard design system
```

---

## 🛠️ Setup Guide

### Step 1 — Supabase Setup

1. Go to [supabase.com](https://supabase.com) → **New Project**
2. Name it `driveshare`, set a strong DB password
3. Open **SQL Editor** → paste contents of `schema.sql` → **Run**
4. Go to **Storage** → create a new bucket: `car-images` (set it to **Public**)
5. Collect these credentials from **Project Settings → API**:
   - Project URL: `https://xxxx.supabase.co`
   - Anon Key
   - Service Role Key
6. From **Project Settings → Database**: copy Host, Port, Database, User, Password

---

### Step 2 — Local Development

```bash
# 1. Clone / open the project
cd "khushboo project"

# 2. Copy env file and fill in your credentials
cp .env.example .env
# Edit .env with your Supabase values

# 3. Install dependencies
composer install

# 4. Run PHP built-in server
php -S localhost:8000

# 5. Open http://localhost:8000
```

> **Note:** For the `.env` file to be loaded, add this at the top of `config/database.php` (already handled by `getenv()`). If running on a local dev server, you can also export env vars manually.

---

### Step 3 — Deploy to Render (Free)

1. Push the project to **GitHub**
2. Go to [render.com](https://render.com) → **New Web Service**
3. Connect your GitHub repo
4. Settings:
   - **Runtime:** `PHP` (or Docker if PHP not available — use a `Dockerfile`)
   - **Build Command:** `composer install --no-dev`
   - **Start Command:** `php -S 0.0.0.0:$PORT`
   - **Root Directory:** *(leave blank or set to project root)*
5. Add **Environment Variables** (from your Supabase dashboard):

| Key | Value |
|---|---|
| `SUPABASE_DB_HOST` | `db.xxxx.supabase.co` |
| `SUPABASE_DB_PORT` | `5432` |
| `SUPABASE_DB_NAME` | `postgres` |
| `SUPABASE_DB_USER` | `postgres` |
| `SUPABASE_DB_PASS` | your db password |
| `SUPABASE_URL` | `https://xxxx.supabase.co` |
| `SUPABASE_ANON_KEY` | your anon key |
| `SUPABASE_SERVICE_KEY` | your service role key |
| `APP_URL` | your Render URL (e.g. `https://driveshare.onrender.com`) |

6. Click **Deploy** — done! 🎉

---

## 🔄 Booking Status Flow

```
Customer Books Car
       ↓
   [ pending ]          ← Owner sees request on dashboard
       ↓
Owner clicks Approve
       ↓
 [ owner_approved ]     ← Customer sees "Confirm Booking" + Owner's phone
       ↓
Customer clicks WhatsApp / Confirm
       ↓
[ customer_confirmed ]  ← Both parties see "Confirmed" status
       ↓
   [ completed ]        ← (future: auto after end date)
```

---

## 🔐 Security Features

- All database queries use **PDO Prepared Statements** — SQL injection proof
- Passwords hashed with **bcrypt (cost 12)**
- Sessions use **cryptographically random tokens** stored in DB
- Owner phone number is **hidden in SQL query** until status is `owner_approved`+
- Cookies set with `HttpOnly` flag

---

## 📱 WhatsApp Integration

When an Owner approves a booking, the Customer sees:
1. **Owner's phone number** (revealed in dashboard)
2. A **"Confirm via WhatsApp"** button

Clicking it redirects to `https://wa.me/{OWNER_PHONE}?text=...` with a pre-filled message containing:
- Car name & type
- Booking dates & duration
- Total price
- Customer's contact number

---

## 🎨 Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.2+ (OOP, PDO) |
| Database | PostgreSQL via Supabase |
| Storage | Supabase Storage (S3-compatible) |
| Frontend | HTML5, CSS3, Vanilla JavaScript |
| UI Framework | Bootstrap 5.3 |
| Icons | Bootstrap Icons |
| Font | Google Fonts — Inter |
| Hosting | Render.com (Free Tier) |
| Auth | Native sessions (DB-backed tokens) |

---

## 🚀 Cost

Completely **free** using:
- **Render** Free Web Service
- **Supabase** Free Tier (500MB DB + 1GB Storage)

---

*Built with ❤️ — DriveShare*
