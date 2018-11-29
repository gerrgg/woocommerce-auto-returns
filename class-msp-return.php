<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MSP_Return{
  public $exists = false;

  public function __construct( $id ){
    if( $this->return_exists( $id ) ){
      $this->exists = true;
      $this->data = $this->get_data( $id );
    }
  }

  protected function get_data( $id ){
    global $wpdb;
    $row = $wpdb->get_row( "SELECT * FROM $wpdb->prefix" . "msp_return" . " WHERE order_id = " . $id . " OR id = " . $id );
    if ( ! empty( $row ) ) return $row;
  }

  protected function return_exists( $id ){
    global $wpdb;
    $return_id = $wpdb->get_var( $wpdb->prepare(
      "
            SELECT id
            FROM %1s
            WHERE order_id = %2s
      ",
      $wpdb->prefix . 'msp_return', $id
    ) );
    return ( empty( $return_id ) ) ? false : $return_id;
  }

  public function get_id(){
    return $this->data->id;
  }

  public function get_order_id(){
    return $this->data->order_id;
  }

  public function get_type(){
    return $this->data->type;
  }

  public function get_cost(){
    return '$' . $this->data->shipment_cost;
  }

  public function get_billing_weight(){
    return $this->data->billing_weight;
  }

  public function get_tracking(){
    return $this->data->tracking;
  }

  public function get_created(){
    return $this->data->created;
  }

  public function get_label(){
    return $this->data->label;
  }

  public function get_receipt(){
    return $this->data->receipt;
  }

  public function get_digest(){
    return $this->data->digest;
  }

  public function get_user_id(){
    return $this->data->user_id;
  }

  public function can_void_shipment(){
    $user = wp_get_current_user();
    $order = wc_get_order( $this->get_order_id() );
    $customer_id = $order->get_user_id();
    return ( in_array( 'administrator', (array) $user->roles ) || $user->ID === $customer_id  );
  }

  public function get_label_dir(){
    $upload_dir = wp_upload_dir();
    return $upload_dir['basedir'] . '/returns/' . $this->get_order_id() . '/';
  }

  public function rm_label_dir(){
    $path = $this->get_label_dir();
    $tracking = $this->get_tracking();
    unlink( $path . 'label' . $tracking . '.gif' );
    unlink( $path . 'reciept' . $tracking . '.html' );
    unlink( $path . 'html_image' . $tracking . '.html' );
    rmdir( $path );
  }

  public function get_redo_return_url(){
    $order = wc_get_order( $this->get_order_id() );
    if( ! $order ) return;

    return get_site_url() . '/returns/?id=' . $order->get_id() . '&email=' . $order->get_billing_email();
  }

  public function get_view_return_url(){
    return get_site_url() . '/returns/?order_id=' . $this->get_order_id() . '&digest=' . $this->get_digest();
  }

  public function get_void_shipment_url(){
      return get_site_url() . '/returns/?order_id=' . $this->get_order_id() . '&digest=' . $this->get_digest() . '&action=void&id='.get_current_user_id();
  }

  public function destroy(){
    global $wpdb;
    $wpdb->delete(
      $wpdb->prefix . 'msp_return',
      array( 'id' => $this->get_id() )
    );
  }


  public function set($insert){
    global $wpdb;
    $wpdb->insert(
      $wpdb->prefix . 'msp_return',
      $insert
    );
  }

}
