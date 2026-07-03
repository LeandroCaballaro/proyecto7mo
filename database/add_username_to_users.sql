USE proyecto7mo;

ALTER TABLE users
  ADD COLUMN username VARCHAR(20) NULL AFTER name;

UPDATE users
SET username = CONCAT('user', id)
WHERE username IS NULL OR username = '';

ALTER TABLE users
  MODIFY username VARCHAR(20) NOT NULL,
  ADD UNIQUE KEY uk_users_username (username);
