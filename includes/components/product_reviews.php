<?php
function displayProductReviews($product_id) {
    $db = Database::getInstance()->getConnection();
    
    // Get approved reviews
    $stmt = $db->prepare("SELECT r.*, u.username 
                         FROM reviews r 
                         JOIN users u ON r.user_id = u.id 
                         WHERE r.product_id = ? AND r.status = 'approved' 
                         ORDER BY r.created_at DESC");
    $stmt->execute([$product_id]);
    $reviews = $stmt->fetchAll();
    
    // Check if user can review
    $can_review = false;
    if (isLoggedIn()) {
        // Check if user has purchased and not reviewed
        $stmt = $db->prepare("SELECT o.id 
                             FROM orders o 
                             JOIN order_items oi ON o.id = oi.order_id 
                             WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'delivered'");
        $stmt->execute([$_SESSION['user_id'], $product_id]);
        $has_purchased = $stmt->fetch();
        
        $stmt = $db->prepare("SELECT id FROM reviews WHERE product_id = ? AND user_id = ?");
        $stmt->execute([$product_id, $_SESSION['user_id']]);
        $has_reviewed = $stmt->fetch();
        
        $can_review = $has_purchased && !$has_reviewed;
    }
    ?>
    
    <div class="review-section">
        <h3>Customer Reviews</h3>
        
        <?php if ($can_review): ?>
        <div class="review-form">
            <h4>Write a Review</h4>
            <form id="reviewForm">
                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                
                <div class="rating-input">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" required>
                        <label for="star<?php echo $i; ?>">★</label>
                    <?php endfor; ?>
                </div>
                
                <div class="form-group">
                    <textarea name="comment" rows="4" placeholder="Write your review here..." required></textarea>
                </div>
                
                <button type="submit">Submit Review</button>
            </form>
        </div>
        <?php endif; ?>
        
        <div class="review-list">
            <?php if (empty($reviews)): ?>
                <p>No reviews yet</p>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <span class="review-author"><?php echo $review['username']; ?></span>
                            <span class="review-date">
                                <?php echo date('F j, Y', strtotime($review['created_at'])); ?>
                            </span>
                        </div>
                        <div class="star-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <div class="review-comment">
                            <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    $(document).ready(function() {
        $('#reviewForm').on('submit', function(e) {
            e.preventDefault();
            
            $.post('modules/reviews/add.php', $(this).serialize(), function(response) {
                if (response.success) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert(response.message || 'Failed to submit review');
                }
            }, 'json');
        });
    });
    </script>
    <?php
}
?> 