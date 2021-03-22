<?php

// Check if we should exit the file.
if ( ! isset( $_GET['postid'] ) ) {
	exit();
}

// Require files.
require dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php';
wp();

// Authenticate user-based access permission
$current_user              = wp_get_current_user();
$current_user_id           = (int) $current_user->ID;
$affiliated_it_reps        = get_post_meta( $_GET['postid'], 'affiliated_it_reps', true );
$affiliated_business_staff = get_post_meta( $_GET['postid'], 'affiliated_business_staff', true );
$author_id                 = (int) get_post_field( 'post_author', $_GET['postid'] );

if (
	! is_user_logged_in()
	|| (
		! in_array( $current_user_id, $affiliated_it_reps )
		&& ! in_array( $current_user_id, $affiliated_business_staff )
		&& $current_user_id !== $author_id
		&& ! current_user_can( 'wso_logistics' )
		&& ! current_user_can( 'wso_admin' )
	)
) {
	exit();
}

// Validate nonce.
check_admin_referer( 'auth-post_'.$_GET['postid'], 'token' );

// Load PDF library.
require CLA_WORKSTATION_ORDER_DIR_PATH . 'vendor/setasign/fpdf/fpdf.php';

// Gather post meta.
$post_id      = $_GET['postid'];
$post         = get_post( $post_id );
$meta         = get_post_meta( $post_id );
$meta['logo'] = CLA_WORKSTATION_ORDER_DIR_URL . 'images/logo-support-center.png';
// Extra basic order data.
$publish_date                   = strtotime( $post->post_date );
$meta['publish_date_formatted'] = date( 'M j, Y \a\t g:i a', $publish_date );
$meta['post_title']             = $post->post_title;
$meta['now']                    = date( 'M j, Y \a\t g:i a' );
$meta['program_name']           = get_the_title( $meta['program'] );
$meta['program_fiscal_year']    = get_post_meta( $meta['program'], 'fiscal_year', true );
// Extra author data.
$author                    = get_userdata( $post->post_author );
$meta['author']            = $author->data->display_name;
$meta['first_name']        = get_user_meta( $post->post_author, 'first_name', true );
$meta['last_name']         = get_user_meta( $post->post_author, 'last_name', true );
$meta['author_email']      = $author->data->user_email;
$meta['author_department'] = get_the_title( $meta['author_department'] );
// Extra IT Rep data.
$it_rep_user                  = get_user_by( 'id', intval( $meta['it_rep_status_it_rep'] ) );
$meta['it_rep_status_it_rep'] = $it_rep_user->data->display_name;
$it_rep_confirm_date          = strtotime( $meta['it_rep_status_date'] );
$meta['it_rep_status_date']   = 'Confirmed - ' . date( 'M j, Y \a\t g:i a', $it_rep_confirm_date );
// Extra Business Staff data.
if ( '0' === $meta['business_staff_status_confirmed'] ) {
	$meta['business_staff_status_date'] = 'Not required';
} else {
  $business_staff_confirm_date                  = strtotime( $meta['business_staff_status_date'] );
	$meta['business_staff_status_date']           = 'Confirmed - ' . date( 'M j, Y \a\t g:i a', $business_staff_confirm_date );
  $business_user                                = get_user_by('id', intval( $meta['business_staff_status_business_staff'] ) );
  $meta['business_staff_status_business_staff'] = $business_user->data->display_name;
}
// Extra Logistics data.
$logistics_confirm_date           = strtotime( $meta['it_logistics_status_date'] );
$meta['it_logistics_status_date'] = 'Confirmed - ' . date( 'M j, Y \a\t g:i a', $logistics_confirm_date );
// Modify purchase item data.
$meta['products_subtotal'] = '$' . number_format( $meta['products_subtotal'], 2, '.', ',' );
for ($inc=0; $inc < $meta['order_items']; $inc++) {
	$price                            = $meta["order_items_{$inc}_price"];
	$meta["order_items_{$inc}_price"] = '$' . number_format( $price, 2, '.', ',' );
	$date                             = $meta["order_items_{$inc}_requisition_date"];
	if ( ! empty( $date ) ) {
		$date                                        = strtotime( $date );
		$date                                        = date('M j, Y', $date);
		$meta["order_items_{$inc}_requisition_date"] = $date;
	}
}

// Generate the PDF.
class PDF extends FPDF
{
	// Page header
	function Header() {
    // Logo
    global $meta;
    $this->Image($meta['logo'],10,6,70);
    // Arial bold 15
    $this->SetFont('Arial','B',15);
    // Move to the right
    $this->setXY(-120,8);
    // Title
    $this->Cell(110,6,'Order #' . $meta['post_title'],0,0,'R');
    $this->setXY(-120,14);
    $this->SetFont('Arial','',10);
    $this->Cell(110,4,$meta['publish_date_formatted'],0,0,'R');
    $this->setXY(-120,18);
    $this->Cell(110,4,$meta['program_name'],0,0,'R');
    $this->setXY(-120,22);
    $this->Cell(110,4,$meta['program_fiscal_year'],0,0,'R');
    // Line break
    $this->Ln(20);
	}

	// Page footer
	function Footer() {
    global $meta;
    // Position at 1.5 cm from bottom
    $this->SetY(-15);
    // Arial italic 8
    $this->SetFont('Arial','',8);
    // Draw line
    $this->Line(10, 266, 206, 266);
    // Page number
    $this->MultiCell(196,10,$meta['program_name'] . '   |   ' . $meta['post_title'] . '   |   ' . $meta['first_name'] . ' ' . $meta['last_name'] . ' - ' . $meta['author_department'] . '   |   Generated at: ' . $meta['now'],0,'C');
	}
}

// Instanciation of inherited class
$pdf = new PDF( 'P', 'mm', 'Letter' );
$pdf->AliasNbPages();
$pdf->AddPage();
// Program Title
$pdf->SetFont('Arial','B',18);
$pdf->setXY(10,30);
$pdf->Write(10, $meta['program_name'] . ' Order');
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
$column_1_width = 0.5 * $full_width;
$column_2_width = 0.18 * $full_width;
$column_3_width = 0.08 * $full_width;
$column_4_width = 0.14 * $full_width;
$column_5_width = 0.1 * $full_width;
$item_length = intval( $meta['order_items'] );
// Heading.
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
	$pdf->MultiCell($column_1_width, 5, $meta['order_items_' . $inc . '_item'], 0);
	// SKU.
	$offsetX += $column_1_width;
	$pdf->setXY($offsetX, $offsetY);
	$pdf->MultiCell($column_2_width, 5, $meta['order_items_' . $inc . '_sku'], 0);
	// Requisition Number.
	$offsetX += $column_2_width;
	$pdf->setXY($offsetX, $offsetY);
	$pdf->MultiCell($column_3_width, 5, $meta['order_items_' . $inc . '_requisition_number'], 0);
	// Requisition Date.
	$offsetX += $column_3_width;
	$pdf->setXY($offsetX, $offsetY);
	$pdf->MultiCell($column_4_width, 5, $meta['order_items_' . $inc . '_requisition_date'], 0);
	// Price.
	$offsetX += $column_4_width;
	$pdf->setXY($offsetX, $offsetY);
	$pdf->MultiCell($column_5_width, 5, $meta['order_items_' . $inc . '_price'], 0);
	// Figure out the maximum line count among all details of this item.
	$lines = 1;
	$meta_detail = $meta['order_items_' . $inc . '_item'];
	$text_width = $pdf->GetStringWidth($meta_detail);
	$col_width = $column_1_width;
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
	$offsetY += ( 5 * $line_count ) + 5;
}
$offsetY += 2;
$pdf->Line($left_margin_x, $offsetY, $right_margin_x, $offsetY);
/**
 * Subtotal
 */
$offsetY += 6;
$pdf->setXY(146, $offsetY);
$pdf->Cell(33, 5, 'Products Subtotal', 0, 0);
$pdf->setXY(180, $offsetY);
$pdf->Cell(25, 5, $meta['products_subtotal'], 0, 0, 'R');
/**
 * Finish
 */
$pdf->Output('D','order-receipt-' . $meta['post_title'] . '.pdf');
?>
