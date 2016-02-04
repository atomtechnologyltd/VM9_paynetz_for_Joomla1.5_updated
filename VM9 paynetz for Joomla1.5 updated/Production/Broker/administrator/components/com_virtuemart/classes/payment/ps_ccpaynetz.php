<?php
if( !defined( '_VALID_MOS' ) && !defined( '_JEXEC' ) ) die( 'Direct Access to '.basename(__FILE__).' is not allowed.' );

/**
*
* The ps_ccpaynetz class, containing the default payment processing code
* for Atom Paynetz payment gateway for Credi/Debit Card
* @author Ashish Solanki
*
*/
class ps_ccpaynetz {
    var $classname = "ps_ccpaynetz";
      
    /**
    * Show all configuration parameters for this payment method
    * @returns boolean False when the Payment method has no configration
    */
    function show_configuration() {
        
		global $VM_LANG;
       
		/** Read current Configuration ***/
		require_once(CLASSPATH ."payment/".$this->classname.".cfg.php");
        ?>
        <table>
          <tr>
          <td><strong><?php echo "Paynetz Login Id" ?></strong></td>
              <td>
                  <input type="text" name="PAYNETZ_CC_LOGIN_ID" class="inputbox" value="<?php echo PAYNETZ_CC_LOGIN_ID ?>" />
              </td>
              <td><?php echo "Provided by ATOM" ?></td>
          </tr> 
		   <tr>
          <td><strong><?php echo "Paynetz Password" ?></strong></td>
              <td>
                  <input type="text" name="PAYNETZ_CC_PASSWORD" class="inputbox" value="<?php echo PAYNETZ_CC_PASSWORD ?>" />
              </td>
              <td><?php echo "Provided by ATOM" ?></td>
          </tr>
		   <tr>
          <td><strong><?php echo "Product Id" ?></strong></td>
              <td>
                  <input type="text" name="PAYNETZ_CC_PRODUCT_ID" class="inputbox" value="<?php echo PAYNETZ_CC_PRODUCT_ID ?>" />
              </td>
              <td><?php echo "As you wants" ?></td>
          </tr>
		    <tr>
                <td colaspan="3">
                  <input type="hidden" name="PAYNETZ_TRANSACTION_URL" value="https://payment.atomtech.in/paynetz/epi/fts" />
				  <input type="hidden" name="PAYNETZ_TRAN_AMOUNT" value="0" />
				  <input type="hidden" name="PAYNETZ_TRAN_CURR" value="INR" />
				  <input type="hidden" name="PAYNETZ_CLIENT_CODE" value="Virtuemart" />
				  <input type="hidden" name="PAYNETZ_CUST_ACCOUNT_NO" value="0253143165113216" />
				  <input type="hidden" name="PAYNETZ_CC_TTYPE" value="CCFundTransfer" />
              </td>              
          </tr>
        </table>
        <?php
    }
    
    function has_configuration() {
      // return false if there's no configuration
      return true;
   }
   
  /**
	* Returns the "is_writeable" status of the configuration file
	* @param void
	* @returns boolean True when the configuration file is writeable, false when not
	*/
   function configfile_writeable() {
      return is_writeable( CLASSPATH."payment/".$this->classname.".cfg.php" );
   }
   
  /**
	* Returns the "is_readable" status of the configuration file
	* @param void
	* @returns boolean True when the configuration file is writeable, false when not
	*/
   function configfile_readable() {
      return is_readable( CLASSPATH."payment/".$this->classname.".cfg.php" );
   }
   
  /**
	* Writes the configuration file for this payment method
	* @param array An array of objects
	* @returns boolean True when writing was successful
	*/
   function write_configuration( &$d ) {
      
      $my_config_array = array("PAYNETZ_CC_LOGIN_ID" => $d['PAYNETZ_CC_LOGIN_ID']
		  ,"PAYNETZ_CC_PASSWORD" => $d['PAYNETZ_CC_PASSWORD']
		  ,"PAYNETZ_CC_PRODUCT_ID" => $d['PAYNETZ_CC_PRODUCT_ID']
		  ,"PAYNETZ_TRANSACTION_URL" => $d['PAYNETZ_TRANSACTION_URL']
		  ,"PAYNETZ_TRAN_AMOUNT" => $d['PAYNETZ_TRAN_AMOUNT']
		  ,"PAYNETZ_TRAN_CURR" => $d['PAYNETZ_TRAN_CURR']
		  ,"PAYNETZ_CLIENT_CODE" => $d['PAYNETZ_CLIENT_CODE']
		  ,"PAYNETZ_CUST_ACCOUNT_NO" => $d['PAYNETZ_CUST_ACCOUNT_NO']
		  ,"PAYNETZ_CC_TTYPE" => $d['PAYNETZ_CC_TTYPE']
           );
      $config = "<?php\n";
      $config .= "if( !defined( '_VALID_MOS' ) && !defined( '_JEXEC' ) ) die( 'Direct Access to '.basename(__FILE__).' is not allowed.' ); \n\n";
      foreach( $my_config_array as $key => $value ) {
        $config .= "define ('$key', '$value');\n";
      }
      
      $config .= "?>";
  
      if ($fp = fopen(CLASSPATH ."payment/".$this->classname.".cfg.php", "w")) {
          fputs($fp, $config, strlen($config));
          fclose ($fp);
          return true;
     }
     else
        return false;
   }
   
  /**************************************************************************
  ** name: process_payment()
  ***************************************************************************/
   function process_payment($order_number, $order_total, &$d) {
	   require_once( CLASSPATH."payment/".$this->classname.".cfg.php" );
	   require_once(dirname(__FILE__).'/paynetz_api/CallerService.php');
	   $param = array(
		   "login_id" => PAYNETZ_CC_LOGIN_ID
		   ,"password" => PAYNETZ_CC_PASSWORD
		   ,"prod_id" => PAYNETZ_CC_PRODUCT_ID
		   ,"ttype" => PAYNETZ_CC_TTYPE
		   ,"ordernum" => urlencode(substr($order_number, 0, 20))
		   ,"amount" =>   $order_total
		   ,"curr" => PAYNETZ_TRAN_CURR
		   ,"txnamt" => PAYNETZ_TRAN_AMOUNT
		   ,"client_code" => PAYNETZ_CLIENT_CODE
		   ,"customer_acc_no" => PAYNETZ_CUST_ACCOUNT_NO
		   ,"paynetz_url" => PAYNETZ_TRANSACTION_URL
		);

		$this->updateRecords( $order_number, $order_total, $d );
		requestMerchant($param);
		exit;
   }


	/**
	 * This is the main function which stores the order information in the database
	 * 
	 * @author Ashish Solanki!
	 * @return boolean
	 */
	function updateRecords($order_number, $order_total, &$d ) {
		require_once(CLASSPATH. 'ps_checkout.php' );
		$ps_chkout = new ps_checkout;
		global $order_tax_details, $afid, $VM_LANG, $auth, $my, $mosConfig_offset,
		$vmLogger, $vmInputFilter, $discount_factor;
		$ps_vendor_id = $_SESSION["ps_vendor_id"];
		$cart = $_SESSION['cart'];
		require_once(CLASSPATH. 'ps_payment_method.php' );
		$ps_payment_method = new ps_payment_method;
		require_once(CLASSPATH. 'ps_product.php' );
		$ps_product= new ps_product;
		require_once(CLASSPATH.'ps_cart.php');
		$ps_cart = new ps_cart;
		$db = new ps_DB;
		$totals = $ps_chkout->calc_order_totals( $d );
		extract( $totals );
		$timestamp = time();
		$vmLogger->debug( '-- Checkout Debug--
						Subtotal: '.$order_subtotal.'
						Taxable: '.$order_taxable.'
						Payment Discount: '.$payment_discount.'
						Coupon Discount: '.$coupon_discount.'
						Shipping: '.$order_shipping.'
						Shipping Tax : '.$order_shipping_tax.'
						Tax : '.$order_tax.'
						------------------------
						Order Total: '.$order_total.'
						----------------------------' 
						);
		
		// Check to see if Payment Class File exists	
		$payment_class = $ps_payment_method->get_field($d["payment_method_id"], "payment_class");
		$d['new_order_status'] = 'P'; // This is meant to be updated by a payment modules' process_payment method
		if( !class_exists( $payment_class )) {
			include( CLASSPATH. "payment/$payment_class.php" );
		}
		$_PAYMENT = new $payment_class();

		// Remove the Coupon, because it is a Gift Coupon and now is used!!
		if( @$_SESSION['coupon_type'] == "gift" ) {
			$d['coupon_id'] = $_SESSION['coupon_id'];
			include_once( CLASSPATH.'ps_coupon.php' );
			ps_coupon::remove_coupon_code( $d );
		}
		
		// Get the IP Address
		if (!empty($_SERVER['REMOTE_ADDR'])) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		else {
			$ip = 'unknown';
		}
		
		// Collect all fields and values to store them!
		$fields = array(
			'user_id' => $auth["user_id"], 
			'vendor_id' => $ps_vendor_id, 
			'order_number' => $order_number, 
			'user_info_id' =>  $d["ship_to_info_id"], 
			'ship_method_id' => @urldecode($d["shipping_rate_id"]),
			'order_total' => $order_total, 
			'order_subtotal' => $order_subtotal, 
			'order_tax' => $order_tax, 
			'order_tax_details' => serialize($order_tax_details), 
			'order_shipping' => $order_shipping,
			'order_shipping_tax' => $order_shipping_tax, 
			'order_discount' => $payment_discount, 
			'coupon_discount' => $coupon_discount,
			'coupon_code' => @$_SESSION['coupon_code'],
			'order_currency' => $GLOBALS['product_currency'], 
			'order_status' => 'P', 
			'cdate' => $timestamp,
			'mdate' => $timestamp,
			'customer_note' => htmlspecialchars(vmRequest::getString('customer_note','', 'POST', 'none' ), ENT_QUOTES ),
			'ip_address' => $ip
			);

		// Insert the main order information
		$db->buildQuery( 'INSERT', '#__{vm}_orders', $fields );
		$result = $db->query();
		$d["order_id"] = $order_id = $db->last_insert_id();
		if( $result === false || empty( $order_id )) {
			$vmLogger->crit( 'Adding the Order into the Database failed! User ID: '.$auth["user_id"] );
			return false;
		}

		// Insert the initial Order History.	    
		$mysqlDatetime = date("Y-m-d G:i:s", $timestamp);
		$fields = array(
					'order_id' => $order_id,
					'order_status_code' => 'P',
					'date_added' => $mysqlDatetime,
					'customer_notified' => 1,
					'comments' => ''
				  );
		$db->buildQuery( 'INSERT', '#__{vm}_order_history', $fields );
		$db->query();
		
		/**
	    * Insert the Order payment info 
	    */
		$payment_number = str_replace(array(' ','|','-'), '', @$_SESSION['ccdata']['order_payment_number']);
		$d["order_payment_code"] = @$_SESSION['ccdata']['credit_card_code'];
		
		// Payment number is encrypted using mySQL encryption functions.
		$fields = array(
					'order_id' => $order_id, 
					'payment_method_id' => $d["payment_method_id"], 
					'order_payment_log' => @$d["order_payment_log"], 
					'order_payment_trans_id' => $vmInputFilter->safeSQL( @$d["order_payment_trans_id"] )
				  );
		if( !empty( $payment_number ) && VM_STORE_CREDITCARD_DATA == '1' ) {
			// Store Credit Card Information only if the Store Owner has decided to do so
			$fields['order_payment_code'] = $d["order_payment_code"];
			$fields['order_payment_expire'] = @$_SESSION["ccdata"]["order_payment_expire"];
			$fields['order_payment_name'] = @$_SESSION["ccdata"]["order_payment_name"];
			$fields['order_payment_number'] = VM_ENCRYPT_FUNCTION."( '$payment_number','" . ENCODE_KEY . "')";
			$specialfield = array('order_payment_number');
		} else {
			$specialfield = array();
		}
		$db->buildQuery( 'INSERT', '#__{vm}_order_payment', $fields, '', $specialfield );
		$db->query();

		/**
		* Insert the User Billto & Shipto Info
		*/
		// First: get all the fields from the user field list to copy them from user_info into the order_user_info
		$fields = array();
		require_once( CLASSPATH . 'ps_userfield.php' );
		$userfields = ps_userfield::getUserFields('', false, '', true, true );
		foreach ( $userfields as $field ) {
    		if ($field->name=='email') $fields[] = 'user_email'; 
    		else $fields[] = $field->name;			
		}
		$fieldstr = implode( ',', $fields );
		
		// Save current Bill To Address
		$q = "INSERT INTO `#__{vm}_order_user_info` 
			(`order_info_id`,`order_id`,`user_id`,address_type, ".$fieldstr.") ";
		$q .= "SELECT NULL, '$order_id', '".$auth['user_id']."', address_type, ".$fieldstr." FROM #__{vm}_user_info WHERE user_id='".$auth['user_id']."' AND address_type='BT'";
		$db->query( $q );

		// Save current Ship to Address if applicable
		$q = "INSERT INTO `#__{vm}_order_user_info` 
			(`order_info_id`,`order_id`,`user_id`,address_type, ".$fieldstr.") ";
		$q .= "SELECT NULL, '$order_id', '".$auth['user_id']."', address_type, ".$fieldstr." FROM #__{vm}_user_info WHERE user_id='".$auth['user_id']."' AND user_info_id='".$d['ship_to_info_id']."' AND address_type='ST'";
		$db->query( $q );

		/**
    	* Insert all Products from the Cart into order line items; 
    	* one row per product in the cart 
    	*/
		$dboi = new ps_DB;

		for($i = 0; $i < $cart["idx"]; $i++) {

			$r = "SELECT product_id,product_in_stock,product_sales,product_parent_id,product_sku,product_name ";
			$r .= "FROM #__{vm}_product WHERE product_id='".$cart[$i]["product_id"]."'";
			$dboi->query($r);
			$dboi->next_record();

			$product_price_arr = $ps_product->get_adjusted_attribute_price($cart[$i]["product_id"], $cart[$i]["description"]);
			$product_price = $GLOBALS['CURRENCY']->convert( $product_price_arr["product_price"], $product_price_arr["product_currency"] );

			if( empty( $_SESSION['product_sess'][$cart[$i]["product_id"]]['tax_rate'] )) {
				$my_taxrate = $ps_product->get_product_taxrate($cart[$i]["product_id"] );
			}
			else {
				$my_taxrate = $_SESSION['product_sess'][$cart[$i]["product_id"]]['tax_rate'];
			}
			// Attribute handling
			$product_parent_id = $dboi->f('product_parent_id');
			$description = '';
			if( $product_parent_id > 0 ) {
				
				$db_atts = $ps_product->attribute_sql( $dboi->f('product_id'), $product_parent_id );
				while( $db_atts->next_record()) {
					$description .=	$db_atts->f('attribute_name').': '.$db_atts->f('attribute_value').'; ';
				}
			}
			
			$description .= $ps_product->getDescriptionWithTax($_SESSION['cart'][$i]["description"], $dboi->f('product_id'));
			
			$product_final_price = round( ($product_price *($my_taxrate+1)), 2 );

			$vendor_id = $ps_vendor_id;
			
			$fields = array('order_id' => $order_id, 
									'user_info_id' => $d["ship_to_info_id"],
									'vendor_id' => $vendor_id, 
									'product_id' => $cart[$i]["product_id"], 
									'order_item_sku' => $dboi->f("product_sku"), 
									'order_item_name' => $dboi->f("product_name"), 
									'product_quantity' => $cart[$i]["quantity"], 
									'product_item_price' => $product_price, 
									'product_final_price' => $product_final_price, 		
									'order_item_currency' => $GLOBALS['product_currency'],
									'order_status' => 'P', 
									'product_attribute' => $description, 
									'cdate' => $timestamp, 
									'mdate' => $timestamp
						);
			$db->buildQuery( 'INSERT', '#__{vm}_order_item', $fields );
			$db->query();

			// Update Stock Level and Product Sales, decrease - no matter if in stock or not!
			$q = "UPDATE #__{vm}_product ";
			$q .= "SET product_in_stock = product_in_stock - ".(int)$cart[$i]["quantity"];
			$q .= " WHERE product_id = '" . $cart[$i]["product_id"]. "'";
			$db->query($q);

			$q = "UPDATE #__{vm}_product ";
			$q .= "SET product_sales= product_sales + ".(int)$cart[$i]["quantity"];
			$q .= " WHERE product_id='".$cart[$i]["product_id"]."'";
			$db->query($q);
			
			// Update stock of parent product, if all child products are sold, thanks Ragnar Brynjulfsson
			if ($dboi->f("product_parent_id") != 0) {
				$q = "SELECT COUNT(product_id) ";
				$q .= "FROM #__{vm}_product ";
				$q .= "WHERE product_parent_id = ".$dboi->f("product_parent_id");
				$q .= " AND product_in_stock > 0";
				$db->query($q);
				$db->next_record();
				if (!$db->f("COUNT(product_id)")) {
					$q = "UPDATE #__{vm}_product ";
					$q .= "SET product_in_stock = 0 ";
					$q .= "WHERE product_id = ".$dboi->f("product_parent_id")." LIMIT 1";
					$db->query($q);
			  }
			}
		}

		######## BEGIN DOWNLOAD MOD ###############
		if( ENABLE_DOWNLOADS == "1" ) {
			require_once( CLASSPATH.'ps_order.php');
			for($i = 0; $i < $cart["idx"]; $i++) {
				// only handle downloadable products here
				if( ps_product::is_downloadable($cart[$i]["product_id"])) {
					$params = array('product_id' => $cart[$i]["product_id"], 'order_id' => $order_id, 'user_id' => $auth["user_id"] );
					ps_order::insert_downloads_for_product( $params );
					
					if( @VM_DOWNLOADABLE_PRODUCTS_KEEP_STOCKLEVEL == '1' ) {
						// Update the product stock level back to where it was.
						$q = "UPDATE #__{vm}_product ";
						$q .= "SET product_in_stock = product_in_stock + ".(int)$cart[$i]["quantity"];
						$q .= " WHERE product_id = '" .(int)$cart[$i]["product_id"]. "'";
						$db->query($q);
					}
				}
			}
		}
		################## END DOWNLOAD MOD ###########

		// Export the order_id so the checkout complete page can get it
		$d["order_id"] = $order_id;

		/*
		 * Let the shipping module know which shipping method
		 * was selected.  This way it can save any information
		 * it might need later to print a shipping label.
		 */
		if( is_callable( array($this->_SHIPPING, 'save_rate_info') )) {
			$this->_SHIPPING->save_rate_info($d);
		}

		// Now as everything else has been done, we can update the Order Status 
		$update_order = false;
		if( $order_total == 0.00 ) { // code moved out of $_PAYMENT check as no payment will be needed when $order_total=0.0
					// If the Order Total is zero, we can confirm the order to automatically enable the download
					$d['order_status'] = ENABLE_DOWNLOAD_STATUS;
					$update_order = true;
		} elseif (isset($_PAYMENT)) {
				if( $d['new_order_status'] != 'P' ) {
					$d['order_status'] = $d['new_order_status'];
					$update_order = true;
				}
			}

		if ( $update_order ) {
			require_once(CLASSPATH."ps_order.php");
			$ps_order = new ps_order();
			$ps_order->order_status_update($d);
		}
		
		// Send the e-mail confirmation messages
		$ps_chkout->email_receipt($order_id);

		// Reset the cart (=empty it)
		$ps_cart->reset();
        $_SESSION['savedcart']['idx']=0;
        $ps_cart->saveCart();

		// Unset the payment_method variables
		$d["payment_method_id"] = "";
		$d["order_payment_number"] = "";
		$d["order_payment_expire"] = "";
		$d["order_payment_name"] = "";
		$d["credit_card_code"] = "";
		// Clear the sensitive Session data
		$_SESSION['ccdata']['order_payment_name']  = "";
		$_SESSION['ccdata']['order_payment_number']  = "";
		$_SESSION['ccdata']['order_payment_expire_month'] = "";
		$_SESSION['ccdata']['order_payment_expire_year'] = "";
		$_SESSION['ccdata']['credit_card_code'] = "";
		$_SESSION['coupon_discount'] = "";
		$_SESSION['coupon_id'] = "";
		$_SESSION['coupon_redeemed'] = false;
		
		$_POST["payment_method_id"] = "";
		$_POST["order_payment_number"] = "";
		$_POST["order_payment_expire"] = "";
		$_POST["order_payment_name"] = "";
		$_SESSION['order_id'] = $order_id;
	}
}
