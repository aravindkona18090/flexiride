# 🚗 FlexiRide

**Safe, affordable campus ride-sharing — for everyone going the same way.**

FlexiRide is a full-stack ride-sharing web application built with PHP and MySQL. It connects drivers and passengers for shared commutes, with a strong focus on **safety, identity verification, and trust** — built specifically around the campus ride-sharing use case.

<p>
  <img src="https://img.shields.io/badge/PHP-777BB4?style=flat&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-4479A1?style=flat&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/JavaScript-F7DF1E?style=flat&logo=javascript&logoColor=black" alt="JavaScript">
  <img src="https://img.shields.io/badge/Composer-885630?style=flat&logo=composer&logoColor=white" alt="Composer">
  <img src="https://img.shields.io/badge/Docker-2496ED?style=flat&logo=docker&logoColor=white" alt="Docker">
  <img src="https://img.shields.io/badge/Railway-0B0D0E?style=flat&logo=railway&logoColor=white" alt="Railway">
  <img src="https://img.shields.io/badge/status-active-success" alt="status">
</p>

**🔗 Live Demo:** [flexiride.up.railway.app](https://flexiride.up.railway.app/)

---

## 📸 Screenshots

![Home](screenshots/home.png)
![Post Ride](screenshots/post-ride.png)
![Find Ride](screenshots/find-ride.png)
![My Rides](screenshots/myrides.png)

---

## 📖 Overview

FlexiRide covers the complete ride-sharing lifecycle: **post → discover → book → travel → rate**. Beyond the core booking flow, it includes real-time in-app chat between driver and passenger, an emergency SOS system with automatic email alerts, Aadhaar/DL document verification reviewed by admins, OTP-based phone and account recovery, trip receipts, a trust scoring system, CO₂ savings tracking, and a full admin dashboard.

The platform is deployed on [Railway](https://railway.app/) with Docker + FrankenPHP and uses environment variables for all secrets — no hardcoded credentials.

---

## ✨ Features

### 👤 User Accounts
- Registration and secure login (bcrypt password hashing)
- Profile management with photo upload
- OTP-based forgot password flow (email delivery via Resend API)
- Emergency contact registration
- CO₂ saved and money saved statistics per user

### 🚘 Ride Management
- Post a ride (bike or car, EV flag, helmet availability, gender preference, luggage limit)
- Dynamic tiered pricing suggestion based on distance, vehicle type, and peak hours
- Find rides with geospatial matching using the Haversine formula
- Book a seat with automatic email confirmation to both driver and passenger
- View and manage all posted and booked rides
- Edit or delete a posted ride
- Mark a trip as reached (complete the journey)
- Trip receipt download after completion

### 💬 In-App Chat
- Real-time-style chat between driver and passenger per booking
- Accessible from the ride management page

### 🛡️ Safety & Verification
- **Emergency SOS ("Danger") button** — triggers instant email alerts to registered emergency contacts
- Admin-monitored SOS log dashboard
- **Trust Score** — weighted score (0–100%) based on phone OTP verification (+30), Aadhaar verification (+35), and DL verification (+15)
- Aadhaar number validation using the Verhoeff checksum algorithm
- Document upload for Aadhaar and Driving Licence — admin reviews and approves
- DigiLocker OAuth integration for government document verification
- Privacy policy in-app

### ⭐ Ratings & Feedback
- Post-trip star ratings and written reviews for driver/passenger
- Average rating displayed on profile and ride listings
- Platform feedback and support queries module

### 🔔 Notifications
- In-app notification bell with unread count
- Booking confirmation, new booking, and trip update notifications

### 👨‍💼 Admin Panel (`/admin`)
- Dashboard with platform statistics
- Manage, edit, or delete user accounts
- Review and approve uploaded identity documents (Aadhaar, DL)
- Broadcast announcements to all users
- View SOS emergency logs
- Moderate user feedback and support queries

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.2 |
| Database | MySQL / MariaDB (3NF normalised schema) |
| Frontend | HTML5, CSS3, Vanilla JavaScript |
| Fonts & Icons | Google Fonts (Outfit), Boxicons |
| Email | Resend API, PHPMailer |
| SMS / OTP | Twilio SDK |
| Deployment | Docker (FrankenPHP), Railway |
| Dependencies | Composer |

---

## 🗂️ Project Structure

```
FlexiRide/
│
├── admin/                    # Admin-only pages (dashboard, users, docs, SOS logs)
│   ├── admin_dashboard.php
│   ├── admin_manage_users.php
│   ├── admin_edit_user.php
│   ├── admin_delete_user.php
│   ├── admin_verify_docs.php
│   ├── admin_sos_logs.php
│   ├── admin_broadcast.php
│   ├── admin_feedback.php
│   └── admin_queries.php
│
├── includes/                 # Shared PHP utilities
│   ├── db.php                # Database connection
│   ├── mailer.php            # Email sending (Resend API)
│   ├── sms.php               # SMS OTP (Twilio)
│   ├── navbar.php / footer.php
│   ├── dynamic_pricing.php   # Tiered pricing with peak-hour surge
│   ├── trust_score.php       # Weighted user trust scoring
│   └── geo_utils.php         # Haversine distance calculation
│
├── assets/js/                # Frontend JavaScript
├── screenshots/              # README screenshots
│
├── index.php                 # Landing page
├── login.php / logout.php
├── find_ride.php             # Ride search and listing
├── post_ride.php             # Ride creation form
├── book_ride.php             # Seat booking
├── booking_success.php
├── my_booked_rides.php       # Passenger's bookings
├── myrides.php               # Driver's posted rides
├── ride_details.php
├── edit_ride.php / delete_ride.php
├── reached.php               # Trip completion
├── receipt.php               # Trip receipt
├── chat.php                  # Driver–passenger messaging
├── rate_ride.php             # Post-trip ratings
├── profile.php / edit_profile.php
├── notifications.php
├── danger.php                # Emergency SOS trigger
├── digilocker_connect.php    # DigiLocker OAuth
├── forgot_password.php / forgot_otp.php / otp_verify.php
├── feedback.php / queries.php / about.php / privacy.php
│
├── flexiride.sql             # Full database schema + seed admin account
├── .env.example              # Environment variable template
├── Dockerfile                # FrankenPHP container config
└── composer.json             # PHP dependencies
```

---

## 🚀 Getting Started (Local — XAMPP)

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) with Apache and MySQL running
- PHP 8.0+

### Steps

1. **Clone the repository** into your XAMPP htdocs folder:
   ```bash
   git clone https://github.com/aravindkona18090/FlexiRide.git C:/xampp/htdocs/FlexiRide
   ```

2. **Import the database schema:**
   - Open [phpMyAdmin](http://localhost/phpmyadmin)
   - Click **Import** and select `flexiride.sql`
   - This creates the `flexiride` database with all tables and a default admin account

3. **Configure environment variables:**
   ```bash
   cp .env.example .env
   ```
   Edit `.env` with your database credentials and API keys.

4. **Install PHP dependencies:**
   ```bash
   composer install
   ```

5. **Open in browser:**
   ```
   http://localhost/FlexiRide
   ```

### Default Admin Login
| Field | Value |
|---|---|
| Email | `admin@flexiride.com` |
| Password | `Admin@123` |

> Prefer not to set up locally? Try the live demo: **[flexiride.up.railway.app](https://flexiride.up.railway.app/)**

---

## 🐳 Docker Deployment

The project includes a `Dockerfile` using [FrankenPHP](https://frankenphp.dev/) (PHP 8.2).

```bash
docker build -t flexiride .
docker run -p 8080:8080 --env-file .env flexiride
```

The app is deployed on Railway via this Docker config with environment variables set in the Railway dashboard — no secrets committed to the repository.

---

## 🔒 Security

- Passwords hashed with `password_hash()` (bcrypt)
- Session-based authentication with role separation (user / admin)
- All database queries use MySQLi prepared statements
- OTP-based phone verification and account recovery
- Aadhaar number validated with Verhoeff checksum before storage
- File uploads validated by server-side MIME type detection and renamed with random IDs
- Environment variables for all credentials (never hardcoded)
- Admin panel protected by explicit `is_admin` session role check on every page

---

## 👨‍💻 Author

**Kona Aravind Ranga Reddy**  
B.Tech, Artificial Intelligence & Machine Learning

- GitHub: [@aravindkona18090](https://github.com/aravindkona18090)

---

## 📄 License

This project is developed for educational and portfolio purposes.
