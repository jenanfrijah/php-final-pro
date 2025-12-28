<?php
//logic checkout.php
class Checkout
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

  
    public function getUser(int $user_id)
    {
        $stmt = $this->connection->prepare(
            "SELECT * FROM users WHERE user_id = :user_id"
        );
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

   
    public function getCartItems(int $user_id)
    {
        // cart_id
        $stmt = $this->connection->prepare(
            "SELECT cart_id FROM cart WHERE user_id = :user_id"
        );
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        $cart = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cart) {
            return [];
        }

        $cart_id = $cart['cart_id'];

        // cart items
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
                (ci.quantity * ci.price) AS subtotal
            FROM cart_items ci
            INNER JOIN products p ON ci.product_id = p.product_id
            WHERE ci.cart_id = :cart_id"
        );

        $stmt->bindParam(':cart_id', $cart_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

  
    public function calculateTotals(array $cartItems)
    {
        $subtotal = 0;

        foreach ($cartItems as $item) {
            $subtotal += $item['subtotal'];
        }

        $shipping = 5.00;
        $tax      = $subtotal * 0.10;
        $total    = $subtotal + $shipping + $tax;

        return [
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'tax'      => $tax,
            'total'    => $total
        ];
    }
}
