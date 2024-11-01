<?php
/*
Plugin Name: TT Add to Cart Buy Now for WooCommerce
Plugin URI: http://terrytsang.com/product/tt-woocommerce-add-to-cart-buy-now/
Description: Customize the Add to cart button and add simple "Buy Now" button
Version: 1.0.0
Author: Terry Tsang
Author URI: https://terrytsang.com
*/

/*  Copyright 2012-2022 Terry Tsang (email: terrytsang811@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

// Define plugin name
define('wc_plugin_name_add_to_cart_buy_now', 'TT Add to Cart Buy Now for WooCommerce');

// Define plugin version
define('wc_version_add_to_cart_buy_now', '1.0.0');


// Checks if the WooCommerce plugins is installed and active.
if(in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))){
	if(!class_exists('WooCommerce_Add_To_Cart_Buy_Now')){
		class WooCommerce_Add_To_Cart_Buy_Now{

			public static $plugin_prefix;
			public static $plugin_url;
			public static $plugin_path;
			public static $plugin_basefile;

			/**
			 * Gets things started by adding an action to initialize this plugin once
			 * WooCommerce is known to be active and initialized
			 */
			public function __construct(){
				load_plugin_textdomain('wc-add-to-cart-buy-now', false, dirname(plugin_basename(__FILE__)) . '/languages/');
				
				WooCommerce_Add_To_Cart_Buy_Now::$plugin_prefix = 'wc_add_to_cart_buy_now_';
				WooCommerce_Add_To_Cart_Buy_Now::$plugin_basefile = plugin_basename(__FILE__);
				WooCommerce_Add_To_Cart_Buy_Now::$plugin_url = plugin_dir_url(WooCommerce_Add_To_Cart_Buy_Now::$plugin_basefile);
				WooCommerce_Add_To_Cart_Buy_Now::$plugin_path = trailingslashit(dirname(__FILE__));

				$this->buy_now_position = array('after' => 'After Add to Cart', 'percentage' => 'Before Add to Cart');

				$this->options_add_to_cart_buy_now = array(
					'tt_add_to_cart_enabled' => '',
					'tt_add_to_cart_button_text' => __( 'Add to cart', "tt-add-to-cart-buy-now-for-woocommerce" ),
					'tt_add_to_cart_url' => '',
					'tt_add_to_cart_icon_enabled' => '',
					'tt_buy_now_enabled' => '',
					'tt_buy_now_button_text' => __( 'Buy now', "tt-add-to-cart-buy-now-for-woocommerce" ),
					'tt_buy_now_button_position' => 'after',
					'tt_buy_now_url' => '',
					'tt_buy_now_icon_enabled' => '',
					'tt_skip_cart_checkout_enabled' => '',
				);
	
				$this->saved_options_add_to_cart_buy_now = array();

				add_action( 'wp_enqueue_scripts', array(&$this, 'tt_load_dashicons_frontend') );
				
				add_action('woocommerce_init', array(&$this, 'init'));
			}

			public function tt_load_dashicons_frontend() {
			  	wp_enqueue_style( 'dashicons' );
			}

			/**
			 * Initialize extension when WooCommerce is active
			 */
			public function init(){
				
				//add menu link for the plugin (backend)
				add_action( 'admin_menu', array( &$this, 'add_menu_add_to_cart_buy_now' ) );

				add_action( 'admin_enqueue_scripts', array( &$this, 'wc_addcart_buynow_admin_scripts' ) );
				
				if(get_option('tt_add_to_cart_enabled'))
				{
					add_filter( 'woocommerce_loop_add_to_cart_link', array( &$this, 'tt_loop_add_to_cart_button_text' ) );
					add_filter( 'woocommerce_product_single_add_to_cart_text', array( &$this, 'tt_add_to_cart_button_text' ) ) ;
				}

				if(get_option('tt_add_to_cart_icon_enabled'))
				{
					add_action( 'wp_enqueue_scripts', array( &$this, 'tt_add_icon_add_cart_button') );
				}

				if(get_option('tt_buy_now_enabled'))
				{
					if(get_option('tt_buy_now_button_position') == 'before') {
						add_action('woocommerce_after_add_to_cart_quantity', array( &$this, 'tt_add_buy_now_button') );
						add_action( 'woocommerce_after_shop_loop_item', array( &$this, 'tt_add_buy_now_button') , 5 );
					} else {
						add_action('woocommerce_after_add_to_cart_button', array( &$this, 'tt_add_buy_now_button') );
						add_action( 'woocommerce_after_shop_loop_item', array( &$this, 'tt_add_buy_now_button') , 20 );
					}
				}

				if(get_option('tt_buy_now_icon_enabled'))
				{
					add_action( 'wp_enqueue_scripts', array( &$this, 'tt_add_icon_buy_now_button') );
				}

				if(get_option('tt_skip_cart_checkout_enabled'))
				{
					add_filter('woocommerce_add_to_cart_redirect', array( $this, 'tt_skip_cart_redirect_to_checkout') );
				}

			}

			public function tt_add_icon_add_cart_button( $button ) {
			   wp_register_style( 'addtocart-css', plugins_url('/assets/css/addtocart.css', __FILE__) );
			   wp_enqueue_style( 'addtocart-css' );
			}

			public function tt_add_icon_buy_now_button( $button ) {
			   wp_register_style( 'buynow-css', plugins_url('/assets/css/buynow.css', __FILE__) );
			   wp_enqueue_style( 'buynow-css' );
			}

			public function tt_add_buy_now_button(){
				global $product;

		       	$current_product_id = get_the_ID();
 
				$product = wc_get_product( $current_product_id );
				 
				$checkout_url = get_option('tt_buy_now_url') ? trailingslashit(get_option('tt_buy_now_url')) : wc_get_checkout_url();

				if(get_option('tt_buy_now_button_text') != '') {
					$buy_now_button_text = get_option('tt_buy_now_button_text');
				} else {
					$buy_now_button_text = 'Buy now';
				}
				 
				if( $product->is_type( 'variable' ) ) {

					wc_enqueue_js( "
			         $( 'input.variation_id' ).change( function(){
			            if( '' != $(this).val() ) {
			        		var var_id = $(this).val();

			        		var qty_no = $('input.qty').val();

			        		var existingBuyNow = document.getElementById('buy-now-variation');
			        		if(existingBuyNow){
						  		existingBuyNow.remove();
			        		}

						  	document.getElementById('main').getElementsByClassName('quantity')[0].innerHTML += '<a href=\"$checkout_url?quantity='+qty_no+'&add-to-cart='+var_id+'\" id=\"buy-now-variation\" class=\"button\">$buy_now_button_text</a>';

			            } else {
			            	var existingBuyNow = document.getElementById('buy-now-variation');
			        		if(existingBuyNow)
						  		existingBuyNow.remove();
			            }
			         });

			         $( '.woocommerce-variation-add-to-cart input.qty' ).change( function(){
			         	var var_id = $( 'input.variation_id' ).val();

		         		var qty_no = $(this).parent( '.quantity' ).find( '.qty' ).val();

		         		var existingBuyNow = document.getElementById('buy-now-variation');
		        		if(existingBuyNow){
					  		existingBuyNow.remove();
		        		}

					  	document.getElementById('main').getElementsByClassName('quantity')[0].innerHTML += '<a href=\"$checkout_url?quantity='+qty_no+'&add-to-cart='+var_id+'\" id=\"buy-now-variation\" class=\"button\">$buy_now_button_text</a>';
			         });

			         $( 'a.reset_variations' ).click( function(){
			            var existingBuyNow = document.getElementById('buy-now-variation');
		        		if(existingBuyNow)
					  		existingBuyNow.remove();
			         });


			      " );

				} 

				if( $product->is_type( 'simple' ) ) {
					echo '&nbsp;<a href="'. esc_url($checkout_url) .'?add-to-cart='. esc_attr($current_product_id) .'" class="buy-now button">'. esc_attr($buy_now_button_text) .'</a>&nbsp;';
				}
		    }

			public function tt_loop_add_to_cart_button_text( $add_to_cart_html ) {
				$add_to_cart_button_text	= get_option( 'tt_add_to_cart_button_text' ) ? get_option( 'tt_add_to_cart_button_text' ) : __( 'Add to cart', "tt-add-to-cart-buy-now-for-woocommerce" );

				return str_replace( 'Add to cart', $add_to_cart_button_text, $add_to_cart_html );
			}

			public function tt_add_to_cart_button_text( $product ){
				$add_to_cart_button_text	= get_option( 'tt_add_to_cart_button_text' ) ? get_option( 'tt_add_to_cart_button_text' ) : __( 'Add to cart', "tt-add-to-cart-buy-now-for-woocommerce" );

				return $add_to_cart_button_text;
			}
			
			public function tt_skip_cart_redirect_to_checkout( $url ) {
				return wc_get_checkout_url();
			}
			
			public function wc_addcart_buynow_admin_scripts($hook){
				/* Register admin stylesheet. */
				wp_register_style( 'admin-css', plugins_url('/assets/css/admin.css', __FILE__) );
				
				wp_enqueue_style( 'admin-css' );
			}
			
			/**
			 * Add a menu link to the woocommerce section menu
			 */
			function add_menu_add_to_cart_buy_now() {
				$wc_page = 'woocommerce';
				$addcart_buynow_settings_page = add_submenu_page( $wc_page , __( 'TT Add to Cart Buy Now', "tt-add-to-cart-buy-now-for-woocommerce" ), __( 'TT Add to Cart Buy Now', "tt-add-to-cart-buy-now-for-woocommerce" ), 'manage_options', 'wc-add-to-cart-buy-now', array(
						&$this,
						'settings_page_add_to_cart_buy_now'
				));
			}
			
			/**
			 * Create the settings page content
			 */
			public function settings_page_add_to_cart_buy_now() {
			
				// If form was submitted
				if ( isset( $_POST['submitted'] ) )
				{
					check_admin_referer( "tt-add-to-cart-buy-now-for-woocommerce" );
	
					$this->saved_options_add_to_cart_buy_now['tt_add_to_cart_enabled'] = ! isset( $_POST['tt_add_to_cart_enabled'] ) ? '1' : sanitize_text_field( $_POST['tt_add_to_cart_enabled'] );
					$this->saved_options_add_to_cart_buy_now['tt_add_to_cart_button_text'] = ! isset( $_POST['tt_add_to_cart_button_text'] ) ? 'Add to cart' : sanitize_text_field( $_POST['tt_add_to_cart_button_text'] );
					$this->saved_options_add_to_cart_buy_now['tt_add_to_cart_url'] = ! isset( $_POST['tt_add_to_cart_url'] ) ? '' : sanitize_text_field( $_POST['tt_add_to_cart_url'] );
					$this->saved_options_add_to_cart_buy_now['tt_add_to_cart_icon_enabled'] = ! isset( $_POST['tt_add_to_cart_icon_enabled'] ) ? '0' : sanitize_text_field( $_POST['tt_add_to_cart_icon_enabled'] );


					$this->saved_options_add_to_cart_buy_now['tt_buy_now_enabled'] = ! isset( $_POST['tt_buy_now_enabled'] ) ? '1' : sanitize_text_field( $_POST['tt_buy_now_enabled'] );
					$this->saved_options_add_to_cart_buy_now['tt_buy_now_button_text'] = ! isset( $_POST['tt_buy_now_button_text'] ) ? 'Add to cart' : sanitize_text_field( $_POST['tt_buy_now_button_text'] );
					$this->saved_options_add_to_cart_buy_now['tt_buy_now_url'] = ! isset( $_POST['tt_buy_now_url'] ) ? '' : sanitize_text_field( $_POST['tt_buy_now_url'] );
					$this->saved_options_add_to_cart_buy_now['tt_buy_now_button_position'] = ! isset( $_POST['tt_buy_now_button_position'] ) ? 'after' : sanitize_text_field( $_POST['tt_buy_now_button_position'] );
					$this->saved_options_add_to_cart_buy_now['tt_buy_now_icon_enabled'] = ! isset( $_POST['tt_buy_now_icon_enabled'] ) ? '0' : sanitize_text_field( $_POST['tt_buy_now_icon_enabled'] );

					$this->saved_options_add_to_cart_buy_now['tt_skip_cart_checkout_enabled'] = ! isset( $_POST['tt_skip_cart_checkout_enabled'] ) ? '1' : sanitize_text_field( $_POST['tt_skip_cart_checkout_enabled'] );
					

					foreach($this->options_add_to_cart_buy_now as $field => $value)
					{
						$option_add_to_cart_buy_now = get_option( $field );
			
						if($option_add_to_cart_buy_now != $this->saved_options_add_to_cart_buy_now[$field])
							update_option( $field, $this->saved_options_add_to_cart_buy_now[$field] );
					}
						
					// Show message
					echo '<div id="message" class="updated fade"><p>' . __( 'WooCommerce Add to Cart Buy Now options saved.', "tt-add-to-cart-buy-now-for-woocommerce" ) . '</p></div>';
				}

				$checked_enabled_addtocart = '';
				$checked_enabled_addtocart_icon = '';
				$checked_enabled_buynow	= '';
				$checked_enabled_buynow_icon	= '';
				$checked_enabled_skip_cart_checkout	= '';

				$tt_add_to_cart_enabled		= get_option( 'tt_add_to_cart_enabled' );
				$tt_add_to_cart_icon_enabled = get_option( 'tt_add_to_cart_icon_enabled' );
				$tt_add_to_cart_button_text	= get_option( 'tt_add_to_cart_button_text' ) ? get_option( 'tt_add_to_cart_button_text' ) : __('Add to cart', 'woocommerce-add-to-cart-buy-now');
				$tt_add_to_cart_url= get_option( 'tt_add_to_cart_url' ) ? get_option( 'tt_add_to_cart_url' ) : '';

				$tt_buy_now_enabled		= get_option( 'tt_buy_now_enabled' );
				$tt_buy_now_icon_enabled = get_option( 'tt_buy_now_icon_enabled' );
				$tt_skip_cart_checkout_enabled	= get_option( 'tt_skip_cart_checkout_enabled' );
				$tt_buy_now_button_position = get_option( 'tt_buy_now_button_position' );
				$tt_buy_now_button_text	= get_option( 'tt_buy_now_button_text' ) ? get_option( 'tt_buy_now_button_text' ) : __('Buy now', 'woocommerce-add-to-cart-buy-now');
				$tt_buy_now_url= get_option( 'tt_buy_now_url' ) ? get_option( 'tt_buy_now_url' ) : '';

				if($tt_add_to_cart_enabled)
					$checked_enabled_addtocart = 'checked="checked"';

				if($tt_add_to_cart_icon_enabled)
					$checked_enabled_addtocart_icon = 'checked="checked"';

				if($tt_buy_now_enabled)
					$checked_enabled_buynow = 'checked="checked"';

				if($tt_buy_now_icon_enabled)
					$checked_enabled_buynow_icon = 'checked="checked"';

				if($tt_skip_cart_checkout_enabled)
					$checked_enabled_skip_cart_checkout = 'checked="checked"';
			
				$actionurl = sanitize_url( $_SERVER['REQUEST_URI'] );
				$nonce = wp_create_nonce( "tt-add-to-cart-buy-now-for-woocommerce" );
			
			
				// Configuration Page
			
				?>
				<div id="icon-options-general" class="icon32"></div>
				<h3><?php _e( 'TT Add to Cart Buy Now', "tt-add-to-cart-buy-now-for-woocommerce"); ?></h3>

						<form action="<?php echo esc_url( $actionurl ); ?>" method="post">
						<table>
								<tbody>
									<tr>
										<td colspan="2">
											<table class="widefat fixed" cellspacing="2" cellpadding="2" border="0">
												<tr>
													<td width="300"><b><?php _e( 'Enable Custom Add to Cart Button', "tt-add-to-cart-buy-now-for-woocommerce" ); ?></b></td>
													<td>
														<input class="checkbox" name="tt_add_to_cart_enabled" id="tt_add_to_cart_enabled" value="0" type="hidden">
														<input class="checkbox" name="tt_add_to_cart_enabled" id="tt_add_to_cart_enabled" value="1" <?php echo esc_attr( $checked_enabled_addtocart ); ?> type="checkbox">
													</td>
												</tr>
	
												<tr>
													<td><?php _e( 'Custom "Add to cart" Button Text', "tt-add-to-cart-buy-now-for-woocommerce" ); ?></td>
													<td>
														<input type="text" id="tt_add_to_cart_button_text" name="tt_add_to_cart_button_text" value="<?php echo esc_attr( $tt_add_to_cart_button_text ); ?>" placeholder="Add to cart" size="26" />
													</td>
												</tr>

												<tr>
													<td><?php _e( 'Custom "Add to cart" Page URL', "tt-add-to-cart-buy-now-for-woocommerce" ); ?></td>
													<td>
														<input type="text" id="tt_add_to_cart_url" name="tt_add_to_cart_url" value="<?php echo esc_attr( $tt_add_to_cart_url ); ?>" placeholder="https://yourwebsite.com/cart/" size="40" />
													</td>
												</tr>

												<tr>
													<td width="300"><?php _e( 'Enabled Add to Cart Icon', "tt-add-to-cart-buy-now-for-woocommerce" ); ?></span></td>
													<td>
														<input class="checkbox" name="tt_add_to_cart_icon_enabled" id="tt_add_to_cart_icon_enabled" value="0" type="hidden">
														<input class="checkbox" name="tt_add_to_cart_icon_enabled" id="tt_add_to_cart_icon_enabled" value="1" <?php echo esc_attr( $checked_enabled_addtocart_icon); ?> type="checkbox">
														&nbsp;<span class="dashicons dashicons-cart">
													</td>
												</tr>

												<tr>
													<td width="300"><?php _e( 'Skip Cart and Redirect to Checkout', "tt-add-to-cart-buy-now-for-woocommerce" ); ?></td>
													<td>
														<input class="checkbox" name="tt_skip_cart_checkout_enabled" id="tt_skip_cart_checkout_enabled" value="0" type="hidden">
														<input class="checkbox" name="tt_skip_cart_checkout_enabled" id="tt_skip_cart_checkout_enabled" value="1" <?php echo esc_attr( $checked_enabled_skip_cart_checkout); ?> type="checkbox">
													</td>
												</tr>

												<tr>
													<td><hr /></td>
												</tr>

												<tr>
													<td width="300"><b><?php _e( 'Enable Custom Buy Now Button', "tt-add-to-cart-buy-now-for-woocommerce" ); ?></b></td>
													<td>
														<input class="checkbox" name="tt_buy_now_enabled" id="tt_buy_now_enabled" value="0" type="hidden">
														<input class="checkbox" name="tt_buy_now_enabled" id="tt_buy_now_enabled" value="1" <?php echo esc_attr( $checked_enabled_buynow); ?> type="checkbox">
													</td>
												</tr>
	
												<tr>
													<td><?php _e( 'Custom "Buy Now" Button Text', "tt-add-to-cart-buy-now-for-woocommerce" ); ?></td>
													<td>
														<input type="text" id="tt_buy_now_button_text" name="tt_buy_now_button_text" value="<?php echo esc_attr( $tt_buy_now_button_text ); ?>" placeholder="Buy now" size="26" />
													</td>
												</tr>

												<tr>
													<td><?php _e( 'Custom "Buy Now" Page URL', "tt-add-to-cart-buy-now-for-woocommerce" ); ?></td>
													<td>
														<input type="text" id="tt_buy_now_url" name="tt_buy_now_url" value="<?php echo esc_attr( $tt_buy_now_url ); ?>" placeholder="https://yourwebsite.com/checkout/" size="40" />
													</td>
												</tr>

												<tr>
													<td><?php _e( '"Buy Now" Button Position', "tt-add-to-cart-buy-now-for-woocommerce" ); ?></td>
													<td>
														<select name="tt_buy_now_button_position">
															<option value="after" <?php if($tt_buy_now_button_position == 'after') { echo 'selected="selected"'; } ?>><?php _e( 'After Add to Cart Button', "tt-add-to-cart-buy-now-for-woocommerce" ); ?></option>
															<option value="before" <?php if($tt_buy_now_button_position
 == 'before') { echo 'selected="selected"'; } ?>><?php _e( 'Before Add to Cart Button', "tt-add-to-cart-buy-now-for-woocommerce" ); ?></option>
														</select>
													</td>
												</tr>

												<tr>
													<td width="300"><?php _e( 'Enabled Buy Now Icon', "tt-add-to-cart-buy-now-for-woocommerce" ); ?></td>
													<td>
														<input class="checkbox" name="tt_buy_now_icon_enabled" id="tt_buy_now_icon_enabled" value="0" type="hidden">
														<input class="checkbox" name="tt_buy_now_icon_enabled" id="tt_buy_now_icon_enabled" value="1" <?php echo esc_attr( $checked_enabled_buynow_icon); ?> type="checkbox">
														&nbsp;<span class="dashicons dashicons-arrow-right-alt"></span>
													</td>
												</tr>

												<tr><td><hr /></td></tr>

												
												<tr>
													<td><?php _e( 'Replace Add to Cart with View Product (Shop)', "tt-add-to-cart-buy-now-for-woocommerce" ); ?> <small><small style="color:#cccccc">(PRO)</small></small></td>
													<td>
														<input type="text" id="add_to_cart_buy_now_yourorder" name="add_to_cart_buy_now_yourorder" value="" placeholder="PRO VERSION" size="26" disabled />
													</td>
												</tr>

												<tr>
													<td><?php _e( 'Product-Based Settings (Add to Cart)', "tt-add-to-cart-buy-now-for-woocommerce" ); ?> <small><small style="color:#cccccc">(PRO)</small></small></td>
													<td>
														<input type="text" id="add_to_cart_product" name="add_to_cart_product" value="" placeholder="PRO VERSION" size="26" disabled />
													</td>
												</tr>

												<tr>
													<td><?php _e( 'Product-Based Settings (Buy Now)', "tt-add-to-cart-buy-now-for-woocommerce" ); ?> <small><small style="color:#cccccc">(PRO)</small></small></td>
													<td>
														<input type="text" id="buy_now_product" name="buy_now_product" value="" placeholder="PRO VERSION" size="26" disabled />
													</td>
												</tr>
									
											</table>
										</td>
									</tr>
									<tr><td>&nbsp;</td></tr>
									<tr>
										<td colspan=2">
											<input class="button-primary" type="submit" name="Save" value="<?php _e('Save Options', "tt-add-to-cart-buy-now-for-woocommerce"); ?>" id="submitbutton" />
											<input type="hidden" name="submitted" value="1" /> 
											<input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo esc_attr( $nonce ); ?>" />
										</td>
									</tr>
								</tbody>
						</table>
						</form>

						


					<br />
				<hr />
				<div style="height:30px"></div>
				<div class="center woocommerce-BlankState">
					<p><img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ); ?>logo-terrytsang.png" title="Terry Tsang" alt="Terry Tsang" /></p>
					<h2 class="woocommerce-BlankState-message">Hi, I'm <a href="https://terrytsang.com" target="_blank">Terry Tsang</a> from 3 Mini Monsters. I have built WooCommerce plugins since 10 years ago and always find ways to make WooCommerce experience better through my products and articles. Thanks for using my plugin and do share around if you love this.</h2>	

				</div>

				<div class="center woocommerce-BlankState" style="border:1px solid #ccc; padding: 20px; margin: 20px;">

					<div style="display: block; float: left; width: 35%; height: 150px;">
						<a class="woocommerce-BlankState-cta button-primary button" href="https://terrytsang.com/product/tt-woocommerce-add-to-cart-buy-now-pro" target="_blank">Upgrade to TT Add to Cart Buy Now PRO</a>
					</div>

					<div style="padding: 0 10px;">
						<h3>PRO version will have below additional features and functionalities:</h3>
						<ul style="list-style:square;">	
							<li>Replace "Add to cart" button with "View product" button at Shop page</li>
							<li>Product-based options for Add to Cart button - Custom Button Text and Quantity Suffix</li>
							<li>Product-based options for Buy Now button - Custom Button Text and Redirect Page URL</li>
						</ul>
					</div>

				</div>

				<br /><br /><br />

				<div class="components-card is-size-medium woocommerce-marketing-recommended-extensions-card woocommerce-marketing-recommended-extensions-card__category-coupons woocommerce-admin-marketing-card">
					<div class="components-flex components-card__header is-size-medium"><div>
						<span class="components-truncate components-text"></span>
						<div style="margin: 20px 20px">Try my other plugins to power up your online store and bring more sales/leads to you.</div>
					</div>
				</div>

				<div class="components-card__body is-size-medium">
					<div class="woocommerce-marketing-recommended-extensions-card__items woocommerce-marketing-recommended-extensions-card__items--count-6">
						<a href="https://terrytsang.com/product/tt-woocommerce-discount-option-pro/" target="_blank" class="woocommerce-marketing-recommended-extensions-item">
							<div class="woocommerce-admin-marketing-product-icon">
								<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ); ?>assets/images/icon-woocommerce-custom-checkout.png" title="WooCommerce Discount Option" alt="WooCommerce Discount Option" width="50" height="50" />
							</div>
							<div class="woocommerce-marketing-recommended-extensions-item__text">
								<h4>TT Discount Option</h4>
								<p style="color:#333333;">Add a fixed fee/percentage discount based on minimum order amount, products, categories, date range and day.</p>
							</div>
						</a>

						<a href="https://terrytsang.com/product/tt-woocommerce-donation-checkout-pro/" target="_blank" class="woocommerce-marketing-recommended-extensions-item">
							<div class="woocommerce-admin-marketing-product-icon">
								<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ); ?>assets/images/icon-woocommerce-donation-checkout.png" title="WooCommerce Donation Checkout" alt="WooCommerce Donation Checkout" width="50" height="50" />
							</div>
							<div class="woocommerce-marketing-recommended-extensions-item__text">
								<h4>TT Donation Checkout</h4>
								<p style="color:#333333;">Enable customers to topup their donation/tips at the checkout page.</p>
							</div>
						</a>

						<a href="https://terrytsang.com/product/tt-woocommerce-extra-fee-option-pro/" target="_blank" class="woocommerce-marketing-recommended-extensions-item">
							<div class="woocommerce-admin-marketing-product-icon">
								<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ); ?>assets/images/icon-woocommerce-custom-checkout.png" title="WooCommerce Extra Fee Options" alt="WooCommerce Extra Fee Options" width="50" height="50" />
							</div>
							<div class="woocommerce-marketing-recommended-extensions-item__text">
								<h4>TT Extra Fee Option</h4>
								<p style="color:#333333;">Add a discount based on minimum order amount, product categories, products and date range.</p>
							</div>
						</a>

						<a href="https://terrytsang.com/product/tt-woocommerce-one-page-checkout-pro/" target="_blank" class="woocommerce-marketing-recommended-extensions-item">
							<div class="woocommerce-admin-marketing-product-icon">
								<img src="<?php echo plugin_dir_url( __FILE__ ) ?>assets/images/icon-woocommerce-onepage-checkout.png" title="WooCommerce OnePage Checkout" alt="WooCommerce OnePage Checkout" width="50" height="50" />
							</div>
							<div class="woocommerce-marketing-recommended-extensions-item__text">
								<h4>TT One-Page Checkout</h4>
								<p style="color:#333333;">Combine cart and checkout at one page to simplify entire WooCommerce checkout process.</p>
							</div>
						</a>

						<!-- <a href="https://terrytsang.com/product/tt-woocommerce-coming-soon/" target="_blank" class="woocommerce-marketing-recommended-extensions-item">
							<div class="woocommerce-admin-marketing-product-icon">
								<img src="<?php //echo plugin_dir_url( __FILE__ ) ?>assets/images/icon-woocommerce-coming-soon-product.png" title="WooCommerce Coming Soon Product" alt="WooCommerce Coming Soon Product" width="50" height="50" />
							</div>
							<div class="woocommerce-marketing-recommended-extensions-item__text">
								<h4>TT Coming Soon</h4>
								<p style="color:#333333;">Display countdown clock at coming-soon product page.</p>
							</div>
						</a> -->

						<!-- <a href="https://terrytsang.com/product/tt-woocommerce-product-badge/" target="_blank" class="woocommerce-marketing-recommended-extensions-item">
							<div class="woocommerce-admin-marketing-product-icon">
								<img src="<?php //echo plugin_dir_url( __FILE__ ) ?>assets/images/icon-woocommerce-product-badge.png" title="WooCommerce Product Badge" alt="WooCommerce Product Badge" width="50" height="50" />
							</div>
							<div class="woocommerce-marketing-recommended-extensions-item__text">
								<h4>TT Product Badge</h4>
								<p style="color:#333333;">Add product badges liked Popular, Sales, Featured to the product.</p>
							</div>
						</a> -->

						<!-- <a href="https://terrytsang.com/product/tt-woocommerce-product-catalog/" target="_blank" class="woocommerce-marketing-recommended-extensions-item">
							<div class="woocommerce-admin-marketing-product-icon">
								<img src="<?php //echo plugin_dir_url( __FILE__ ) ?>assets/images/icon-woocommerce-product-catalog.png" title="WooCommerce Product Catalog" alt="WooCommerce Product Catalog" width="50" height="50" />
							</div>
							<div class="woocommerce-marketing-recommended-extensions-item__text">
								<h4>TT Product Catalog</h4>
								<p style="color:#333333;">Hide Add to Cart / Checkout button and turn your website into product catalog.</p>
							</div>
						</a> -->

					
					</div>
				</div>
					

				
			<?php
			}
			
			function update_checkout(){
			?>
				<script>
				jQuery(document).ready(function($){
				$('.payment_methods input.input-radio').live('click', function(){
					$('#billing_country, #shipping_country, .country_to_state').trigger('change');
				})
				});
				</script>
			<?php
			}
			
			/**
			 * Get the setting options
			 */
			function get_options() {
				
				foreach($this->options_add_to_cart_buy_now as $field => $value)
				{
					$array_options[$field] = get_option( $field );
				}
					
				return $array_options;
			}
			
			
		}//end class
			
	}//if class does not exist
	
	$woocommerce_add_to_cart_buy_now = new WooCommerce_Add_To_Cart_Buy_Now();
}
else{
	add_action('admin_notices', 'wc_add_to_cart_buy_now_error_notice');
	function wc_add_to_cart_buy_now_error_notice(){
		global $current_screen;
		if($current_screen->parent_base == 'plugins'){
			echo '<div class="error"><p>'.__(wc_plugin_name_add_to_cart_buy_now.' requires <a href="http://www.woocommerce.com/" target="_blank">WooCommerce</a> to be activated in order to work. Please install and activate <a href="'.admin_url('plugin-install.php?tab=search&type=term&s=WooCommerce').'" target="_blank">WooCommerce</a> first.').'</p></div>';
		}
	}
}

?>