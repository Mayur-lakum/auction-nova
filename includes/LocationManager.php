<?php
/**
 * Location Manager
 * Handles location services, address management, and delivery
 */

class LocationManager {
    private $db;
    private $googleMapsEnabled;
    private $googleMapsApiKey;
    
    public function __construct() {
        $this->db = new Database();
        $this->googleMapsEnabled = defined('GOOGLE_MAPS_ENABLED') ? GOOGLE_MAPS_ENABLED : false;
        $this->googleMapsApiKey = defined('GOOGLE_MAPS_API_KEY') ? GOOGLE_MAPS_API_KEY : '';
    }
    
    /**
     * Add shipping address
     */
    public function addShippingAddress($userId, $addressData) {
        try {
            $conn = $this->db->connect();
            
            // If this is set as default, unset other default addresses
            if ($addressData['is_default']) {
                $query = "UPDATE shipping_addresses SET is_default = FALSE WHERE user_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$userId]);
            }
            
            $query = "INSERT INTO shipping_addresses 
                     (user_id, address_type, recipient_name, phone, address_line1, address_line2, 
                      city, state, postal_code, country, is_default) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([
                $userId,
                $addressData['address_type'] ?? 'home',
                $addressData['recipient_name'],
                $addressData['phone'],
                $addressData['address_line1'],
                $addressData['address_line2'] ?? null,
                $addressData['city'],
                $addressData['state'],
                $addressData['postal_code'],
                $addressData['country'] ?? 'India',
                $addressData['is_default'] ?? false
            ]);
            
            if ($result) {
                return [
                    'success' => true,
                    'address_id' => $conn->lastInsertId(),
                    'message' => 'Address added successfully'
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to add address'];
            
        } catch (Exception $e) {
            error_log("Location Manager Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred'];
        }
    }
    
    /**
     * Get user shipping addresses
     */
    public function getUserShippingAddresses($userId) {
        $conn = $this->db->connect();
        $query = "SELECT * FROM shipping_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get default shipping address
     */
    public function getDefaultShippingAddress($userId) {
        $conn = $this->db->connect();
        $query = "SELECT * FROM shipping_addresses WHERE user_id = ? AND is_default = TRUE";
        $stmt = $conn->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update shipping address
     */
    public function updateShippingAddress($addressId, $userId, $addressData) {
        try {
            $conn = $this->db->connect();
            
            // If this is set as default, unset other default addresses
            if ($addressData['is_default']) {
                $query = "UPDATE shipping_addresses SET is_default = FALSE WHERE user_id = ? AND id != ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$userId, $addressId]);
            }
            
            $query = "UPDATE shipping_addresses SET 
                     address_type = ?, recipient_name = ?, phone = ?, address_line1 = ?, 
                     address_line2 = ?, city = ?, state = ?, postal_code = ?, 
                     country = ?, is_default = ?, updated_at = NOW() 
                     WHERE id = ? AND user_id = ?";
            
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([
                $addressData['address_type'] ?? 'home',
                $addressData['recipient_name'],
                $addressData['phone'],
                $addressData['address_line1'],
                $addressData['address_line2'] ?? null,
                $addressData['city'],
                $addressData['state'],
                $addressData['postal_code'],
                $addressData['country'] ?? 'India',
                $addressData['is_default'] ?? false,
                $addressId,
                $userId
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Address updated successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to update address'];
            
        } catch (Exception $e) {
            error_log("Location Manager Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred'];
        }
    }
    
    /**
     * Delete shipping address
     */
    public function deleteShippingAddress($addressId, $userId) {
        try {
            $conn = $this->db->connect();
            $query = "DELETE FROM shipping_addresses WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([$addressId, $userId]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Address deleted successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to delete address'];
            
        } catch (Exception $e) {
            error_log("Location Manager Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred'];
        }
    }
    
    /**
     * Set default address
     */
    public function setDefaultAddress($addressId, $userId) {
        try {
            $conn = $this->db->connect();
            
            // Unset all default addresses for this user
            $query = "UPDATE shipping_addresses SET is_default = FALSE WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$userId]);
            
            // Set new default address
            $query = "UPDATE shipping_addresses SET is_default = TRUE WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([$addressId, $userId]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Default address updated'];
            }
            
            return ['success' => false, 'message' => 'Failed to set default address'];
            
        } catch (Exception $e) {
            error_log("Location Manager Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred'];
        }
    }
    
    /**
     * Calculate shipping cost
     */
    public function calculateShippingCost($auctionId, $addressId) {
        try {
            $auction = $this->getAuction($auctionId);
            $address = $this->getAddress($addressId);
            
            if (!$auction || !$address) {
                return ['success' => false, 'message' => 'Invalid auction or address'];
            }
            
            // Get seller location
            $sellerLocation = $this->getSellerLocation($auction['seller_id']);
            
            // Calculate distance and shipping cost
            $distance = $this->calculateDistance($sellerLocation, $address);
            $shippingCost = $this->calculateCostByDistance($distance, $auction['shipping_cost']);
            
            return [
                'success' => true,
                'distance' => $distance,
                'shipping_cost' => $shippingCost,
                'estimated_delivery' => $this->estimateDeliveryTime($distance)
            ];
            
        } catch (Exception $e) {
            error_log("Location Manager Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to calculate shipping cost'];
        }
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
     * Get address details
     */
    private function getAddress($addressId) {
        $conn = $this->db->connect();
        $query = "SELECT * FROM shipping_addresses WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$addressId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get seller location
     */
    private function getSellerLocation($sellerId) {
        $conn = $this->db->connect();
        $query = "SELECT location_city, location_state FROM auctions WHERE seller_id = ? AND status = 'active' LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->execute([$sellerId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['location_city']) {
            return [
                'city' => $result['location_city'],
                'state' => $result['location_state']
            ];
        }
        
        // Default to major cities if seller location not set
        return ['city' => 'Mumbai', 'state' => 'Maharashtra'];
    }
    
    /**
     * Calculate distance between two locations
     */
    private function calculateDistance($origin, $destination) {
        if (!$this->googleMapsEnabled || !$this->googleMapsApiKey) {
            // Fallback: simple distance calculation based on states
            return $this->calculateSimpleDistance($origin, $destination);
        }
        
        $originStr = $origin['city'] . ', ' . $origin['state'];
        $destStr = $destination['city'] . ', ' . $destination['state'];
        
        $url = "https://maps.googleapis.com/maps/api/distancematrix/json";
        $params = [
            'origins' => $originStr,
            'destinations' => $destStr,
            'key' => $this->googleMapsApiKey,
            'units' => 'metric'
        ];
        
        $url .= '?' . http_build_query($params);
        
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if ($data['status'] === 'OK' && isset($data['rows'][0]['elements'][0]['distance'])) {
            return $data['rows'][0]['elements'][0]['distance']['value'] / 1000; // Convert to km
        }
        
        return $this->calculateSimpleDistance($origin, $destination);
    }
    
    /**
     * Simple distance calculation (fallback)
     */
    private function calculateSimpleDistance($origin, $destination) {
        // Basic distance estimation based on Indian states
        $stateDistances = [
            'same_city' => 10,
            'same_state' => 100,
            'neighboring_state' => 500,
            'different_region' => 1000,
            'far_region' => 2000
        ];
        
        if ($origin['city'] === $destination['city']) {
            return $stateDistances['same_city'];
        } elseif ($origin['state'] === $destination['state']) {
            return $stateDistances['same_state'];
        } elseif ($this->areNeighboringStates($origin['state'], $destination['state'])) {
            return $stateDistances['neighboring_state'];
        } elseif ($this->areSameRegion($origin['state'], $destination['state'])) {
            return $stateDistances['different_region'];
        } else {
            return $stateDistances['far_region'];
        }
    }
    
    /**
     * Check if states are neighboring
     */
    private function areNeighboringStates($state1, $state2) {
        $neighbors = [
            'Maharashtra' => ['Gujarat', 'Madhya Pradesh', 'Chhattisgarh', 'Telangana', 'Karnataka', 'Goa'],
            'Gujarat' => ['Maharashtra', 'Madhya Pradesh', 'Rajasthan'],
            'Karnataka' => ['Maharashtra', 'Goa', 'Kerala', 'Tamil Nadu', 'Andhra Pradesh', 'Telangana'],
            'Tamil Nadu' => ['Karnataka', 'Kerala', 'Andhra Pradesh'],
            // Add more state neighbors as needed
        ];
        
        return isset($neighbors[$state1]) && in_array($state2, $neighbors[$state1]);
    }
    
    /**
     * Check if states are in same region
     */
    private function areSameRegion($state1, $state2) {
        $regions = [
            'North' => ['Jammu & Kashmir', 'Himachal Pradesh', 'Punjab', 'Uttarakhand', 'Haryana', 'Delhi', 'Rajasthan'],
            'South' => ['Kerala', 'Tamil Nadu', 'Karnataka', 'Andhra Pradesh', 'Telangana'],
            'East' => ['West Bengal', 'Odisha', 'Jharkhand', 'Bihar', 'Assam', 'Sikkim'],
            'West' => ['Maharashtra', 'Gujarat', 'Goa', 'Madhya Pradesh', 'Chhattisgarh'],
            'Central' => ['Madhya Pradesh', 'Chhattisgarh', 'Uttar Pradesh']
        ];
        
        foreach ($regions as $region => $states) {
            if (in_array($state1, $states) && in_array($state2, $states)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Calculate shipping cost based on distance
     */
    private function calculateCostByDistance($distance, $baseCost) {
        $baseCost = $baseCost ?? 0;
        
        if ($distance <= 50) {
            return $baseCost + 50;
        } elseif ($distance <= 200) {
            return $baseCost + 100;
        } elseif ($distance <= 500) {
            return $baseCost + 200;
        } elseif ($distance <= 1000) {
            return $baseCost + 300;
        } else {
            return $baseCost + 500;
        }
    }
    
    /**
     * Estimate delivery time
     */
    private function estimateDeliveryTime($distance) {
        if ($distance <= 50) {
            return '1-2 days';
        } elseif ($distance <= 200) {
            return '2-3 days';
        } elseif ($distance <= 500) {
            return '3-5 days';
        } elseif ($distance <= 1000) {
            return '5-7 days';
        } else {
            return '7-10 days';
        }
    }
    
    /**
     * Get address autocomplete suggestions
     */
    public function getAddressAutocomplete($input) {
        if (!$this->googleMapsEnabled || !$this->googleMapsApiKey) {
            return ['success' => false, 'message' => 'Google Maps not enabled'];
        }
        
        $url = "https://maps.googleapis.com/maps/api/place/autocomplete/json";
        $params = [
            'input' => $input,
            'key' => $this->googleMapsApiKey,
            'components' => 'country:in',
            'types' => 'address'
        ];
        
        $url .= '?' . http_build_query($params);
        
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if ($data['status'] === 'OK') {
            $suggestions = [];
            foreach ($data['predictions'] as $prediction) {
                $suggestions[] = [
                    'description' => $prediction['description'],
                    'place_id' => $prediction['place_id']
                ];
            }
            return ['success' => true, 'suggestions' => $suggestions];
        }
        
        return ['success' => false, 'message' => 'No suggestions found'];
    }
    
    /**
     * Get place details
     */
    public function getPlaceDetails($placeId) {
        if (!$this->googleMapsEnabled || !$this->googleMapsApiKey) {
            return ['success' => false, 'message' => 'Google Maps not enabled'];
        }
        
        $url = "https://maps.googleapis.com/maps/api/place/details/json";
        $params = [
            'place_id' => $placeId,
            'key' => $this->googleMapsApiKey,
            'fields' => 'address_component,formatted_address'
        ];
        
        $url .= '?' . http_build_query($params);
        
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if ($data['status'] === 'OK') {
            $addressComponents = [];
            foreach ($data['result']['address_components'] as $component) {
                $types = $component['types'];
                $addressComponents[$types[0]] = $component['long_name'];
            }
            
            return [
                'success' => true,
                'formatted_address' => $data['result']['formatted_address'],
                'components' => $addressComponents
            ];
        }
        
        return ['success' => false, 'message' => 'Place details not found'];
    }
    
    /**
     * Validate postal code
     */
    public function validatePostalCode($postalCode, $state) {
        // Indian PIN code validation (6 digits)
        if (!preg_match('/^\d{6}$/', $postalCode)) {
            return false;
        }
        
        // Basic state-wise PIN validation (simplified)
        $statePinRanges = [
            'Maharashtra' => ['400000', '499999'],
            'Gujarat' => ['360000', '399999'],
            'Karnataka' => ['560000', '599999'],
            'Tamil Nadu' => ['600000', '699999'],
            'Delhi' => ['110000', '119999']
        ];
        
        if (isset($statePinRanges[$state])) {
            $range = $statePinRanges[$state];
            return $postalCode >= $range[0] && $postalCode <= $range[1];
        }
        
        return true; // Allow if state not in validation list
    }
    
    /**
     * Get Indian states list
     */
    public static function getIndianStates() {
        return [
            'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh',
            'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand',
            'Karnataka', 'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur',
            'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab',
            'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura',
            'Uttar Pradesh', 'Uttarakhand', 'West Bengal', 'Delhi', 'Puducherry'
        ];
    }
    
    /**
     * Format address for display
     */
    public static function formatAddress($address) {
        $formatted = [];
        
        if (!empty($address['address_line1'])) {
            $formatted[] = $address['address_line1'];
        }
        if (!empty($address['address_line2'])) {
            $formatted[] = $address['address_line2'];
        }
        
        $cityState = [];
        if (!empty($address['city'])) {
            $cityState[] = $address['city'];
        }
        if (!empty($address['state'])) {
            $cityState[] = $address['state'];
        }
        if (!empty($address['postal_code'])) {
            $cityState[] = $address['postal_code'];
        }
        
        if (!empty($cityState)) {
            $formatted[] = implode(', ', $cityState);
        }
        
        if (!empty($address['country']) && $address['country'] !== 'India') {
            $formatted[] = $address['country'];
        }
        
        return implode("\n", $formatted);
    }
}

?>
