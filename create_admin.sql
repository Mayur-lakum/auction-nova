-- Option 1: Create admin user with password "admin123"
INSERT INTO users (username, email, password, first_name, last_name, role, is_verified, is_active) VALUES
('newadmin', 'newadmin@auctionnova.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'New', 'Admin', 'admin', TRUE, TRUE);

-- Option 2: Create super admin user with password "admin123"
INSERT INTO users (username, email, password, first_name, last_name, role, is_verified, is_active) VALUES
('superadmin', 'superadmin@auctionnova.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super', 'Admin', 'super_admin', TRUE, TRUE);

-- Option 3: Create admin user with custom password "password123"
-- First generate hash for "password123": $2y$10$ABC123... (replace with actual hash)
INSERT INTO users (username, email, password, first_name, last_name, role, is_verified, is_active) VALUES
('myadmin', 'myadmin@auctionnova.com', '$2y$10$YOUR_CUSTOM_HASH_HERE', 'My', 'Admin', 'admin', TRUE, TRUE);

-- Option 4: Update existing user to admin role
UPDATE users SET role = 'admin' WHERE email = 'user@example.com';

-- Option 5: Update existing user to super admin role  
UPDATE users SET role = 'super_admin' WHERE email = 'user@example.com';
