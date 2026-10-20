PERSONALIZED TRAVEL COMPANION — PHP + MySQL starter project
===========================================================

Matches the uploaded synopsis/UI: Karnataka destinations, guest browsing, registration/login,
destination search/filter, trip planning, day-wise itinerary, budget estimates/alerts, trip CRUD,
and admin management of destinations/categories/activities.

REQUIREMENTS
- XAMPP (Apache + MySQL + PHP 8+)
- Browser
- phpMyAdmin

INSTALL
1. Extract the folder `personalized_travel_companion` into:
   C:\xampp\htdocs\
2. Start Apache and MySQL in XAMPP.
3. Open http://localhost/phpmyadmin
4. Import `database.sql`.
5. Check `config.php`. Default XAMPP credentials are user `root`, blank password.
6. Open http://localhost/personalized_travel_companion/

ADMIN ACCOUNT
Register a normal account first. Then in phpMyAdmin run:
UPDATE users SET role='admin' WHERE email='your-email@example.com';
Log out and log in again. Admin menu will appear.

IMPORTANT
- This is a functional academic starter, not a production-hardened commercial system.
- The estimated costs and destination/activity data are sample data. Update them with verified data.
- The project intentionally does not include real bookings, payments, GPS, live weather, or route optimization,
  consistent with the synopsis scope.
- Use a strong password and never deploy the sample project publicly without security review.
