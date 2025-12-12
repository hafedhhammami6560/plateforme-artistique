-- Create admin user with hashed password
-- Password: admin123
INSERT INTO user (email, roles, password, name, is_verified) 
VALUES ('admin@admin.com', '["ROLE_ADMIN"]', '$2y$13$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 1);
