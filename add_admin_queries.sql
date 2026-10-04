-- Method 1: Add new admin with password "admin123"
INSERT INTO users (username, email, password, first_name, last_name, role, is_verified, is_active) VALUES
('admin2', 'admin2@auctionnova.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin', TRUE, TRUE);

-- Method 2: Add super admin with password "admin123"
INSERT INTO users (username, email, password, first_name, last_name, role, is_verified, is_active) VALUES
('superadmin2', 'superadmin2@auctionnova.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super', 'Admin', 'super_admin', TRUE, TRUE);

-- Method 3: Add admin with custom password "password123"
-- First generate hash: $2y$10$ABC... (replace with actual hash)
INSERT INTO users (username, email, password, first_name, last_name, role, is_verified, is_active) VALUES
('myadmin', 'myadmin@auctionnova.com', '$2y$10$YOUR_CUSTOM_HASH_HERE', 'My', 'Admin', 'admin', TRUE, TRUE);

-- Method 4: Promote existing user to admin
UPDATE users SET role = 'admin' WHERE email = 'user@example.com';

-- Method 5: Promote existing user to super admin
UPDATE users SET role = 'super_admin' WHERE email = 'user@example.com';

-- Method 6: Add multiple admins at once
INSERT INTO users (username, email, password, first_name, last_name, role, is_verified, is_active) VALUES
('admin3', 'admin3@auctionnova.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Three', 'admin', TRUE, TRUE),
('admin4', 'admin4@auctionnova.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Four', 'admin', TRUE, TRUE);
