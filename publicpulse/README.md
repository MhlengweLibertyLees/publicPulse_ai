# 🏛 PublicPulse AI
### Smart Public Service Intelligence & Citizen Analytics System
**Version:** 1.0.0 | **Stack:** PHP 8.0+ · MySQL 8.0+ · XAMPP

---

## 📋 System Requirements

| Requirement  | Minimum              |
|-------------|----------------------|
| PHP         | 8.0+                 |
| MySQL       | 5.7+ / 8.0+         |
| Web Server  | Apache 2.4+ (XAMPP) |
| RAM         | 512 MB+              |
| Extensions  | PDO, pdo_mysql, json, mbstring, fileinfo |

---

## 🚀 Installation (XAMPP / Local)

### Step 1 — Copy Files

```
Copy the `publicpulse` folder to:
C:\xampp\htdocs\publicpulse\
```

### Step 2 — Create Database

1. Open **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Click **New** → Database name: `publicpulse_ai` → Encoding: `utf8mb4_unicode_ci`
3. Click **Import** → Select `database/publicpulse.sql`
4. Click **Go**

### Step 3 — Configure Connection

Edit `includes/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'publicpulse_ai');
define('DB_USER', 'root');       // Your MySQL username
define('DB_PASS', '');           // Your MySQL password (empty for XAMPP default)
define('APP_URL', 'http://localhost/publicpulse');
```

### Step 4 — Create Upload Directory

Create and make writable:
```
publicpulse/uploads/complaints/
```
On Windows (XAMPP) this is automatic. On Linux:
```bash
mkdir -p uploads/complaints
chmod 755 uploads/complaints
```

### Step 5 — Access the System

Open: **http://localhost/publicpulse**

---

## 🔑 Demo Login Credentials

| Role       | Email                          | Password   |
|-----------|-------------------------------|------------|
| Admin     | admin@publicpulse.gov.za      | password   |
| Analyst   | analyst@publicpulse.gov.za    | password   |
| Citizen   | john@example.com              | password   |

> ⚠️ **Change passwords immediately** before any live deployment!

---

## 📁 Project Structure

```
publicpulse/
├── index.php               ← Entry point (auto-redirects by role)
├── login.php               ← Login page
├── register.php            ← Citizen registration
├── .htaccess               ← Apache security rules
│
├── includes/
│   ├── config.php          ← DB config + Database class
│   ├── functions.php       ← Security, helpers, session
│   ├── analytics.php       ← All data aggregation queries
│   ├── ai_engine.php       ← AI pattern detection engine
│   └── layout.php          ← Reusable sidebar/topbar components
│
├── admin/
│   ├── dashboard.php       ← Admin analytics dashboard
│   ├── complaints.php      ← Complaints management + filters
│   ├── complaint_view.php  ← Complaint detail + status update
│   ├── ai_insights.php     ← AI Intelligence Center
│   ├── reports.php         ← Report generator
│   ├── users.php           ← User management (stub)
│   └── categories.php      ← Category management (stub)
│
├── citizen/
│   ├── dashboard.php       ← Citizen home + overview
│   ├── submit.php          ← Submit complaint form
│   ├── complaints.php      ← My complaints list
│   ├── complaint_detail.php← Complaint status + timeline
│   ├── track.php           ← Track by reference number
│   └── notifications.php   ← Notifications center
│
├── analyst/
│   ├── dashboard.php       ← Analytics hub + AI insights
│   ├── reports.php         ← Report generator
│   └── export.php          ← CSV data export
│
├── api/
│   ├── kpis.php            ← AJAX KPI endpoint
│   ├── export.php          ← CSV download endpoint
│   └── dismiss_insight.php ← Dismiss AI insight
│
├── assets/
│   ├── css/app.css         ← Complete design system
│   └── js/app.js           ← Charts, AJAX, UI helpers
│
├── database/
│   └── publicpulse.sql     ← Full schema + seed data
│
└── uploads/
    └── complaints/         ← Uploaded complaint images
```

---

## 🧠 AI Engine (Phase 1 — Rule-Based)

The `AIEngine` class in `includes/ai_engine.php` implements:

| Detection          | Logic                                                                 |
|-------------------|-----------------------------------------------------------------------|
| **Hotspot**       | IF complaints_in_ward > 3 in 30 days → flag as hotspot               |
| **Trend**         | IF category volume increases > 25% month-over-month → flag as trend  |
| **Prediction**    | IF same category+ward recurs weekly ≥ 3 weeks → predict next incident|
| **Anomaly**       | IF 48-hour critical complaints > 3× daily average → spike alert      |
| **AI Risk Score** | Score 0–100 per complaint based on priority, category, age, ward heat|

---

## 🔐 Security Features

- ✅ Bcrypt password hashing (cost factor 12)
- ✅ CSRF token protection on all POST forms
- ✅ PDO prepared statements (SQL injection prevention)
- ✅ HTML entity encoding on all output (XSS prevention)
- ✅ Role-based access control (citizen / admin / analyst)
- ✅ Session regeneration on login
- ✅ .htaccess blocks directory traversal and PHP in uploads

---

## 📊 Database Tables

| Table              | Purpose                               |
|-------------------|---------------------------------------|
| `users`            | All user accounts + roles             |
| `categories`       | 8 service categories                  |
| `complaints`       | All citizen complaints                |
| `status_logs`      | Full audit trail of status changes    |
| `ai_insights`      | AI-generated flags and predictions    |
| `notifications`    | User notification inbox               |
| `analytics_cache`  | Performance cache for heavy queries   |
| `reports`          | Generated report history              |

---

## 🚀 Future Expansion (Roadmap)

- [ ] Advanced ML models (scikit-learn / Python microservice)
- [ ] Google Maps API heatmap integration
- [ ] WhatsApp / SMS notification gateway
- [ ] Mobile app (React Native)
- [ ] REST API for government system integration
- [ ] Real-time IoT sensor data ingestion
- [ ] PDF report generation (mPDF / TCPDF)
- [ ] Multi-language support (isiZulu, Afrikaans, Sesotho)

---

## 🏆 Project Statement

**PublicPulse AI** is a full-stack intelligent public service analytics platform built with PHP, MySQL, Chart.js, and a rule-based AI engine. It collects citizen complaints, detects service patterns, predicts infrastructure failures, and supports data-driven decision-making for public institutions.

---

*Built with ❤️ for community-driven public service improvement.*
