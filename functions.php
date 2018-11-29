<?php
/*
Plugin Name: MSP Shipping
Description: Allows for a website to connect with shipping API's
Version: 1.1
Author: Gregory Bastianelli
Author URI: http://drunk.kiwi
Text Domain: msp-shipping
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

register_activation_hook( __FILE__, 'msp_install' );
require_once( plugin_dir_path( __FILE__ ) . '/class-msp-return.php' );
add_action( 'admin_notices', 'admin_error' );
add_action( 'wp_enqueue_scripts', 'msp_enqueue_scripts');
add_shortcode( 'return_form', 'msp_return_form_dispatcher' );
add_action( 'admin_init', 'msp_register_settings');

add_filter( 'wp_mail_from', function( $email ) {
	return 'returns@'. get_bloginfo( 'name' ) .'.com';
});
add_filter( 'wp_mail_from_name', function( $email ) {
	return get_bloginfo( 'name' );
});

function wpdocs_set_html_mail_content_type() {
    return 'text/html';
}
add_filter( 'wp_mail_content_type', 'wpdocs_set_html_mail_content_type' );

function msp_enqueue_scripts(){
  wp_enqueue_style( 'style', plugin_dir_url( __FILE__ ) . '/style.css', false, rand(1, 1000), 'all' );
  wp_enqueue_script( 'script', plugin_dir_url( __FILE__ ) . '/main.js', array( 'jquery' ), rand(1, 1000) );
	wp_localize_script( 'script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
}

if( ! function_exists( 'pre_dump' ) ){
	function pre_dump( $arr ){
	  echo '<pre>';
	  var_dump( $arr );
	  echo '<pre>';
	}
}

function admin_error( $error = '' ) {
	if( empty( get_option( 'msp_ups_api_key' ) ) ){
		$class = 'notice notice-warning is-dismissible';
		$message = __( 'You are going to want to <a href="'. admin_url( 'plugins.php?page=msp_ship_menu' ) .'">setup MSP_Shipping Plugin</a> before you go!', 'sample-text-domain' );
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
	} else {
		if( ! empty( $error ) ){
			$class = 'notice notice-error is-dismissible';
			$message = __( $error . '<a href="'. admin_url( 'plugins.php?page=msp_ship_menu' ) .'">Go to settings to fix it!</a>', 'sample-text-domain' );
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
		}
	}
}

add_action( 'wp_ajax_get_variations_for_js', 'get_variations_for_js' );
function get_variations_for_js(){
	$variations = array();

	$product = wc_get_product( $_POST['id'] );
	if( ! $product ) return 'Not a product';

	$parent = wc_get_product( $product->get_parent_id() );
	if( ! $parent ) return 'Not a child';

	$children = $parent->get_children();
	if( ! $children ) return 'Has no children';

	foreach( $children as $child_id ){
		if( $child_id != $_POST['id'] ){
			$child = wc_get_product( $child_id );
			array_push( $variations, array(
				'id'		=> $child_id,
				'title' => $child->get_name(),
				'price' => $child->get_price(),
				'sku' => $child->get_sku(),
				'stock' => $child->get_stock_quantity(),
			) );
		}
	}

	echo json_encode( $variations );
	wp_die();
}

function msp_register_settings(){
  register_setting( 'msp_shipping_creds', 'msp_ups_api_key' );
  register_setting( 'msp_shipping_creds', 'msp_ups_user_name' );
  register_setting( 'msp_shipping_creds', 'msp_ups_password' );
  register_setting( 'msp_shipping_creds', 'msp_usps_user_name' );
  register_setting( 'msp_shipping_creds', 'msp_usps_password' );
  register_setting( 'msp_shipping_creds', 'msp_fedex_api_key' );
  register_setting( 'msp_shipping_creds', 'msp_fedex_user_name' );
  register_setting( 'msp_shipping_creds', 'msp_fedex_password' );
  register_setting( 'msp_shipping_creds', 'msp_log_to_file' );
  register_setting( 'msp_shipping_creds', 'msp_send_return_email_to' );
  register_setting( 'msp_shipping_creds', 'msp_ups_return_service' );
	register_setting( 'msp_shipping_creds', 'msp_ups_account_number' );
	register_setting( 'msp_shipping_creds', 'msp_ups_shipper_company_name' );
	register_setting( 'msp_shipping_creds', 'msp_ups_shipper_attn' );
	register_setting( 'msp_shipping_creds', 'msp_ups_shipper_company_display_name' );
	register_setting( 'msp_shipping_creds', 'msp_ups_shipper_phone' );
	register_setting( 'msp_shipping_creds', 'msp_ups_shipper_tin' );
	register_setting( 'msp_shipping_creds', 'msp_ups_shipper_address_1' );
	register_setting( 'msp_shipping_creds', 'msp_ups_shipper_address_2' );
	register_setting( 'msp_shipping_creds', 'msp_ups_shipper_city' );
	register_setting( 'msp_shipping_creds', 'msp_ups_shipper_state' );
	register_setting( 'msp_shipping_creds', 'msp_ups_shipper_postal_code' );
	register_setting( 'msp_shipping_creds', 'msp_ups_shipper_country_code' );
	register_setting( 'msp_shipping_creds', 'msp_ups_box_dims_length' );
	register_setting( 'msp_shipping_creds', 'msp_ups_box_dims_width' );
	register_setting( 'msp_shipping_creds', 'msp_ups_box_dims_height' );
	register_setting( 'msp_shipping_creds', 'msp_ups_box_dims_units' );
	register_setting( 'msp_shipping_creds', 'msp_ups_box_weight_units' );
	register_setting( 'msp_shipping_creds', 'msp_ups_validation_strictness' );
	register_setting( 'msp_shipping_creds', 'msp_ups_test_mode' );
	register_setting( 'msp_shipping_creds', 'msp_return_by' );
}

function msp_install(){
	global $wpdb;

	$table_name = $wpdb->prefix . 'msp_return';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
	order_id mediumint(9) NOT NULL UNIQUE,
	user_id mediumint(9) NOT NULL,
	type text NOT NULL,
	items text NOT NULL,
	shipment_cost text NOT NULL,
	billing_weight text NOT NULL,
	tracking text NOT NULL,
	label text NULL,
	receipt text NULL,
	digest text NOT NULL,
	created timestamp DEFAULT CURRENT_TIMESTAMP NULL,
	complete boolean DEFAULT 0,
  PRIMARY KEY  (id)
) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

function msp_get_current_returns(){
	global $wpdb;
	$rows = $wpdb->get_results( "SELECT *
																FROM " . $wpdb->prefix . "msp_return
																WHERE complete <> 1" );
	foreach( $rows as $row ) : ?>
	<tr>
		<td><?php echo $row->id; ?></td>
		<td><?php echo $row->order_id; ?></td>
		<td><?php echo $row->type; ?></td>
		<td><?php echo $row->items; ?></td>
		<td><?php echo $row->shipment_cost; ?></td>
		<td><?php echo $row->billing_weight; ?></td>
		<td><?php echo $row->tracking; ?></td>
		<td><?php echo $row->created; ?></td>
		<td><input type="checkbox" value="1" name="order[<?php echo $row->id ?>]" <?php checked( $row->complete, '1' ); ?> /></td>
		<td>
			<?php $return = new MSP_Return( $row->order_id ); ?>
			<a href="<?php echo $return->get_view_return_url() ?>">View</a>
		</td>
	</tr>
	<?php endforeach;
}

if( ! function_exists( 'msp_ship_menu_html' ) ){
  /**
  *
  * Creates the spot in the backend for user to enter credentials.
  * removes hand coded sensitive materials.
  *
  */
  function msp_ship_menu_html(){
    ?>
    <div class="wrap">
			<form id="current_returns" method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<h1>Current Returns</h1>
			<div class="returns">
				<table>
					<thead>
						<th>ID</th>
						<th>Order ID</th>
						<th>Type</th>
						<th>Items</th>
						<th>Cost</th>
						<th>Billing Weight</th>
						<th>Tracking</th>
						<th>Created</th>
						<th>Complete</th>
						<th>View</th>
					</thead>
					<?php msp_get_current_returns(); ?>
				</table>
			</div>
			<input type="hidden" name="action" value="complete_returns">
			<?php submit_button(); ?>
		</form>
      <h1>Michigan Safety Products Shipping settings</h1>
      <div class="ups">
        <form method="post" action="options.php">
          <?php
          settings_fields( 'msp_shipping_creds' );
          do_settings_sections( 'msp_shipping_creds' );
          ?>
          <table class="form-table">
						<h3>API Creds</h3>
            <tr valign="top">
              <th scope="row">UPS API KEY</th>
              <td><input type="text" name="msp_ups_api_key" value="<?php echo esc_attr( get_option('msp_ups_api_key') ); ?>" /></td>
            </tr>

            <tr valign="top">
              <th scope="row">UPS USER NAME</th>
              <td><input type="text" name="msp_ups_user_name" value="<?php echo esc_attr( get_option('msp_ups_user_name') ); ?>" /></td>
            </tr>

            <tr valign="top">
              <th scope="row">UPS PASSWORD</th>
              <td><input type="text" name="msp_ups_password" value="<?php echo esc_attr( get_option('msp_ups_password') ); ?>" /></td>
            </tr>

            <tr valign="top">
              <th scope="row">USPS USERNAME</th>
              <td><input type="text" name="msp_usps_user_name" value="<?php echo esc_attr( get_option('msp_usps_user_name') ); ?>" /></td>
            </tr>

            <tr valign="top">
              <th scope="row">USPS PASSWORD</th>
              <td><input type="text" name="msp_usps_password" value="<?php echo esc_attr( get_option('msp_usps_password') ); ?>" /></td>
            </tr>

						<tr valign="top">
              <th scope="row">Check to Log to File</th>
              <td><input type="checkbox" name="msp_log_to_file" value="1" <?php checked( get_option( 'msp_log_to_file' ) ); ?> /></td>
            </tr>
        </table>

				<hr>
				<h3>Returns</h3>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">Send Return Emails to</th>
						<td><input type="email" name="msp_send_return_email_to" value="<?php echo esc_attr( get_option('msp_send_return_email_to') ); ?>" /></td>
					</tr>
					<h4>Shipper / Shipto</h4>
					<tr valign="top">
						<th scope="row">UPS Test Mode</th>
						<td>
							<label>Test Mode</label>
							<input type="radio" name="msp_ups_test_mode" value="wwwcie" <?php if( get_option( 'msp_ups_test_mode' ) == 'wwwcie' ) echo 'checked' ?> />
							<label>Production Mode</label>
							<input type="radio" name="msp_ups_test_mode" value="onlinetools" <?php if( get_option( 'msp_ups_test_mode' ) == 'onlinetools' ) echo 'checked' ?> />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">Return Policy</th>
						<td><input type="number" name="msp_return_by" value="<?php echo esc_attr( get_option('msp_return_by') ); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row">Return Service Type</th>
						<td>
							<select name="msp_ups_return_service">
								<option value="">Please Select an Option</option>
								<option value="8" <?php if( get_option( 'msp_ups_return_service' ) == '8') echo 'selected'; ?>> 8 - UPS Electronic Return Label (ERL)</option>
								<option value="9" <?php if( get_option( 'msp_ups_return_service' ) == '9') echo 'selected'; ?>> 9 - UPS Print Return Label (PRL)</option>
								<option value="10" <?php if( get_option( 'msp_ups_return_service' ) == '10') echo 'selected'; ?>> 10 - UPS Exchange Print Return Label</option>
							</select>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">UPS Account Number</th>
						<td><input type="text" name="msp_ups_account_number" value="<?php echo esc_attr( get_option('msp_ups_account_number') ); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row">Company Name</th>
						<td><input type="text" name="msp_ups_shipper_company_name" value="<?php echo esc_attr( get_option('msp_ups_shipper_company_name') ); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row">Ship to whose attention?</th>
						<td><input type="text" name="msp_ups_shipper_attn" value="<?php echo esc_attr( get_option('msp_ups_shipper_attn') ); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row">Company Display Name</th>
						<td><input type="text" name="msp_ups_shipper_company_display_name" value="<?php echo esc_attr( get_option('msp_ups_shipper_company_display_name') ); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row">Phone Number</th>
						<td><input type="text" name="msp_ups_shipper_phone" value="<?php echo esc_attr( get_option('msp_ups_shipper_phone') ); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row">Tax Idenification Number</th>
						<td><input type="text" name="msp_ups_shipper_tin" value="<?php echo esc_attr( get_option('msp_ups_shipper_tin') ); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row">Address Line 1</th>
						<td><input type="text" name="msp_ups_shipper_address_1" value="<?php echo esc_attr( get_option('msp_ups_shipper_address_1') ); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row">Address Line 2</th>
						<td><input type="text" name="msp_ups_shipper_address_2" value="<?php echo esc_attr( get_option('msp_ups_shipper_address_2') ); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row">City</th>
						<td><input type="text" name="msp_ups_shipper_city" value="<?php echo esc_attr( get_option('msp_ups_shipper_city') ); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row">State Code ( MI )</th>
						<td><input type="text" name="msp_ups_shipper_state" value="<?php echo esc_attr( get_option('msp_ups_shipper_state') ); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row">Postal Code</th>
						<td><input type="text" name="msp_ups_shipper_postal_code" value="<?php echo esc_attr( get_option('msp_ups_shipper_postal_code') ); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row">Country Code</th>
						<td><input type="text" name="msp_ups_shipper_country_code" value="<?php echo esc_attr( get_option('msp_ups_shipper_country_code') ); ?>" /></td>
					</tr>
				</table>
				<hr>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">Validation Strictness</th>
						<td>
							<select name="msp_ups_validation_strictness">
								<option value="">Please Select an Option</option>
								<option value="nonvalidate" <?php if( get_option( 'msp_ups_validation_strictness' ) == 'nonvalidate') echo 'selected'; ?>>Validate Shipto Zip/State</option>
								<option value="validate" <?php if( get_option( 'msp_ups_validation_strictness' ) == 'validate') echo 'selected';  ?>>Validate Shipto City/State/Zip</option>
							</select>
						</td>
					</tr>
				</table>
				<hr>
				<table class="form-table">
					<h4>Weight / Dimensions</h4>
					<tr valign="top">
						<th scope="row">Default box DIMS (W x L x H) </th>
						<td>
							<label>Length:</label>
							<input type="text" name="msp_ups_box_dims_length" value="<?php echo esc_attr( get_option('msp_ups_box_dims_length') ); ?>" />
						</td>
						<td>
							<label>Width:</label>
							<input type="text" name="msp_ups_box_dims_width" value="<?php echo esc_attr( get_option('msp_ups_box_dims_width') ); ?>" />
						</td>
						<td>
							<label>Height:</label>
							<input type="text" name="msp_ups_box_dims_height" value="<?php echo esc_attr( get_option('msp_ups_box_dims_height') ); ?>" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">Dimensions Unit Type</th>
						<td>
							<select name="msp_ups_box_dims_units">
								<option value="">Please Select an Option</option>
								<option value="IN" <?php if( get_option( 'msp_ups_box_dims_units' ) == 'IN') echo 'selected'; ?>> Inches</option>
								<option value="CM" <?php if( get_option( 'msp_ups_box_dims_units' ) == 'CM') echo 'selected'; ?>> Centimeters</option>
								<option value="00" <?php if( get_option( 'msp_ups_box_dims_units' ) == '00') echo 'selected'; ?>> Metric Units Of Measurement</option>
								<option value="01" <?php if( get_option( 'msp_ups_box_dims_units' ) == '01') echo 'selected'; ?>> English Units Of Measurement</option>
							</select>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">Weight Unit Type</th>
						<td>
							<select name="msp_ups_box_weight_units">
								<option value="">Please Select an Option</option>
								<option value="LBS" <?php if( get_option( 'msp_ups_box_weight_units' ) == 'LBS') echo 'selected'; ?>>Pounds</option>
								<option value="OZS" <?php if( get_option( 'msp_ups_box_weight_units' ) == 'OZS') echo 'selected'; ?>>Ounces</option>
								<option value="KGS" <?php if( get_option( 'msp_ups_box_weight_units' ) == 'KGS') echo 'selected'; ?>>Kilograms</option>
							</select>
						</td>
					</tr>
				</table>
        <?php submit_button(); ?>
        </form>
      </div>
    </div>
    <?php
  }
}

add_action( 'admin_post_complete_returns', 'complete_the_return' );

function complete_the_return(){
	global $wpdb;
	foreach( $_POST['order'] as $id => $value ){
		$wpdb->update( $wpdb->prefix . 'msp_return',
			array( 'complete' => $value ),
			array( 'id' => $id )
		);
	}
	wp_redirect( admin_url( 'plugins.php?page=msp_ship_menu#current_returns' ) );
}

if( ! function_exists( 'msp_return_form_dispatcher' ) ){
  /**
  *
  * checks for $_GET variables if none, then send to form to get it
  *
  */
  function msp_return_form_dispatcher(){
		// TODO: UGGGGGGGGGLY
    if( isset( $_GET['id'], $_GET['email'] ) ){
      msp_validate_user( $_GET['id'], $_GET['email'] );
    } else if( isset( $_GET['order_id'], $_GET['digest'] ) ){
			$return = new MSP_Return( $_GET['order_id'] );
			// pre_dump( $return );
			if( $return->exists ){
				if( isset( $_GET['action'], $_GET['id'] ) && $_GET['action'] == 'void'){
					msp_ups_void_return_xml( $return );
					wp_redirect( '/my-account/orders' );
				} else {
					msp_view_ups_return( $return );
				}
			}else{
				wp_redirect( '/my-account/orders' );
			}
		} else {
      msp_non_valid_user_return_form();
    }
  }
}

function msp_view_ups_return( $return ){
	?>
	<div class="row">
		<div class="col-xs-12 col-sm-6">
			<h3>Return Info</h3>
			<table>
				<tbody>
					<tr>
						<th>RMA #</th>
						<td><?php echo $return->get_id(); ?></td>
					</tr>
					<tr>
						<th>Order #</th>
						<td><?php echo $return->get_order_id(); ?></td>
					</tr>
					<tr>
						<th>Type</th>
						<td><?php echo $return->get_type(); ?></td>
					</tr>
					<?php
					$user = wp_get_current_user();
					if( in_array( 'administrator', (array) $user->roles ) ) : ?>
					<tr>
						<th>Shipment Cost</th>
						<td><?php echo $return->get_cost(); ?></td>
					</tr>
					<?php endif; ?>
					<tr>
						<th>Billing Weight</th>
						<td><?php echo $return->get_billing_weight(); ?></td>
					</tr>
					<tr>
						<th>Tracking #</th>
						<td><?php echo $return->get_tracking(); ?></td>
					</tr>
					<tr>
						<th>Label Created</th>
						<td><?php echo $return->get_created(); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="col-xs-12 col-md-6">
			<?php if( ! $return->is_complete() ) : ?>
			<h3>Actions</h3>
			<a href="<?php echo $return->get_label()?>" role="button" class="button woocommerce-button button btn-success">View Label</a>
			<a href="<?php echo $return->get_receipt()?>" role="button" class="button woocommerce-button button btn-alt">View Receipt</a>
			<a href="<?php echo $return->get_redo_return_url(); ?>" role="button" class="button woocommerce-button btn-info">Redo Return Request</a>
			<?php if( $return->can_void_shipment() ) : ?>
				<a href="<?php echo $return->get_void_shipment_url(); ?>" role="button" class="void-return button woocommerce-button btn-danger">Void Shipment</a>
			<?php endif; ?>
		<?php else: ?>
			<h3>Return has been marked complete.</h3>
			<!-- TODO: add option to include where you contact us page is && LOGO -->
			<?php echo msp_contact_us(); ?>
		<?php endif ?>
		</div>
	</div>
	<?php
}

function msp_contact_us(){
	return '<p>If you believe this is a mistake, <a href="' . get_site_url( ) . '/contact-us/">please contact us</a></p>';
}

if( ! function_exists( 'msp_non_valid_user_return_form' ) ){
  /**
  * outputs the form for users to enter the order id and creds to verify user
  * @param string $error - provides user with feedback when things dont match up.
  */
  function msp_non_valid_user_return_form( $error = '' ){
    ?>
    <div class="col-12 text-center">
      <h4 class="danger"><?php echo $error; ?></h4>
      <form class="text-center" method="GET" style="max-width: 450px; margin: auto">
        <h2>Please log in, or enter the ID and Email attached to the order.</h2>
        <div class="form-group">
          <input type="tel" name="id" placeholder="Order ID" />
          <input type="email" name="email" placeholder="youremail@example.com" />
        </div>
        <button type="submit" class="woocommerce-button button">Submit</button>
      </form>
    </div>
    <?php
  }
}

if( ! function_exists( 'msp_validate_user' ) ){
  /**
  *
  * makes sure that the user is in fact the person who made the order
  */
  function msp_validate_user( $order_id, $given_email  ){
    if( isset( $_GET['id'], $_GET['email'] ) ){
        $order_id = $_GET['id'];
        $given_email = $_GET['email'];
    }

    $order = wc_get_order( $order_id );
    if( empty( $order ) ) {
      msp_non_valid_user_return_form( 'Sorry, that order does not exist!' );
    } else {
      $order_email = $order->get_billing_email();
      if( $order_email != urldecode( $given_email ) ){
        msp_non_valid_user_return_form( 'Sorry, ' . urldecode( $given_email ) . ' that is not the email on the order!' );
      } else {
        msp_get_return_form_html( $order );
      }
    }
  }
}

add_action( 'admin_post_confirm_return', 'msp_confirm_return' );
add_action( 'admin_post_nopriv_confirm_return', 'msp_confirm_return' );

if( ! function_exists( 'msp_confirm_return' ) ){
  /**
  *
  * Processes data from return request
  *
  *
  */
  function msp_confirm_return(){
    $order = wc_get_order( $_POST['order_id'] );
    if( $order ){
      $returns = array(
        'name'  => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
        'email' => $order->get_billing_email(),
				'phone' => $order->get_billing_phone(),
        'order' => $order->get_id(),
				'type'  => 'return'
      );
      foreach( $_POST as $key => $item ){
        if( $key != 'order_id' && $key != 'action' ){
          $product = wc_get_product( $key );
          if( $product ){
            $returns['items'][$key] = array(
							'qty' => $item['how_many'],
              'sku' => $product->get_sku(),
              'name' => $product->get_name(),
              'weight' => $product->get_weight(),
              'reason' => $item['return_reason'],
							'id' => $key,
            );
          }
					if( $item['return_reason'] == "I\'d like to make an exchange" ){
						$returns['type'] = 'exchange';
						foreach( $item['exchange_for'] as $id => $qty ){
							if( !empty( $qty ) ) $returns['items'][$key]['exchange_for'][$id] = $qty;
						}
					}
        }
      }
			// pre_dump( $_POST );
			// pre_dump( $returns );
			msp_shipment_confirm_request( $returns );
    }
  }
}

if( ! function_exists( 'msp_shipment_confirm_request' ) ){
  /**
  *
  * creates and sends ups shipping confirm
	*	@param array $data - data for ship request
  *
  */
	function msp_shipment_confirm_request( $data ){
		$accessRequest = sc_ups_create_access_request_xml();
		$shipmentConfirmRequest = sc_ups_create_shipment_confirm_request( $data );
		$xml = $accessRequest->asXML() . $shipmentConfirmRequest->asXML();

		$response = sc_get_xml_by_curl( 'https://'. get_option( 'msp_ups_test_mode' ) .'.ups.com/ups.app/xml/ShipConfirm', $xml );
		if( $response['Response']['ResponseStatusCode'] ){
			msp_shipment_accept_request( $response, $data );
		} else {
			admin_error( $response );
		}

	}
}



if( ! function_exists( 'msp_shipment_accept_request' ) ){
  /**
  *
  * creates the msp_shipment_accept_request
  *
  */
	function msp_shipment_accept_request( $response, $data ){
		$access_request = sc_ups_create_access_request_xml();
		$shipment_accept_request = msp_create_shipment_accept_request( $response );
		$xml = $access_request->asXML() . $shipment_accept_request->asXML();
		$response = sc_get_xml_by_curl( 'https://'. get_option( 'msp_ups_test_mode' ) .'.ups.com/ups.app/xml/ShipAccept', $xml );

		if( $response['Response']['ResponseStatusCode'] ){
			msp_set_return( $response, $data );
		} else {
			admin_error( $response );
		}

	}
}

function msp_set_return( $response, $data ){
		global $wpdb;
		$order = wc_get_order( $data['order'] );
		$args = array(
			'order_id' => $data['order'],
			'type' => $data['type'],
			'items' => $data['items'],
			'shipment_cost' => $response['ShipmentResults']['ShipmentCharges']['TotalCharges']['MonetaryValue'],
			'billing_weight' => $response['ShipmentResults']['BillingWeight']['Weight'],
			'tracking' => $response['ShipmentResults']['ShipmentIdentificationNumber'],
			'digest' => msp_create_digest(),
			'user_id' => $order->get_user_id(),
		);

		if( isset( $response['ShipmentResults']['PackageResults']['LabelImage'] ) ){
			$labels = msp_save_ups_label( $response );
			$args['label'] = $labels[1];
			$args['receipt'] = $labels[2];
		}

		$return = new MSP_Return( $data['order'] );

		if ( ! $return->exists ){
			$wpdb->insert(
				$wpdb->prefix . 'msp_return',
				$args
			);
		} else {
			msp_ups_void_return_xml( $return );
			$return->rm_label_dir();
			$wpdb->update(
				$wpdb->prefix . 'msp_return',
				$args,
				array( 'order_id' => $args['order_id'] )
			);
		}

		msp_create_return_email( $data, array(
			'to' => get_option( 'msp_send_return_email_to' ),
			'subject' => $data['name'] . ' wants to make a return',
		) );
		$new_return = new MSP_Return( $data['order'] );
		wp_redirect( $new_return->get_view_return_url() );
}

function msp_create_digest(){
	return sha1( substr( md5( rand() ), 0, 10) );
}

function msp_save_ups_label( $response ){
	$upload_dir = wp_upload_dir();
	$order_id = $response['Response']['TransactionReference']['CustomerContext'];
	$write_to = $upload_dir['basedir'] . '/returns/' . $order_id . '/';
	$write_to_url = $upload_dir['baseurl'] . '/returns/' . $order_id . '/';
	$tracking = $response['ShipmentResults']['ShipmentIdentificationNumber'];

	if( ! file_exists( $write_to ) ) mkdir( $write_to );

	$base_64_images = array(
		'label' => $response['ShipmentResults']['PackageResults']['LabelImage']['GraphicImage'],
		'html_image' => $response['ShipmentResults']['PackageResults']['LabelImage']['HTMLImage'],
		'reciept' => $response['ShipmentResults']['PackageResults']['Receipt']['Image']['GraphicImage'],
	);
	$image_paths = array();

	foreach( $base_64_images as $key => $img ){
		$ext = ( $key == 'label' ) ? '.gif' : '.html';
		$label_file = $key . $tracking . $ext;
		$ifp = fopen($write_to . '/' . $label_file, 'wb');
		fwrite($ifp, base64_decode($img));
		fclose($ifp);
		array_push( $image_paths, $write_to_url . $label_file );
	}

	return $image_paths;
}

function base64_to_img( $base64_string, $output_file ) {
    $ifp = fopen( $output_file, "wb" );
    fwrite( $ifp, base64_decode( $base64_string) );
    fclose( $ifp );
    return( $output_file );
}

if( ! function_exists( 'msp_create_shipment_accept_request' ) ){
  /**
  *
  * creates the ShipmentAcceptRequest xml
  *
  */
	function msp_create_shipment_accept_request( $response ){
		$accept = new SimpleXMLElement( '<ShipmentAcceptRequest></ShipmentAcceptRequest>' );

		$accept->addChild( 'Request' );
		$accept->Request->addChild( 'CustomerContext', $response['Response']['TransactionReference']['CustomerContext'] );
		$accept->Request->addChild( 'RequestAction', 'ShipAccept' );
		$accept->Request->addChild( 'RequestOption', '01' );

		$accept->addChild( 'ShipmentDigest', $response['ShipmentDigest'] );

		return $accept;
	}
}

if( ! function_exists( 'sc_ups_create_shipment_confirm_request' ) ){
  /**
  *
  * creates the shipment xml
  *
  */
	function sc_ups_create_shipment_confirm_request( $data ){
		$shipmentConfirmRequest = new SimpleXMLElement('<ShipmentConfirmRequest></ShipmentConfirmRequest>');
		$request = msp_ups_create_shipment_request( $data['order'] );
		$shipment = msp_ups_create_shipment( $data );
		$label = msp_ups_create_label();

		sxml_append( $shipmentConfirmRequest, $request );
		sxml_append( $shipmentConfirmRequest, $shipment );
		sxml_append( $shipmentConfirmRequest, $label );

		return $shipmentConfirmRequest;

	}
}

function sxml_append(SimpleXMLElement $to, SimpleXMLElement $from) {
	// https://stackoverflow.com/questions/4778865/php-simplexml-addchild-with-another-simplexmlelement
	// LIFESAVER ^^^
    $toDom = dom_import_simplexml($to);
    $fromDom = dom_import_simplexml($from);
    $toDom->appendChild($toDom->ownerDocument->importNode($fromDom, true));
}

if( ! function_exists( 'msp_ups_create_label' ) ){
  /**
  *
  * creates the ups xml for label specification
  *
  */
	function msp_ups_create_label( ){
		$label = new SimpleXMLElement('<LabelSpecification></LabelSpecification>');

		$label->addChild( 'LabelPrintMethod' );
		$label->LabelPrintMethod->addChild( 'Code', 'GIF' );
		$label->LabelPrintMethod->addChild( 'Description', 'GIF' );

		$label->addChild( 'LabelImageFormat' );
		$label->LabelImageFormat->addChild( 'Code', 'GIF' );
		$label->LabelImageFormat->addChild( 'Description', 'GIF' );

		$label->addChild( 'HTTPUserAgent', $_SERVER['HTTP_USER_AGENT'] );

		return $label;
	}
}

if( ! function_exists( 'msp_ups_void_return_xml' ) ){
  /**
  *
  *
  */
  function msp_ups_void_return_xml( $return ){
		$accessRequest = sc_ups_create_access_request_xml();
		$voidShipmentRequest = msp_ups_create_void_shipment_xml( $return->get_tracking() );
		$requestXML = $accessRequest->asXML() . $voidShipmentRequest->asXML();
		$response = sc_get_xml_by_curl( 'https://'. get_option( 'msp_ups_test_mode' ) .'.ups.com/ups.app/xml/Void', $requestXML );
		if( isset( $response['Status']['StatusCode']['Code'] ) && $response['Status']['StatusCode']['Code'] ){
			if( $return->can_void_shipment() ){
				$return->rm_label_dir();
				$return->destroy();
			}else{
				admin_error( $response );
			}
		}
	}
}

if( ! function_exists( 'msp_ups_create_void_shipment_xml' ) ){
  /**
  *
	*
  *
  */
  function msp_ups_create_void_shipment_xml( $tracking ){
		$username = 'Idk';
		if( get_current_user_id() ){
			$user = get_userdata( get_current_user_id() );
			$username = $user->user_login;
		}

		$voidShipment = new SimpleXMLElement( '<VoidShipmentRequest></VoidShipmentRequest>' );
		$voidShipment->addChild( 'Request' );
		$voidShipment->Request->addChild( 'TransactionReference' );
		$voidShipment->Request->TransactionReference->addChild( 'CustomerContext', 'Voided by ' . $username  );
		$voidShipment->Request->addChild( 'RequestAction', '1' );
		$voidShipment->addChild( 'ShipmentIdentificationNumber', $tracking );
		return $voidShipment;
	}
}

if( ! function_exists( 'msp_ups_create_shipment' ) ){
  /**
  *
  * creates the shipment xml
  *
	*/
	function msp_ups_create_shipment( $data ){
		$order = wc_get_order( $data['order'] );

		$shipment = new SimpleXMLElement( '<Shipment></Shipment>' );

		// TODO: Add return service options
		$shipment->addChild( 'ReturnService' );
		$shipment->ReturnService->addChild( 'Code', get_option( 'msp_ups_return_service' ) );

		if( get_option( 'msp_ups_return_service' ) == '8' ){
			$shipment->addChild( 'ShipmentServiceOptions' );
			$shipment->ShipmentServiceOptions->addChild( 'LabelDelivery' );
			$shipment->ShipmentServiceOptions->LabelDelivery->addChild( 'EMailMessage' );
			$shipment->ShipmentServiceOptions->LabelDelivery->EMailMessage->addChild( 'EMailAddress', $order->get_billing_email() );
			$shipment->ShipmentServiceOptions->LabelDelivery->EMailMessage->addChild( 'FromEMailAddress', 'returns@' . get_bloginfo( 'name' ) . '.com' );
			$shipment->ShipmentServiceOptions->LabelDelivery->EMailMessage->addChild( 'FromName', get_bloginfo( 'name' ) );
			$shipment->ShipmentServiceOptions->LabelDelivery->EMailMessage->addChild( 'Memo', 'Here\'s your shipping label!' );
			$shipment->ShipmentServiceOptions->LabelDelivery->EMailMessage->addChild( 'Subject', 'Here\'s your shipping label!' );
		}

		$shipment->addChild( 'Shipper' );
		$shipment->Shipper->addChild( 'Name', get_option( 'msp_ups_shipper_company_name' ) );
		$shipment->Shipper->addChild( 'AttentionName', get_option( 'msp_ups_shipper_attn' ) );
		$shipment->Shipper->addChild( 'CompanyDisplayableName', get_option( 'msp_ups_shipper_company_display_name' ) );
		$shipment->Shipper->addChild( 'PhoneNumber', get_option( 'msp_ups_shipper_phone' ) );
		$shipment->Shipper->addChild( 'ShipperNumber', get_option( 'msp_ups_account_number' ) );
		$shipment->Shipper->addChild( 'TaxIdentificationNumber', get_option( 'msp_ups_shipper_tin' ) );

		$shipment->Shipper->addChild( 'Address' );
		$shipment->Shipper->Address->addChild( 'AddressLine1', get_option( 'msp_ups_shipper_tin' ) );
		$shipment->Shipper->Address->addChild( 'City', get_option( 'msp_ups_shipper_city' ) );
		$shipment->Shipper->Address->addChild( 'StateProvinceCode', get_option( 'msp_ups_shipper_state' ) );
		$shipment->Shipper->Address->addChild( 'PostalCode', get_option( 'msp_ups_shipper_postal_code' ) );
		$shipment->Shipper->Address->addChild( 'CountryCode', get_option( 'msp_ups_shipper_country_code' ) );

		$shipment->addChild( 'ShipTo' );
		$shipment->ShipTo->addChild( 'CompanyName', get_option( 'msp_ups_shipper_company_name' ) );
		$shipment->ShipTo->addChild( 'AttentionName', get_option( 'msp_ups_shipper_attn' ) );
		$shipment->ShipTo->addChild( 'PhoneNumber', get_option( 'msp_ups_shipper_phone' ) );

		$shipment->ShipTo->addChild( 'Address' );
		$shipment->ShipTo->Address->addChild( 'AddressLine1', get_option( 'msp_ups_shipper_address_1' ) );
		$shipment->ShipTo->Address->addChild( 'City', get_option( 'msp_ups_shipper_city' ) );
		$shipment->ShipTo->Address->addChild( 'StateProvinceCode', get_option( 'msp_ups_shipper_state' ) );
		$shipment->ShipTo->Address->addChild( 'PostalCode', get_option( 'msp_ups_shipper_postal_code' ) );
		$shipment->ShipTo->Address->addChild( 'CountryCode', get_option( 'msp_ups_shipper_country_code' ) );

		$shipment->addChild( 'ShipFrom' );
		if( ! empty($order->get_billing_company() ) ){
			$shipment->ShipFrom->addChild( 'CompanyName', $order->get_billing_company() );
		} else {
			$shipment->ShipFrom->addChild( 'CompanyName', $data['name'] );
		}
		$shipment->ShipFrom->addChild( 'AttentionName', $data['name'] );
		$shipment->ShipFrom->addChild( 'AttentionName', $order->get_billing_phone() );

		$shipment->ShipFrom->addChild( 'Address' );
		$shipment->ShipFrom->Address->addChild( 'AddressLine1', $order->get_shipping_address_1() );
		$shipment->ShipFrom->Address->addChild( 'AddressLine2', $order->get_shipping_address_2() );
		$shipment->ShipFrom->Address->addChild( 'City', $order->get_shipping_city() );
		$shipment->ShipFrom->Address->addChild( 'StateProvinceCode', $order->get_shipping_state() );
		$shipment->ShipFrom->Address->addChild( 'PostalCode', $order->get_shipping_postcode() );
		$shipment->ShipFrom->Address->addChild( 'CountryCode', $order->get_shipping_country() );

		$shipment->addChild( 'PaymentInformation' );
		$shipment->PaymentInformation->addChild( 'Prepaid' );
		$shipment->PaymentInformation->Prepaid->addChild( 'BillShipper' );
		$shipment->PaymentInformation->Prepaid->BillShipper->addChild( 'AccountNumber', get_option( 'msp_ups_account_number' ) );

		$shipment->addChild( 'Service' );
		$shipment->Service->addChild( 'Code', '03' );
		// TODO: Add option?
		$shipment->Service->addChild( 'Description', 'Ground' );

		$shipment->addChild( 'Package' );
		// TODO: Add more detail to the order return.
		$shipment->Package->addChild( 'Description', msp_get_store_description( $data['order'] ) );

		$shipment->Package->addChild( 'PackagingType' );
		// TODO: Add option
		$shipment->Package->PackagingType->addChild( 'Code', '02' );
		$shipment->Package->PackagingType->addChild( 'Description', 'Customer Supplied Package' );

		$shipment->Package->addChild( 'Dimensions' );
		$shipment->Package->Dimensions->addChild( 'UnitOfMeasurement' );
		$shipment->Package->Dimensions->UnitOfMeasurement->addChild( 'Code', get_option( 'msp_ups_box_dims_units' ) );
		$shipment->Package->Dimensions->addChild( 'Length', get_option( 'msp_ups_box_dims_length' ) );
		$shipment->Package->Dimensions->addChild( 'Width', get_option( 'msp_ups_box_dims_width' ) );
		$shipment->Package->Dimensions->addChild( 'Height', get_option( 'msp_ups_box_dims_height' ) );

		$shipment->Package->addChild( 'PackageWeight' );
		$shipment->Package->PackageWeight->addChild( 'UnitOfMeasurement' );
		$shipment->Package->PackageWeight->UnitOfMeasurement->addChild( 'Code', 'LBS' );
		$shipment->Package->PackageWeight->addChild( 'Weight', msp_get_package_weight( $data['items'] ) );

		return $shipment;
	}
}

function msp_get_store_description( $order_id ){
	return substr( get_bloginfo( 'name' ) . ': #' . $order_id, 0, 35 );
}

if( ! function_exists( 'msp_get_package_weight' ) ){
  /**
  *
  * returns an educated guess of package weight
	* @param array $items - items the user wishes to return, and data about those items
	* @param int $weight - a quick estimation of package weight using item weight + qty
  *
  */
	function msp_get_package_weight( $items ){
		$weight = 0;
		foreach( $items as $item ){
			$item_weight = $item['weight'] * $item['qty'];
			$weight += $item_weight;
		}

		$convert_unit = get_option( 'msp_ups_box_weight_units' );

		if( $convert_unit == 'OZS' ){
			return $weight / 16;
		} else if( $convert_unit == 'KGS' ){
			return $weight * 2.205;
		} else {
			return $weight;
		}

	}
}

if( ! function_exists( 'msp_ups_create_shipment_request' ) ){
  /**
  *
  * creates the shipment xml
  *
  */
	function msp_ups_create_shipment_request( $order_id ){
		$request = new SimpleXMLElement( '<Request></Request>' );
		$request->addChild( 'TransactionReference');
		$request->TransactionReference->addChild( 'CustomerContext', $order_id );
		$request->addChild( 'RequestAction', 'ShipConfirm' );
		$request->addChild( 'RequestOption', 'nonvalidate' );
		return $request;
	}
}

if( ! function_exists( 'msp_create_return_email' ) ){
  /**
  *
  * Takes the formatted data from $_POST and creates a message for emailing
  * @param array $returns - Order / Item Data
  * @param array $args - args for the wp_mail function
  *
  */
  function msp_create_return_email( $data, $args = '' ){
		$return = new MSP_Return( $data['order'] );
		$user = get_userdata( $return->get_user_id() );

		$message = '<h2>' . $user->user_login . ' created a return label for order #' . $data['order'] . '</h2>';
		$message .= '<h3>Items being returned:</h3>';
		$message .= '<table><th>QTY</th><th>SKU</th><th>NAME</th><th>WEIGHT</th><th>Reason</th>';
		foreach( $data['items'] as $item ){
			if( isset( $item['exchange_for'] ) ) $message .= '<th>Exchange For</th>';
			$message .= '<tr>';
			foreach( $item as $key => $prop ){
				if( $key != 'id' && $key != 'exchange_for' ){
					$message .= '<td style="padding-right: 15px;">'. $prop .'</td>';
				}
				if( $key == 'exchange_for' ){
					$message .= '<td style="padding-right: 15px;">';
					foreach( $prop as $id => $qty ){
						$item = wc_get_product( $id );
						$message .= '<p style="display: block">' . $qty . 'x - ' . $item->get_name() . '</p>';
					}
					$message .= '</td>';
				}
			}
			$message .= '</tr>';
		}
		$message .= '</table>';

		$message .= '<h3>Return Details:</h3>';
		$message .= '<p>RMA #: '. $return->get_id() .'</p>';
		$message .= '<p>TYPE: '. $return->get_type() .'</p>';
		$message .= '<p>COST: '. $return->get_cost() .'</p>';
		$message .= '<p>BILLING WEIGHT: '. $return->get_billing_weight() .'</p>';
		$message .= '<p>CREATED AT: '. $return->get_created() .'</p>';
		$message .= '<p>TRACKING #: '. $return->get_tracking() .'</p>';

		$message .= '<h2>So what are you gonna do about it?</h2>';
		$message .= '<p><a href="'. $return->get_view_return_url() .'">View Return</a></p>';
		$message .= '<p><a href="'. $return->get_label() .'">Get Label</a></p>';
		$message .= '<p><a href="'. $return->get_receipt() .'">Get Receipt</a></p>';
		$message .= '<p><a href="'. $return->get_void_shipment_url() .'">Void Return</a></p>';

		$headers[]   = 'Reply-To: '. $user->nicename .' <'. $data['email'] .'>';

		// echo $message;

    wp_mail( $args['to'], $args['subject'], $message, $headers );
		create_customer_return_email( $return, $data['email'] );
  }
}

if( ! function_exists( 'create_customer_return_email' ) ){
	function create_customer_return_email( $return, $email ){
		$message = "<h2>We've got your return label!</h2>";
		$message .= '<p><a href="'. $return->get_label() .'">Get Label</a></p>';
		$message .= '<p><a href="'. $return->get_view_return_url() .'">Redo Return</a></p>';
		$message .= '<p><a href="'. $return->get_void_shipment_url() .'">Void Return</a></p>';

		wp_mail( $email, get_bloginfo('name') . ' - UPS Return Label', $message );
	}
}

if( ! function_exists( 'msp_get_return_form_html' ) ){
  function msp_get_return_form_html( $order ){
    if( $order ){
      $items = $order->get_items();
      // pre_dump( $items );
      ?>
      <h3>Which Item's would you like to return/exchange?</h3>
      <form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="row">
        <div class="col-12 col-sm-6">
          <?php foreach( $items as $key => $item ) : ?>
            <?php $id = msp_get_actual_id( $item ); ?>
            <?php $product = wc_get_product( $id ); ?>
            <?php if( $product ) : ?>
            <div id="<?php echo $id ?>" class="row return-product" data-qty="<?php echo $item['quantity'] ?>" data-product-type="<?php echo $product->get_type(); ?>">
              <div class="col-3">
                <?php $image_src = wp_get_attachment_image_src( $product->get_image_id() ); ?>
                <div class="return-product-img" aria-checked="false">
                  <img src="<?php echo $image_src[0]; ?>" class="thumbnail" />
                  <i class="fa fa-check-circle fa-4x"></i>
                </div>
              </div>
              <div class="col-9">
                <span class="title" style="margin: 0px;"><?php echo $product->get_name(); ?></span>
                <span class="sku"><?php echo $product->get_sku(); ?></span><br>
                <span class="price"><?php echo '$' . $item['total']; ?></span>
              </div>
              </div>
            <div class=" <?php echo $id ?>_hidden-return-form hidden-return-form"></div>
            <?php endif; ?>
          <?php endforeach; ?>
          <br>
          <input type="hidden" name="order_id" value="<?php echo $order->get_id(); ?>">
          <input type="hidden" name="action" value="confirm_return">
        </div>
        <div class="col-12 col-sm-6">
          <div class="confirmation">
            <h4>I am returning...</h4>
            <div id="return-data"></div>
            <div style="display: flex; margin-bottom: 1rem;">
              <input id="check_return" type="checkbox">
              <label style="line-height: 12px;">I am confirming the above information is accurate.</label>
            </div>
            <button type="submit" class="woocommerce-button button w-100" disabled>Submit</button>
          </div>
        </div>
      </form>
      <?php
    }
  }
}

if( ! function_exists( 'sc_return_item_html' ) ){
  /**
  * helper function helps to reduce which id to use - product or variation
  * @param array $item - array of item returned from WC_ORDER->get_items();
  * @return string $actual_id - the proper id to use.
  */
  function msp_get_actual_id( $item ){
    return ( empty( $item['variation_id'] ) ) ? $item['product_id'] : $item['variation_id'];
  }
}

add_action( 'msp_after_my_account_order_actions', 'sc_return_item_html', 5, 1 );
if( ! function_exists( 'sc_return_item_html' ) ){
  /**
  * determines if the user can return the item, if so than display return link.
	*
	* @param string - Order ID
  */
  function sc_return_item_html( $order_id ){
		$return = new MSP_Return( $order_id );

		if( ! $return->exists ){
			msp_get_return_button( $order_id );
		} else {
			msp_view_return_button( $return );
		}
  }
}

function msp_view_return_button( $return ){
	$btn_str = ( ! $return->is_complete() ) ? 'View Return Request' : 'Return Completed';
	// TODO: allow user to edit and recover label;
	echo '<a href="'. $return->get_view_return_url() .'" class="woocommerce-button button">'. $btn_str .'</a>';
}

function msp_get_return_button( $order_id ){
	$order = wc_get_order( $order_id );
	if( $order->get_status( 'completed' ) ){
		$delivered = $order->get_date_completed()->modify( '+10 days' );
		$return_by = $delivered->modify( '+' . get_option( 'msp_return_by' ) . ' days' );

		$today = new DateTime();
		if( $today <= $return_by ){
			$email = $order->get_billing_email();
			$link = get_site_url( ) . '/returns?id='. $order_id . '&email=' . $email;
			$return_btn = '<a href="'. $link .'" class="woocommerce-button button">Return</a>';
			echo $return_btn;
		} else {
			echo 'Return window closed ' . $return_by;
		}
	}

}

add_action( 'admin_menu', 'sc_setup_shipping_integration' );
if( ! function_exists( 'sc_setup_shipping_integration' ) ){
  /**
  *
  * Creates the spot in the backend for user to enter credentials.
  * removes hand coded sensitive materials.
  *
  */
  function sc_setup_shipping_integration(){
    add_plugins_page( 'MSP Shipping', 'MSP Shipping', 'administrator', 'msp_ship_menu', 'msp_ship_menu_html' );
  }
}

if( ! function_exists( 'sc_debug_log' ) ){
  /**
	* @param array $data - prepackaged arryay full of data like shipper & tracking Link.
  * Logs the output of delivery dates, used to catch errors
  */
  function sc_debug_log( $data ){
    file_put_contents( plugin_dir_path( __FILE__ ) . 'msp_debug.txt', print_r( $data, TRUE ), FILE_APPEND );
  }
}


if( ! function_exists( 'sc_bundle_tracking_info' ) ){
  /**
  *
  * retrieves and packs up tracking info
  * @param string $order_id
  * @return array $tracking_info array of shipper, tracking # and prebuilt link
  *
  */
  function sc_bundle_tracking_info( $order_id ){
    $tracking_info = array(
      'shipper'  => get_post_meta( $order_id, 'shipper', true ),
      'tracking' => get_post_meta( $order_id, 'tracking', true ),
      'link'     => get_post_meta( $order_id, 'tracking_link', true ),
    );
    return $tracking_info;
  }
}

if( ! function_exists( 'sc_get_ups_delivery_date' ) ){
  /**
  *
  * creates ups xml file and recieves data via cURL
  * @param string $tracking - the tracking # provided by ups
  */
  function sc_get_ups_delivery_date( $tracking ){
    $accessRequest = sc_ups_create_access_request_xml( );
    $trackRequestXML = sc_ups_create_tracking_request_xml( $tracking );
    $requestXML = $accessRequest->asXML() . $trackRequestXML->asXML();
    $response = sc_get_xml_by_curl( 'https://'. get_option( 'msp_ups_test_mode' ) .'.ups.com/ups.app/xml/Track', $requestXML );
    return sc_format_date_and_return( $response );
  }
}

if( ! function_exists( 'sc_format_date_and_return' ) ){
  /**
  *
  * @param array $shipment - ups tracking api response
	* @return string - output the api response
  *
  */
  function sc_format_date_and_return( $shipment ){
    // pre_dump( $shipment );

    $delivery_details = array(
      'delivered' => $shipment['Shipment']['Package']['DeliveryIndicator'],
      'status' => $shipment['Shipment']['Package']['Activity'][0]['Status']['StatusType']['Description'],
    );

    if( $delivery_details['delivered'] == 'Y' ){
      return 'Delivered ' . date( 'F, j, Y', strtotime( $shipment['Shipment']['Package']['DeliveryDate'] ) );
    } else {
      return 'Delivers ' . date( 'F, j, Y', strtotime( $shipment['Shipment']['ScheduledDeliveryDate'] ) );
    }
  }
}

if( ! function_exists( 'sc_get_fedex_delivery_date' ) ){
  /**
  *
  * TODO: Get fedex API to work
	* TODO: At the very least, find a way to get delivery date.
  *
  */
  function sc_get_fedex_delivery_date( $tracking ){
    return 'Click to see Tracking';
  }

}

if( ! function_exists( 'sc_get_usps_delivery_date' ) ){
  /**
  * creates api requrest, processes response and echos result
  * @param string $tracking - USPS Tracking Number
  *
  */
  function sc_get_usps_delivery_date( $tracking ){
    $request = sc_create_usps_tracking_request( $tracking );
    $response = sc_get_xml_by_curl( $request );
    if( isset( $response['TrackInfo']['TrackSummary'] ) ) return $response['TrackInfo']['TrackSummary'];
  }
}

if( ! function_exists( 'sc_create_usps_tracking_request' ) ){
  /**
  *
  * creates usps xml file returns
  * @param string $tracking - USPS Tracking Number
  * @return string $request - String formated to work with USPS API
  */
  function sc_create_usps_tracking_request( $tracking ){
    $tracking = str_replace( ' ', '', $tracking );
    $url = "https://secure.shippingapis.com/ShippingAPI.dll";
    $service = "TrackV2";
    $xml = rawurlencode('
    <TrackRequest USERID="'. get_option( 'msp_usps_user_name' ) .'">
        <TrackID ID="'. $tracking .'"></TrackID>
        </TrackRequest>');
    $request = $url . "?API=" . $service . "&XML=" . $xml;
    return $request;
  }
}

if( ! function_exists( 'sc_ups_create_access_request_xml' ) ){
  /**
  *
  * generate ups api credentials in XML format
  * @param string $api_key - api key generated by ups
  * @param string $id - Userid for UPS ACcount
  * @param string $password - Password for UPS Account
  * @return object $accessRequest - SimpleXMLElement of ups security credentials
  */
  function sc_ups_create_access_request_xml(){
    $accessRequest = new SimpleXMLElement('<AccessRequest></AccessRequest>');
    $accessRequest->addChild( 'AccessLicenseNumber', get_option( 'msp_ups_api_key' ) );
    $accessRequest->addChild( 'UserId', get_option( 'msp_ups_user_name' ) );
    $accessRequest->addChild( 'Password', get_option( 'msp_ups_password' ) );

    return $accessRequest;
  }
}

if( ! function_exists( 'sc_ups_create_tracking_request_xml' ) ){
  /**
  *
  * generate tracking api request for ups
  * @param string $tracking - the tracking # provided by ups
  * @return object $trackRequestXML - SimpleXMLElement of ups security credentials
  */
  function sc_ups_create_tracking_request_xml( $tracking ){
    $trackRequestXML = new SimpleXMLElement ( "<TrackRequest></TrackRequest>" );
  	$request = $trackRequestXML->addChild ( "Request" );
  	$request->addChild ( "RequestAction", "Track" );
  	$request->addChild ( "RequestOption", "activity" );
    $trackRequestXML->addChild( "TrackingNumber", $tracking );

    return $trackRequestXML;
  }
}

if( ! function_exists( 'sc_get_xml_by_curl' ) ){
  /**
  *
  * sets up the environment for an api call via cURL and converts results to array
  *
  * @param string $url - the url of the api we are cURL'ing
  * @param object $xml - SimpleXMLElement - an prebuilt xml file
  * @return array $array - the response of the api converted to an array
  */
  function sc_get_xml_by_curl( $url, $xml = '', $convert = true ){
    try{
        $ch = curl_init();
        if ($ch === false) {
          throw new Exception('failed to initialize');
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        // uncomment the next line if you get curl error 60: error setting certificate verify locations
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        // uncommenting the next line is most likely not necessary in case of error 60
        // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3600);

        $content = curl_exec($ch);

        // Check the return value of curl_exec(), too
        if ($content === false) {
            throw new Exception(curl_error($ch), curl_errno($ch));
        }

        if( $convert == true ){
          /* Process $content here */
          $xml = simplexml_load_string($content, "SimpleXMLElement", LIBXML_NOCDATA);
          $json = json_encode($xml);
          $content = json_decode($json,TRUE);
        }

        return $content;
        // Close curl handle

        curl_close($ch);
      } catch(Exception $e) {

      trigger_error(sprintf(
          'Curl failed with error #%d: %s',
          $e->getCode(), $e->getMessage()),
          E_USER_ERROR);
    }
  }
}
