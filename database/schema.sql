    -- Auction Nova Database Schema
    -- Run this SQL script to create the database and tables

    CREATE DATABASE IF NOT EXISTS auction_nova;
    USE auction_nova;

    -- Users table
    CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        phone VARCHAR(15),
        address TEXT,
        date_of_birth DATE,
        profile_picture VARCHAR(255) DEFAULT 'default-avatar.png',
        is_verified BOOLEAN DEFAULT FALSE,
        is_active BOOLEAN DEFAULT TRUE,
        role ENUM('user', 'admin', 'super_admin') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );

    -- Categories table
    CREATE TABLE categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        image VARCHAR(255),
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    -- Auctions table
    CREATE TABLE auctions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        seller_id INT NOT NULL,
        category_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        description TEXT NOT NULL,
        starting_price DECIMAL(10,2) NOT NULL,
        current_price DECIMAL(10,2) NOT NULL,
        buy_now_price DECIMAL(10,2) NULL,
        min_increment DECIMAL(10,2) DEFAULT 50.00,
        start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        end_time TIMESTAMP NOT NULL,
        status ENUM('draft', 'active', 'ended', 'cancelled') DEFAULT 'draft',
        is_featured BOOLEAN DEFAULT FALSE,
        view_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
    );

    -- Auction images table
    CREATE TABLE auction_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        auction_id INT NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        is_primary BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (auction_id) REFERENCES auctions(id) ON DELETE CASCADE
    );

    -- Bids table
    CREATE TABLE bids (
        id INT AUTO_INCREMENT PRIMARY KEY,
        auction_id INT NOT NULL,
        bidder_id INT NOT NULL,
        bid_amount DECIMAL(10,2) NOT NULL,
        bid_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_winning BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (auction_id) REFERENCES auctions(id) ON DELETE CASCADE,
        FOREIGN KEY (bidder_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_auction_bid (auction_id, bid_amount DESC)
    );

    -- Watchlist table
    CREATE TABLE watchlist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        auction_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (auction_id) REFERENCES auctions(id) ON DELETE CASCADE,
        UNIQUE KEY unique_watchlist (user_id, auction_id)
    );

    -- Messages table (for user communication)
    CREATE TABLE messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        auction_id INT NULL,
        subject VARCHAR(200),
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (auction_id) REFERENCES auctions(id) ON DELETE SET NULL
    );

    -- Notifications table
    CREATE TABLE notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type ENUM('bid_placed', 'bid_won', 'bid_lost', 'auction_ending', 'auction_ended', 'message_received') NOT NULL,
        title VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        auction_id INT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (auction_id) REFERENCES auctions(id) ON DELETE SET NULL
    );

    -- Admin logs table
    CREATE TABLE admin_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        action VARCHAR(100) NOT NULL,
        target_type ENUM('user', 'auction', 'category', 'system') NOT NULL,
        target_id INT NULL,
        description TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
    );

    -- Password reset tokens table
    CREATE TABLE password_reset_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(255) NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        used BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    -- Insert default categories
    INSERT INTO categories (name, description) VALUES
    ('Electronics', 'Smartphones, laptops, gadgets and electronic devices'),
    ('Vehicles', 'Cars, motorcycles, bicycles and automotive items'),
    ('Antiques', 'Vintage items, collectibles and antique pieces'),
    ('Jewelry', 'Rings, necklaces, watches and precious items'),
    ('Art', 'Paintings, sculptures and artistic creations'),
    ('Books', 'Rare books, manuscripts and literary collections'),
    ('Sports', 'Sports equipment, memorabilia and fitness items'),
    ('Fashion', 'Clothing, accessories and fashion items'),
    ('Home & Garden', 'Furniture, decor and gardening items'),
    ('Others', 'Miscellaneous items and general auctions');

    -- Insert default admin user (password: admin123)
    INSERT INTO users (username, email, password, first_name, last_name, role, is_verified) VALUES
    ('admin', 'admin@auctionnova.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'super_admin', TRUE);
