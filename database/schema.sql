-- Helping Hands Foundation: schema and sample data
-- Run as root in MySQL Workbench. WARNING: this deletes existing data.

CREATE DATABASE IF NOT EXISTS helping_hands
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE helping_hands;

DROP TABLE IF EXISTS fund_allocations;
DROP TABLE IF EXISTS donations;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS campaigns;
DROP TABLE IF EXISTS donors;

CREATE TABLE donors (
  donor_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name   VARCHAR(100) NOT NULL,
  email       VARCHAR(150) NOT NULL,
  phone       VARCHAR(20)  NULL,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (donor_id),
  UNIQUE KEY uq_donors_email (email)
);

CREATE TABLE campaigns (
  campaign_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title          VARCHAR(150) NOT NULL,
  description    TEXT NULL,
  target_amount  DECIMAL(12,2) NOT NULL,
  start_date     DATE NOT NULL,
  end_date       DATE NOT NULL,
  status         ENUM('draft','active','closed') NOT NULL DEFAULT 'draft',
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (campaign_id),
  CONSTRAINT chk_campaign_target CHECK (target_amount > 0),
  CONSTRAINT chk_campaign_dates  CHECK (end_date >= start_date)
);

CREATE TABLE donations (
  donation_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  donor_id       INT UNSIGNED NOT NULL,
  campaign_id    INT UNSIGNED NOT NULL,
  amount         DECIMAL(12,2) NOT NULL,
  payment_mode   ENUM('cash','upi','bank_transfer','cheque','card') NOT NULL,
  status         ENUM('pending','confirmed','failed','refunded') NOT NULL DEFAULT 'pending',
  donation_date  DATE NOT NULL DEFAULT (CURRENT_DATE),
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (donation_id),
  CONSTRAINT fk_donations_donor
    FOREIGN KEY (donor_id) REFERENCES donors (donor_id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_donations_campaign
    FOREIGN KEY (campaign_id) REFERENCES campaigns (campaign_id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_donation_amount CHECK (amount > 0)
);

CREATE TABLE fund_allocations (
  allocation_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  campaign_id      INT UNSIGNED NOT NULL,
  purpose          VARCHAR(200) NOT NULL,
  amount           DECIMAL(12,2) NOT NULL,
  allocation_date  DATE NOT NULL DEFAULT (CURRENT_DATE),
  status           ENUM('planned','approved','cancelled') NOT NULL DEFAULT 'planned',
  notes            TEXT NULL,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (allocation_id),
  CONSTRAINT fk_alloc_campaign
    FOREIGN KEY (campaign_id) REFERENCES campaigns (campaign_id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_alloc_amount CHECK (amount > 0)
);

CREATE TABLE admins (
  admin_id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username       VARCHAR(50)  NOT NULL,
  email          VARCHAR(150) NOT NULL,
  password_hash  VARCHAR(255) NOT NULL,
  is_active      TINYINT      NOT NULL DEFAULT 1,
  last_login     TIMESTAMP    NULL,
  created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (admin_id),
  UNIQUE KEY uq_admins_username (username),
  UNIQUE KEY uq_admins_email (email)
);

CREATE TABLE contact_messages (
  message_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name         VARCHAR(100) NOT NULL,
  email        VARCHAR(150) NOT NULL,
  subject      VARCHAR(150) NOT NULL,
  message      TEXT         NOT NULL,
  is_read      TINYINT      NOT NULL DEFAULT 0,
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (message_id),
  CONSTRAINT chk_contact_name CHECK (CHAR_LENGTH(TRIM(name)) > 0),
  CONSTRAINT chk_contact_message CHECK (CHAR_LENGTH(TRIM(message)) > 0)
);

-- Sample data (fictional)
INSERT INTO donors (full_name, email, phone) VALUES
 ('Asha Verma', 'asha.verma@example.com', '9000000001'),
 ('Rohan Mehta', 'rohan.mehta@example.com', NULL);

INSERT INTO campaigns (title, description, target_amount, start_date, end_date, status) VALUES
 ('Books for Every Child', 'Provide school kits to 500 children.', 250000.00, '2026-09-01', '2026-12-31', 'active'),
 ('Clean Water Drive', 'Install water filters in 10 villages.', 400000.00, '2026-10-01', '2027-03-31', 'active');

INSERT INTO donations (donor_id, campaign_id, amount, payment_mode, status, donation_date) VALUES
 (1, 1, 5000.00,  'upi',           'confirmed', '2026-09-10'),
 (1, 2, 2500.00,  'bank_transfer', 'confirmed', '2026-10-02'),
 (2, 1, 10000.00, 'cash',          'confirmed', '2026-09-15'),
 (2, 2, 3000.00,  'upi',           'pending',   '2026-10-08');

INSERT INTO fund_allocations (campaign_id, purpose, amount, allocation_date, status) VALUES
 (1, 'School kits purchase', 8000.00, '2026-09-20', 'approved'),
 (1, 'Printing and packaging', 2000.00, '2026-09-25', 'planned'),
 (2, 'Water filter units', 2000.00, '2026-10-05', 'approved');

INSERT INTO contact_messages (name, email, subject, message) VALUES
 ('Alina Shaikh', 'alina.shaikh@example.com', 'Volunteering', 'How can I volunteer on weekends?');