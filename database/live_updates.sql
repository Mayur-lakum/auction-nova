-- Live Updates Queue for SSE (Server-Sent Events)
USE auction_nova;

CREATE TABLE IF NOT EXISTS live_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    data JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at),
    INDEX idx_event_type (event_type)
);
