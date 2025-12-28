<?php

class Review
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;}

   
    public function addReview(int $user_id, int $product_id, int $rating, string $comment): bool
    {
        // if ($rating < 1 || $rating > 5) {
        //     return false;
        // }

        $stmt = $this->connection->prepare(
            "INSERT INTO reviews (user_id, product_id, rating, comment)
             VALUES (:user_id, :product_id, :rating, :comment)"
        );

        return $stmt->execute([
            ':user_id' => $user_id,
            ':product_id' => $product_id,
            ':rating' => $rating,
            ':comment' => $comment
        ]);
    }

   //check if he already reviewed 
    // public function hasUserReviewed(int $user_id, int $product_id): bool
    // {
    //     $stmt = $this->connection->prepare(
    //         "SELECT review_id FROM reviews
    //          WHERE user_id = :user_id AND product_id = :product_id"
    //     );

    //     $stmt->execute([
    //         ':user_id' => $user_id,
    //         ':product_id' => $product_id
    //     ]);

    //     return $stmt->rowCount() > 0;
    // }

   //fetch all reviews from review table
    public function getProductReviews(int $product_id): array
    {
        $stmt = $this->connection->prepare(
            "SELECT r.*, u.name
             FROM reviews r
             JOIN users u ON r.user_id = u.user_id
             WHERE r.product_id = :product_id
             ORDER BY r.created_at DESC"
        );

        $stmt->execute([':product_id' => $product_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
