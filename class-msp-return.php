<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MSP_Return{
  public $exists = false;
  protected $data = array(
    'id' => 0,
    'order_id' => 0,
    'type' => '',
    'cost' => '',
    'billing_weight' => '',
    'tracking' => '',
  );

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



  public function set($insert){
    global $wpdb;
    $wpdb->insert(
      $wpdb->prefix . 'msp_return',
      $insert
    );
  }

}
