<?php

if( !defined( '_VALID_MOS' ) && !defined( '_JEXEC' ) ) die( 'Direct Access to '.basename(__FILE__).' is not allowed.' ); 
/**
* Paynetz IPN Result Checker
* http://virtuemart.net
* @author Ashish Solanki
*/
mm_showMyFileName( __FILE__ );

require_once ( CLASSPATH . 'ps_order.php' );
$ps_order= new ps_order;
$order_id = $_SESSION['order_id'];
$d['order_id'] = $order_id;

if($_REQUEST['f_code']!='Ok'){
	// the Payment wasn't successful
    // UPDATE THE ORDER STATUS to 'CANCELLED'
    $d['order_status'] = "X";
    $ps_order->order_status_update($d);
	?>
	<img src="<?php echo VM_THEMEURL  ?>images/button_cancel.png" alt="<?php echo $VM_LANG->_('VM_CHECKOUT_FAILURE'); ?>" style="border: 0;" />
	<h2>Payment Unsuccessful </h2>
	<p>Failure in Processing the Payment</p>
<?php }else{
	//Payment succeed
	// UPDATE THE ORDER STATUS to 'CONFIRMED'
    $d['order_status'] = "C";
    $ps_order->order_status_update($d);
	?>
	<img src="<?php echo VM_THEMEURL ?>images/button_ok.png" alt="Success" style="border: 0;" />
	<h2>Thanks for your payment.</h2>
	<p>The transaction was successful.</p>
	<br />
    <p>
		<a href="index.php?option=com_virtuemart&page=account.order_details&order_id=<?php echo $order_id ?>">
		Follow this link to view the Order Details.</a>
    </p>
<?php }  ?>