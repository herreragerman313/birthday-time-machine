# ⏱️ Birthday Time Machine

Enter your birthday and travel back in time. The site shows the biggest song and movie of your birth year, what things cost then vs now, tech you would have used, what didn't exist yet (and what's gone forever), what happened on your birthday in history, the moon phase that night, NASA's space photo from that day, live "you by the numbers" counters and a shareable story card. The whole page restyles itself to match the decade you were born in.

**CIT 384 Final Project** by German Herrera

## Tech stack
- **Frontend:** HTML, CSS (decade themes with CSS variables), JavaScript (canvas, Web Audio, fetch)
- **Backend:** PHP 8 with PDO (prepared statements)
- **Database:** MySQL / MariaDB
- **APIs:** Wikipedia "On This Day", NASA APOD (called from PHP and cached in MySQL)
- **Hosting:** AWS Lightsail (Ubuntu, Apache), Let's Encrypt SSL

## Features
- Time travel intro animation with a whoosh sound (made with the Web Audio API, no audio files)
- 7 decade themes: 60s, 70s, 80s, 90s (Windows 95), 2000s (Y2K), 2010s (flat), 2020s (glass)
- Then vs now prices with inflation, stamps and minimum wage by exact date
- Then vs now tech, "Didn't exist yet", "Gone forever" and "Came and went in your lifetime"
- Moon phase drawn on a canvas, live counters, age on other planets
- Shareable 1080x1920 story card (download or share on phones)
- Accounts, saved trips with share links, view counts, compare two birthdays, site stats page

## Run it locally (XAMPP)
1. Copy this folder into `C:\xampp\htdocs\birthday-time-machine`
2. Start Apache and MySQL in the XAMPP Control Panel
3. Open phpMyAdmin, import `sql/schema.sql`, then `sql/seed_data.sql`
4. Copy `includes/config.sample.php` to `includes/config.php` and fill in your database login
5. Visit http://localhost/birthday-time-machine

## Security
- Passwords hashed with `password_hash()` (bcrypt)
- All SQL uses prepared statements (no SQL injection)
- All output escaped with `htmlspecialchars()` (no XSS)
- CSRF tokens on every form, session ID regenerated on login
- Login lockout after 5 wrong tries
- API keys live only on the server in `config.php` (ignored by Git)
- `.htaccess` blocks web access to `includes/`, `sql/` and `tools/`

## Data notes
Prices are approximate US averages (BLS CPI, EIA gas, NATO movie tickets, Big Mac Index). Years between data points are estimated with straight line interpolation in `tools/build_seed.py`. Songs are Billboard year end #1 singles; movies are the top US box office film released that year.
