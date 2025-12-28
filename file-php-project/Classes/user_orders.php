<?php

class Order {

private $connection;
    
public function __construct($connection) {
        $this->connection = $connection;
    }
    

public function getUserOrders($user_id) {
    $stmt = $this->connection->prepare(
        "SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC"
    );
    $stmt->execute([':user_id' => $user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($orders as &$order) {
        $stmt2 = $this->connection->prepare(
            "SELECT p.product_id, p.product_name
             FROM order_items oi
             JOIN products p ON oi.product_id = p.product_id
             WHERE oi.order_id = :order_id"
        );
        $stmt2->execute([':order_id' => $order['order_id']]);
        $order['products'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    }

    return $orders;
}

    
public function getOrderById($order_id, $user_id) { //get all orders to a certain user by order_id , user_id 

     $stmt = $this->connection->prepare("
            SELECT o.*,p.payment_method,p.payment_status,u.first_name,u.last_name,u.email,u.location
            FROM orders o
            LEFT JOIN payments p ON o.order_id = p.order_id
            LEFT JOIN users u ON o.user_id = u.user_id
            WHERE o.order_id = :order_id AND o.user_id = :user_id");

     $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
     $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
     $stmt->execute();
        
     return $stmt->fetch(PDO::FETCH_ASSOC);

    }
    
    
public function getOrderItems($order_id) {

      $stmt = $this->connection->prepare("
            SELECT oi.*,p.product_name,p.image,(oi.quantity * oi.price) as subtotal
            FROM order_items oi
            INNER JOIN products p ON oi.product_id = p.product_id
            WHERE oi.order_id = :order_id");

        $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
 
    public function create($user_id, $cart_items, $payment_method, $shipping_address, $notes = '') {

        try {

            $this->connection->beginTransaction();
            
          
            $subtotal = 0;
            foreach ($cart_items as $item) {
                
                if ($item['quantity'] > $item['stock']) {
                    throw new Exception("Not enough stock for: " . $item['product_name']);
                }

                $subtotal += $item['subtotal'];
            }
            
            $shipping_cost = 5.00;
            $tax = $subtotal * 0.10;
            $total_price = $subtotal + $shipping_cost + $tax;
            
           
            $stmt = $this->connection->prepare("
                INSERT INTO orders (user_id, total_price, status, created_at) 
                VALUES (:user_id, :total_price, 'pending', NOW())");

            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':total_price', $total_price);
            $stmt->execute();
            
            $order_id = $this->connection->lastInsertId();
            
            
        foreach ($cart_items as $item) {

        $stmt = $this->connection->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price)
                    VALUES (:order_id, :product_id, :quantity, :price)");

            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
            $stmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
            $stmt->bindParam(':price', $item['price']);
            $stmt->execute();
                
               
            $new_stock = $item['stock'] - $item['quantity'];
            $stmt = $this->connection->prepare("
                    UPDATE products 
                    SET stock = :stock 
                    WHERE product_id = :product_id");
            $stmt->bindParam(':stock', $new_stock, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
            $stmt->execute();

            }
            
           
            $stmt = $this->connection->prepare("
                INSERT INTO payments (order_id, amount, payment_method, payment_status, payment_date)
                VALUES (:order_id, :amount, :payment_method, 'pending', NOW())");

            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt->bindParam(':amount', $total_price);
            $stmt->bindParam(':payment_method', $payment_method);
            $stmt->execute();
            
           
        if (!empty($shipping_address)) {
                $stmt = $this->connection->prepare("
                    UPDATE users 
                    SET location = :location 
                    WHERE user_id = :user_id");

                $stmt->bindParam(':location', $shipping_address);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->execute();

            }
            
        $this->connection->commit();
            
        return $order_id;
            
        } 
        catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
         
    }
    
    
public function cancelOrder($order_id, $user_id) {
       
$order = $this->getOrderById($order_id, $user_id);
        
        if (!$order) {
            throw new Exception("Order not found!");
                                        }
        if ($order['status'] === 'delivered') {
            throw new Exception("Cannot cancel delivered order!");
                    }
        
        if ($order['status'] === 'cancelled') {
            throw new Exception("Order already cancelled!");
                     }
        
        try {
            $this->connection->beginTransaction();
            
        $items = $this->getOrderItems($order_id);
            
        foreach ($items as $item) {

            $stmt = $this->connection->prepare("
                    UPDATE products 
                    SET stock = stock + :quantity 
                    WHERE product_id = :product_id");

                $stmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
                $stmt->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
                $stmt->execute();

              }
            
            
            $stmt = $this->connection->prepare("
                UPDATE orders 
                SET status = 'cancelled' 
                WHERE order_id = :order_id");

            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $this->connection->commit();
            
            return true;
            
        } 
        
        catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }

    }

}