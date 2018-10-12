<?php

function rmt_get_woo_prod_variation($product_id){
		global $woocommerce, $product, $post;
    $args = array(
     'post_type'     => 'product_variation',
     'post_status'   => array( 'private', 'publish' ),
     'numberposts'   => -1,
     'orderby'       => 'menu_order',
     'order'         => 'asc',
     'post_parent'   => $product_id
    );
    $variations = get_posts( $args );

    $goal_type_size = array();

		$output_html = '';

    foreach ( $variations as $variation ) {
        $variation_id = absint( $variation->ID );
        $variation_post_status  = esc_attr( $variation->post_status );
        $variation_data  = get_post_meta( $variation_id );
        $variation_data['variation_post_id'] = $variation_id;

        // echo '<pre>';
        // print_r($variation_data);
        // echo '</pre>';
        $attribute_level = '';
        $goal_type = get_post_meta( $variation_data['variation_post_id'], 'attribute_goal-type', true);
        $size = get_post_meta( $variation_data['variation_post_id'], 'attribute_size', true);

        $attribute_level = $goal_type . ' ' . $size;

        $nutrition_calories = get_post_meta( $variation_data['variation_post_id'], '_nutrition_calories', true);
        $nutrition_protein = get_post_meta( $variation_data['variation_post_id'], '_nutrition_protein', true);
        $nutrition_carb = get_post_meta( $variation_data['variation_post_id'], '_nutrition_carb', true);
        $nutrition_fat = get_post_meta( $variation_data['variation_post_id'], '_nutrition_fat', true);
				$regular_price = get_post_meta( $variation_data['variation_post_id'], '_regular_price', true);

        $goal_type_size[$attribute_level] = array(
          'cal' => $nutrition_calories,
          'prot' => $nutrition_protein,
          'carb' => $nutrition_carb,
          'fat' => $nutrition_fat,
					'cost' => $regular_price,
        );
    }

    if(isset($goal_type_size) && (count($goal_type_size) > 0)){
      foreach ($goal_type_size as $key => $facts) {
        $output_html .= '<tr><td>'.$key.'</td><td>'.$facts['cal'].'</td><td>'.$facts['prot'].'</td><td>'.$facts['carb'].'</td><td>'.$facts['fat'].'</td></tr>';
      }
    }

		return $output_html;
}

function wooprod_generate_products_label_css(){
	$output_css = '';
	$output_css .= '<style type="text/css">';
	$font_family = ' font-family: garuda;';
	$output_css .= 'body{ '.$font_family.'}
	.label-wrapper{ width: 100%; color: #000; }
	.print-block-item{ float: left; width: 377px; }
	.print-block{ font-size:10px; height: auto; float: left; padding: 5px 5px; background-color: #fff; text-align: center; border-radius: 10px; border: 1px solid #dadada; margin-right: 5px; margin-bottom: 5px; }
	.coll-right-item .print-block{margin-right:0;}
	.print-block .table-wrap{ border:none; }
	.content-table{ border: 1px solid #dadada; width:100%; }
	.print-block table{ padding: 0; margin: 0px 0; border-spacing:0;}
	.print-block table tr td{ border-bottom: 1px solid #dadada; border-right: 1px solid #dadada; text-align:center; }
	.print-block table tr .column-td{ border-bottom:none; border-right:none;}
	.print-block table .macro-head td{ font-size: 8px; }
	.print-block table .macro-value td{ font-size: 8px; }
	.print-block table tr td.last-td{ border-right: none; }
	.print-block table tr.ingredients td{ border-bottom: none; }
	tr.ingredients td { font-size: 8px; padding: 5px; height: 80px; overflow:hidden; }
	tr.ingredients td { font-size: 8px; padding: 5px;}
	.prod-logo{ margin-top: 5px; }
	.prod-logo img { width: 60px; }
	.block-top{ font-size: 12px; padding:5px 10px; }
	tr.prod-title{ background-color: #c45911;}
	.prod-title td{ font-size: 12px; text-align:center; color: #FFFD38; padding: 5px; width:100px; height:80px; }
	.prod_name{ display:block; width: 100%; }
	.attribute_label{ display:block; width: 100%; }
	.page-break{box-decoration-break: slice; width: 100%; float:left; clear:both;}
	.block-content{ height:120px; }
	';
	$output_css .= '</style>';

	return $output_css;
}

function wooprod_generate_products_label_html($date_before, $date_after, $order_status, $product_ids = array()){

	$output_html = '';
	$output_html_label = '';

	$page_break_html = '<div class="page-break"><pagebreak></div>';

	$output_html .= '<div class="label-wrapper">';

	$prods = array();
	$output_html_label = '';

	//$output_html .= $page_break_html;

	$timestamp_date_before = wc_string_to_timestamp( get_gmt_from_date( gmdate( 'Y-m-d H:i:s', wc_string_to_timestamp( $date_before ) ) ) );
	$timestamp_date_after = wc_string_to_timestamp( get_gmt_from_date( gmdate( 'Y-m-d H:i:s', wc_string_to_timestamp( $date_after ) ) ) );
	$query_arg = array(
			'limit' => -1,
			'status' => $order_status,
			'date_created' => $timestamp_date_before.'...'.$timestamp_date_after,
			'orderby' => 'date',
			'order' => 'DESC',
			'return' => 'ids',
	);

	if(isset($product_ids) && (count($product_ids) > 0)){
			$orders_data = $product_ids;
	}else{
			$orders_data = wc_get_orders($query_arg);
	}


	$item_counter = 0;
	$print_label_data_arr = array();
	if(isset($orders_data) && (count($orders_data) > 0)){
		foreach ($orders_data as $key => $order_id) {
				$order = wc_get_order( $order_id );

				foreach ($order->get_items() as $key => $lineItem) {
						$product_title = '';
						$order_info = array();
						$item_id = $lineItem->get_id();
						$item_quantity = $lineItem->get_quantity();
						$product_id = $lineItem['product_id'];

						$prod_ingredients = esc_html( get_post_meta( $product_id, '_prod_ingredients', true ) );

						//$product_title = trim($lineItem['name']);
						$product_title = get_the_title($product_id);

						$attribute_level = '';

						if ($lineItem['variation_id']) {
								$variation_id = $lineItem['variation_id'];
								$goal_type = get_post_meta( $variation_id, 'attribute_goal-type', true);
								$size = get_post_meta( $variation_id, 'attribute_size', true);

								$attribute_level = $goal_type . ' ' . $size;

								//$product_title = $lineItem['name'];

								$nutrition_calories = get_post_meta( $variation_id, '_nutrition_calories', true);
								$nutrition_protein = get_post_meta( $variation_id, '_nutrition_protein', true);
								$nutrition_carb = get_post_meta( $variation_id, '_nutrition_carb', true);
								$nutrition_fat = get_post_meta( $variation_id, '_nutrition_fat', true);
								$regular_price = get_post_meta( $variation_id, '_regular_price', true);

						} else {
								$nutrition_calories = get_post_meta( $product_id, '_nutrition_calories', true);
								$nutrition_protein = get_post_meta( $product_id, '_nutrition_protein', true);
								$nutrition_carb = get_post_meta( $product_id, '_nutrition_carb', true);
								$nutrition_fat = get_post_meta( $product_id, '_nutrition_fat', true);
								$regular_price = get_post_meta( $product_id, '_regular_price', true);
						}

						//Take out  (Monthly Feature) from product title.
						$search_word = array();
						$replace_word = array();
						$search_word[] = '(Monthly Feature)';
						$replace_word[] = '';

						$product_title = str_replace($search_word, $replace_word, $product_title);

						if($product_title != ''){
							$product_title = substr($product_title, 0, 60);
						}

						if($prod_ingredients != ''){
							$prod_ingredients = substr($prod_ingredients, 0, 200);
						}

						$order_info['product_id'] = $product_id;
						$order_info['order_id'] = $order_id;
						$order_info['ingredients'] = $prod_ingredients;
						$order_info['product_name'] = $product_title;
						$order_info['attributes'] = $attribute_level;
						$order_info['cal'] = $nutrition_calories;
						$order_info['prot'] = $nutrition_protein;
						$order_info['carb'] = $nutrition_carb;
						$order_info['fat'] = $nutrition_fat;

						for($item_quantity_counter = 1; $item_quantity_counter <= $item_quantity; $item_quantity_counter++){
								if(strlen($product_title) > 2){
										$print_label_data_arr[] = array(
											'product_name' => $product_title,
											'attributes' => $attribute_level,
											'order_data' => $order_info
										);
								}
					}

				}

		}
	}//Retrive order data
	$item_counter = 0;
	if(isset($print_label_data_arr) && (count($print_label_data_arr) > 0)){
			woo_prod_label_array_sort_by_column($print_label_data_arr, 'attributes');
			foreach ($print_label_data_arr as $key => $label_data) {
					$order_info = $label_data['order_data'];
					$item_counter += 1;
					$output_html .= wooprod_build_level_items($order_info, $item_counter);
					if((($item_counter % 10) === 0) && ($item_counter > 1)){
							$output_html .= $page_break_html;
					}
			}
	}

	$output_html .= $output_html_label;

	$output_html .= '</div>';

	return $output_html;

}

function woo_prod_label_array_sort_by_column(&$arr, $col, $dir = SORT_ASC) {
    $sort_col = array();
    foreach ($arr as $key=> $row) {
        $sort_col[$key] = $row[$col];
    }

    array_multisort($sort_col, $dir, $arr);
}


function wooprod_build_level_items($prods, $item_counter = 0){
	$date_next_5 = strtotime("+5 day", time());
	$date_next_5 = date('M d, Y', $date_next_5);

	if(isset($prods['cal']) && ($prods['cal'] > 0)){
		$prod_cal = $prods['cal'];
	}else{
		$prod_cal = 'n/a';
	}

	if(isset($prods['prot']) && ($prods['prot'] > 0)){
		$prod_prot = $prods['prot'];
	}else{
		$prod_prot = 'n/a';
	}

	if(isset($prods['carb']) && ($prods['carb'] > 0)){
		$prod_carb = $prods['carb'];
	}else{
		$prod_carb = 'n/a';
	}

	if(isset($prods['fat']) && ($prods['fat'] > 0)){
		$prod_fat = $prods['fat'];
	}else{
		$prod_fat = 'n/a';
	}

	$col_right_class = '';
	if(($item_counter % 2) == 0){
		$col_right_class = ' coll-right-item ';
	}

	$logo_file_path = ABSPATH . '/wp-content/plugins/woo-product-order-labels/images/logo.png';

	$output_html_label = '
	<div class="print-block-item '.$col_right_class.'">
	<div class="print-block">
		<table class="table-wrap" border="0">
			<tr>
			<td class="block-top column-td" width="100">
					Refrigerate & Consume by:<br />
					<span class="date-5"><em>'.$date_next_5.'</em></span><br />
					Freezing Optional
			</td>
			<td class="block-content column-td" width="200">
			<table class="content-table">
					<tr class="prod-title"><td class="last-td" colspan="4"> <div class="prod_name">'.$prods['product_name'].'</div><div class="attribute_label">'.$prods['attributes'].'</div></td></tr>
					<tr class="macro-head"><td>PROTEIN</td><td>CARB</td><td>FAT</td><td class="last-td">CAL</td></tr>
					<tr class="macro-value"><td>'.$prod_prot.'</td><td>'.$prod_carb.'</td><td>'.$prod_fat.'</td><td class="last-td">'.$prod_cal.'</td></tr>
					<tr class="ingredients"><td colspan="4" class="last-td" valign="top">Ingredients: '.$prods['ingredients'].'</td></tr>
			</table>
			</td>
			<td class="prod-logo column-td" width="60">
					<img width="60" src="'.$logo_file_path.'" alt="" />
			</td>
			</tr>
		</table>
	</div><!-- print_block -->
	</div><!-- print-block-item -->
	';

	return $output_html_label;

}

/****
**Return array of result of generated PDF
***/
function generate_products_label_pdf($output_css, $output_html, $product_id = 0){
  $result = array();
  //https://github.com/fkrauthan/wp-mpdf/tree/master/mpdf
  $pdf_margin_left = 5;
  $pdf_margin_right = 5;
  $pdf_margin_top = 10;
  $pdf_margin_bottom = 5;
  $pdf_margin_header = 10;
  $pdf_margin_footer = 10;
  $pdf_orientation = 'L';
  //A4-L, Letter-L
  $mpdf = new mPDF( 'utf-8', '[216, 280]', '', 'arial', $pdf_margin_left, $pdf_margin_right, $pdf_margin_top, $pdf_margin_bottom, $pdf_margin_header, $pdf_margin_footer, $pdf_orientation );
  $mpdf->WriteHTML( $output_css, 1 );
  $mpdf->WriteHTML($output_html);

  //$pdf_filename = $mpdf->Output('', 'S');

  $dir = "/product-order-label-pdf";
  $upload_dir = wp_upload_dir();
  $report_pdf_dir = $upload_dir['basedir'].$dir;
  $report_pdf_url = $upload_dir['baseurl'].$dir;
  if( ! file_exists( $report_pdf_dir ) ){
  	wp_mkdir_p( $report_pdf_dir );
  }
  //$filename_title = 'products-labels-'.time();
	$filename_title = 'product-order-labels';

  $mpdf->Output($report_pdf_dir.'/'.$filename_title.'.pdf', 'F');

  $report_pdf_file = $report_pdf_dir.'/'.$filename_title.'.pdf';
  $report_pdf_file_url = $report_pdf_url.'/'.$filename_title.'.pdf';

  $result['pdf_path'] = $report_pdf_file;
  $result['pdf_url'] = $report_pdf_file_url;

  return $result;

}

function pro_order_lebel_admin_styles( $hook ) {
	global $pagenow, $post;
	$screen = get_current_screen();
	if('woocommerce_page_woo-prod-ord-label' == $screen->id){
			wp_enqueue_style( 'jquery-ui-datepicker-style' , '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/smoothness/jquery-ui.css');
	}
}
add_action('admin_print_styles', 'pro_order_lebel_admin_styles');
function pro_order_lebel_admin_scripts( $hook ) {
	global $pagenow, $post;
	$screen = get_current_screen();
	if('woocommerce_page_woo-prod-ord-label' == $screen->id){
  	wp_enqueue_script( 'jquery-ui-datepicker' );
	}
}
add_action('admin_enqueue_scripts', 'pro_order_lebel_admin_scripts');
