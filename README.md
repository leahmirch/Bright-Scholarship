# Bright Scholarship Application System

## Overview

The **Bright Scholarship Application System** is a web-based platform designed to streamline the scholarship application process for students and facilitate review and selection processes for committee members and administrators. This system provides individual dashboards for students, committee members, and administrators, with tailored functionalities to suit each role.

## Table of Contents

- [Features](#features)
- [System Requirements](#system-requirements)
- [Installation and Setup](#installation-and-setup)
- [Database Creation and Configuration](#database-creation-and-configuration)
- [Code Structure](#code-structure)
- [Usage](#usage)
  - [Student Role](#student-role)
  - [Committee Role](#committee-role)
  - [Admin Role](#admin-role)
- [Database Structure](#database-structure)
- [Notes and Known Limitations](#notes-and-known-limitations)

---

## Features

### 1. Student Dashboard
- Application submission form with eligibility checks.
- Track application status, receive notifications, and view application history.

### 2. Committee Dashboard
- View, filter, and review applications submitted by students.
- Cast votes and add remarks on applications.
- Access voting history and receive notifications for application submissions and updates.

### 3. Admin Dashboard
- Manage and monitor all applications with search and filtering.
- Handle user management tasks, including role updates and deactivation.
- Generate reports on applications and users, view system logs, and receive notifications for application updates.

---

## System Requirements

- PHP 7.4 or higher
- SQLite (for database storage)
- Web server (e.g., Apache or Nginx via Xampp)

---

## Installation and Setup

1. **Clone the repository**:
   ```bash
   git clone https://github.com/leahmirch/Bright-Scholarship.git
   cd Bright-Scholarship
   ```

2. **Set up the environment**:
   - Place the project folder in your web server’s root directory (e.g., `htdocs` for XAMPP).
   - Ensure PHP, SQLite, and Python are installed on the server.

3. **Configure the Web Server**:
   - Enable `.htaccess` for added security (if using Apache).
   - Start the server and open the website in your browser via 'http://localhost/Bright-Scholarship/index.php'.

## Database Creation and Configuration

### Creating the Database Files

To create and configure the SQLite databases, follow these steps:

1. **Initialize the `bright_scholarship_db.sqlite` database**:
   - The `db.php` file creates the primary database, `bright_scholarship_db.sqlite`, and sets up tables such as `users`, `applications`, `notifications`, and others.
   - Run the `db.php` file directly in the browser by entering: http://localhost/Bright-Scholarship/db.php.

2. **Initialize the `registrar_db.sqlite` database**:
   - The `registrar_db.php` file creates a mock database for the registrar’s office, which contains student academic records for verification.
   - Run the `registrar_db.php` file directly in the browser by entering: http://localhost/Bright-Scholarship/registrar_db.php.

## Code Structure

### Explanation of Each File

- **admin_dashboard.php**: Admin dashboard where administrators can manage applications, update user roles, and export data. Admins can see all applications and user activities.
- **committee_dashboard.php**: Committee dashboard allowing members to review applications, vote on applicants, and receive notifications for application status updates and submissions.
- **db.php**: Sets up the main `bright_scholarship_db.sqlite` database with tables for users, applications, voting, notifications, and application status logs.
- **index.php**: Home page introducing the Bright Scholarship program with links to login and registration.
- **login.php**: Authentication page for users, redirecting them to the appropriate dashboard based on their role.
- **logout.php**: Terminates the user session and redirects to the home page.
- **register.php**: Allows students to create an account with validation on username, email, and password requirements.
- **registrar_db.php**: Creates the `registrar_db.sqlite` database containing mock student records for verification purposes.
- **student_dashboard.php**: Student dashboard where students submit applications, view application status, and receive notifications.
- **voting.php**: Contains functions for the committee voting system, including functions to determine top candidates and the final winner based on voting results.

---

## Usage

### Student Role
- **Register**: Students create an account and log in to access their dashboard.
- **Submit Application**: Students submit their scholarship applications, which include GPA, credit hours, and age checks.
- **View Status**: Students can track their application’s progress and receive notifications of any updates.
- **Application History**: Students can view past applications and any relevant remarks from the committee.
- **Receive Reimbursement Notification**: If awarded, students receive a notification directly from the Accounting Department confirming their reimbursement amount.

### Committee Role
- **Review Applications**: Committee members view and assess submitted applications.
- **Vote on Applications**: Committee members can approve or decline applications and add comments.
- **Receive Notifications**: Committee members are notified of new submissions and status changes on applications.

### Admin Role
- **Manage Applications**: Admins view, filter, and update all applications.
- **User Management**: Admins can manage user accounts and update roles.
- **Data Export**: Admins can export application and user data in CSV format.
- **Notifications**: Admins receive notifications for significant application updates.

---

## Database Structure

The system uses two SQLite databases:

1. **`bright_scholarship_db.sqlite`**:
   - **`users`**: Contains user information, including role and application history.
   - **`applications`**: Stores application data for each user, including GPA, credit hours, and age.
   - **`application_status_log`**: Logs changes in application status with timestamps and remarks.
   - **`notifications`**: Tracks notifications for each user.
   - **`committee_votes`**: Records votes from committee members.
   - **`application_session`**: Manages application sessions (open/closed).

2. **`registrar_db.sqlite`**:
   - **`registrar_records`**: Holds mock student records for verifying applications against official registrar records.

---