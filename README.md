# SMS — School Management System

A modern, secure, and responsive School Management System for **Grades 9–12**, built on PHP 8 + MySQL 8 + PDO + Bootstrap 5.

![Status](https://img.shields.io/badge/status-active-success)
![PHP](https://img.shields.io/badge/PHP-8%2B-blue)
![MySQL](https://img.shields.io/badge/MySQL-8%2B-blue)
![License](https://img.shields.io/badge/license-Proprietary-lightgrey)

---

## ✨ Features

### Core modules
- **Authentication** — login, logout, change password, forgot/reset password, brute-force throttling
- **Role-Based Access Control** — admin, teacher, student, parent with granular permissions
- **Admin Dashboard** — statistics, charts (Chart.js), recent activity, quick actions
- **Academic Management** — academic years, terms, grades 9–12, sections, subjects
- **Student Management** — CRUD, photo upload, enrollment, guardians (many-to-many), profile with 7 tabs
- **Teacher Management** — CRUD, photo upload, teacher assignments (year/grade/section/subject), class teacher flag
- **Attendance** — fast take UI (keyboard shortcuts P/A/L/E/J/K), history, reports, printable sheets
- **Examinations** — quizzes, assignments, midterm, final; exam subjects per section; status workflow (draft/open/completed/published)
- **Marks Entry** — grid entry with live percentage + grade preview, absent toggle, Enter-to-next
- **Grading Rules** — configurable boundaries with letter + points + remark
- **Results** — per-student/per-exam with color badges
- **Report Cards** — printable, school-branded
- **Announcements** — priority, audience targeting (everyone/teachers/students/parents/grade/section), publish/expire
- **Reports Hub** — academic performance, attendance analytics, student directory, class summary
- **Role Dashboards** — personalized views for student, teacher, parent
- **Administration** — school settings (with logo upload), users, roles & permissions matrix, audit logs
- **Security** — CSRF, session fingerprinting, rate limiting, security headers, `.htaccess` hardening

### UI/UX
- Modern, commercial SaaS-grade interface
- Responsive (desktop / tablet / mobile)
- Dark mode toggle (persisted)
- Collapsible sidebar with mobile drawer
- Breadcrumbs, notifications, user profile menu
- Toast notifications, empty states, loading states
- Semantic, accessible markup

---

## 🧰 Technology Stack

| Layer       | Technology                          |
|-------------|-------------------------------------|
| Backend     | PHP 8+, PDO                         |
| Database    | MySQL 8+ / MariaDB 10.4+            |
| Frontend    | HTML5, CSS3, JavaScript, Bootstrap 5 |
| Charts      | Chart.js 4                          |
| Icons       | Bootstrap Icons 1.11                |
| Fonts       | Inter (Google Fonts)                |
| Server      | Apache (XAMPP)                      |

---

## 📁 Project Structure
