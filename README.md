# PublicPulse AI – Smart Public Service Intelligence System
Project Description
PublicPulse AI is a full-stack citizen complaint intelligence platform built with PHP, MySQL, and a rule-based AI engine. It enables citizens to report public service issues (water, electricity, roads, waste, safety, health, housing, education), empowers administrators with real‑time analytics, and provides data analysts with trend detection, hotspot mapping, and predictive insights. The system includes role‑based dashboards, live geolocation mapping, AI‑driven anomaly detection, notification management, user administration, category management, and exportable reporting.

**System Requirements**
Component	Minimum
PHP	8.0+
MySQL	5.7+ / 8.0+
Web Server	Apache 2.4+ (XAMPP recommended)
RAM	512 MB+
PHP Extensions	PDO, pdo_mysql, json, mbstring, fileinfo
Installation Guide (XAMPP / Local)

**Step 1 – Download & Extract**
Download the project ZIP from GitHub.

Extract the folder publicpulse to C:\xampp\htdocs\publicpulse\

**Step 2 – Start XAMPP**
Launch XAMPP Control Panel.

Start Apache and MySQL services.

**Step 3 – Create Database**
Open your browser and go to http://localhost/phpmyadmin

Click New → Database name: publicpulse_ai

Choose utf8mb4_unicode_ci as collation.

Click Import → Select the file database/publicpulse.sql from the extracted folder.

Click Go (the file will create all tables and insert initial data).

**Step 4 – Configure Database Connection**
Navigate to publicpulse/includes/config.php

Verify/update the database settings (usually for XAMPP default):

php
define('DB_HOST', 'localhost');
define('DB_NAME', 'publicpulse_ai');
define('DB_USER', 'root');
define('DB_PASS', '');
define('APP_URL', 'http://localhost/publicpulse');
Step 5 – Set Upload Directory
Create the folder: publicpulse/uploads/complaints/

Ensure the web server can write to it (on Windows/XAMPP this is automatic).

**Step 6 – Access the System**
Open http://localhost/publicpulse/ in your browser.

Pre‑configured User Accounts
Use the following credentials to log in (you can also register new citizen accounts):

**Login details	Email	Password**
Admin	admin@publicpulse.gov.za	Admin@123
Analyst	analyst@publicpulse.gov.za	Analyst@123
Citizen	john@example.com	John@123
Citizen	mary@example.com	Mary@123
Citizen	peter@example.com	Peter@123
⚠️ Security Note: Change these passwords immediately before any live deployment.

**Key Features**
**🧑‍💼 Citizen Portal**
Submit complaints (title, description, category, location, ward, GPS, photo upload).

Track complaint status via reference number.

View personal complaint history and detailed timeline.

Receive real‑time notifications (status updates, resolutions).

**🛠️ Admin Dashboard**
Centralised complaint management (filter, search, pagination, export).

Update complaint status, priority, assignment, add notes.

View complete audit trail (status logs).

Manage users (create, edit, activate/deactivate, delete).

Manage complaint categories (CRUD with icons, colours).

AI Intelligence Centre (hotspots, trends, predictions, anomalies).

Generate period‑based reports with CSV export.

**📊 Analyst Hub**
Full analytics dashboard (KPIs, monthly trends, category distribution, ward ranking, resolution rates).

AI‑powered pattern detection.

Location intelligence map (Leaflet with clustering, ward hotspots).

Data export (CSV, filtered by date/status/priority/category).

**🧠 Rule‑Based AI Engine**
Hotspot detection: flags wards with ≥3 complaints in 30 days.

Trend analysis: alerts when category volume changes >25% month‑over‑month.

Predictive recurrence: identifies weekly patterns (≥3 consecutive weeks) and forecasts next incident.

Anomaly detection: 48‑hour spike in critical complaints >3× daily average.

Risk scoring: each open complaint scored 0–100 (priority, category, age, ward heat).

**🔔 Notification System**
Real‑time bell icon with unread count.

In‑app notifications for status changes, new complaints (admins), assignments.

Mark as read / mark all read.

🗺️ Location Intelligence
Interactive map with GPS‑tagged complaints.

Marker clustering.

Filter by status, priority, category.

Ward hotspot visualisation.

Technology Stack
Backend: PHP 8.0+ (procedural with OOP Database class)

Database: MySQL 8.0 (normalised schema, foreign keys)

Frontend: HTML5, CSS3 (custom design system), JavaScript (ES6)

Libraries: Chart.js (graphs), Leaflet.js (maps), MarkerCluster

Security: Bcrypt hashing, CSRF tokens, PDO prepared statements, XSS protection, role‑based access control

**Folder Structure (Simplified)**









Troubleshooting
Issue	Solution
White screen / 500 error	Enable PHP error reporting or check error_log.
Database connection error	Verify DB credentials in config.php and that MySQL is running.
Map not loading	Ensure Leaflet CSS/JS URLs are reachable (internet connection required).
Image upload fails	Check write permissions on uploads/complaints/ folder.
Charts not displaying	Confirm Chart.js CDN is accessible (internet).
Future Enhancements (Roadmap)
Machine learning integration (scikit‑learn / Python microservice)



License & Contribution
This project is open‑source under the MIT License. Contributions, issues, and feature requests are welcome via GitHub.

Support
For installation assistance or bug reports, please open an issue on the GitHub repository.

Built with ❤️ for community‑driven public service improvement.
