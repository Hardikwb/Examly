# Examly

Examly is a server-rendered online examination management system for XAMPP. It uses PHP 8+, MySQL/MariaDB, PDO, HTML and CSS. It contains no JavaScript.

## Requirements

- XAMPP with PHP 8.0+ and MySQL/MariaDB
- PDO MySQL extension enabled
- Apache with `.htaccess` support
- A modern browser

## XAMPP installation

1. Copy this folder into `C:\xampp\htdocs\online-exam-app` (Windows) or the equivalent XAMPP `htdocs` directory.
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Open phpMyAdmin, select **Import**, and import `database/schema.sql` first. This creates the `online_exam` database and tables.
4. Import `database/seed.sql` to add the local demo accounts, example classes and sample examinations.
5. If your MySQL settings differ from XAMPP defaults, edit `config/database.php` or set `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` and `DB_PASSWORD` in the PHP environment.
6. Visit `http://localhost/online-exam-app/`. The app detects its path below the Apache document root. Set `APP_URL` if it is behind a reverse proxy or mounted at a custom URL.

Importing `schema.sql` drops and recreates the Examly tables. Back up any existing `online_exam` data before importing it again.

## Local development accounts

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@examly.local` | `Admin123!` |
| Teacher | `teacher@examly.local` | `Teacher123!` |
| Student | `student@examly.local` | `Student123!` |

Change the demo passwords before using the seeded database outside a local development environment. Students may register from the public site. Admin and teacher accounts are created by an administrator.

## Project structure

- `config/` — application and PDO connection settings
- `core/` — database access, sessions, validation, CSRF and responses
- `models/` — user, class, subject, exam, question, attempt and result queries
- `services/` — authentication, exam workflows, evaluation and notifications
- `middleware/` — reusable role and login checks
- `helpers/` — escaping, dates, auditing and auth helpers
- `components/` — shared public and dashboard layouts
- `admin/` — account, class, subject, exam, report and settings management
- `teacher/` — exam authoring, question management and result grading
- `student/` — exam schedule, server-rendered exam attempt and results
- `public/css/` — shared, dashboard and exam design system
- `database/` — MySQL schema and local sample data

The generic `Database` class uses prepared statements and a fixed table/column allowlist for identifiers. Entity-specific joins and workflow queries live in their models and services.

## Examination workflow

1. An admin creates accounts, classes, subjects, enrolls students and assigns teachers to a class/subject.
2. A teacher creates an exam draft for an assigned class, adds questions, then publishes it when ready.
3. A student enrolled in that class starts the exam during its scheduled window. The server creates an owned attempt, fixes its randomized question/option order, and persists responses as the student navigates.
4. The student submits the attempt, or the next request after expiry causes the server to finalize it. Objective questions are graded automatically. Descriptive responses wait for teacher grading.
5. Results are visible to the student after evaluation and the configured `result_publish_at` time. Leave that setting blank to release the result immediately after evaluation.

The remaining-time label is calculated when the page loads. Without client-side scripting, an inactive browser cannot refresh the countdown or submit itself at the deadline. The server checks the deadline on every navigation, save and submit request and finalizes an expired attempt on the next request. Students can also submit manually at any time during the allowed window.

## Email and password recovery

Password reset and contact messages use PHP's `mail()` function. For local XAMPP use, configure a local mail transport in `php.ini` (or use a configured sendmail-compatible service), then restart Apache. Set `MAIL_FROM` and `SUPPORT_EMAIL` in the PHP environment. Reset links are random, stored as hashes and expire after one hour. The app uses the same generic response for known and unknown email addresses.

## Security notes

- Passwords are stored with `password_hash()` and checked with `password_verify()`.
- Login requires the selected role to match the database role; role checks use the authenticated session.
- Sessions use HttpOnly and SameSite cookies and regenerate the session ID after login. Sessions expire after eight hours of inactivity.
- Remember-me uses a random, rotating cookie token with only its hash stored in the database.
- State-changing forms use CSRF tokens and POST/Redirect/GET.
- Teacher exam access is restricted to exams they own; students can access only their own attempts and enrolled class exams.
- Attempts lock after submission. Correct options are not sent to the active exam page.
- Apache rules deny direct web access to configuration, code, component and SQL folders.
- A Content Security Policy blocks scripts, and no JavaScript is included.

For an internet-facing deployment, use HTTPS, configure database credentials through environment variables, set up reliable email delivery and review the Apache/PHP/MySQL configuration for your environment.
