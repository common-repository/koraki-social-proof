<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://koraki.io
 * @since      1.0.0
 *
 * @package    Koraki
 * @subpackage Koraki/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two hooks to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Koraki
 * @subpackage Koraki/public
 * @author     Madusha <madusha@koraki.io>
 */
class Koraki_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;
    
    /**
	 * The Koraki API host.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    Koraki base host.
	 */
	private $host;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version, $host ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
        $this->host = $host;
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
        $options = get_option( 'koraki_settings' );
        
        if($options && $options['client_id'] && strlen($options['client_id']) == 32){        
            $script = $this->host . "/widget/v1.0/js/{$options['client_id']}";
            wp_register_script( $this->plugin_name, $script, [], '', true );
            wp_enqueue_script( $this->plugin_name );
        }
        
        add_action('woocommerce_before_single_product', array($this, 'add_wc_product_meta_tags'));
        add_action('woocommerce_before_checkout_form', array($this, 'add_wc_checkout_meta_tags'));
    }
    
    public function add_wc_product_meta_tags() {
        global $product;
        $count = $product->get_stock_quantity();
        echo '<meta property="ko:type" content="product" />';
        echo '<meta property="ko:title" content="'. esc_attr( $product->get_name() ) .'" />';
        echo '<meta property="ko:product_id" content="'. esc_attr( $product->get_id()) .'" />';
        echo '<meta property="ko:url" content="' . get_permalink( $product->get_id() ) .'" />';
        echo '<meta property="ko:image" content="' . get_the_post_thumbnail_url($product->get_id(), array(200,200)) . '" />';
        if( $count ){
            echo '<meta property="ko:count" content="' . esc_attr( $count ) . '" />';
        }
    }
    
    public function add_wc_checkout_meta_tags() {
        global $woocommerce;
        $contents = array();
        $items = $woocommerce->cart->get_cart();
        foreach($items as $item => $values) { 
            $product =  wc_get_product( $values['data']->get_id());
            $item = array( "_" => $product->get_id(), "u" => get_permalink( $product->get_id() ), "p" => $product->get_title(), "i" => get_the_post_thumbnail_url($product->get_id(), array(200,200)), "q" => $values['quantity'] );
            $contents[] = $item;
        } 
        
        echo '<meta property="ko:type" content="checkout" />';
        echo '<meta property="ko:order" content="'. esc_html(json_encode($contents)) .'" />';
    }

    public function koraki_order_created($order_id) {
        $data = array();
        if ( class_exists( 'woocommerce' ) ){
            $order = new WC_Order( $order_id );
            if(isset($order)){
                $order = $order->get_data();
                $first_qty = 0;

                foreach($order['line_items'] as $item){
                    $product = wc_get_product( $item['product_id']);
                    $product = $product->get_data();
                    $first_qty = $item['quantity'];
                    if(isset($product)){
                        $image_array = wp_get_attachment_image_src($product['image_id'], 'thumbnail');
                        $first_price = $item['price'];
                        break;
                    }
                }

                $data['order_id'] = $order_id;
                $data['image'] = isset($image_array) && count($image_array) > 0 ? $image_array[0] : "";
                $data['first_name'] = isset($order['billing']) ? $order['billing']['first_name'] : "";
                $data['city'] = isset($order['billing']) ? $order['billing']['city'] : "";
                $data['country'] = isset($order['billing']) ? WC()->countries->countries[$order['billing']['country']] : "";
                $data['first_product_name'] = $product['name'];
                $data['first_product_slug'] = $product['slug'];
                $data['first_product_id'] = $product['id'];
                $data['first_product_qty'] = $first_qty;
                $data['first_product_url'] = get_permalink( $product['id'] );
                $data['product_count'] = count($order['line_items']);
                $data['ip_address'] = $order['customer_ip_address'];
                
                $this->create_koraki_notification($data, "wcordercreated");
            }
        }
    }
    
    public function koraki_customer_created($customer_id) {
        $data = array();
        if ( class_exists( 'woocommerce' ) ){
            $customer = new WC_Customer( $customer_id );
            if(isset($customer)){

                $customer = $customer->get_data();
                $image = get_avatar_url($customer_id);
                
                $data['customer_id'] = $customer_id;
                $data['first_name'] = $customer['first_name'];
                $data['city'] = isset($customer['billing']) && isset($customer['billing']['city']) ? $customer['billing']['city'] : "";
                $data['country'] = isset($customer['billing']) && isset($customer['billing']['country']) ? $customer['billing']['country'] : "";
                $data['image'] = $image;

                $this->create_koraki_notification($data, "wccustomercreated");
            }
        }
    }
    
    public function koraki_review_created($comment_id) {
        $data = array();
        $comment = get_comment($comment_id);
        if ( class_exists( 'woocommerce' ) ){
            if(isset($comment)){ 
                if($comment->comment_type == "review" && $comment->comment_approved == "1"){
                    $rating = get_comment_meta( $comment_id, 'rating', true );
                    
                    $data['rating'] = $rating;
                    $data['comment_author'] = $comment->comment_author;
                    $data['comment_content'] = substr(wp_strip_all_tags($comment->comment_content, true), 0, 40);
                    $data['ip_address'] = $comment->comment_author_IP;
                    $data['product_id'] = $comment->comment_post_ID;
                    $data['review_id'] = $comment->comment_ID;
                    $data['product_url'] = get_permalink( $comment->comment_post_ID );
                    $data['review_url'] = get_comment_link( $comment->comment_ID );

                    $product = wc_get_product( $comment->comment_post_ID );
                    if(isset($product)){
                        $product = $product->get_data();
                        $data['product_name'] = $product['name'];
                        $image_array = wp_get_attachment_image_src($product['image_id'], 'thumbnail');
                        $data['image'] = isset($image_array) && count($image_array) > 0 ? $image_array[0] : "";
                    }

                    $this->create_koraki_notification($data, "wcreviewcreated");
                }
            }
        }
    }
    
    public function koraki_comment_created($comment_id) {
        $data = array();
        $comment = get_comment($comment_id);
        if(isset($comment) && $comment->comment_type == "" && $comment->comment_approved == "1"){
            $post = get_post($comment->comment_post_ID);
            $data['user_id'] = $comment->user_id;
            $data['image'] = get_avatar_url($comment->user_id);
            $data['comment_author'] = $comment->comment_author;
            $data['comment_author_url'] = $comment->comment_author_url;
            $data['comment_date'] = $comment->comment_date;
            $data['comment_content'] = substr(wp_strip_all_tags($comment->comment_content, true), 0, 40);
            $data['ip_address'] = $comment->comment_author_IP;
            $data['post_id'] = $comment->comment_post_ID;
            $data['post_title'] = $post->post_title;
            $data['post_name'] = $post->post_name;
            $data['post_url'] = get_permalink( $comment->comment_post_ID );
            $data['comment_url'] = get_comment_link( $comment_id );
            $data['comment_id'] = $comment->comment_ID;
            $this->create_koraki_notification($data, "wpcommentcreated");
        }
    }
    
    private function create_koraki_notification($data, $topic) {
        $settings = get_option( 'koraki_settings' );
        $body = $data;
        $body = wp_json_encode( $body );
        $token = base64_encode( $settings['client_id'] . ':' . $settings['client_secret'] );
        $options = [
            'body'        => $body,
            'blocking'    => true,
            'headers'     => [
                'Content-Type' => 'application/json'
            ],
            'sslverify'   => false,
            'data_format' => 'body',
        ];
        
        wp_remote_post($this->host . "/modules/v1.0/webhooks/wordpress/" . $topic . "/" . $token, $options);
    }
}
