<?php
// Birthday Time Machine configuration
// Copy this file to config.php and fill in your own values.
// config.php is ignored by Git so your passwords never end up on GitHub.

// Server 1: 'localhost'. Server 2: the PRIVATE IP of Server 1 (it holds the shared database).
define('DB_HOST', 'localhost');
define('DB_NAME', 'birthday_time_machine');
define('DB_USER', 'btm_user');
define('DB_PASS', 'change_this_password');

// Free key from https://api.nasa.gov (DEMO_KEY works but has a low limit)
define('NASA_API_KEY', 'DEMO_KEY');

// Used in the shareable card and links
define('SITE_URL', 'https://birthdaytimemachine.online');

// Shown in the footer so you can see the load balancer switching servers
define('SERVER_LABEL', 'Server 1');
define('SITE_NAME', 'Birthday Time Machine');

// Contact info sent to Wikipedia with each request (their API rules ask for this)
define('CONTACT_EMAIL', 'you@example.com');

// Set to false on the live server so errors are not shown to visitors
define('DEBUG', true);
