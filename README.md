# CoreCount - Web-Based Fitness Management System

CoreCount is a comprehensive web-based fitness application built using PHP and MySQL. It helps users achieve fitness goals through structured workout programs, progress tracking, and personalized scheduling.

---

## 🚀 Features

- 5 Workout Categories: Cardio, Strength, Flexibility, Core, and HIIT.
- Beginner to Advanced levels.
- Structured exercise sequences and guidance.
- User authentication and dashboard.
- Personalized workout schedule.
- Progress tracking.

---

## 🛠️ Requirements

- WAMP Server (or any local Apache + PHP + MySQL stack)

---

## ⚙️ Setup Instructions

1. **Download & Install WAMP Server**  
   [WAMP Official Site](https://www.wampserver.com/en/)

2. **Copy Project Folder**
   - Place all source files into a folder named `corecount`.

3. **Move Project to WAMP Directory**
   - Copy the `corecount` folder to:
     ```
     C:\wamp64\www\
     ```

4. **Import the SQL Files**
   - Launch **WAMP Server** and open **phpMyAdmin**.
   - Create a new database (name it anything, e.g., `corecount`).
   - Import both:
     - `admin_sql.sql`
     - `database.sql`  
     *(located in `corecount/sql/` folder)*

5. **Run the Website**
   - Open your browser and go to:
     ```
     http://localhost/corecount
     ```

---

## 📂 SQL Files

Stored in `sql/` folder:
- `admin_sql.sql` – Admin panel structure
- `database.sql` – User and workout database schema

---

## 🧑‍💻 Author

Alfred Arouza

---

## 📄 License

This project is open-source for educational and personal use.
