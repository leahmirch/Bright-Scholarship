# Bright Scholarship Application System

## Overview

The **Bright Scholarship Application System** is a web-based platform designed to facilitate the scholarship application process for students and streamline review processes for committee members and administrators. The system includes separate dashboards for students, committee members, and administrators, each with tailored functionalities to support their roles in the application workflow.

## Table of Contents

- [Features](#features)
- [System Requirements](#system-requirements)
- [Installation and Setup](#installation-and-setup)
- [Usage](#usage)
  - [Student Role](#student-role)
  - [Committee Role](#committee-role)
  - [Admin Role](#admin-role)
- [Database Structure](#database-structure)
- [Notes and Known Limitations](#notes-and-known-limitations)

---

## Features

### 1. Student Dashboard
- Application submission form.
- Status tracking for each application with detailed discrepancy messages, if applicable.
- Access to application history and notification center.

### 2. Committee Dashboard
- View, sort, and filter pending applications for review.
- Voting and comments section for each application.
- Access to a voting history log and notifications.

### 3. Admin Dashboard
- Comprehensive application overview with summary statistics.
- Full access to all applications, with search and filtering options.
- User management functionalities: add/edit/deactivate users.
- System logs and reports for tracking application changes and user activities.

---

## System Requirements

- PHP 7.4 or higher
- Python 3.6 or higher (for verification scripts)
- SQLite (for database storage)
- Web server (e.g., Apache or Nginx)
- Email server configured for sending notifications (optional but recommended)

---

## Installation and Setup

1. **Clone the repository**:
   ```bash
   git clone https://github.com/yourusername/bright-scholarship.git
   cd bright-scholarship
   ```

2. **Set up the environment**:
   - Place the project folder in your web server’s root directory (e.g., `htdocs` for XAMPP).
   - Ensure PHP, SQLite, and Python are installed on the server.

3. **Database Configuration**:
   - Run `db.php` and `registrar_db.php` files to initialize the databases and tables. This will create the SQLite databases and populate sample data.

4. **File Permissions**:
   - Ensure the web server has read/write permissions on the database files.

5. **Configure the Web Server**:
   - If using Apache, ensure `.htaccess` files are enabled for security.
   - Start the server and open the website in your browser.

---

## Usage

### Student Role
- **Register**: Students register an account and log in to access their dashboard.
- **Submit Application**: Fill out the application form, which includes details like GPA, credit hours, and age.
- **View Application Status**: Track application progress and view any notifications or discrepancy messages.
- **History and Notifications**: Access a log of all previous applications and receive updates from the committee.

### Committee Role
- **Review Applications**: View and filter pending applications for review.
- **Vote and Comment**: Approve or decline applications, add remarks, and review registrar verification details.
- **Voting History**: Access a record of previous votes and the committee's comments.

### Admin Role
- **Manage Applications**: View all applications, including search and filtering functionalities.
- **User Management**: Add/edit committee members, deactivate/reactivate accounts.
- **Application Logs and Reports**: Access application logs and system reports, and export data for analysis.
- **Notifications and Alerts**: Review high-priority alerts for applications flagged for further review.

---

## Database Structure

The website uses two SQLite databases:
1. **`bright_scholarship_db.sqlite`** - Stores application data, users, votes, notifications, and logs.
2. **`registrar_db.sqlite`** - Holds the mock registrar's office data used for application verification.

### Database Tables

- **Users**: Stores user information, including roles (student, committee, admin).
- **Applications**: Stores application data, status, and associated student IDs.
- **Application Status Log**: Logs changes in application status with remarks.
- **Votes**: Records committee votes on each application.
- **Notifications**: Manages notifications for user actions.
- **Registrar Records**: Holds the registrar's mock records for verification.

---

## Notes and Known Limitations

- **Email Server**: Email notifications require a configured email server. By default, the `send_email` function uses the PHP `mail()` function, but it can be modified for SMTP or a third-party email API.
- **Python Integration**: Application verification requires Python. Ensure the server can execute Python scripts and that `verify_application.py` has execute permissions.
- **Security**: For production, implement stronger password policies and consider HTTPS.
