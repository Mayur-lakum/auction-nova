<?php
class Cart {
    private $db;
    private $cart = [];
    
    public function __construct($db) {
        $this->db = $db;
        $this->initializeCart();
    }
    
    private function initializeCart() {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [
                'items' => [],
                'totals' => [
                    'subtotal' => 0,
                    'shipping' => 0,
                    'tax' => 0,
                    'total' => 0,
                    'itemCount' => 0
                ]
            ];
        }
        $this->cart = &$_SESSION['cart'];
    }
    
    public function addItem($auctionId, $quantity = 1) {
        try {
            $auction = $this->getAuctionDetails($auctionId);
            
            if (!$auction) {
                throw new Exception('Auction not found');
            }
            
            // Allow adding ended auctions when called from admin sell action
            if ($auction['end_time'] < date('Y-m-d H:i:s') && $auction['status'] !== 'ended') {
                throw new Exception('This auction has already ended');
            }
            
            if (isset($this->cart['items']) && isset($this->cart['items'][$auctionId])) {
                return $this->updateQuantity($auctionId, $this->cart['items'][$auctionId]['quantity'] + $quantity);
            }
            
            if (!isset($this->cart['items'])) {
                $this->cart['items'] = [];
            }
            $this->cart['items'][$auctionId] = [
                'id' => $auction['id'],
                'title' => $auction['title'],
                'price' => $auction['current_price'],
                'quantity' => $quantity,
                'image' => $auction['primary_image'] ?? 'default.jpg',
                'seller_id' => $auction['seller_id'],
                'end_time' => $auction['end_time']
            ];
            
            $this->calculateTotals();
            return ['success' => true, 'message' => 'Item added to cart', 'cart_count' => $this->getItemCount()];
            
        } catch (Exception $e) {
            error_log('Cart Error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function updateQuantity($auctionId, $quantity) {
        if (!isset($this->cart['items']) || !isset($this->cart['items'][$auctionId])) {
            return ['success' => false, 'message' => 'Item not found in cart'];
        }
        
        if ($quantity <= 0) {
            return $this->removeItem($auctionId);
        }
        
        $this->cart['items'][$auctionId]['quantity'] = $quantity;
        $this->calculateTotals();
        
        return [
            'success' => true,
            'message' => 'Cart updated',
            'item_total' => $this->formatCurrency($this->cart['items'][$auctionId]['price'] * $quantity),
            'cart_count' => $this->getItemCount(),
            'totals' => $this->cart['totals']
        ];
    }
    
    public function removeItem($auctionId) {
        if (!isset($this->cart['items']) || !isset($this->cart['items'][$auctionId])) {
            return ['success' => false, 'message' => 'Item not found in cart'];
        }
        
        unset($this->cart['items'][$auctionId]);
        $this->calculateTotals();
        
        return [
            'success' => true,
            'message' => 'Item removed from cart',
            'cart_count' => $this->getItemCount(),
            'totals' => $this->cart['totals']
        ];
    }
    
    public function clear() {
        if (!isset($this->cart['items'])) {
            $this->cart['items'] = [];
        } else {
            $this->cart['items'] = [];
        }
        $this->calculateTotals();
        return ['success' => true, 'message' => 'Cart cleared'];
    }
    
    public function getCart() {
        return $this->cart;
    }
    
    public function getItemCount() {
        $count = 0;
        if (isset($this->cart['items']) && is_array($this->cart['items'])) {
            foreach ($this->cart['items'] as $item) {
                $count += $item['quantity'];
            }
        }
        return $count;
    }
    
    private function calculateTotals() {
        $subtotal = 0;
        $itemCount = 0;
        
        if (isset($this->cart['items']) && is_array($this->cart['items'])) {
            foreach ($this->cart['items'] as $item) {
                $itemTotal = $item['price'] * $item['quantity'];
                $subtotal += $itemTotal;
                $itemCount += $item['quantity'];
            }
        }
        
        // Calculate shipping (example: $5 per item)
        $shipping = $itemCount * 5;
        
        // Calculate tax (example: 8% of subtotal)
        $tax = $subtotal * 0.08;
        
        $this->cart['totals'] = [
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'tax' => $tax,
            'total' => $subtotal + $shipping + $tax,
            'itemCount' => $itemCount
        ];
    }
    
    private function getAuctionDetails($auctionId) {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*, i.image_path as primary_image 
                FROM auctions a 
                LEFT JOIN (
                    SELECT auction_id, MIN(id) as min_id 
                    FROM auction_images 
                    GROUP BY auction_id
                ) ai ON a.id = ai.auction_id
                LEFT JOIN images i ON ai.min_id = i.id
                WHERE a.id = ?
            ");
            $stmt->execute([$auctionId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Database Error: ' . $e->getMessage());
            return null;
        }
    }
    
    private function formatCurrency($amount) {
        return number_format($amount, 2, '.', ',');
    }
}
?>
