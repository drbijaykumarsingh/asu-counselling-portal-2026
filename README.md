# Assam Skill University Admission Counselling Portal 2026

<div align="center">

<img src="images/ASU_logo.png" width="150">

## Assam Skill University (ASU)

### Admission Counselling Portal 2026

A centralised web-based admission counselling and seat allocation system developed for **Assam Skill University** to streamline the complete admission workflow from applicant verification to final admission.

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge\&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-8.x-4479A1?style=for-the-badge\&logo=mysql)
![Apache](https://img.shields.io/badge/Apache-2.4-D22128?style=for-the-badge\&logo=apache)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?style=for-the-badge\&logo=bootstrap)

</div>

---

# Overview

The **ASU Admission Counselling Portal 2026** is a web-based application developed to automate and manage the complete admission counselling process at Assam Skill University.

The portal eliminates manual admission processing by providing a centralised workflow for:

* Student verification
* Entrance data processing
* Seat allocation
* Department approval
* Finance verification
* HOD approval
* Final admission
* Report generation
* Live seat availability

The system supports multiple administrative roles with role-based authentication and provides real-time seat monitoring throughout the counselling process.

---

# Key Features

## Student Counselling

* Student search using registration details
* Candidate verification
* Admission processing
* Seat availability checking
* Admission confirmation

---

## Seat Management

* Real-time seat availability
* Department-wise seat allocation
* Seat transfer
* Seat updates
* Seat monitoring dashboard

---

## Administrative Modules

* Upload student database
* Upload entrance examination data
* Department verification
* Finance verification
* HOD approval
* Final admission approval
* User management

---

## Reports

Generate various reports, including:

* Admitted students
* Students in the counselling pipeline
* Rejected candidates
* Seat availability reports
* Dashboard statistics

---

## Public Portal

The system provides a public-facing seat availability dashboard allowing applicants to monitor available seats in real time.

---

## Authentication

* Secure login system
* Session management
* Password change
* Role-based access control

---

## QR Code Support

Integrated QR Code generation for admission-related activities using the PHP QR Code library.

---

# Technology Stack

| Technology | Version                            |
| ---------- | ---------------------------------- |
| PHP        | 8.x                                |
| MySQL      | 8.x                                |
| Apache     | WAMP Server                        |
| HTML5      | ✔                                  |
| CSS3       | ✔                                  |
| JavaScript | ✔                                  |
| Bootstrap  | ✔                                  |
| Python     | Used for XLSX to CSV preprocessing |

---

# Project Structure

```text
.
├── admin/                 # Administrative modules
├── auth/                  # Authentication
├── config/                # Database & application configuration
├── counselling/           # Counselling workflow
├── dashboard/             # Dashboard
├── public/                # Public seat display
├── report/                # Reports
├── seat/                  # Seat management
├── sqls/                  # Database scripts
├── images/                # Logos & assets
├── css/                   # Stylesheets
├── phpqrcode/             # QR Code library
└── index.php
```

---

# User Roles

The portal supports multiple administrative roles, including:

* Administrator
* Counselling Staff
* Department
* Finance Section
* Head of Department (HOD)

Each role has access only to the modules required for their responsibilities.

---

# Installation

## Requirements

* PHP 8.x
* MySQL 8.x
* Apache (WAMP/XAMPP/LAMP)
* Web Browser

---

## Clone Repository

```bash
git clone https://github.com/drbijaykumarsingh/asu-counselling-portal-2026.git
```

---

## Database Setup

1. Create a MySQL database.

2. Import one of the SQL files available inside:

```
sqls/
```

Example:

```
asu_portal.sql
```

3. Update database credentials in:

```
config/db.php
```

---

## Configure Web Server

Place the project inside your web server directory.

Example:

```
C:\wamp64\www\
```

or

```
/var/www/html/
```

---

## Launch

Open your browser and navigate to:

```
http://localhost/asu-counselling-portal-2026
```

---

# Main Modules

* Authentication
* Dashboard
* Counselling
* Seat Allocation
* Seat Transfer
* Student Upload
* Entrance Upload
* Finance Verification
* Department Verification
* HOD Verification
* Reports
* Public Seat Dashboard

---

# Security Features

* Session-based authentication
* Role-based authorisation
* Database-driven access control
* Secure login/logout workflow

---

# Future Enhancements

* Online student registration
* OTP-based authentication
* SMS & Email notifications
* PDF admission letters
* Payment gateway integration
* Student self-service portal
* REST API
* Audit logging
* Analytics dashboard
* Mobile responsive interface

---

# Screenshots

Screenshots of the portal can be added here.

```
docs/screenshots/
```

---

# Author

**Dr. Abdul Hannan**
**Mr. Sagar Kalita**
**Mr. Bijay Kumar Singh**

Department of Information Technology
Assam Skill University, Mangaldoi

---

# License

This project is developed for **Assam Skill University**.

For academic and institutional use only unless otherwise specified.

---

# Acknowledgements

* Assam Skill University
* Admission Committee
* PHP Community
* MySQL Community
* PHP QR Code Library

---
