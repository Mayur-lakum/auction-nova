<?php
// SSE endpoint for live updates
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');

// Attempt to disable any proxy/webserver buffering
header('X-Accel-Buffering: no');

// Never time out while streaming
@set_time_limit(0);
@ignore_user_abort(true);

// Prevent output buffering
while (ob_get_level() > 0) {
    @ob_end_clean();
}

require_once '../includes/Database.php';

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

$db = Database::getInstance();
$conn = $db->getConnection();

$lastId = 0;
if (isset($_GET['last_id']) && is_numeric($_GET['last_id'])) {
    $lastId = (int)$_GET['last_id'];
} elseif (!empty($_SERVER['HTTP_LAST_EVENT_ID']) && is_numeric($_SERVER['HTTP_LAST_EVENT_ID'])) {
    $lastId = (int)$_SERVER['HTTP_LAST_EVENT_ID'];
}

// Initial retry hint for the browser
echo "retry: 3000\n\n";
@ob_flush();
@flush();

while (true) {
    if (connection_aborted()) {
        break;
    }
    try {
        try {
            $updates = $db->fetchAll(
                "SELECT id, event_type, data AS event_data FROM live_updates WHERE id > ? ORDER BY id ASC LIMIT 10",
                [$lastId]
            );
        } catch (Exception $e) {
            $updates = $db->fetchAll(
                "SELECT id, event_type, event_data FROM live_updates WHERE id > ? ORDER BY id ASC LIMIT 10",
                [$lastId]
            );
        }

        foreach ($updates as $row) {
            // Standard SSE: include id so the browser can resume after reconnect
            echo "id: {$row['id']}\n";
            echo "event: {$row['event_type']}\n";

            // event_data is stored as JSON in DB (string). Send it directly.
            $payload = $row['event_data'];
            if (!is_string($payload)) {
                $payload = json_encode($payload);
            }
            echo "data: {$payload}\n\n";
            $lastId = $row['id'];
        }

        // Keep connection alive
        echo ": heartbeat\n\n";
        @ob_flush();
        @flush();

        // Sleep briefly to reduce CPU load
        usleep(500000);
    } catch (Exception $e) {
        error_log('SSE error: ' . $e->getMessage());
        echo "event: error\ndata: {\"error\":\"SSE error\"}\n\n";
        @ob_flush();
        @flush();
        usleep(1000000);
    }
}
