<?php
/**
 * Tempalate file for displaying wsorder post type fields.
 *
 * @link       https://github.tamu.edu/liberalarts-web/cla-workstation-order/blob/master/templates/template-order-values.php
 * @author     Zachary Watkins <zwatkins2@tamu.edu>
 * @since      1.0.0
 * @package    cla-workstation-order
 * @subpackage cla-workstation-order/templates
 * @license    https://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License v2.0 or later
 */

$template_order_values = "<div class=\"col\">
  <div class=\"page-header\">
    <h2>Order {$i['program_id']}-{$i['order_number']} Details</h2>
  </div>
  <div class=\"row\">
		<div class=\"col\">
			<h2>User Details</h2>
			<dl class=\"row horizontal\">
			  <dt>First Name</dt>
			  <dd>{$i['first_name']}</dd>

			  <dt>Last Name</dt>
			  <dd>{$i['last_name']}</dd>

			  <dt>Email Address</dt>
			  <dd>{$i['email']}</dd>

			  <dt>Department</dt>
			  <dd>{$i['department']}</dd>

			  <dt>Contribution Amount</dt>
			  <dd>{$i['contribution_amount']}</dd>

			  <dt>Account Number</dt>
			      <dd>{$i['account_number']}</dd>

			  <dt>Office Location</dt>
			  <dd>{$i['office_location']}</dd>

			  <dt>Current Asset</dt>
			  <dd>{$i['current_asset']}</dd>

			  <dt>Order Comment</dt>
			  <dd>{$i['order_comment']}</dd>

			  <dt>Order Placed At</dt>
			  <dd>{$i['post_date']}</dd>

			  <dt>Program</dt>
			  <dd>{$i['program_name']}</dd>

			  <dt>Fiscal Year</dt>
			  <dd>{$i['fiscal_year']}</dd>

			</dl>

		</div>
    <div class=\"col\">
			<h3>Processing</h3>
			<dl class=\"row horizontal mt-3\">
			  <dt>IT Staff ({$i['it_staff']})</dt>
			  <dd><span class=\"badge badge-light\">{$i['it_staff_confirmed']}</span> </dd>

			  <dt>Business Staff</dt>
			  <dd><span class=\"badge badge-light\">{$i['business_staff']}</span> </dd>

			  <dt>IT Logistics</dt>
			  <dd><span class=\"badge badge-light\">{$i['it_logistics']}</span>  <br>


			  </dd>

			</dl>


		</div>
  </div>
  <div class=\"row\">
		<div class=\"col\">
      <h3>Order Items</h3>
<p><small>Note: some items in the catalog are bundles, which are a collection of products. Any bundles that you selected will be expanded as their products below.</small></p>
		</div>
  </div>
</div>";
