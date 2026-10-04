<?php
/**
 * KYC (Know Your Customer) Verification Manager
 * Handles document upload, verification, and compliance
 */

class KYCManager {
    private $db;
    private $uploadPath;
    private $allowedTypes;
    private $maxFileSize;
    
    public function __construct() {
        $this->db = new Database();
        $this->uploadPath = defined('KYC_UPLOAD_PATH') ? KYC_UPLOAD_PATH : 'uploads/kyc/';
        $this->allowedTypes = defined('KYC_ALLOWED_TYPES') ? KYC_ALLOWED_TYPES : ['jpg', 'jpeg', 'png', 'pdf'];
        $this->maxFileSize = defined('KYC_MAX_FILE_SIZE') ? KYC_MAX_FILE_SIZE : 10 * 1024 * 1024; // 10MB
        
        // Create upload directory if not exists
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }
    
    /**
     * Upload KYC document
     */
    public function uploadDocument($userId, $documentType, $documentNumber, $frontFile, $backFile = null) {
        try {
            // Validate document type
            if (!in_array($documentType, ['aadhaar', 'pan', 'passport', 'driving_license'])) {
                return ['success' => false, 'message' => 'Invalid document type'];
            }
            
            // Check if user already has pending verification for this document type
            if ($this->hasPendingVerification($userId, $documentType)) {
                return ['success' => false, 'message' => 'You already have a pending verification for this document type'];
            }
            
            // Upload front document
            $frontPath = $this->uploadFile($frontFile, $userId, $documentType . '_front');
            if (!$frontPath) {
                return ['success' => false, 'message' => 'Failed to upload front document'];
            }
            
            // Upload back document if required
            $backPath = null;
            if ($backFile) {
                $backPath = $this->uploadFile($backFile, $userId, $documentType . '_back');
                if (!$backPath) {
                    // Clean up front file if back file upload fails
                    unlink($frontPath);
                    return ['success' => false, 'message' => 'Failed to upload back document'];
                }
            }
            
            // Save to database
            $conn = $this->db->connect();
            $query = "INSERT INTO kyc_documents (user_id, document_type, document_number, document_front, document_back) 
                     VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([$userId, $documentType, $documentNumber, $frontPath, $backPath]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Document uploaded successfully',
                    'document_id' => $conn->lastInsertId()
                ];
            } else {
                // Clean up uploaded files if database insert fails
                if ($frontPath && file_exists($frontPath)) unlink($frontPath);
                if ($backPath && file_exists($backPath)) unlink($backPath);
                return ['success' => false, 'message' => 'Failed to save document information'];
            }
            
        } catch (Exception $e) {
            error_log("KYC Upload Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred during upload'];
        }
    }
    
    /**
     * Upload file to server
     */
    private function uploadFile($file, $userId, $prefix) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return false;
        }
        
        // Validate file size
        if ($file['size'] > $this->maxFileSize) {
            return false;
        }
        
        // Validate file type
        $fileInfo = pathinfo($file['name']);
        $extension = strtolower($fileInfo['extension']);
        
        if (!in_array($extension, $this->allowedTypes)) {
            return false;
        }
        
        // Generate unique filename
        $filename = $prefix . '_' . $userId . '_' . time() . '.' . $extension;
        $filepath = $this->uploadPath . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return $filepath;
        }
        
        return false;
    }
    
    /**
     * Check if user has pending verification
     */
    private function hasPendingVerification($userId, $documentType) {
        $conn = $this->db->connect();
        $query = "SELECT id FROM kyc_documents 
                 WHERE user_id = ? AND document_type = ? AND verification_status = 'pending'";
        $stmt = $conn->prepare($query);
        $stmt->execute([$userId, $documentType]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Get user KYC documents
     */
    public function getUserDocuments($userId) {
        $conn = $this->db->connect();
        $query = "SELECT * FROM kyc_documents WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get document details
     */
    public function getDocument($documentId) {
        $conn = $this->db->connect();
        $query = "SELECT kd.*, u.username, u.email, u.first_name, u.last_name 
                 FROM kyc_documents kd 
                 JOIN users u ON kd.user_id = u.id 
                 WHERE kd.id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$documentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Verify document (Admin function)
     */
    public function verifyDocument($documentId, $adminId, $status, $reason = null) {
        try {
            $document = $this->getDocument($documentId);
            if (!$document) {
                return ['success' => false, 'message' => 'Document not found'];
            }
            
            if (!in_array($status, ['verified', 'rejected'])) {
                return ['success' => false, 'message' => 'Invalid status'];
            }
            
            $conn = $this->db->connect();
            $query = "UPDATE kyc_documents 
                     SET verification_status = ?, rejection_reason = ?, verified_at = NOW(), verified_by = ? 
                     WHERE id = ?";
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([$status, $reason, $adminId, $documentId]);
            
            if ($result) {
                // Update user KYC status if all documents are verified
                $this->updateUserKYCStatus($document['user_id']);
                
                // Log admin action
                $this->logAdminAction($adminId, 'kyc_verification', 'kyc_document', $documentId, 
                    "Document {$status}: {$document['document_type']} for user {$document['username']}");
                
                return [
                    'success' => true,
                    'message' => "Document {$status} successfully"
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to update document status'];
            
        } catch (Exception $e) {
            error_log("KYC Verification Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred during verification'];
        }
    }
    
    /**
     * Update user KYC status
     */
    private function updateUserKYCStatus($userId) {
        $conn = $this->db->connect();
        
        // Check if user has at least one verified document
        $query = "SELECT COUNT(*) as verified_count FROM kyc_documents 
                 WHERE user_id = ? AND verification_status = 'verified'";
        $stmt = $conn->prepare($query);
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $kycVerified = $result['verified_count'] > 0;
        
        // Update user table
        $query = "UPDATE users SET kyc_verified = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$kycVerified, $userId]);
    }
    
    /**
     * Get pending verifications (Admin function)
     */
    public function getPendingVerifications() {
        $conn = $this->db->connect();
        $query = "SELECT kd.*, u.username, u.email, u.first_name, u.last_name 
                 FROM kyc_documents kd 
                 JOIN users u ON kd.user_id = u.id 
                 WHERE kd.verification_status = 'pending' 
                 ORDER BY kd.created_at ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get KYC statistics (Admin function)
     */
    public function getKYCStatistics() {
        $conn = $this->db->connect();
        
        $stats = [];
        
        // Total documents
        $query = "SELECT COUNT(*) as total FROM kyc_documents";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $stats['total_documents'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Pending verifications
        $query = "SELECT COUNT(*) as pending FROM kyc_documents WHERE verification_status = 'pending'";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $stats['pending_verifications'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending'];
        
        // Verified documents
        $query = "SELECT COUNT(*) as verified FROM kyc_documents WHERE verification_status = 'verified'";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $stats['verified_documents'] = $stmt->fetch(PDO::FETCH_ASSOC)['verified'];
        
        // Rejected documents
        $query = "SELECT COUNT(*) as rejected FROM kyc_documents WHERE verification_status = 'rejected'";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $stats['rejected_documents'] = $stmt->fetch(PDO::FETCH_ASSOC)['rejected'];
        
        // KYC verified users
        $query = "SELECT COUNT(*) as users FROM users WHERE kyc_verified = TRUE";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $stats['kyc_verified_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['users'];
        
        return $stats;
    }
    
    /**
     * Validate document number format
     */
    public function validateDocumentNumber($documentType, $documentNumber) {
        switch ($documentType) {
            case 'aadhaar':
                // Aadhaar: 12 digits
                return preg_match('/^\d{12}$/', $documentNumber);
            case 'pan':
                // PAN: 5 letters + 4 digits + 1 letter
                return preg_match('/^[A-Z]{5}\d{4}[A-Z]{1}$/', strtoupper($documentNumber));
            case 'passport':
                // Passport: Alphanumeric, 8-15 characters
                return preg_match('/^[A-Z0-9]{8,15}$/', strtoupper($documentNumber));
            case 'driving_license':
                // Driving License: Varies by state, basic validation
                return preg_match('/^[A-Z0-9]{10,20}$/', strtoupper($documentNumber));
            default:
                return false;
        }
    }
    
    /**
     * Delete document (Admin function)
     */
    public function deleteDocument($documentId, $adminId, $reason = null) {
        try {
            $document = $this->getDocument($documentId);
            if (!$document) {
                return ['success' => false, 'message' => 'Document not found'];
            }
            
            $conn = $this->db->connect();
            
            // Delete files
            if ($document['document_front'] && file_exists($document['document_front'])) {
                unlink($document['document_front']);
            }
            if ($document['document_back'] && file_exists($document['document_back'])) {
                unlink($document['document_back']);
            }
            
            // Delete database record
            $query = "DELETE FROM kyc_documents WHERE id = ?";
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([$documentId]);
            
            if ($result) {
                // Update user KYC status
                $this->updateUserKYCStatus($document['user_id']);
                
                // Log admin action
                $this->logAdminAction($adminId, 'kyc_delete', 'kyc_document', $documentId, 
                    "Deleted document: {$document['document_type']} for user {$document['username']}");
                
                return ['success' => true, 'message' => 'Document deleted successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to delete document'];
            
        } catch (Exception $e) {
            error_log("KYC Delete Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred during deletion'];
        }
    }
    
    /**
     * Log admin action
     */
    private function logAdminAction($adminId, $action, $targetType, $targetId, $description) {
        $conn = $this->db->connect();
        $query = "INSERT INTO admin_logs (admin_id, action, target_type, target_id, description, ip_address) 
                 VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            $adminId, 
            $action, 
            $targetType, 
            $targetId, 
            $description, 
            $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
    }
    
    /**
     * Check if user is KYC verified
     */
    public function isUserKYCVerified($userId) {
        $conn = $this->db->connect();
        $query = "SELECT kyc_verified FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['kyc_verified'] == true;
    }
    
    /**
     * Get document types for dropdown
     */
    public static function getDocumentTypes() {
        return [
            'aadhaar' => 'Aadhaar Card',
            'pan' => 'PAN Card',
            'passport' => 'Passport',
            'driving_license' => 'Driving License'
        ];
    }
    
    /**
     * Get verification status labels
     */
    public static function getStatusLabels() {
        return [
            'pending' => 'Pending Verification',
            'verified' => 'Verified',
            'rejected' => 'Rejected'
        ];
    }
    
    /**
     * Get status badge HTML
     */
    public static function getStatusBadge($status) {
        $badges = [
            'pending' => '<span class="badge bg-warning">Pending</span>',
            'verified' => '<span class="badge bg-success">Verified</span>',
            'rejected' => '<span class="badge bg-danger">Rejected</span>'
        ];
        return $badges[$status] ?? '<span class="badge bg-secondary">Unknown</span>';
    }
}

?>
