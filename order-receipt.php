<?php
/**
 * Order Receipt Renderer
 *
 * @link       https://github.tamu.edu/liberalarts-web/cla-workstation-order/blob/master/order-receipt.php
 * @author:    Zachary Watkins <zwatkins2@tamu.edu>
 * @since      1.0.0
 * @package    cla-workstation-order
 * @license    https://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License v2.0 or later
 *
 * README:   The FPDF library was chosen purely based on what is in this file.
 *           If you are taking custody of this application you have freedom to
 *           choose a different document rendering solution without searching
 *           through the rest of the codebase. Just keep in mind other files
 *           call this file.
 *           The FPDF library seemed highly used, no dependencies, and therefore
 *           a stable solution that would be supported by its developers for the
 *           long term. However, the x/y/width/height system is do-it-yourself.
 *           I have taken an incremental approach to this problem, which you
 *           will see as the script progresses.
 * Requires: setasign/fpdf, WordPress 5.4+
 * Mirrors:  https://packagist.org/packages/setasign/fpdf
 *           https://github.com/Setasign/FPDF
 *           http://www.fpdf.org/en/download.php
 */

// Check if we should exit the file.
if ( ! isset( $_GET['postid'] ) ) {
	exit();
}

// Require files.
require dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php';
wp();

$order_post_id = $_GET['postid'];

// Authenticate user-based access permission
$current_user              = wp_get_current_user();
$current_user_id           = (int) $current_user->ID;
$affiliated_it_reps        = get_field( 'affiliated_it_reps', $order_post_id );
$affiliated_business_staff = get_field( 'affiliated_business_staff', $order_post_id );
$author_id                 = (int) get_post_field( 'post_author', $order_post_id );

if (
	! is_user_logged_in()
	|| (
		! in_array( $current_user_id, $affiliated_it_reps, true )
		&& ! in_array( $current_user_id, $affiliated_business_staff, true )
		&& $current_user_id !== $author_id
		&& ! current_user_can( 'wso_logistics' )
		&& ! current_user_can( 'wso_logistics_admin' )
		&& ! current_user_can( 'wso_admin' )
	)
) {
	exit();
}

// Validate nonce.
check_admin_referer( 'auth-post_' . $order_post_id, 'token' );

// Load PDF library.
require CLA_WORKSTATION_ORDER_DIR_PATH . 'vendor/setasign/fpdf/fpdf.php';

// Gather post meta.
$order_post = get_post( $order_post_id );
$meta = get_post_meta( $order_post_id );
foreach ( $meta as $key => $value ) {
	if ( strpos( $key, '_' ) === 0 ) {
		unset( $meta[ $key ] );
	} elseif ( count( $value ) === 1 ) {
		$meta[ $key ] = $value[0];
	}
}
$meta['logo'] = CLA_WORKSTATION_ORDER_DIR_URL . 'images/logo-support-center.png';
// Get timestamps.
date_default_timezone_set( 'America/Chicago' );
$publish_time        = strtotime( $order_post->post_modified_gmt . ' UTC' );
$publish_date        = date( 'M j, Y \a\t g:i a', $publish_time );
$now_date            = date( 'M j, Y \a\t g:i a' );
$it_rep_confirm_time = strtotime( $meta['it_rep_status_date'] . ' UTC' );
$it_rep_confirm_date = date( 'M j, Y \a\t g:i a', $it_rep_confirm_time );
$business_staff_date = '';
if ( isset( $meta['business_staff_status_date'] ) && ! empty( $meta['business_staff_status_date'] ) ) {
	$business_staff_time = strtotime( $meta['business_staff_status_date'] . ' UTC' );
	$business_staff_date = date( 'M j, Y \a\t g:i a', $business_staff_time );
}
$logistics_confirm_time = strtotime( $meta['it_logistics_status_date'] . ' UTC' );
$logistics_confirm_date = date( 'M j, Y \a\t g:i a', $logistics_confirm_time );
// Extra basic order data.
$meta['publish_date_formatted'] = $publish_date;
$meta['now']                    = $now_date;
$meta['post_title']             = $order_post->post_title;
$meta['program_name']           = get_the_title( $meta['program'] );
$meta['program_fiscal_year']    = get_post_meta( $meta['program'], 'fiscal_year', true );
// Extra author data.
$author                    = get_userdata( $order_post->post_author );
$meta['author']            = $author->data->display_name;
$meta['first_name']        = get_user_meta( $order_post->post_author, 'first_name', true );
$meta['last_name']         = get_user_meta( $order_post->post_author, 'last_name', true );
$meta['author_email']      = $author->data->user_email;
$meta['author_department'] = get_the_title( $meta['author_department'] );
// Extra IT Rep data.
$it_rep_user                  = get_user_by( 'id', intval( $meta['it_rep_status_it_rep'] ) );
$meta['it_rep_status_it_rep'] = $it_rep_user->data->display_name;
$meta['it_rep_status_date']   = 'Confirmed - ' . $it_rep_confirm_date;
// Extra Business Staff data.
if ( ! isset( $meta['business_staff_status_business_staff'] ) || '' === $meta['business_staff_status_business_staff'] ) {
	$meta['business_staff_status_date'] = 'Not required';
} else {
	$meta['business_staff_status_date']           = 'Confirmed - ' . $business_staff_date;
	$business_user                                = get_user_by('id', intval( $meta['business_staff_status_business_staff'] ) );
	$meta['business_staff_status_business_staff'] = $business_user->data->display_name;
}
// Extra Logistics data.\
$meta['it_logistics_status_date'] = 'Confirmed - ' . $logistics_confirm_date;
// Modify purchase item data.
$meta['products_subtotal'] = '$' . number_format( $meta['products_subtotal'], 2, '.', ',' );
if ( isset( $meta['order_items'] ) ) {
	for ($inc=0; $inc < $meta['order_items']; $inc++) {
		$price                            = $meta["order_items_{$inc}_price"];
		$meta["order_items_{$inc}_price"] = '$' . number_format( $price, 2, '.', ',' );
	}
}
if ( isset( $meta['quotes'] ) ) {
	for ($inc=0; $inc < $meta['quotes']; $inc++) {
		$price                       = $meta["quotes_{$inc}_price"];
		$meta["quotes_{$inc}_price"] = '$' . number_format( $price, 2, '.', ',' );
	}
}

/**
 * Generate the PDF.
 */
class PDF extends FPDF {
	/**
	 * Page header.
	 */
	public function Header() {
		// Logo.
		global $meta;
		$this->Image( $meta['logo'], 10, 6, 70 );
		// Arial bold 15.
		$this->SetFont( 'Arial', 'B', 15 );
		// Move to the right.
		$this->setXY( -120, 8 );
		// Title.
		$this->Cell( 110, 6, 'Order #' . $meta['post_title'], 0, 0, 'R' );
		$this->setXY( -120, 14 );
		$this->SetFont( 'Arial', '', 10 );
		$this->Cell( 110, 4, $meta['publish_date_formatted'], 0, 0, 'R' );
		$this->setXY( -120, 18 );
		$this->Cell( 110, 4, $meta['program_name'], 0, 0, 'R' );
		$this->setXY( -120, 22 );
		$this->Cell( 110, 4, $meta['program_fiscal_year'], 0, 0, 'R' );
		// Line break.
		$this->Ln( 20 );
	}

	/**
	 * Page footer
	 */
	public function Footer() {
		global $meta;
		// Position at 1.5 cm from bottom.
		$this->SetY( -15 );
		// Arial italic 8.
		$this->SetFont( 'Arial', '', 8 );
		// Draw line.
		$this->Line( 10, 266, 206, 266 );
		// Page number.
		$this->MultiCell( 196, 10, $meta['program_name'] . '   |   ' . $meta['post_title'] . '   |   ' . $meta['first_name'] . ' ' . $meta['last_name'] . ' - ' . $meta['author_department'] . '   |   Generated at: ' . $meta['now'], 0, 'C' );
	}
}

// Instanciation of inherited class.
$pdf = new PDF( 'P', 'mm', 'Letter' );
$pdf->AliasNbPages();
$pdf->AddPage();
// Program Title.
$pdf->SetFont( 'Arial', 'B', 18 );
$pdf->setXY( 10, 30 );
$pdf->Write( 10, $meta['program_name'] . ' Order');
// Details.
$columns_width = 91;
$left_margin_x = 11;
$right_margin_x = 205;
/**
 * User Details.
 */
$column_1_x = 11;
$column_1_width = 40;
$column_2_x = $column_1_x + $column_1_width;
$column_2_width = $columns_width - $column_1_width;
$offset = 0;
// Heading.
$pdf->SetFont('Arial','B',11);
$pdf->setXY($column_1_x, 50);
$pdf->Cell($columns_width, 5, 'User Details', 0, 0, 'C');
$pdf->Line($column_1_x, 55, $column_1_x + $columns_width, 55);
$pdf->SetFont('Arial','',11);
// First Name.
$pdf->setXY($column_1_x, 56);
$pdf->Cell($column_1_width, 5, 'First Name', 0, 0);
$pdf->setXY($column_2_x, 56);
$pdf->Cell($column_2_width, 5, $meta['first_name'], 0, 0);
// Last Name.
$pdf->setXY($column_1_x, 61);
$pdf->Cell($column_1_width, 5, 'Last Name', 0, 0);
$pdf->setXY($column_2_x, 61);
$pdf->Cell($column_2_width, 5, $meta['last_name'], 0, 0);
// Email.
$pdf->setXY($column_1_x, 66);
$pdf->Cell($column_1_width, 5, 'Email', 0, 0);
$pdf->setXY($column_2_x, 66);
// Email is possibly multi-line so we have to calculate its length.
$email_width = $pdf->GetStringWidth($meta['author_email']);
$email_container_width = $column_2_width;
$email_container_height = 5;
if ( $email_width <= $email_container_width ) {
	$pdf->Cell($column_2_width, 5, $meta['author_email'], 0, 0);
} else {
	$email_container_height = 10;
	$offset = $offset + 5;
	$pdf->MultiCell($column_2_width, 5, $meta['author_email'], 0, 'L');
}
// Department.
$pdf->setXY($column_1_x, 71 + $offset);
$pdf->Cell($column_1_width, 5, 'Department', 0, 0);
$pdf->setXY($column_2_x, 71 + $offset);
// Department is possibly multi-line so we have to calculate its length.
$dept_width = $pdf->GetStringWidth($meta['author_department']);
$dept_container_width = $column_2_width;
$dept_container_height = 5;
if ( $dept_width <= $dept_container_width ) {
	$pdf->Cell($column_2_width, 5, $meta['author_department'], 0, 0);
} else {
	$dept_container_height = 10;
	$offset = $offset + 5;
	$pdf->MultiCell($column_2_width, 5, $meta['author_department'], 0, 'L');
}
// Office Location.
$pdf->setXY($column_1_x, 76 + $offset);
$pdf->Cell($column_1_width, 5, 'Office Location', 0, 0);
$pdf->setXY($column_2_x, 76 + $offset);
$pdf->Cell($column_2_width, 5, $meta['building'] . ' ' . $meta['office_location'], 0, 0);
// Current Asset.
$pdf->setXY($column_1_x, 81 + $offset);
$pdf->Cell($column_1_width, 5, 'Current Asset', 0, 0);
$pdf->setXY($column_2_x, 81 + $offset);
if ( '1' === $meta['i_dont_have_a_computer_yet'] ) {
	$pdf->Cell($column_2_width, 5, 'none', 0, 0);
} else {
	$pdf->Cell($column_2_width, 5, $meta['current_asset'], 0, 0);
}
/**
 * Processing Steps
 */
$column_1_x = 114;
$column_1_y = 50;
$column_1_width = 40;
$column_2_x = $column_1_x + $column_1_width;
$column_2_width = $columns_width - $column_1_width;
$offsetY = $column_1_y;
// Heading.
$pdf->SetFont('Arial','B',11);
$pdf->setXY($column_1_x, $offsetY);
$pdf->Cell($columns_width, 5, 'Processing Steps', 0, 0, 'C');
$offsetY += 5;
$pdf->Line($column_1_x, $offsetY, $column_1_x + $columns_width, 55);
$offsetY += 1;
$pdf->SetFont('Arial','',11);
// IT Staff.
$pdf->setXY($column_1_x, $offsetY);
$pdf->Cell($column_1_width, 5, 'IT Staff', 0, 0);
$offsetY += 5;
// IT Staff Timestamp.
$pdf->setXY($column_2_x, $column_1_y + 6);
$pdf->MultiCell($column_2_width, 5, $meta['it_rep_status_date'], 0, 'L');
// IT Staff Name.
$pdf->setXY($column_1_x, $offsetY);
$it_rep_width = $pdf->GetStringWidth($meta['it_rep_status_it_rep']);
if ( $it_rep_width <= $column_1_width ) {
	$pdf->Cell($column_1_width, 5, '(' . $meta['it_rep_status_it_rep'] . ')', 0, 0);
	$offsetY += 5;
} else {
	$pdf->MultiCell($column_1_width, 5, '(' . $meta['it_rep_status_it_rep'] . ')', 0, 'L');
	$offsetY += 10;
}
// Business Staff Timestamp.
$pdf->setXY($column_2_x, $offsetY);
$pdf->MultiCell($column_2_width, 5, $meta['business_staff_status_date'], 0, 'L');
// Business Staff.
$pdf->setXY($column_1_x, $offsetY);
$pdf->Cell($column_1_width, 5, 'Business', 0, 0);
$offsetY += 5;
// Business Staff Name.
$pdf->setXY($column_1_x, $offsetY);
$it_rep_width = $pdf->GetStringWidth($meta['business_staff_status_business_staff']);
if ( $it_rep_width <= $column_1_width ) {
	$pdf->Cell($column_1_width, 5, '(' . $meta['business_staff_status_business_staff'] . ')', 0, 0);
	$offsetY += 5;
} else {
	$pdf->MultiCell($column_1_width, 5, '(' . $meta['business_staff_status_business_staff'] . ')', 0, 'L');
	$offsetY += 10;
}
// Logistics.
$pdf->setXY($column_1_x, $offsetY);
$pdf->Cell($column_1_width, 5, 'Logistics', 0, 0);
$pdf->setXY($column_2_x, $offsetY);
$pdf->MultiCell($column_2_width, 5, $meta['it_logistics_status_date'], 0, 'L');
/**
 * Items
 */
$offsetY += 20;
$full_width = 194;
$column_gap = 4;
$column_1_width = 0.48 * $full_width;
$column_2_width = 0.18 * $full_width;
$column_3_width = 0.12 * $full_width;
$column_4_width = 0.12 * $full_width;
$column_5_width = 0.1 * $full_width;
$item_length = 0;
if ( isset( $meta['order_items'] ) && ! empty( $meta['order_items'] ) ) {
	$item_length = intval( $meta['order_items'] );
}
$quote_item_length = 0;
if ( isset( $meta['quotes'] ) && ! empty( $meta['quotes'] ) ) {
	$quote_item_length = intval( $meta['quotes'] );
}

// Determine flexible column widths.
$requisition_numbers = array();
$item_names          = array();
if ( $item_length > 0 ) {
	for($inc=0; $inc < $item_length; $inc++) {
		$item_names[]          = $meta['order_items_' . $inc . '_item'];
		$requisition_numbers[] = $meta['order_items_' . $inc . '_requisition_number'];
	}
}
if ( $quote_item_length > 0 ) {
	for($inc=0; $inc < $quote_item_length; $inc++) {
		$item_names[]          = $meta['quotes_' . $inc . '_name'];
		$requisition_numbers[] = $meta['quotes_' . $inc . '_requisition_number'];
	}
}
// Column 3.
$pdf->SetFont('Arial','B',11);
$req_num_width = $pdf->GetStringWidth( 'Req #' );
$pdf->SetFont('Arial','',11);
foreach ( $requisition_numbers as $key => $req_num ) {
	$text_width = $pdf->GetStringWidth( $req_num );
	if ( $text_width > $req_num_width ) {
		$req_num_width = $text_width;
	}
}
$column_3_width = $req_num_width + $column_gap;
// Column 1.
$pdf->SetFont('Arial','B',11);
$item_name_width = $pdf->GetStringWidth( 'Advanced Teaching/Research Quote' );
$pdf->SetFont('Arial','',11);
foreach ( $item_names as $key => $name ) {
	$text_width = $pdf->GetStringWidth( $name );
	if ( $text_width > $item_name_width ) {
		$item_name_width = $text_width;
	}
}
$limit_column_1_width = $full_width - $column_2_width - $column_3_width - $column_4_width - $column_5_width;
$column_1_width       = $item_name_width + $column_gap;
if ( $column_1_width > $limit_column_1_width ) {
	$column_1_width = $limit_column_1_width;
} elseif ( $column_1_width < $limit_column_1_width ) {
	$column_difference = $limit_column_1_width - $column_1_width;
	$column_2_width = $column_2_width + $column_difference;
}


/**
 * Product Items for Purchase.
 */
if ( $item_length > 0 ) {
	// Items Heading.
	$pdf->SetFont('Arial','B',11);
	$pdf->setXY($left_margin_x, $offsetY);
	$pdf->Cell($column_1_width, 5, 'Item', 0, 0);
	$pdf->setXY($left_margin_x + $column_1_width, $offsetY);
	$pdf->Cell($column_2_width, 5, 'SKU', 0, 0);
	$pdf->setXY($left_margin_x + $column_1_width + $column_2_width, $offsetY);
	$pdf->Cell($column_3_width, 5, 'Req #', 0, 0);
	$pdf->setXY($left_margin_x + $column_1_width + $column_2_width + $column_3_width, $offsetY);
	$pdf->Cell($column_4_width, 5, 'Req Date', 0, 0);
	$pdf->setXY($left_margin_x + $column_1_width + $column_2_width + $column_3_width + $column_4_width, $offsetY);
	$pdf->Cell($column_5_width, 5, 'Price', 0, 0, 'R');
	$offsetY += 6;
	$pdf->Line($left_margin_x, $offsetY, $right_margin_x, $offsetY);
	$offsetY += 2;
	// Items
	$pdf->SetFont('Arial','',11);
	for ($inc=0; $inc < $item_length; $inc++) {
		// Item Name.
		$offsetX = $left_margin_x;
		$pdf->setXY($offsetX, $offsetY);
		$pdf->MultiCell($column_1_width, 5, $meta['order_items_' . $inc . '_item'], 0, 'L');
		// SKU.
		$offsetX += $column_1_width;
		$pdf->setXY($offsetX, $offsetY);
		$pdf->MultiCell($column_2_width, 5, $meta['order_items_' . $inc . '_sku'], 0, 'L');
		// Requisition Number.
		$offsetX += $column_2_width;
		$pdf->setXY($offsetX, $offsetY);
		$pdf->MultiCell($column_3_width, 5, $meta['order_items_' . $inc . '_requisition_number'], 0, 'L');
		// Requisition Date.
		$offsetX += $column_3_width;
		$pdf->setXY($offsetX, $offsetY);
		$pdf->MultiCell($column_4_width, 5, $meta['order_items_' . $inc . '_requisition_date'], 0, 'L');
		// Price.
		$offsetX += $column_4_width;
		$pdf->setXY($offsetX, $offsetY);
		$pdf->MultiCell($column_5_width, 5, $meta['order_items_' . $inc . '_price'], 0, 'R');
		// Figure out the maximum line count among all details of this item.
		$lines       = 1;
		$meta_detail = $meta['order_items_' . $inc . '_item'];
		$text_width  = $pdf->GetStringWidth($meta_detail);
		$col_width   = $column_1_width;
		if ( $text_width > $col_width ) {
			$line_count = ceil( $text_width / $col_width );
			if ( $line_count > $lines ) {
				$lines = $line_count;
			}
		}
		$meta_detail = $meta['order_items_' . $inc . '_sku'];
		$text_width = $pdf->GetStringWidth($meta_detail);
		$col_width = $column_2_width;
		if ( $text_width > $col_width ) {
			$line_count = ceil( $text_width / $col_width );
			if ( $line_count > $lines ) {
				$lines = $line_count;
			}
		}
		$meta_detail = $meta['order_items_' . $inc . '_requisition_number'];
		$text_width = $pdf->GetStringWidth($meta_detail);
		$col_width = $column_3_width;
		if ( $text_width > $col_width ) {
			$line_count = ceil( $text_width / $col_width );
			if ( $line_count > $lines ) {
				$lines = $line_count;
			}
		}
		$meta_detail = $meta['order_items_' . $inc . '_requisition_date'];
		$text_width = $pdf->GetStringWidth($meta_detail);
		$col_width = $column_4_width;
		if ( $text_width > $col_width ) {
			$line_count = ceil( $text_width / $col_width );
			if ( $line_count > $lines ) {
				$lines = $line_count;
			}
		}
		$meta_detail = $meta['order_items_' . $inc . '_price'];
		$text_width = $pdf->GetStringWidth($meta_detail);
		$col_width = $column_5_width;
		if ( $text_width > $col_width ) {
			$line_count = ceil( $text_width / $col_width );
			if ( $line_count > $lines ) {
				$lines = $line_count;
			}
		}
		// Take the max line count and point the next item's coordinates
		$offsetY += 5 * $lines;
	}
}

if ( $item_length > 0 && $quote_item_length > 0 ) {
	$offsetY += 5;
}

/**
 * Advanced Teaching/Research Quote.
 */
if ( $quote_item_length > 0 ) {
	// Quotes Heading.
	$pdf->SetFont('Arial','B',11);
	$pdf->setXY($left_margin_x, $offsetY);
	$pdf->Cell($column_1_width, 5, 'Advanced Teaching/Research Quote', 0, 0);
	$pdf->setXY($left_margin_x + $column_1_width + $column_2_width, $offsetY);
	$pdf->Cell($column_3_width, 5, 'Req #', 0, 0);
	$pdf->setXY($left_margin_x + $column_1_width + $column_2_width + $column_3_width, $offsetY);
	$pdf->Cell($column_4_width, 5, 'Req Date', 0, 0);
	$pdf->setXY($left_margin_x + $column_1_width + $column_2_width + $column_3_width + $column_4_width, $offsetY);
	$pdf->Cell($column_5_width, 5, 'Price', 0, 0, 'R');
	$offsetY += 6;
	$pdf->Line($left_margin_x, $offsetY, $right_margin_x, $offsetY);
	$offsetY += 2;
	$pdf->SetFont('Arial','',11);
	// Quote items.
	for ($inc=0; $inc < $quote_item_length; $inc++) {
		// Item Name.
		$offsetX = $left_margin_x;
		$pdf->setXY($offsetX, $offsetY);
		$pdf->MultiCell($column_1_width, 5, $meta['quotes_' . $inc . '_name'], 0);
		// // SKU.
		$offsetX += $column_1_width;
		// $pdf->setXY($offsetX, $offsetY);
		// $pdf->MultiCell($column_2_width, 5, $meta['quotes_' . $inc . '_sku'], 0);
		// // Requisition Number.
		$offsetX += $column_2_width;
		$pdf->setXY($offsetX, $offsetY);
		$pdf->MultiCell($column_3_width, 5, $meta['quotes_' . $inc . '_requisition_number'], 0);
		// // Requisition Date.
		$offsetX += $column_3_width;
		$pdf->setXY($offsetX, $offsetY);
		$pdf->MultiCell($column_4_width, 5, $meta['quotes_' . $inc . '_requisition_date'], 0);
		// Price.
		$offsetX += $column_4_width;
		$pdf->setXY($offsetX, $offsetY);
		$pdf->MultiCell($column_5_width, 5, $meta['quotes_' . $inc . '_price'], 0, 'R');
		// Figure out the maximum line count among all details of this item.
		$lines = 1;
		$meta_detail = $meta['quotes_' . $inc . '_name'];
		$text_width = $pdf->GetStringWidth($meta_detail);
		$col_width = $column_1_width;
		if ( $text_width > $col_width ) {
			$line_count = ceil( $text_width / $col_width );
			if ( $line_count > $lines ) {
				$lines = $line_count;
			}
		}
		// $meta_detail = $meta['quotes_' . $inc . '_sku'];
		// $text_width = $pdf->GetStringWidth($meta_detail);
		// $col_width = $column_2_width;
		// if ( $text_width > $col_width ) {
		// 	$line_count = ceil( $text_width / $col_width );
		// 	if ( $line_count > $lines ) {
		// 		$lines = $line_count;
		// 	}
		// }
		// $meta_detail = $meta['quotes_' . $inc . '_requisition_number'];
		// $text_width = $pdf->GetStringWidth($meta_detail);
		// $col_width = $column_3_width;
		// if ( $text_width > $col_width ) {
		// 	$line_count = ceil( $text_width / $col_width );
		// 	if ( $line_count > $lines ) {
		// 		$lines = $line_count;
		// 	}
		// }
		// $meta_detail = $meta['quotes_' . $inc . '_requisition_date'];
		// $text_width = $pdf->GetStringWidth($meta_detail);
		// $col_width = $column_4_width;
		// if ( $text_width > $col_width ) {
		// 	$line_count = ceil( $text_width / $col_width );
		// 	if ( $line_count > $lines ) {
		// 		$lines = $line_count;
		// 	}
		// }
		$meta_detail = $meta['quotes_' . $inc . '_price'];
		$text_width = $pdf->GetStringWidth($meta_detail);
		$col_width = $column_5_width;
		if ( $text_width > $col_width ) {
			$line_count = ceil( $text_width / $col_width );
			if ( $line_count > $lines ) {
				$lines = $line_count;
			}
		}
		// Take the max line count and point the next item's coordinates
		$offsetY += 5 * $lines;
	}
}
$offsetY += 2;
$pdf->Line($left_margin_x, $offsetY, $right_margin_x, $offsetY);
/**
 * Subtotal.
 */
$offsetY += 6;
$pdf->setXY(10, $offsetY);
$pdf->Cell(169, 5, 'Products Subtotal', 0, 0, 'R');
$pdf->setXY(180, $offsetY);
$pdf->Cell(25, 5, $meta['products_subtotal'], 0, 0, 'R');
/**
 * Contributions.
 */
if ( isset( $meta['contribution_amount'] ) && ! empty( $meta['contribution_amount'][0] ) ) {
	$account_number = $meta['contribution_account'];
	if ( isset( $meta['business_staff_status_account_number'] ) && ! empty( $meta['business_staff_status_account_number'] ) ) {
		$account_number = $meta['business_staff_status_account_number'];
	}
	$contribution_amount = '$' . number_format( $meta['contribution_amount'], 2, '.', ',' );;
	$offsetY += 6;
	$pdf->setXY(10, $offsetY);
	$pdf->MultiCell(169, 5, 'Contributions from ' . $account_number, 0, 'R');
	$pdf->setXY(180, $offsetY);
	$pdf->Cell(25, 5, $contribution_amount, 0, 0, 'R');
}
/**
 * Finish
 */
$pdf->Output('I', $meta['post_title'] . '.pdf');
?>
