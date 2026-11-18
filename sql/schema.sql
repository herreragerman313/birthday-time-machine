-- Birthday Time Machine database schema
-- Run this first, then run seed_data.sql

CREATE DATABASE IF NOT EXISTS birthday_time_machine
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE birthday_time_machine;

-- ---------- User accounts ----------
CREATE TABLE IF NOT EXISTS users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(30)  NOT NULL UNIQUE,
  email         VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Time Machine pages a user saved (each one gets a share link)
CREATE TABLE IF NOT EXISTS saved_pages (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT          NOT NULL,
  slug       CHAR(10)     NOT NULL UNIQUE,
  title      VARCHAR(60)  NOT NULL,
  birth_date DATE         NOT NULL,
  views      INT          NOT NULL DEFAULT 0,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Every trip in the time machine (powers the Site Stats page)
CREATE TABLE IF NOT EXISTS lookups (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  birth_date DATE     NOT NULL,
  user_id    INT      NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (birth_date),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------- Reference data (filled by seed_data.sql) ----------
-- One row per year: prices, inflation index, biggest song and movie
CREATE TABLE IF NOT EXISTS year_facts (
  year            SMALLINT PRIMARY KEY,
  cpi             DECIMAL(7,2)  NOT NULL,   -- consumer price index (1982-84 = 100)
  gas_gallon      DECIMAL(5,2)  NULL,       -- US average, regular
  movie_ticket    DECIMAL(5,2)  NULL,       -- US average
  big_mac         DECIMAL(5,2)  NULL,       -- US price
  new_car         INT           NULL,       -- average new car price
  song_title      VARCHAR(150)  NULL,       -- Billboard year end #1 single
  song_artist     VARCHAR(150)  NULL,
  movie_title     VARCHAR(150)  NULL        -- top domestic box office (by release year)
) ENGINE=InnoDB;

-- Price changes that happen on exact dates (stamps, minimum wage)
CREATE TABLE IF NOT EXISTS dated_prices (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  item       VARCHAR(30)  NOT NULL,         -- 'stamp' or 'min_wage'
  start_date DATE         NOT NULL,
  price      DECIMAL(6,2) NOT NULL,
  INDEX (item, start_date)
) ENGINE=InnoDB;

-- Things that launched (and sometimes died) - powers "Didn't Exist Yet" and "Gone Forever"
CREATE TABLE IF NOT EXISTS inventions (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(80)  NOT NULL,
  emoji      VARCHAR(16)  NOT NULL,
  start_date DATE         NOT NULL,
  end_date   DATE         NULL,
  note       VARCHAR(150) NULL
) ENGINE=InnoDB;

-- What tech people used in each era - powers "Then vs Now Tech"
CREATE TABLE IF NOT EXISTS tech_eras (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  category   VARCHAR(20)  NOT NULL,         -- phone, music, internet, gaming, tv
  start_year SMALLINT     NOT NULL,
  end_year   SMALLINT     NOT NULL,
  label      VARCHAR(120) NOT NULL,
  detail     VARCHAR(200) NULL,
  INDEX (category, start_year)
) ENGINE=InnoDB;

-- ---------- API caches (so we stay under free API limits) ----------
CREATE TABLE IF NOT EXISTS cache_onthisday (
  month_day  CHAR(5)  PRIMARY KEY,          -- 'MM-DD'
  payload    MEDIUMTEXT NOT NULL,
  fetched_at DATETIME NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cache_apod (
  apod_date  DATE     PRIMARY KEY,
  payload    TEXT     NOT NULL,
  fetched_at DATETIME NOT NULL
) ENGINE=InnoDB;

-- ---------- Login sessions (shared by both web servers behind the load balancer) ----------
CREATE TABLE IF NOT EXISTS sessions (
  id         VARCHAR(128) PRIMARY KEY,
  data       MEDIUMTEXT   NOT NULL,
  expires_at DATETIME     NOT NULL,
  INDEX (expires_at)
) ENGINE=InnoDB;
