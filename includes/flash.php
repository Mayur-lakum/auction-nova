<?php
// This file is deprecated. Use functions.php instead.

// For backward compatibility
if (!function_exists('set_flash_message')) {
    function set_flash_message($type, $message) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flash'][$type] = $message;
    }
}

if (!function_exists('display_flash_messages')) {
    function display_flash_messages() {
        if (!function_exists('get_flash_messages')) {
            return '';
        }
        
        $messages = get_flash_messages();
        $output = '';
        
        foreach ($messages as $type => $message) {
            $output .= sprintf(
                '<div class="alert alert-%s alert-dismissible fade show" role="alert">
                    %s
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>',
                htmlspecialchars($type),
                htmlspecialchars($message)
            );
        }
        
        return $output;
    }
}
?>
