<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://koraki.io
 * @since      1.0.0
 *
 * @package    Koraki
 * @subpackage Koraki/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two hooks to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Koraki
 * @subpackage Koraki/admin
 * @author     Madusha <madusha@koraki.io>
 */
class Koraki_Admin {

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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version, $host ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
        $this->host = $host;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/koraki-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/koraki-admin.js', array( 'jquery' ), $this->version, false );
	}
    
    /**
	 * Callback method to trigger post create or update event
	 *
	 * @since    1.0.0
	 */
    public function koraki_post_created_or_updated( $post_id, $post, $update ) {

        // If this is a revision, return
        if ( wp_is_post_revision( $post_id ) || $post->post_status != "publish" || $post->post_type != "post")
            return;

        $ok_to_post = get_post_meta($post_id, '_koraki_post_field', true);
        if ($ok_to_post !== "true")
            return;
        
        $data = array();
        $image = get_the_post_thumbnail_url($post_id, array(200,200));
        if(!isset($image) || empty($image)){
            $image = get_avatar_url($post->post_author);
        }
        $data['image'] = $image;
        $data['url'] = get_permalink( $post_id );
        $data['group'] = "wp_{$post_id}";
        $data['post_title'] = $post->post_title;
        $data['post_content'] = substr(wp_strip_all_tags($post->post_content, true), 0, 40);
        $data['post_name'] = $post->post_name;
        $data['post_modified'] = $post->post_modified;
        $data['post_date'] = $post->post_date;
        $data['post_id'] = $post->ID;
        $data['author_id'] = $post->post_author;
        $data['author_name'] = get_the_author_meta('display_name', $post->post_author);
        $time_differ = round(abs(strtotime($post->post_modified) - strtotime($post->post_date)) / 60,2);
        if( $time_differ > 0.20  ){
            $this->create_koraki_notification($data, "wppostupdated");
        }else{
            $this->create_koraki_notification($data, "wppostcreated");
        }
    }
    
    /**
	 * Callback method to trigger review status update
	 *
	 * @since    1.0.0
	 */
    public function koraki_review_updated($s1, $s2, $comment) {
        $data = array();
        if ( class_exists( 'woocommerce' ) ){
            if(isset($comment)){ 
                if($comment->comment_type == "review" && $comment->comment_approved == "1"){
                    $rating = get_comment_meta( $comment->comment_ID, 'rating', true );

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
    
    /**
	 * Callback method to trigger comment status update 
	 *
	 * @since    1.0.0
	 */
    public function koraki_comment_updated($s1, $s2, $comment) {
        $data = array();
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
    
    
    function koraki_post_to_koraki_meta_box_html($post){
        $settings = get_option( 'koraki_settings' );
        $id = $settings['id'];
        ?>
        <label for="koraki_field">Create a Koraki Social Proof notification for this post?</label>
        <select name="koraki_post_field" id="koraki_post_field" class="postbox">
            <option value="true">Yes</option>
            <option value="false">No</option>
        </select>
        <br/>
        <i>See <a href="https://app.koraki.io/applications/view/<?php echo $id;?>/integrations/wordpress?source=wpplugin" target="_blank">advanced settings</a></i>
        <?php
    }
    
    function koraki_add_meta_boxes(){
        $screens = ['post'];
        foreach ($screens as $screen) {
            add_meta_box(
                'koraki_post_to_koraki_meta_box',           
                'Koraki Social Proof',  
                array($this, 'koraki_post_to_koraki_meta_box_html'),
                $screen
            );
        }
    }
    
    function koraki_save_meta_boxes($post_id){
        if (array_key_exists('koraki_post_field', $_POST)) {
            update_post_meta(
                $post_id,
                '_koraki_post_field',
                sanitize_text_field($_POST['koraki_post_field'])
            );
        }
    }

    public function add_menu_items() {
        $icon = "data:image/svg+xml;base64,PCFET0NUWVBFIHN2ZyBQVUJMSUMgIi0vL1czQy8vRFREIFNWRyAyMDAxMDkwNC8vRU4iICJodHRwOi8vd3d3LnczLm9yZy9UUi8yMDAxL1JFQy1TVkctMjAwMTA5MDQvRFREL3N2ZzEwLmR0ZCI+PHN2ZyB2ZXJzaW9uPSIxLjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjE1MHB4IiBoZWlnaHQ9IjEyNHB4IiB2aWV3Qm94PSIwIDAgMTUwMCAxMjQwIiBwcmVzZXJ2ZUFzcGVjdFJhdGlvPSJ4TWlkWU1pZCBtZWV0Ij48ZyBpZD0ibGF5ZXIxMDEiIGZpbGw9IiNmZTM4MDAiIHN0cm9rZT0ibm9uZSI+IDxwYXRoIGQ9Ik01MzAgMTA2NSBsMCAtMTc1IC0yNjUgMCAtMjY1IDAgMCAtNDQ1IDAgLTQ0NSA3NTAgMCA3NTAgMCAwIDQ0NSAwIDQ0NSAtMjYyIDAgLTI2MyAxIC0yMTQgMTc0IGMtMTE4IDk2IC0yMTggMTc1IC0yMjMgMTc1IC00IDAgLTggLTc5IC04IC0xNzV6Ii8+IDwvZz48ZyBpZD0ibGF5ZXIxMDIiIGZpbGw9IiNmZjc5MDAiIHN0cm9rZT0ibm9uZSI+IDxwYXRoIGQ9Ik01MzAgMTA2NSBsMCAtMTc1IC0yNjUgMCAtMjY1IDAgMCAtNDQ1IDAgLTQ0NSA1OTAgMCA1OTAgMCAyIDE2MyAzIDE2MiAxNTggMyAxNTcgMyAwIDI3OSAwIDI4MCAtMjYyIDAgLTI2MyAxIC0yMTQgMTc0IGMtMTE4IDk2IC0yMTggMTc1IC0yMjMgMTc1IC00IDAgLTggLTc5IC04IC0xNzV6Ii8+IDwvZz48ZyBpZD0ibGF5ZXIxMDMiIGZpbGw9IiNmZmEyMDAiIHN0cm9rZT0ibm9uZSI+IDxwYXRoIGQ9Ik01MzAgMTA2NSBsMCAtMTc1IC0yNjUgMCAtMjY1IDAgMCAtNDQ1IDAgLTQ0NSA0MzAgMCA0MzAgMCAwIDIzNSAwIDIzNSA0MCAwIGM1OCAwIDkwIDM0IDkwIDk1IDAgNDUgMSA0NiAyOCA0MyBsMjcgLTMgMyAtMTEyIGMzIC0xMTMgMyAtMTEzIDI3IC0xMTMgMjUgMCAyNSAxIDI1IDg4IGwwIDg3IDM2IC00MiBjMjcgLTMzIDQzIC00MyA2NSAtNDMgMzggMCAzNyA5IC02IDUwIC0xOSAxOSAtMzUgMzkgLTM1IDQ2IDAgMzAgNzEgNTQgOTggMzIgOSAtOCAxMiAtMzAgMTEgLTY3IC0zIC01NCAtMiAtNTYgMjQgLTU5IGwyNyAtMyAwIDY5IDAgNjkgODggLTEgODcgLTEgMyAxNDMgMyAxNDIgLTI2MSAwIC0yNjIgMCAtNTggNDUgYy0zMiAyNSAtNjUgNTEgLTcyIDU4IC03IDcgLTM1IDMwIC02MyA1MiAtNzggNjMgLTg1IDY5IC0xMDggODkgLTQyIDM1IC0xMzYgMTA2IC0xNDEgMTA2IC0zIDAgLTYgLTc5IC02IC0xNzV6IG00MDMgLTQ3MCBjLTMgLTE1IC0xNyAtMTYgLTM3IC0yIC0xNiAxMiAtMTUgMTMgMTEgMTIgMTUgMCAyNyAtNCAyNiAtMTB6IG0tNSAtNTcgYy0xMSAtMzMgLTY4IC0zNiAtNjggLTMgMCAxMSAxMCAxNSAzNiAxNSAyMyAwIDM0IC00IDMyIC0xMnoiLz4gPHBhdGggZD0iTTEyNjQgNDM2IGMtMTAgLTI2IDQgLTQ4IDI4IC00NCAxNyAyIDIzIDEwIDIzIDI4IDAgMTggLTYgMjYgLTIzIDI4IC0xMyAyIC0yNSAtMyAtMjggLTEyeiIvPiA8L2c+PGcgaWQ9ImxheWVyMTA0IiBmaWxsPSIjZmVjOTAwIiBzdHJva2U9Im5vbmUiPiA8cGF0aCBkPSJNNTMwIDEwNjUgbDAgLTE3NSAtMjY1IDAgLTI2NSAwIDAgLTQ0NSAwIC00NDUgMjYzIDAgMjYzIDAgLTEgMjM2IC0xIDIzNSAyNyA2IGMxNSAzIDM3IDE2IDQ5IDI4IDE5IDIwIDI0IDM4IDI5IDk4IDEgNCAxMCA3IDIxIDcgMTggMCAyMCAtNyAyMCAtNzEgMCAtNjcgMSAtNzAgMjMgLTY3IDE2IDIgMjEgOSAxOSAyMyAtNCAxOSAtMiAxOCAyMiAtNCAzMCAtMjggNTkgLTIyIDU0IDExIC0yIDE0IC0xMiAyNCAtMjggMjggLTIwIDUgLTI2IDEzIC0yOCA0MiBsLTMgMzUgMzkgLTIgYzI0IC0xIDQ3IC0xMCA2MCAtMjMgMTEgLTExIDM5IC0yMyA2MSAtMjcgMzIgLTUgNDEgLTExIDM5IC0yMyAtMyAtMTMgLTE0IC0xNyAtNTAgLTE1IC0zMiAxIC00OCAtMyAtNTIgLTEzIC03IC0xOCAxNCAtMjcgNzAgLTI4IDU3IC0xIDg0IDI1IDkxIDg4IDUgNDQgOCA0NyAzMiA0NCBsMjYgLTMgMyAtMTEyIGMzIC0xMTMgMyAtMTEzIDI3IC0xMTMgMjUgMCAyNSAxIDI1IDkzIDEgMTAwIC0zIDk4IDQ5IDI5IDEyIC0xOCAyNSAtMjMgNDYgLTIwIGwzMCAzIC0zNyA0MCBjLTM1IDQwIC0zNiA0MSAtMTkgNjQgMTQgMTggMjUgMjIgNTcgMTkgbDM5IC0zIDMgLTYzIGMzIC01OCA0IC02MiAyNyAtNjIgMjUgMCAyNSAwIDI1IDEwNSBsMCAxMDUgLTMxIDAgYy0yMyAwIC0zMCAtNCAtMjYgLTE1IDQgLTggMiAtMTcgLTQgLTIxIC02IC0zIC0xMyAzIC0xNiAxNSAtMyAxMiAtMTEgMjEgLTE5IDIxIC0xNCAwIC0yMjQgMTY2IC0yNTkgMjA0IC0xMSAxMiAtNDIgNDAgLTcwIDYyIC0yNyAyMSAtNTUgNDMgLTYxIDQ5IC02IDUgLTI2IDIyIC00NSAzNyAtMTkgMTQgLTQ0IDM1IC01NSA0NSAtMTIgMTAgLTM2IDI5IC01NCA0MyAtMTggMTQgLTM4IDMwIC00NCAzNSAtMjQgMjIgLTk0IDc1IC0xMDAgNzUgLTMgMCAtNiAtNzkgLTYgLTE3NXogbTM4IC00NzYgYzUgLTI1IC0xNiAtNjkgLTMzIC02OSAtMTMgMCAtMTUgOCAtMTEgNDMgMyAyMyA1IDQzIDUgNDUgMiAxMSAzNiAtNiAzOSAtMTl6IG0zNjIgNiBjLTYgLTEyIC00MSAtOCAtNTUgNiAtNyA3IDEgOSAyNSA3IDE5IC0yIDMzIC03IDMwIC0xM3oiLz4gPHBhdGggZD0iTTEyNzAgNDM1IGMtMTcgLTIwIC01IC00NSAyMCAtNDUgMTEgMCAyMyA3IDI2IDE1IDYgMTUgLTExIDQ1IC0yNiA0NSAtNCAwIC0xMyAtNyAtMjAgLTE1eiIvPiA8L2c+PGcgaWQ9ImxheWVyMTA1IiBmaWxsPSIjZmZmZmZmIiBzdHJva2U9Im5vbmUiPiA8cGF0aCBkPSJNMjEwIDUzNSBsMCAtMTU1IDI1IDAgYzI1IDAgMjUgMSAyNSA5MyAxIDEwMCAtMyA5OCA0OSAyOSAxMiAtMTggMjUgLTIzIDQ3IC0yMCBsMjkgMyAtMzYgNDAgLTM3IDM5IDQ1IDYzIDQ1IDYzIC0zMSAwIGMtMjQgMCAtMzYgLTggLTYxIC00NSAtMTcgLTI1IC0zNSAtNDMgLTQxIC00MCAtNSA0IC05IDI0IC05IDQ2IDAgMzUgLTMgMzkgLTI1IDM5IGwtMjUgMCAwIC0xNTV6Ii8+IDxwYXRoIGQ9Ik00NTkgNjc1IGMtNTEgLTI3IC02NCAtMTIxIC0yMyAtMTY2IDI3IC0zMCA3OCAtNDIgMTE5IC0yOSA0NyAxNSA2NSA0NCA2NSAxMDMgMCA0MCAtNSA1MyAtMjkgNzggLTMzIDMzIC04NyAzOSAtMTMyIDE0eiBtOTkgLTQzIGMxOCAtMjMgMTUgLTc2IC02IC0xMDIgLTI3IC0zMyAtNjcgLTIxIC04MiAyNCAtMjQgNzQgNDIgMTM0IDg4IDc4eiIvPiA8cGF0aCBkPSJNNjcwIDU4NSBjMCAtOTggMSAtMTA1IDIwIC0xMDUgMTEgMCAyMCA3IDIwIDE1IDAgMTMgNiAxMSAyOSAtNiAxNiAtMTIgMzQgLTE4IDQwIC0xNSAxNiAxMSAxMyA0NiAtNCA0NiAtMzUgMCAtNTUgMzcgLTU1IDEwNSAwIDYzIC0xIDY1IC0yNSA2NSBsLTI1IDAgMCAtMTA1eiIvPiA8cGF0aCBkPSJNODMwIDY3MCBjLTQzIC00MyAtMTAgLTEwMiA2MyAtMTE1IDI4IC02IDM3IC0xMiAzNSAtMjQgLTMgLTEyIC0xNCAtMTYgLTUxIC0xNCAtNDAgMSAtNDggLTIgLTQ1IC0xNSA1IC0yNCA3MCAtMzcgMTA1IC0yMCAzOSAxOSA1MyA1MyA1MyAxMzYgMCA3MCAtMSA3MiAtMjUgNzIgLTE0IDAgLTI1IC01IC0yNSAtMTAgMCAtNyAtNiAtNyAtMTkgMCAtMzEgMTYgLTY5IDEyIC05MSAtMTB6IG0xMDUgLTUxIGMxMCAtMzAgLTYgLTQyIC0zOSAtMzAgLTM1IDE0IC00NSA0MCAtMjEgNTcgMjIgMTYgNTEgMyA2MCAtMjd6Ii8+IDxwYXRoIGQ9Ik0xMDUwIDUzNSBsMCAtMTU1IDI1IDAgYzI1IDAgMjUgMSAyNSA5MyAxIDEwMCAtMyA5OCA0OSAyOSAxMiAtMTggMjUgLTIzIDQ3IC0yMCBsMjkgMyAtMzYgNDAgLTM3IDM5IDQ1IDYzIDQ1IDYzIC0zMSAwIGMtMjQgMCAtMzYgLTggLTYxIC00NSAtMTcgLTI1IC0zNSAtNDMgLTQxIC00MCAtNSA0IC05IDI0IC05IDQ2IDAgMzUgLTMgMzkgLTI1IDM5IGwtMjUgMCAwIC0xNTV6Ii8+IDxwYXRoIGQ9Ik0xMjcwIDU4NSBsMCAtMTA1IDI1IDAgMjUgMCAwIDEwNSAwIDEwNSAtMjUgMCAtMjUgMCAwIC0xMDV6Ii8+IDxwYXRoIGQ9Ik0xMjcwIDQzNSBjLTE3IC0yMCAtNSAtNDUgMjAgLTQ1IDExIDAgMjMgNyAyNiAxNSA2IDE1IC0xMSA0NSAtMjYgNDUgLTQgMCAtMTMgLTcgLTIwIC0xNXoiLz4gPC9nPjwvc3ZnPg==";
        add_menu_page('Koraki Widget', 'Koraki Widget', 'manage_options', 'koraki', '', $icon );
        add_filter( 'plugin_action_links_koraki/koraki.php', array($this, 'koraki_settings_link') );
    }
    
    function koraki_settings_link( $links ) {
        $koraki_links = array(
            '<a href="' . admin_url( 'admin.php?page=koraki' ) . '">' . __( 'Settings' ) . '</a>',
        );
        return array_merge( $koraki_links, $links );
    }

    
    function koraki_admin_page(  ) {
        $settings = get_option( 'koraki_settings' );
        $linked = isset($settings['id']) && !empty($settings['id']);
    
        if(!$linked){
            if(!empty($_GET['client_id']) && !empty($_GET['client_secret']) && !isset($_POST['unlink'])){
                $input = $this->koraki_settings_validation_callback($_GET);
                $updated = update_option('koraki_settings', $input);
                if($updated){
                    if ( wp_redirect( admin_url( 'admin.php?page=koraki' ) ) ) {
                        exit;
                    }
                }
            }
        } else if(isset($_POST['unlink'])){
            $this->delete_integration($settings['client_id'], $settings['client_secret']);
            ?>
                <form action='options.php' id="unlink" method='post' autocomplete="false">
                    <?php settings_fields( 'koraki_plugin' ); ?>
                    <input type='hidden' name='koraki_settings[client_id]' value='' />
                    <input type='hidden' name='koraki_settings[client_secret]' value='' />
                    <input type='hidden' name='koraki_settings[id]' value='' />
                    <input type='hidden' name='unlink' value='1' />
                </form>
                <script>document.getElementById("unlink").submit();</script>
            <?php
        }
        
        ?>
        <h2>Koraki Widget Settings</h2>
        
        <?php settings_errors(); ?>

        <?php if($linked){ 
            $response = $this->validate_credentials_with_koraki($settings['client_id'], $settings['client_secret']);
            $plan = $response['headers']['x-koraki-plan'];
            ?>
            <div class="notice notice-notice"> 
                <div style="text-align:center;">
                    <img src="https://static.koraki.io/wpkoraki.png" height="100px" />
                    <p>
                        Your WordPress site is connected to Koraki <?php switch($plan) { 
                        case "free" : 
                            echo "<b>Free Plan</b>"; break;
                        case "tier1" : 
                                echo "<b>Personal Plan</b>"; break;
                        case "tier2" : 
                                echo "<b>Enterprise Plan</b>"; break;
                        }?>
                    </p>
                    <p>
                        Check your application settings by logging in to <a href="https://app.koraki.io/applications/view/<?php echo $settings['id']; ?>/integrations/wordpress?source=wpplugin" target="_blank">Koraki dashboard</a>
                    </p>
                    
                    <?php if($plan == "free") { ?>
                    <div style="margin-top:75px;">
                        <a href="https://app.koraki.io/subscription/add" target="_blank">
                            <img src="https://static.koraki.io/freewp.png" height="300px"/>
                        </a>
                    </div>
                    <?php } ?>
                    <div style="margin-top:10px;">
                        <img src="https://static.koraki.io/thankyouforusing.png" height="300px"/>
                    </div>
                    <img src="https://static.koraki.io/thankyouwp.png" style="width:100%"/>
                </div>
            </div>
        <?php } ?>

        <div id="manual" style="display:none;">
            <div class="panel">
                <div style="text-align:center;">
                    <img src="https://static.koraki.io/wpkoraki.png" height="60px" />
                <form action='options.php' method='post' autocomplete="false">
                    <?php
                    settings_fields( 'koraki_plugin' );
                    do_settings_sections( 'koraki_plugin' );
                    submit_button();
                    ?>
                </form>
                <p>
                    Please log into <a href="https://app.koraki.io/applications/?source=wpplugin" target="_blank">Koraki dashboard</a> to obtain your credentials
                </p>
                
                <br/>
                <br/>
                <a href="#" onclick="jQuery('#manual').hide();jQuery('#auto').show(); return false;">One click setup</a>
                </div>
            </div>
        </div>


        <?php if(!$linked){ ?>
            <div id="auto" class="panel">
                <?php
                    $url = "https://app.koraki.io/applications/new?autocreate=true&name=" . get_bloginfo( 'name' ) . "&url=" . site_url() . "&redirect=" . urlencode(admin_url( 'admin.php?page=koraki' ));    
                ?>
                
                <div style="text-align:center;">
                    <img src="https://static.koraki.io/wpkoraki.png" height="60px" />
            
                    <br/>

                    <a style="margin-top:75px; margin-bottom:75px;" href="<?php echo $url;?>" class="button button-primary">Connect to Koraki</a>

                    <p style="width: 700px;margin: auto;">
                        You will be redirected to koraki.io dashboard. Please use your Koraki login details if you have an account, or signup for a new Koraki account. You will be brought back to this page when integration is completed.
                    </p>
                
                    <br/>
                    <br/>
                    <a href="#" onclick="jQuery('#manual').show();jQuery('#auto').hide(); return false;">Manual setup</a>    
                </div>
            </div>
        <?php } ?>
        

        <?php if($linked){ ?>
            <div class="panel">
                <form method='post' autocomplete="false">
                    <?php settings_fields( 'koraki_plugin' ); ?>
                    <input type='hidden' name='unlink' value='1' />
                    <input type="submit" value="Unlink" class="button"/>
                </form>
            </div>
        <?php } ?>

    <style>
        .panel{
            margin: 15px;
            padding: 15px;
            background-color: #fff;
            border: 1px solid #eee;
        }
        
        .form-table{
            margin: 0 auto;
            width: auto;
        }
        
        .submit{
            text-align: center!important;
        }

    </style>
    <?php }
    
    public function plugin_admin_init(){
        register_setting( 'koraki_plugin', 'koraki_settings', array ( "default" => array(), "sanitize_callback" => array($this, 'koraki_settings_validation_callback')) );
        
        add_settings_section(
            'koraki_koraki_plugin_credentials_section',
            __( 'Koraki Credentials', 'wordpress' ),
            array($this, 'koraki_settings_credentials_section_callback'),
            'koraki_plugin'
        );
        
        add_settings_field(
            'koraki_client_id_field',
            __( 'Client Id', 'wordpress' ),
            array($this, 'koraki_client_id_field'),
            'koraki_plugin',
            'koraki_koraki_plugin_credentials_section'
        );

        add_settings_field(
            'koraki_client_secret_field',
            __( 'Client Secret', 'wordpress' ),
            array($this, 'koraki_client_secret_field'),
            'koraki_plugin',
            'koraki_koraki_plugin_credentials_section'
        );
    }
    
    function koraki_settings_validation_callback($input){
        if(!isset($_POST['unlink'])) {
            $client_id = sanitize_text_field($input['client_id']);
            $client_secret = sanitize_text_field($input['client_secret']);

            if(isset($input) && isset($client_id) && isset($client_secret)){
                $response = $this->check_credentials_with_koraki($client_id, $client_secret);
                if(!is_wp_error($response)){
                    if($response['response']['code'] == 404 || $response['response']['code'] == 401){
                        add_settings_error('invalid_credentials',esc_attr('settings_updated'),__('Invalid client id or client secret provided'),'error');
                    } else if($response['response']['code'] == 406){
                        add_settings_error('app_inactive',esc_attr('settings_updated'),__('Koraki application is inactive. Please activate it from <a href="https://app.koraki.io" target="_blank">https://app.koraki.io</a>'),'error');
                    } else if($response['response']['code'] == 200){
                        $json = json_decode($response['body'], true);
                        add_settings_error('success',esc_attr('settings_updated'),__('Successfully integrated with ' . $json['applicationName'] . '. <a href="https://app.koraki.io/applications/view/' . $json['id'] . '/customize?source=wpplugin" target="_blank">Click here</a> to customize your widget'),'success');
                        $input['id'] = $json['id'];
                        $input['success'] = true;
                    }
                }else{
                    add_settings_error('service_not_available',esc_attr('settings_updated'),__('Service not available at the moment'),'error');
                }
            }else{
                add_settings_error('no_credentials',esc_attr('settings_updated'),__('Please enter client id and client secret obtained from <a href="https://app.koraki.io" target="_blank">https://app.koraki.io</a>'),'error');
            }

            return $input;
        }
    }
    
    function koraki_client_id_field(  ) {
        $options = get_option( 'koraki_settings' );
        ?>
        <input type='text' name='koraki_settings[client_id]' required autocomplete="false" value='<?php if(isset($options['client_id'])) { echo $options['client_id']; } ?>' />
        <?php
    }

    function koraki_client_secret_field(  ) {
        $options = get_option( 'koraki_settings' );
        ?>
        <input type='password' name='koraki_settings[client_secret]' required autocomplete="false" value='<?php if(isset($options['client_secret'])) { echo $options['client_secret']; } ?>' />
        <?php
    }

    function koraki_settings_credentials_section_callback(  ) {
    }

    function koraki_settings_events_section_callback(  ) {
        echo __( 'Select which events you want to be published to Koraki as notifications', 'wordpress' );
    }
    
    private function validate_credentials_with_koraki($client_id, $client_secret) {
        $options = [
            'method'      => 'GET',
            'headers'     => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $client_secret )
            ],
            'sslverify'   => true
        ];
        return wp_remote_request($this->host . "/api/v1.0/applications/0/integrations", $options);
    }
    
    private function check_credentials_with_koraki($client_id, $client_secret) {
        $body = wp_json_encode( array("integration" => "wordpress", "meta" => array("url" => site_url())) );
        $options = [
            'body'        => $body,
            'method'      => 'PUT',
            'headers'     => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $client_secret )
            ],
            'sslverify'   => true,
            'data_format' => 'body',
        ];
        return wp_remote_request($this->host . "/api/v1.0/applications/0/integrations", $options);
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
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode( $settings['client_id'] . ':' . $settings['client_secret'] ),
                'x-woo-topic' => "$topic"
            ],
            'sslverify'   => false,
            'data_format' => 'body',
        ];
        
        wp_remote_post($this->host . "/modules/v1.0/webhooks/wordpress/" . $topic . "/" . $token, $options);
    }
    
     private function delete_integration($client_id, $client_secret) {
        $options = [
            'method'      => 'DELETE',
            'headers'     => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $client_secret )
            ],
            'sslverify'   => true
        ];
        return wp_remote_request($this->host . "/api/v1.0/applications/0/integrations/wordpress", $options);
    }
}
