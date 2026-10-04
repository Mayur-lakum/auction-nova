-- Create admin user with email: admin1@gmail.com and password: admin1password
INSERT INTO users (username, email, password, first_name, last_name, role, is_verified, is_active) VALUES
('admin1', 'admin1@gmail.com', '$2y$10$K2Yqg8vL6X9mF7cD3nN5O.1a2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p7q8r9s0t', 'Admin', 'User', 'admin', TRUE, TRUE);
