<?php

class Product
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

  
    public function getProductById(int $product_id) //return a certain product details
    {
        $stmt = $this->connection->prepare("SELECT * FROM products WHERE product_id = :product_id" );
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function addToCart(int $product_id, int $quantity, ?int $user_id = null){ //user id here is optional because maybe he is guest
    
        $stmt = $this->connection->prepare("SELECT price, stock FROM products WHERE product_id = :product_id");
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();

        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new Exception("Product not found");
        }

        if ($quantity < 1 || $product['stock'] < $quantity) {
            throw new Exception("Invalid quantity or not enough stock");
        }

        $price = $product['price'];

      
    if ($user_id) {

        $stmt = $this->connection->prepare("SELECT cart_id FROM cart WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cart) {//if cart does not exist just create cart
                $stmt = $this->connection->prepare("INSERT INTO cart (user_id) VALUES (:user_id)");
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->execute();
                $cart_id = $this->connection->lastInsertId();
            } 
            
            else {
                $cart_id = $cart['cart_id'];
            }

          
            $stmt = $this->connection->prepare(
             "SELECT cart_item_id,quantity
              FROM cart_items 
             WHERE cart_id = :cart_id AND product_id = :product_id"
              );

        $stmt->bindParam(':cart_id', $cart_id, PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();

        $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cart_item) {
                $new_quantity = $cart_item['quantity'] + $quantity;

        if ($product['stock'] < $new_quantity) {
                    throw new Exception("Not enough stock");
                }

        $stmt = $this->connection->prepare("UPDATE cart_items SET quantity = :quantity WHERE cart_item_id = :cart_item_id" );
        $stmt->bindParam(':quantity', $new_quantity, PDO::PARAM_INT);
        $stmt->bindParam(':cart_item_id', $cart_item['cart_item_id'], PDO::PARAM_INT);
        $stmt->execute();
                        } 
              
    else {

    $stmt = $this->connection->prepare(
    "INSERT INTO cart_items (cart_id, product_id, quantity, price)
    VALUES (:cart_id, :product_id, :quantity, :price)"
                                                               );
      $stmt->bindParam(':cart_id', $cart_id, PDO::PARAM_INT);
      $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
      $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
      $stmt->bindParam(':price', $price);
      $stmt->execute();
                      }

  } //if guest
  
  else {
     if (!isset($_SESSION['guest_cart'])) {
         $_SESSION['guest_cart'] = [];
            }

            $_SESSION['guest_cart'][$product_id] =
                ($_SESSION['guest_cart'][$product_id] ?? 0) + $quantity;
        }
    }
}
