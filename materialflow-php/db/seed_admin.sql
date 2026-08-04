-- MaterialFlow — first admin user.
-- Import AFTER materialflow_mysql.sql, then LOG IN AND CHANGE THIS PASSWORD IMMEDIATELY.
-- Email:    admin@materialflow.com
-- Password: ChangeMe@123
-- Delete this file from the server after use.

INSERT INTO app_users (id, full_name, email, password_hash, role, is_active)
VALUES (
  UUID(),
  'Administrator',
  'admin@materialflow.com',
  '$2y$12$wKonYKmElRl8hzz3y42Q7.Y1LrstxIt2ZVhNJF8.lEu/UZmVtaraW',
  'ADMIN',
  1
);
