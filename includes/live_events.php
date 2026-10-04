<?php
// Helper to broadcast events into live_updates table
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Database.php';

function broadcast_event(string $eventType, array $data) {
    $db = Database::getInstance();
    try {
        $db->query(
            "INSERT INTO live_updates (event_type, event_data) VALUES (?, ?)",
            [$eventType, json_encode($data)]
        );
    } catch (Exception $e) {
        $db->query(
            "INSERT INTO live_updates (event_type, data) VALUES (?, ?)",
            [$eventType, json_encode($data)]
        );
    }
    return $db->lastInsertId();
}
