<?php
/**
 * Notification Manager
 * Handles email, SMS, and in-app notifications
 */

class NotificationManager {
    private $db;
    private $emailEnabled;
    private $smsEnabled;
    private $pushEnabled;
    
    public function __construct() {
        $this->db = new Database();
        $this->emailEnabled = defined('SENDGRID_ENABLED') ? SENDGRID_ENABLED : false;
        $this->smsEnabled = defined('TWILIO_ENABLED') ? TWILIO_ENABLED : false;
        $this->pushEnabled = false; // Future feature
    }
    
    /**
     * Send notification
     */
    public function sendNotification($userId, $type, $title, $message, $auctionId = null) {
        try {
            // Create in-app notification
            $this->createInAppNotification($userId, $type, $title, $message, $auctionId);
            
            // Get user notification preferences
            $preferences = $this->getUserNotificationPreferences($userId);
            
            // Send email notification
            if ($preferences['email_notifications'] && $this->emailEnabled) {
                $this->sendEmailNotification($userId, $type, $title, $message, $auctionId);
            }
            
            // Send SMS notification
            if ($preferences['sms_notifications'] && $this->smsEnabled) {
                $this->sendSMSNotification($userId, $type, $message, $auctionId);
            }
            
            return ['success' => true, 'message' => 'Notification sent successfully'];
            
        } catch (Exception $e) {
            error_log("Notification Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send notification'];
        }
    }
    
    /**
     * Create in-app notification
     */
    private function createInAppNotification($userId, $type, $title, $message, $auctionId) {
        $conn = $this->db->connect();
        $query = "INSERT INTO notifications (user_id, type, title, message, auction_id) 
                 VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$userId, $type, $title, $message, $auctionId]);
    }
    
    /**
     * Send email notification
     */
    private function sendEmailNotification($userId, $type, $title, $message, $auctionId) {
        $user = $this->getUser($userId);
        if (!$user || !$user['email_verified']) {
            return false;
        }
        
        // Get email template
        $template = $this->getEmailTemplate($type);
        if (!$template) {
            return false;
        }
        
        // Replace placeholders
        $subject = $this->replacePlaceholders($template['subject'], $user, $auctionId);
        $htmlContent = $this->replacePlaceholders($template['html_content'], $user, $auctionId);
        $textContent = $this->replacePlaceholders($template['text_content'], $user, $auctionId);
        
        // Send email using SendGrid
        if ($this->emailEnabled) {
            return $this->sendSendGridEmail($user['email'], $subject, $htmlContent, $textContent);
        }
        
        return false;
    }
    
    /**
     * Send SMS notification
     */
    private function sendSMSNotification($userId, $type, $message, $auctionId) {
        $user = $this->getUser($userId);
        if (!$user || !$user['phone_verified']) {
            return false;
        }
        
        // Get SMS template
        $template = $this->getSMSTemplate($type);
        if (!$template) {
            return false;
        }
        
        // Replace placeholders
        $smsMessage = $this->replacePlaceholders($template['content'], $user, $auctionId);
        
        // Send SMS using Twilio
        if ($this->smsEnabled) {
            return $this->sendTwilioSMS($user['phone'], $smsMessage);
        }
        
        return false;
    }
    
    /**
     * Get user notification preferences
     */
    private function getUserNotificationPreferences($userId) {
        $conn = $this->db->connect();
        $query = "SELECT * FROM notification_settings WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$userId]);
        $preferences = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$preferences) {
            // Create default preferences
            $query = "INSERT INTO notification_settings (user_id) VALUES (?)";
            $stmt = $conn->prepare($query);
            $stmt->execute([$userId]);
            
            return [
                'email_notifications' => true,
                'sms_notifications' => false,
                'push_notifications' => true,
                'bid_outbid' => true,
                'bid_won' => true,
                'bid_lost' => true,
                'auction_ending' => true,
                'auction_ended' => true,
                'payment_reminder' => true,
                'order_updates' => true
            ];
        }
        
        return $preferences;
    }
    
    /**
     * Get user details
     */
    private function getUser($userId) {
        $conn = $this->db->connect();
        $query = "SELECT * FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get email template
     */
    private function getEmailTemplate($type) {
        $conn = $this->db->connect();
        $query = "SELECT * FROM email_templates WHERE template_name = ? AND is_active = TRUE";
        $stmt = $conn->prepare($query);
        $stmt->execute([$type]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get SMS template
     */
    private function getSMSTemplate($type) {
        $conn = $this->db->connect();
        $query = "SELECT * FROM sms_templates WHERE template_name = ? AND is_active = TRUE";
        $stmt = $conn->prepare($query);
        $stmt->execute([$type]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Replace placeholders in templates
     */
    private function replacePlaceholders($content, $user, $auctionId) {
        $placeholders = [
            '{user_name}' => $user['first_name'] . ' ' . $user['last_name'],
            '{user_email}' => $user['email'],
            '{user_phone}' => $user['phone'],
            '{site_name}' => defined('SITE_NAME') ? SITE_NAME : 'Auction Nova',
            '{site_url}' => defined('SITE_URL') ? SITE_URL : 'http://localhost/'
        ];
        
        // Add auction-specific placeholders
        if ($auctionId) {
            $auction = $this->getAuction($auctionId);
            if ($auction) {
                $placeholders['{auction_title}'] = $auction['title'];
                $placeholders['{auction_id}'] = $auction['id'];
                $placeholders['{current_price}'] = number_format($auction['current_price'], 2);
                $placeholders['{end_time}'] = date('M j, Y H:i', strtotime($auction['end_time']));
            }
        }
        
        foreach ($placeholders as $placeholder => $value) {
            $content = str_replace($placeholder, $value, $content);
        }
        
        return $content;
    }
    
    /**
     * Get auction details
     */
    private function getAuction($auctionId) {
        $conn = $this->db->connect();
        $query = "SELECT * FROM auctions WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$auctionId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Send email using SendGrid
     */
    private function sendSendGridEmail($to, $subject, $htmlContent, $textContent) {
        if (!defined('SENDGRID_API_KEY') || !SENDGRID_API_KEY) {
            return false;
        }
        
        $url = 'https://api.sendgrid.com/v3/mail/send';
        $apiKey = SENDGRID_API_KEY;
        
        $data = [
            'personalizations' => [
                [
                    'to' => [['email' => $to]],
                    'subject' => $subject
                ]
            ],
            'from' => [
                'email' => defined('SITE_EMAIL') ? SITE_EMAIL : 'noreply@auctionnova.com',
                'name' => defined('SITE_NAME') ? SITE_NAME : 'Auction Nova'
            ],
            'content' => [
                [
                    'type' => 'text/html',
                    'value' => $htmlContent
                ]
            ]
        ];
        
        if ($textContent) {
            $data['content'][] = [
                'type' => 'text/plain',
                'value' => $textContent
            ];
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode >= 200 && $httpCode < 300;
    }
    
    /**
     * Send SMS using Twilio
     */
    private function sendTwilioSMS($to, $message) {
        if (!defined('TWILIO_ACCOUNT_SID') || !defined('TWILIO_AUTH_TOKEN') || !defined('TWILIO_PHONE_NUMBER')) {
            return false;
        }
        
        $sid = TWILIO_ACCOUNT_SID;
        $token = TWILIO_AUTH_TOKEN;
        $from = TWILIO_PHONE_NUMBER;
        
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";
        
        $data = [
            'From' => $from,
            'To' => $to,
            'Body' => $message
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_USERPWD, $sid . ':' . $token);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch); 
        curl_close($ch); 
        
        return $response !== false; 
    } 
    
    /**
     * Get user notifications 
     */
    public function getUserNotifications($userId, $limit = 50, $offset = 0) { 
        $conn = $this->db->connect(); 
        $query = "SELECT n.*, a.title as auction_title 
                 FROM notifications n 
                 LEFT JOIN auctions a ON n.auction_id = a.id 
                 WHERE n.user_id = ? 
                 ORDER BY n.created_at DESC 
                 LIMIT ? OFFSET ?"; 
        $stmt = $conn->prepare($query); 
        $stmt->execute([$userId, $limit, $offset]); 
        return $stmt->fetchAll(PDO::FETCH_ASSOC); 
    } 
    
    /**
     * Send bid placed notification 
     */
    public function sendBidPlacedNotification($userId, $auctionId, $bidAmount) { 
        $auction = $this->getAuction($auctionId); 
        if (!$auction) return false; 
        
        $title = "Bid Placed Successfully"; 
        $message = "Your bid of ₹" . number_format($bidAmount, 2) . " has been placed on {$auction['title']}"; 
        
        return $this->sendNotification($userId, 'bid_placed', $title, $message, $auctionId); 
    } 
    
    /**
     * Send outbid notification 
     */
    public function sendOutbidNotification($userId, $auctionId, $currentPrice) { 
        $auction = $this->getAuction($auctionId); 
        if (!$auction) return false; 
        
        $title = "You've been outbid!"; 
        $message = "You have been outbid on {$auction['title']}. Current highest bid: ₹" . number_format($currentPrice, 2); 
        
        return $this->sendNotification($userId, 'bid_outbid', $title, $message, $auctionId); 
    } 
    
    /**
     * Send auction won notification 
     */
    public function sendAuctionWonNotification($userId, $auctionId, $winningAmount) { 
        $auction = $this->getAuction($auctionId); 
        if (!$auction) return false; 
        
        $title = "Congratulations! You won!"; 
        $message = "You won {$auction['title']} with a bid of ₹" . number_format($winningAmount, 2); 
        
        return $this->sendNotification($userId, 'bid_won', $title, $message, $auctionId); 
    } 
    
    /**
     * Send auction ending notification 
     */
    public function sendAuctionEndingNotification($userId, $auctionId) { 
        $auction = $this->getAuction($auctionId); 
        if (!$auction) return false; 
        
        $title = "Auction Ending Soon"; 
        $message = "Auction {$auction['title']} is ending soon. Place your bid now!"; 
        
        return $this->sendNotification($userId, 'auction_ending', $title, $message, $auctionId); 
    } 
    
    /**
     * Send payment reminder notification 
     */
    public function sendPaymentReminder($userId, $auctionId, $amount) { 
        $auction = $this->getAuction($auctionId); 
        if (!$auction) return false; 
        
        $title = "Payment Reminder"; 
        $message = "Complete your payment of ₹" . number_format($amount, 2) . " for {$auction['title']}"; 
        
        return $this->sendNotification($userId, 'payment_reminder', $title, $message, $auctionId); 
    } 
    
    /**
     * Update notification preferences 
     */
    public function updateNotificationPreferences($userId, $preferences) { 
        $conn = $this->db->connect(); 
        $query = "UPDATE notification_settings SET 
                 email_notifications = ?, 
                 sms_notifications = ?, 
                 push_notifications = ?, 
                 bid_outbid = ?, 
                 bid_won = ?, 
                 bid_lost = ?, 
                 auction_ending = ?, 
                 auction_ended = ?, 
                 payment_reminder = ?, 
                 order_updates = ? 
                 WHERE user_id = ?"; 
        $stmt = $conn->prepare($query); 
        return $stmt->execute([ 
            $preferences['email_notifications'] ?? true, 
            $preferences['sms_notifications'] ?? false, 
            $preferences['push_notifications'] ?? true, 
            $preferences['bid_outbid'] ?? true, 
            $preferences['bid_won'] ?? true, 
            $preferences['bid_lost'] ?? true, 
            $preferences['auction_ending'] ?? true, 
            $preferences['auction_ended'] ?? true, 
            $preferences['payment_reminder'] ?? true, 
            $preferences['order_updates'] ?? true, 
            $userId 
        ]); 
    } 
} 
?>