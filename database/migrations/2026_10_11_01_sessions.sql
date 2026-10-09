-- Logins and carts stored in the database (SESSION_DRIVER=database), for hosts where each
-- request may run on a different server (Vercel and other serverless platforms).
CREATE TABLE IF NOT EXISTS sessions (
  id VARCHAR(128) NOT NULL PRIMARY KEY,
  data MEDIUMBLOB NOT NULL,
  last_activity INT UNSIGNED NOT NULL,
  INDEX idx_sessions_last_activity (last_activity)
) ENGINE=InnoDB;
