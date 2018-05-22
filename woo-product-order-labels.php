<?php
/*
Plugin Name: WooCommerce Proudct Order Labels
Plugin URI: http://rmweblab.com/
Description: Print Woocommerce Proudct Label
Author: RM Web Lab
Version: 1.0
Author URI: http://rmweblab.com
Text Domain: woo-prod-ord-label
Domain Path: /languages

Copyright: © 2018 RMWebLab.
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*****/


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define contants
define('WOOPRODLABEL', dirname(__FILE__));
define('WOOPRODLABEL_URL', plugins_url( 'woo-product-order-labels/' ));

require_once("mpdf/mpdf.php");

class RMWooProductsOrderLabel {

    /* Constructor for the class */
    function __construct() {
  		//add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
  		add_action( 'plugins_loaded', array( $this, 'init' ), 0 );

  		/* Add admin menu */
  		add_action('admin_menu', array(&$this, 'woo_prod_plug_settings_page'));

			if(is_admin()) {
				// admin actions/filters
				add_action('admin_footer-edit.php', array(&$this, 'woo_prod_custom_bulk_admin_footer'));
				add_action('load-edit.php',         array(&$this, 'woo_prod_custom_bulk_action'));
				add_action('admin_notices',         array(&$this, 'woo_prod_custom_bulk_admin_notices'));
			}


    }

	/**
	 * Init localisations and files
	 */
	public function init() {
    require_once('label-functions.php');
	}


	/**
	 * Add relevant links to plugins page
	 * @param  array $links
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=woo-prod-ord-label' ) . '">' . __( 'Settings', 'woo-prod-ord-label' ) . '</a>',
		);
		return array_merge( $plugin_links, $links );
	}


    /**
     * Plugins settings page
     */
    public function woo_prod_plug_settings_page() {
				add_submenu_page( 'woocommerce', 'Order Labels', 'Order Labels', 'manage_woocommerce', 'woo-prod-ord-label', array(&$this, 'rm_woo_prod_label_plug_page'));
    }

		public function rm_woo_prod_label_plug_page(){
			require_once('label-settings.php');
		}


		/**
		 * Step 1: add the custom Bulk Action to the select menus
		 */
		function woo_prod_custom_bulk_admin_footer() {
			global $post_type;

			if($post_type == 'shop_order') {
				?>
					<script type="text/javascript">
						jQuery(document).ready(function() {
							jQuery('<option>').val('prod_labels').text('<?php _e('Product Labels')?>').appendTo("select[name='action']");
							jQuery('<option>').val('prod_labels').text('<?php _e('Product Labels')?>').appendTo("select[name='action2']");
						});
					</script>
				<?php
				}
		}


		/**
		 * Step 2: handle the custom Bulk Action
		 *
		 * Based on the post http://wordpress.stackexchange.com/questions/29822/custom-bulk-action
		 */
		function woo_prod_custom_bulk_action() {
			global $typenow;
			$post_type = $typenow;

			if($post_type == 'shop_order') {

				// get the action
				$wp_list_table = _get_list_table('WP_Posts_List_Table');  // depending on your resource type this could be WP_Users_List_Table, WP_Comments_List_Table, etc
				$action = $wp_list_table->current_action();

				$allowed_actions = array("prod_labels");
				if(!in_array($action, $allowed_actions)) return;

				// security check
				check_admin_referer('bulk-posts');

				// make sure ids are submitted.  depending on the resource type, this may be 'media' or 'ids'
				if(isset($_REQUEST['post'])) {
					$post_ids = array_map('intval', $_REQUEST['post']);
				}

				if(empty($post_ids)) return;

				// this is based on wp-admin/edit.php
				$sendback = remove_query_arg( array('prod_labels', 'untrashed', 'deleted', 'ids'), wp_get_referer() );
				if ( ! $sendback )
					$sendback = admin_url( "edit.php?post_type=$post_type" );

				$pagenum = $wp_list_table->get_pagenum();
				$sendback = add_query_arg( 'paged', $pagenum, $sendback );

				switch($action) {
					case 'prod_labels':

						// if we set up user permissions/capabilities, the code might look like:
						//if ( !current_user_can($post_type_object->cap->export_post, $post_id) )
						//	wp_die( __('You are not allowed to export this post.') );

						$printed = 0;
						foreach( $post_ids as $post_id ) {

							if ( !$this->perform_export($post_id) )
								wp_die( __('Error exporting post.') );

							$printed++;
						}

						$pdf_file_url = '';
						$output_html = wooprod_generate_products_label_html('', '', '', $post_ids);
						$output_css = wooprod_generate_products_label_css();
						$generate_result = generate_products_label_pdf($output_css, $output_html);
						if(isset($generate_result['pdf_url'])){
							$pdf_file_url = $generate_result['pdf_url'];
						}

						$sendback = add_query_arg( array('prod_labels' => $printed, 'pdf_url' => $pdf_file_url, 'ids' => join(',', $post_ids) ), $sendback );
					break;

					default: return;
				}

				$sendback = remove_query_arg( array('action', 'action2', 'tags_input', 'post_author', 'comment_status', 'ping_status', '_status',  'post', 'bulk_edit', 'post_view'), $sendback );

				wp_redirect($sendback);
				exit();
			}
		}


		/**
		 * Step 3: display an admin notice on the Posts page after exporting
		 */
		function woo_prod_custom_bulk_admin_notices() {
			global $post_type, $pagenow;

			if($pagenow == 'edit.php' && $post_type == 'shop_order' && isset($_REQUEST['prod_labels']) && (int) $_REQUEST['prod_labels']) {
				$pdf_file_url = $_REQUEST['pdf_url'];	
				//$message = sprintf( _n( 'Orders exported.', '%s orders exported.', $_REQUEST['prod_labels'] ), number_format_i18n( $_REQUEST['prod_labels'] ) );
				$message = 'PDF Generate Successfull!<br /><a href="'.$pdf_file_url.'" target="_blank" style="" class="">Download/Print PDF</a>';
				echo "<div class=\"updated\"><p>{$message}</p></div>";
			}
		}

		function perform_export($post_id) {
			// do whatever work needs to be done
			return true;
		}


}


global $RMWooProductsOrderLabel;
$RMWooProductsOrderLabel = new RMWooProductsOrderLabel();
