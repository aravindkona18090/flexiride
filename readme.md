# 🚗 FlexiRide

<div align="center">
  <img src="images/logo_dark.png" alt="FlexiRide Logo" width="130" style="border-radius: 24px; box-shadow: 0 8px 30px rgba(2, 132, 199, 0.35);">
  <br><br>
  <h3>Safe, Smart & Sustainable Campus & Daily Commuter Ride-Sharing</h3>
  <p>Connecting drivers and riders for shared commutes with verified campus trust, fair pricing, and real-time mapping.</p>

  <p>
    <img src="https://img.shields.io/badge/PHP-8.2-777BB4?style=flat&logo=php&logoColor=white" alt="PHP 8.2">
    <img src="https://img.shields.io/badge/TiDB%20Cloud-Serverless-F37021?style=flat&logo=mysql&logoColor=white" alt="TiDB Cloud">
    <img src="https://img.shields.io/badge/Render-Cloud%20Deploy-46E3B7?style=flat&logo=render&logoColor=black" alt="Render">
    <img src="https://img.shields.io/badge/Docker-Apache%202.4-2496ED?style=flat&logo=docker&logoColor=white" alt="Docker">
    <img src="https://img.shields.io/badge/Leaflet-OSM%20Maps-199900?style=flat&logo=leaflet&logoColor=white" alt="Leaflet Maps">
    <img src="https://img.shields.io/badge/Composer-2.x-885630?style=flat&logo=composer&logoColor=white" alt="Composer">
  </p>

  <p>
    <a href="https://flexiride-pnnz.onrender.com/" target="_blank">
      <img src="https://img.shields.io/badge/Live%20Demo-flexiride--pnnz.onrender.com-success?style=for-the-badge&logo=render&logoColor=white" alt="Live Demo">
    </a>
  </p>
  <p><strong>🌐 Live Application:</strong> <a href="https://flexiride-pnnz.onrender.com/">https://flexiride-pnnz.onrender.com/</a></p>
</div>

---

## 📖 Overview

**FlexiRide** is a production-ready, full-stack ride-sharing platform engineered with **PHP 8.2, TiDB Cloud Serverless (MySQL 8.0 compatible with TLS/SSL), and Docker**. It facilitates effortless bike-pooling and carpooling for university campuses and daily commuters, built around a core philosophy of **safety, identity verification, and community trust**.

The platform is deployed globally on **Render** with dynamic port management, zero-downtime SSL encryption, and high-performance container virtualization.

---

## ✨ Key Features

### 🚘 Ride & Commute Management
- **Interactive Routing & Maps**: Integrated **Leaflet.js + OpenStreetMap** with real-time OSRM route tracing, live turn-by-turn geometry, and an authentic glowing GPS location halo.
- **Smart Garage & Multi-Vehicle Support**: Add multiple vehicles (Bikes, Scooters, Cars, EVs) with custom seat capacities, helmet availability, and registration verification.
- **Dynamic Pricing Engine**: Automated distance-based pricing algorithm with EV discounts and peak-hour surge calculations.
- **Geospatial Proximity Search**: Ride discovery powered by the **Haversine formula** to match riders and drivers on the same transit corridor.

### 🛡️ Campus Safety & Verification
- **Verhoeff Checksum Verification**: Algorithmic validation of Indian Aadhaar numbers before storage.
- **Document Queue & DigiLocker OAuth**: Secure administrative verification of Government IDs (Aadhaar & Driving License).
- **Emergency SOS ("Danger") Button**: Instant trigger transmitting critical GPS coordinates and alert emails to registered emergency contacts.
- **Weighted Trust Scoring (0–100%)**: Multi-factor trust badge calculated from phone verification, document approvals, and community ratings.

### 💬 In-App Commute Tools
- **Live Ride Chat**: Secure peer-to-peer messaging between confirmed riders and drivers.
- **Trip OTP Verification**: Digital handshake OTP to authenticate passenger boarding before commencing the ride.
- **Digital Invoices & Carbon Ledger**: Instant downloadable PDF trip receipts tracking individual fuel savings and kilograms of CO₂ offset.

### 🎨 Master Theme Engine & Brand Identity
- **Theme Selector**: Seamless real-time switching between 4 themes:
  - 🌌 **Slate** (Electric Cyan Dark Mode)
  - 💚 **Emerald** (Bio-Green Eco Mode)
  - 💜 **Cyberpunk** (Neon Magenta Mode)
  - ☀️ **Royal / Light** (Crisp High-Contrast Light Mode)
- **Bespoke Dual-Vehicle Emblem**: Dynamic brand logo unifying the car silhouette and bike rider with radiant headlight beams, auto-adapting between Dark and Light environments.

### 👨‍💼 Administrator Operations Center (`/admin`)
- **System Telemetry Dashboard**: Platform metrics, active trip tracking, and user analytics.
- **Document Verification Queue**: Side-by-side identity verification with one-click approve/reject actions.
- **Campus Broadcast Hub**: Dispatch platform-wide alerts and announcements.
- **SOS Logs & Audit Trail**: Real-time monitoring of all triggered emergency distress beacons.

---

## 🛠️ Architecture & Tech Stack

| Layer | Technologies & Services |
|---|---|
| **Backend** | PHP 8.2 (Apache 2.4, mod_rewrite, MySQLi Prepared Statements) |
| **Cloud Database** | **TiDB Cloud Serverless** (AWS ap-southeast-1, TLS 1.3 / SSL encrypted) |
| **Frontend** | Vanilla JavaScript (ES6+), HTML5, CSS3 Custom Properties (Design Tokens) |
| **Maps & Routing** | Leaflet.js, OpenStreetMap, OSRM Routing API, Nominatim Geocoding |
| **Containerization** | Docker (`php:8.2-apache` base image with custom dynamic entrypoint) |
| **Cloud Platform** | **Render** (Auto-deploy on git push, dynamic `$PORT` routing) |
| **Typography & Icons**| Google Fonts (Outfit), Boxicons 2.1 |
| **Notifications** | Resend API / PHPMailer, Twilio SMS API |

---

## 🗂️ Project Directory

```
FlexiRide/
├── admin/                    # Administrative Control Panel
│   ├── admin_dashboard.php   # Platform metrics and system health
│   ├── admin_verify_docs.php # Document moderation & approval
│   ├── admin_manage_users.php# Account management
│   ├── admin_sos_logs.php    # Emergency SOS incident logs
│   └── admin_broadcast.php   # Platform-wide announcements
│
├── assets/
│   ├── css/flexiride.css     # "Velocity Eco-Pulse" CSS Design System
│   └── js/
│       ├── theme.js          # Client theme switcher & offline alert engine
│       └── main.js           # Shared interactive utilities
│
├── includes/
│   ├── db.php                # TiDB Cloud SSL connection & fallback engine
│   ├── db_helpers.php        # 3NF synchronization & sanitization utilities
│   ├── navbar.php            # Master responsive navigation header
│   ├── admin_navbar.php      # Admin navigation bar
│   ├── dynamic_pricing.php   # Pricing & surge calculation logic
│   ├── trust_score.php       # Verhoeff & Trust score algorithms
│   └── mailer.php            # Transactional email dispatcher
│
├── images/                   # Brand assets (Dark, Light, Favicon)
├── find_ride.php             # Search rides with live map & filters
├── post_ride.php             # Create rides with waypoint routing
├── myrides.php               # Driver ride management
├── my_booked_rides.php       # Passenger booking ledger
├── chat.php                  # Driver–rider messaging
├── danger.php                # Emergency SOS alert beacon
├── profile.php               # Garage, vehicle list & trust profile
├── Dockerfile                # Render production container configuration
├── entrypoint.sh             # Dynamic Apache port adapter
├── flexiride.sql             # Relational 3NF database schema
└── composer.json             # Backend dependencies
```

---

## 🚀 Local Development Setup (XAMPP)

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) with PHP 8.1+ and MySQL/MariaDB
- [Composer](https://getcomposer.org/)

### Quick Start

1. **Clone the repository:**
   ```bash
   git clone https://github.com/aravindkona18090/flexiride.git C:/xampp/htdocs/FlexiRide
   ```

2. **Import Database Schema:**
   - Open [phpMyAdmin](http://localhost/phpmyadmin)
   - Create a database named `flexiride`
   - Import [`flexiride.sql`](flexiride.sql)

3. **Configure Environment:**
   Create a `.env` file in the project root:
   ```ini
   DB_HOST=127.0.0.1
   DB_USER=root
   DB_PASS=
   DB_NAME=flexiride
   DB_PORT=3306
   ```

4. **Install Dependencies:**
   ```bash
   composer install
   ```

5. **Launch Application:**
   Navigate to: `http://localhost/FlexiRide`

> 💡 **Prefer not to set up locally?** Test the production deployment directly at: **[https://flexiride-pnnz.onrender.com/](https://flexiride-pnnz.onrender.com/)**

---

## ☁️ TiDB Cloud Serverless & Render Deployment

### 1. Database Connection (TiDB Cloud)
The platform connects to **TiDB Cloud Serverless** via SSL/TLS on port `4000`:
- **Windows / XAMPP CA Bundle**: `C:\xampp\apache\bin\curl-ca-bundle.crt`
- **Linux / Docker CA Bundle**: `/etc/ssl/certs/ca-certificates.crt`

[`includes/db.php`](includes/db.php) automatically detects the operating environment and configures `MYSQLI_OPT_SSL_VERIFY_SERVER_CERT` seamlessly.

### 2. Docker & Dynamic Port Binding (Render)
Render binds containers dynamically to an arbitrary `$PORT` at runtime. FlexiRide utilizes [`entrypoint.sh`](entrypoint.sh) to substitute the port dynamically:

```bash
#!/bin/bash
PORT="${PORT:-80}"
sed -i "s/Listen [0-9]*/Listen $PORT/g" /etc/apache2/ports.conf
sed -i "s/:[0-9]*>/:$PORT>/g" /etc/apache2/sites-available/000-default.conf
exec apache2-foreground
```

---

## 🔐 Security & Best Practices

- **Zero-Plaintext Passwords**: Password hashing using `bcrypt` via PHP's native `password_hash()`.
- **SQL Injection Prevention**: 100% prepared statements across all database queries (`$conn->prepare()`).
- **Role-Based Access Control**: Strict server-side verification of `is_admin` sessions on all administrative endpoints.
- **MIME-Type & Magic Byte File Validation**: Strict white-listing and randomized hashing of all uploaded documents and avatars.
- **XSS Sanitization**: Rigorous output escaping via `htmlspecialchars()` on all dynamic user data.

---

## 👨‍💻 Author

**Kona Aravind Ranga Reddy**  
*B.Tech in Artificial Intelligence & Machine Learning*  
- **GitHub**: [@aravindkona18090](https://github.com/aravindkona18090)

---

## 📄 License

This software is built for educational and portfolio demonstration purposes.
