<?php

namespace WooCommerce_Dev;

/**
 * Class WooCommerce_Review
 * @package WooCommerce_Dev
 *
 * @Require: First Active Review in Settings Woocommerce page in product Tab
 */
class WooCommerce_Review
{
    /**
     * Check Is Enable Review
     *
     * @return bool
     */
    public static function is_enable_review()
    {
        $option = get_option('woocommerce_enable_reviews');
        return $option == "yes";
    }

    /**
     * Is enable Rating System in Woocommerce
     *
     * @return bool
     */
    public static function is_enable_rating()
    {
        $option = get_option('woocommerce_enable_review_rating');
        return $option == "yes";
    }

    /**
     * Get List Review
     *
     * @see https://developer.wordpress.org/reference/classes/WP_Comment_Query/__construct/
     * @param array $args
     * @return array|int|\WP_Comment[]
     */
    public static function get($args = array())
    {
        $default = array(
            'type' => 'comment',
            'status' => 'approve',
            'post_id' => 0,
            'number' => false,
            'order' => 'DESC',
            'orderby' => 'comment_ID',
            //@see https://wordpress.stackexchange.com/questions/265014/wp-comment-query-with-5-top-level-comments-per-page
            // use hierarchical => false if count = yes
            'hierarchical' => 'threaded',
            'count' => false,
            'update_comment_meta_cache' => false,
            'update_comment_post_cache' => false,
        );
        $arg = wp_parse_args($args, $default);

        // The comment query
        $comments_query = new \WP_Comment_Query;
        return $comments_query->query($arg);

        /**
         * if (!empty($comments)) {
         * foreach ($comments as $comment) {
         * $comment_id = $comment->comment_ID;
         * $user_id = $comment->user_id;
         * $comment_content = $comment->comment_content;
         * $comment_date = $comment->comment_date;
         * $comment_children_list = $comment->get_children();
         * }
         */
    }

    /**
     * Add Review
     *
     * @param array $args
     * @param null $rating
     * @return bool|false|int
     */
    public static function add($args = array(), $rating = null)
    {
        // Add Comment
        $user_email = '';
        if (is_user_logged_in()) {
            $user_data = get_userdata(get_current_user_id());
            if (!empty($user_data->user_email)) {
                $user_email = $user_data->user_email;
            }
        }

        // Get User IP
        $ip = isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];

        // Get User Agent
        $user_agent = '';
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
        }

        // Default
        $default = array(
            'comment_type' => 'review',
            'comment_post_ID' => 0,
            'comment_author' => '',
            'comment_author_email' => $user_email,
            // Use in Ajax Request For Send | 'comment_text': comment_content.replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '<br />'),
            'comment_content' => '',
            'comment_parent' => 0,
            'user_id' => get_current_user_id(),
            'comment_date' => current_time('mysql'),
            'comment_date_gmt' => current_time('mysql', true),
            'comment_author_IP' => $ip,
            'comment_agent' => $user_agent,
            // 0= Hold 1= Approved @see https://developer.wordpress.org/reference/functions/wp_set_comment_status/
            'comment_approved' => 0,
        );
        $arg = wp_parse_args($args, $default);

        // Insert Data
        $insert_id = wp_insert_comment(wp_filter_comment($arg));

        // Update Rating
        if (self::is_enable_rating() and $rating != null) {
            update_comment_meta($insert_id, 'rating', !empty($rating) ? $rating : '0');
        }

        // return $insert_id
        return $insert_id;
    }

    /**
     * Show Review in loop
     */
    public static function loop()
    {
        global $product;

        if (get_option('woocommerce_enable_review_rating') === 'no') {
            return;
        }

        $rating_count = $product->get_rating_count();
        $review_count = $product->get_review_count();
        $average = $product->get_average_rating();

        if ($rating_count >= 0) {
            echo wc_get_rating_html($average, $rating_count);
        }
    }

}

new WooCommerce_Review;