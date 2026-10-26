PERSONALIZED TRAVEL COMPANION — PHP + MySQL (Frontend & Backend Modular Architecture)
======================================================================================

Matches the Karnataka travel planning system synopsis: guest browsing, registration/login,
destination search/filter, trip planning, day-wise itinerary, budget tracking/alerts, trip CRUD,
simulated booking checkout, and comprehensive admin management.

PROJECT ARCHITECTURE
====================
The project is cleanly separated into Frontend and Backend layers, with dedicated Admin sub-modules:

personalized_travel_companion/
│
├── index.php                 <-- Root entry router (forwards to frontend/index.php)
├── database.sql              <-- MySQL Database schema & starter dataset
├── README.txt                <-- Project overview and installation guide
│
├── backend/                  <-- Data layer, models & business processing logic
│   ├── config.php            <-- PDO Database connection & path constants
│   ├── helpers.php           <-- Sanitization, auth checks, CSRF, flash & routing helpers
│   ├── auth.php              <-- User login, registration, logout & profile business logic
│   ├── destinations.php      <-- Destination queries, search/filtering & activity retrieval
│   ├── trips.php             <-- Trip creation, day-wise itinerary management, expenses & CRUD
│   └── admin/                <-- Backend logic for administrative tasks
│       ├── admin_data.php    <-- Overview statistics, counts & reporting data
│       └── admin_actions.php <-- CRUD actions (destinations, categories, activities, security)
│
└── frontend/                 <-- Presentation layer (pages, templates & static assets)
    ├── index.php             <-- Home / Destination browsing & filter page
    ├── destination.php       <-- Destination details & activity listing page
    ├── plan_trip.php         <-- Trip planner & activity selector
    ├── itinerary.php         <-- Day-wise itinerary schedule & live budget tracker
    ├── my_trips.php          <-- User trips dashboard (Upcoming, Past, Draft)
    ├── edit_trip.php         <-- Trip date & budget editing view
    ├── login.php             <-- User login form
    ├── register.php          <-- User registration form
    ├── logout.php            <-- Logout action & session cleanup
    ├── profile.php           <-- Profile management form
    ├── dummy_payment.php     <-- Simulated secure checkout modal
    │
    ├── includes/             <-- Reusable UI layouts & components
    │   ├── header.php        <-- Responsive navigation bar, active links & alerts
    │   └── footer.php        <-- Footer markup & script loading
    │
    ├── assets/               <-- Static CSS and JavaScript
    │   ├── style.css         <-- Core application stylesheet
    │   └── app.js            <-- Confirmation modals & live budget watching
    │
    ├── images/               <-- Destination photos and uploaded images
    │
    └── admin/                <-- Frontend Administration Module
        ├── index.php         <-- Admin Dashboard UI (Destinations, Categories, Activities, Security)
        └── assets/
            └── admin.js      <-- Admin UI logic, modal binding, toasts & text-to-speech reader

REQUIREMENTS
============
- WAMP / XAMPP (Apache + MySQL + PHP 8+)
- Web Browser
- phpMyAdmin

INSTALLATION
============
1. Place `personalized_travel_companion` into your web root (e.g. `C:\wamp64\www\vikki\...` or `C:\xampp\htdocs\`).
2. Start Apache and MySQL in your WAMP / XAMPP server.
3. Open phpMyAdmin (http://localhost/phpmyadmin).
4. Create database `travel_companion` and import `database.sql`.
5. Check `backend/config.php` to ensure MySQL credentials match your setup (default is user `root`, empty password).
6. Open http://localhost/.../personalized_travel_companion/ in your browser.

ADMIN ACCESS
============
1. Register a normal account through the frontend register page.
2. In phpMyAdmin, run:
   UPDATE users SET role='admin' WHERE email='your-email@example.com';
3. Log out and log back in; the "Admin" button will appear in the navigation bar.
