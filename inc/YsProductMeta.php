<?php

class YsProductMeta {
  private $field_name = 'product_limit';
  static $meta_name = 'ys_product_limit';

  public function __construct() {
    add_action( 'add_meta_boxes', [$this, 'ys_add_woo_time_meta'] );
    add_action( 'save_post', [$this, 'ys_save_productData'] );
    add_filter( 'pre_get_posts', [$this, 'ys_custom_product_query'] );
    add_action( 'template_redirect', [$this, 'ys_filter_cart'] );
    add_filter( 'woocommerce_is_purchasable', [$this, 'ys_product_is_purchasable'], 20, 2 );
    add_action( 'woocommerce_single_product_summary', [$this, 'ys_add_date_infos'], 6 );
  }

  public function ys_add_woo_time_meta () {
      add_meta_box(
        'ys_woo_time',
        'Date maximum de vente',
        [$this, 'ys_woo_time_meta_html'],
        'product',
        'side'
      );
  }

  public function ys_woo_time_meta_html ($post) {
    $value = get_limit_date_product( $post->ID );
    ?>
    <label for="product_limit">Date maximum de vente :</label>
    <input type="date" name="<?= $this->field_name ?>" id="<?= $this->field_name ?>" value="<?= $value ?>">
    <?php
  }

  public function ys_save_productData( $post_id ) {
    global $post; 
    if ($post->post_type != 'product')
      return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
      return;

    if ( array_key_exists( $this->field_name, $_POST ) ) {
      update_post_meta(
        $post_id,
        self::$meta_name,
        $_POST[$this->field_name]
      );
    }
  }

  public function ys_custom_product_query ( $query ) {
    if ( is_admin() || is_single() ) return;
    if(
      (!empty($query->query['post_type']) && $query->query['post_type'] === 'product') 
      || is_tax( 'product_cat' ) 
      || is_post_type_archive('product')
    ) {
      $query->set( 
        'meta_query', 
        array(
          'relation'    => 'OR',
          array(
            'key'     => self::$meta_name,
            'compare' => 'NOT EXISTS',
          ),
          array(
            'key'     => self::$meta_name,
            'compare' => '=',
            'value'   => ''
          ),
          array(
            'key'       => self::$meta_name,
            'compare'   => '>=',
            'value'     => date('Y-m-d'),
          )
        )
      );
    }
  }

  public function ys_filter_cart () {
    if ( is_admin() ) return;
    $cart = WC()->cart;
    foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
      $product_id = $cart_item['product_id'];
      if(is_max_sell_date_over($product_id)) {
        $product_cart_id = $cart->generate_cart_id( $product_id );
        $cart_item_key = $cart->find_product_in_cart( $product_cart_id );
        if ( $cart_item_key ) $cart->remove_cart_item( $cart_item_key );
      }
    }
  }

  public function ys_product_is_purchasable ($is_purchasable, $product) {
    return !is_max_sell_date_over($product->get_id());
  }

  function ys_add_date_infos($original) {

      $text = '';
      $cls = '';
      global $post;
      if ($date = get_limit_date_product($post->ID)) {
        $formated_date = format_the_date($date);
        if (is_this_date_over($date)) {
          $cls = 'date-over';
          $text = 'Indisponnible depuis le : '.$formated_date; 
        } else {
          $text = 'Disponnible jusqu\'au : '.$formated_date; 
        }
      }

      // returning the text before the price
      print "<p class=\"date-info $cls\">".$text.'</p>';
  }


}