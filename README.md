# 🚀 TaskFlow — Modern PHP Todo App

A modern full-stack productivity dashboard built with PHP, MySQL, Tailwind CSS, and Vanilla JavaScript.

TaskFlow is a responsive SaaS-inspired task management application featuring authentication, analytics, AJAX interactions, profile customization, dark/light mode, and a polished dashboard UI.

---

# ✨ Features

## 🔐 Authentication

* User registration & login
* Secure password hashing with `password_hash()` / `password_verify()`
* Session-based authentication
* Protected routes and ownership validation

---

## ✅ Task Management

* Create, edit, update, and delete tasks
* Task priorities:

  * High
  * Medium
  * Low
* Category assignment system
* Due date management
* Overdue task highlighting

---

## 💬 AJAX Comment System

* Add comments without page reload
* Inline comment editing
* Comment deletion
* Real-time UI updates

---

## 📊 Analytics Dashboard

* Total tasks counter
* Completed tasks counter
* Pending tasks counter
* Overdue tasks counter
* Interactive Chart.js donut chart

---

## 🎨 Theme System

* Dark / Light mode support
* Theme persistence using:

  * `localStorage`
  * database `theme_preference`
* Smooth theme transitions

---

## 👤 Profile System

* Avatar upload
* Username editing
* Password changing
* Profile dropdown menu
* Theme preference saving

---

## 🔔 UX Enhancements

* Toast notification system
* Responsive SaaS-inspired UI
* Smooth hover and transition animations
* Modern dashboard layout

---

# 🛠 Tech Stack

| Layer           | Technology            |
| --------------- | --------------------- |
| Back-end        | PHP 8+                |
| Database        | MySQL / MariaDB       |
| Database Access | PDO                   |
| Front-end       | Tailwind CSS          |
| JavaScript      | Vanilla JavaScript    |
| Charts          | Chart.js              |
| Architecture    | Modular PHP Structure |

---

# 📁 Project Structure

```bash
TodoApp/
│
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
│
├── config/
├── helpers/
├── includes/
├── migrations/
├── public/
│   ├── auth/
│   ├── comments/
│   ├── tasks/
│   └── uploads/
│
├── scripts/
└── README.md
```

---

# ⚙️ Installation

## 1️⃣ Clone Repository

```bash
git clone https://github.com/RITH-1437/todo_app.git

cd taskflow
```

---

## 2️⃣ Create Database

```sql
CREATE DATABASE todo_app
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

---

## 3️⃣ Configure Environment

Create:

```bash
.env
```

Example:

```env
DB_HOST=localhost
DB_NAME=todo_app
DB_USER=root
DB_PASS=
```

---

## 4️⃣ Run Migrations

```bash
php scripts/migrate.php
```

---

## 5️⃣ Start Development Server

```bash
php -S localhost:8080 -t public
```

---

## 6️⃣ Open Browser

```txt
http://localhost:8080/public/auth/login.php
```

---

# 📸 Screenshots

## Dashboard

* Analytics cards
* Donut chart
* Task management UI

## Authentication

* Modern dark login/register pages

## Profile Settings

* Avatar upload
* Theme settings

> Add screenshots here later for a more professional portfolio presentation.

---

# 🔒 Security Features

* PDO prepared statements
* Password hashing
* Session regeneration
* Ownership validation
* Secure avatar upload validation
* Escaped output with `htmlspecialchars()`

---

# 🌟 Future Improvements

* Kanban board
* Calendar planner
* Team collaboration
* Real-time notifications
* Drag & drop tasks
* Email reminders
* REST API version
* Docker deployment

---

# 📌 Inspiration

UI inspired by:

* Linear
* Vercel
* Notion
* Modern SaaS dashboards

---

# 👨‍💻 Author

Developed by Nairith & Lyhor.

If you like this project, feel free to ⭐ the repository.
