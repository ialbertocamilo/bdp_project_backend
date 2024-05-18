CREATE DATABASE IF NOT EXISTS laravel;
CREATE USER 'laravel'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON DATABASE laravel.* TO 'laravel'@'localhost';
FLUSH PRIVILEGES;
