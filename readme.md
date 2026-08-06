# 🚗 FlexiRide

**Safe, affordable, shared rides — for everyone going the same way.**

A full-stack ride-sharing web application that connects drivers and passengers, inspired by services like BlaBlaCar. Post a ride, find a ride, book a seat, and travel with built-in safety features.

<p>
  <img src="https://img.shields.io/badge/PHP-777BB4?style=flat&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-4479A1?style=flat&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/JavaScript-F7DF1E?style=flat&logo=javascript&logoColor=black" alt="JavaScript">
  <img src="https://img.shields.io/badge/Composer-885630?style=flat&logo=composer&logoColor=white" alt="Composer">
  <img src="https://img.shields.io/badge/status-active-success" alt="status">
</p>

**🔗 Live Demo:** [flexiride.up.railway.app](https://flexiride.up.railway.app/)

---

## 📖 Overview

FlexiRide is a PHP and MySQL ride-sharing platform built around four things: **easy ride posting and discovery, secure profile management, OTP-based account recovery, and rider safety** — all wrapped in a straightforward booking flow with feedback collection and an admin dashboard to keep the platform healthy.

---

## ✨ Features

### 👤 User Module
- Registration & secure login
- Profile management with photo upload
- Forgot password via OTP
- Logout

### 🚘 Ride Management
- Post a ride
- Find available rides
- Book a ride
- View booked & posted rides
- Edit or delete ride details
- Mark a ride as reached

### 🛡️ Safety Features
- Emergency SOS ("Danger") button
- Automatic emergency email notifications
- User verification
- Privacy policy in-app

### ⭐ Feedback & Support
- Submit feedback
- Contact / queries module

### 👨‍💼 Admin Module
- Admin login & dashboard
- Manage, edit, or delete users
- View user feedback and queries

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, JavaScript |
| Backend | PHP |
| Database | MySQL |
| Libraries | PHPMailer, Composer |

---

## 🔒 Security

- Password hashing
- Session-based authentication
- OTP password recovery
- Prepared SQL statements
- Input validation

---

## 📂 Project Structure

```
FlexiRide/
│
├── images/
├── uploads/
├── PHPMailer/
├── vendor/
├── about.php
├── admin_dashboard.php
├── admin_manage_users.php
├── admin_edit_user.php
├── admin_delete_user.php
├── admin_feedback.php
├── admin_queries.php
├── book_ride.php
├── booking_success.php
├── danger.php
├── db.php
├── edit_profile.php
├── edit_ride.php
├── feedback.php
├── find_ride.php
├── forgot_password.php
├── forgot_otp.php
├── index.php
├── login.php
├── logout.php
├── myrides.php
├── my_booked_rides.php
├── otp_verify.php
├── post_ride.php
├── privacy.php
├── profile.php
├── queries.php
├── reached.php
├── rides.php
├── ride_output.php
├── ride_success.php
├── upload_photo.php
├── view_photos.php
├── blablacar_clone.sql
└── README.md
```

---

## 🚀 Getting Started

1. Install [XAMPP](https://www.apachefriends.org/).
2. Start **Apache** and **MySQL**.
3. Copy the project into:
   ```
   C:\xampp\htdocs\
   ```
4. Open phpMyAdmin and create a database named:
   ```
   blablacar_clone
   ```
5. Import:
   ```
   blablacar_clone.sql
   ```
6. Update the database credentials in:
   ```
   db.php
   ```
7. Open your browser:
   ```
   http://localhost/FlexiRide
   ```

> Prefer not to set up locally? Try the live demo instead: **[flexiride.up.railway.app](https://flexiride.up.railway.app/)**

---

## 📸 Screenshots

```
screenshots/
```

![Home](screenshots/home.png)
![Post Ride](screenshots/post-ride.png)
![Find Ride](screenshots/find-ride.png)
![My Rides](screenshots/myrides.png)

---

## 🎯 Future Enhancements

- Google Maps integration
- Live GPS tracking
- Ride ratings & reviews
- AI-based ride recommendations
- Real-time notifications
- Mobile application

---

## 👨‍💻 Author

**Kona Aravind Ranga Reddy**
B.Tech, Artificial Intelligence & Machine Learning

- GitHub: [@aravindkona18090](https://github.com/aravindkona18090)

---

## 📄 License

This project is developed for educational and portfolio purposes.
