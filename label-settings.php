<div class="wrap">
	<h2>Product Labels</h2>

	<form method="post" action="" id="woopol-status-export">
		<?php wp_nonce_field( 'woopol_wclabels_export', 'woopol_wclabels_nonce' ); ?>

		<table class="form-table">
			<tr>
				<td width="180px"><?php _e( 'From', 'woo-prod-ord-label' ); ?></td>
				<td>
					<?php $last_export = get_option( 'woopol_wclabels_last_export', array('date'=>'','hour'=>'','minute'=>'') ); ?>
					<input type="text" id="date-from" name="date-from" class="date-range" value="<?php echo $last_export['date']; ?>" size="10">
				</td>
			</tr>
			<tr>
				<td><?php _e( 'To', 'woo-prod-ord-label' ); ?></td>
				<td>
					<?php $now = array('date'=>date_i18n('Y-m-d'),'hour'=>date_i18n('H'),'minute'=>date_i18n('i')); ?>
					<input type="text" id="date-to" name="date-to"  class="date-range" value="<?php echo $now['date']; ?>" size="10">
				</td>
			</tr>
			<tr>
				<td valign="top">
					<?php _e( 'Filter status', 'woo-prod-ord-label' ); ?>
				</td>
				<td>
					<fieldset>
						<?php
						// get list of WooCommerce statuses
						$order_statuses = array();
						if ( version_compare( WOOCOMMERCE_VERSION, '2.2', '<' ) ) {
							$statuses = (array) get_terms( 'shop_order_status', array( 'hide_empty' => 0, 'orderby' => 'id' ) );
							foreach ( $statuses as $status ) {
								$order_statuses[esc_attr( $status->slug )] = esc_html__( $status->name, 'woocommerce' );
							}
						} else {
							$statuses = wc_get_order_statuses();
							foreach ( $statuses as $status_slug => $status ) {
								// $status_slug   = 'wc-' === substr( $status_slug, 0, 3 ) ? substr( $status_slug, 3 ) : $status_slug;
								$order_statuses[$status_slug] = $status;
							}
						}

						// list status checkboxes
						echo '<select name="filter_order_status" id="filter_order_status">';
						echo '<option value="">Select Status</optoion>';
						foreach ($order_statuses as $status_slug => $status) {
							//printf('<input type="checkbox" id="status_filter[]" name="status_filter[]" value="%s" /> %s<br />', $status_slug, $status);
							echo '<option value="'.$status_slug.'">'.$status.'</optoion>';
						}
						echo '</select>';
						?>
					</fieldset>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Order labels PDF', 'woo-prod-ord-label' ) ); ?>
	</form>

	<script>
			jQuery(document).ready(function ($) {
				jQuery( ".date-range" ).datepicker({ dateFormat: 'yy-mm-dd' });
			});
	</script>

<?php
if ( isset($_POST['woopol_wclabels_nonce']) && (! isset( $_POST['woopol_wclabels_nonce'] ) || ! wp_verify_nonce( $_POST['woopol_wclabels_nonce'], 'woopol_wclabels_export' ) ) ) {
   //Verifiy not match..
   starfish_notice_data_nonce_verify_required();
} else {
   if(isset($_POST['submit'])){

		 if(isset($_POST['date-from'])){
			 $date_before = $_POST['date-from'];
		 }else{
			 $date_before = date('Y-m-d', strtotime('-1 day'));
		 }

		 if(isset($_POST['date-to'])){
			 $date_after = $_POST['date-to'];
		 }else{
			 $date_after = date('Y-m-d');
		 }

		 if(isset($_POST['filter_order_status'])){
			 $order_status = $_POST['filter_order_status'];
		 }else{
			 $order_status = 'processing';
		 }

		$output_html = wooprod_generate_products_label_html($date_before, $date_after, $order_status);

		$output_css = wooprod_generate_products_label_css();

		$generate_result = generate_products_label_pdf($output_css, $output_html);
		if(isset($generate_result['pdf_url'])){
		  $pdf_file_url = $generate_result['pdf_url'];
			//echo $pdf_file_url;
			echo '<div style="text-align:center; width:100%; float:left;"><h4 style="color:#46b450;">PDF Generate Successfull!</h4><a href="'.$pdf_file_url.'" target="_blank" style="font-size:18px;" class="button button-primary">Download/Print PDF</a><br /><small>If you see same pdf content, Make hard refresh cache to see latest content.</small></div>';
		}



	}
}

?>

</div>
