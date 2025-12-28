<?php

class Cart {
    private $connection;
    
    public function __construct($connection) { //once we use New Cart() --> connection established here
        $this->connection = $connection;
    }
    
    
        private function getOrCreateCartId($user_id) {
        $stmt = $this->connection->prepare("SELECT cart_id FROM cart WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cart) {
            $stmt = $this->connection->prepare("INSERT INTO cart (user_id) VALUES (:user_id)");
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            return $this->connection->lastInsertId();//this function return the last inserted id 
        }
        
        return $cart['cart_id'];
    }
    
  
        public function getItems($user_id) { //fetch all items from the cart 
        $stmt = $this->connection->prepare("SELECT cart_id FROM cart WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cart) {
            return [];
        }
        
     $stmt = $this->connection->prepare(
        "
                SELECT 
                ci.cart_item_id,
                ci.product_id,
                ci.quantity,
                ci.price,
                p.product_name,
                p.image,
                p.stock,
                (ci.quantity * ci.price) AS item_total  
                FROM cart_items ci
                INNER JOIN products p ON ci.product_id = p.product_id
                WHERE ci.cart_id = :cart_id"

            );  //item_total

        $stmt->bindParam(':cart_id', $cart['cart_id'], PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
   
        public function getTotal($user_id) {//return the total price 
        $items = $this->getItems($user_id);
        $total = 0;
        
        foreach ($items as $item) {
            $total += $item['item_total'];
        }
        
        return $total;
    }
    
 
       public function addItem($user_id, $product_id, $quantity) {//add item to cart 
       
        $stmt = $this->connection->prepare("SELECT price, stock FROM products WHERE product_id = :product_id");
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            throw new Exception("Product not found!");
        }
        
        if ($product['stock'] < $quantity) {
            throw new Exception("Not enough stock! Available: " . $product['stock']);
        }
        
        $price = $product['price'];
        $cart_id = $this->getOrCreateCartId($user_id);
        
        
        $stmt = $this->connection->prepare(
            "
            SELECT cart_item_id, quantity 
            FROM cart_items 
            WHERE cart_id = :cart_id AND product_id = :product_id"
        );

        $stmt->bindParam(':cart_id', $cart_id, PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
        $existing_item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_item) {//if item already existed and i want to update the quantity 
         
            $new_quantity = $existing_item['quantity'] + $quantity;
    

            if ($product['stock'] < $new_quantity) {
                throw new Exception("Not enough stock!");
            }
            
            $stmt = $this->connection->prepare(
                "
                UPDATE cart_items 
                SET quantity = :quantity 
                WHERE cart_item_id = :cart_item_id "
            );

            $stmt->bindParam(':quantity', $new_quantity, PDO::PARAM_INT);
            $stmt->bindParam(':cart_item_id', $existing_item['cart_item_id'], PDO::PARAM_INT);
            $stmt->execute();
          } 

        
        else {
         //if the item doesnt exist 
            $stmt = $this->connection->prepare(
                "
                INSERT INTO cart_items (cart_id, product_id, quantity, price) 
                VALUES (:cart_id, :product_id, :quantity, :price)"
            );

            $stmt->bindParam(':cart_id', $cart_id, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
            $stmt->bindParam(':price', $price);
            $stmt->execute();
        }
        
        return true;
    }
    
    
       public function updateQuantity($cart_item_id, $quantity) {
        if ($quantity < 1) {
            throw new Exception("Invalid quantity!");
        }
        
     
            $stmt = $this->connection->prepare(
         "
            SELECT p.stock, p.product_id
            FROM cart_items ci
            INNER JOIN products p ON ci.product_id = p.product_id
            WHERE ci.cart_item_id = :cart_item_id"
        );

        $stmt->bindParam(':cart_item_id', $cart_item_id, PDO::PARAM_INT);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            throw new Exception("Product not found!");
        }
        
        if ($quantity > $product['stock']) {
            throw new Exception("Not enough stock! Available: " . $product['stock']);
        }
        
        $stmt = $this->connection->prepare(
            "
            UPDATE cart_items 
            SET quantity = :quantity 
            WHERE cart_item_id = :cart_item_id
        "
           );

        $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        $stmt->bindParam(':cart_item_id', $cart_item_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return true;

         }
    
   
    public function removeItem($cart_item_id) {
      $stmt = $this->connection->prepare("DELETE FROM cart_items WHERE cart_item_id = :cart_item_id");
      $stmt->bindParam(':cart_item_id', $cart_item_id, PDO::PARAM_INT);
      $stmt->execute();
        
        return true;
    }
    
  
    public function clear($user_id) {

        $stmt = $this->connection->prepare("SELECT cart_id FROM cart WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cart) {
            $stmt = $this->connection->prepare("DELETE FROM cart_items WHERE cart_id = :cart_id");//depends on cart_id to delete all items
            $stmt->bindParam(':cart_id', $cart['cart_id'], PDO::PARAM_INT);
            $stmt->execute();
        }
        
        return true;
         }
    
   
      public function transferGuestCart($user_id, $guest_cart) { // transfer from guest cart to user cart
        if (empty($guest_cart)) {
            return false;
        }
        
        $cart_id = $this->getOrCreateCartId($user_id);
        
        foreach ($guest_cart as $product_id => $quantity) {
          
            $stmt = $this->connection->prepare("SELECT price FROM products WHERE product_id = :product_id");
            $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $stmt->execute();
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                continue;
            }//if product does not exist just skip 
            
            $price = $product['price'];
            
            
            $stmt = $this->connection->prepare(
                "
                SELECT cart_item_id, quantity 
                FROM cart_items 
                WHERE cart_id = :cart_id AND product_id = :product_id"
            );

            $stmt->bindParam(':cart_id', $cart_id, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $stmt->execute();
            $existing_item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_item) {
               
                $new_quantity = $existing_item['quantity'] + $quantity;
                $stmt = $this->connection->prepare(
                    "
                    UPDATE cart_items    
                    SET quantity = :quantity 
                    WHERE cart_item_id = :cart_item_id");

                $stmt->bindParam(':quantity', $new_quantity, PDO::PARAM_INT);
                $stmt->bindParam(':cart_item_id', $existing_item['cart_item_id'], PDO::PARAM_INT);
                $stmt->execute();

            } 
            
            else {
                
                $stmt = $this->connection->prepare(
                    "
                    INSERT INTO cart_items (cart_id, product_id, quantity, price) 
                    VALUES (:cart_id, :product_id, :quantity, :price)"
                );


                $stmt->bindParam(':cart_id', $cart_id, PDO::PARAM_INT);
                $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
                $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
                $stmt->bindParam(':price', $price);
                $stmt->execute();
              }
        }
        
        return true;
      }
    
    
    public function getItemCount($user_id) {
        $items = $this->getItems($user_id);
        return count($items);
    }
}