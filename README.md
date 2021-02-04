# cla-workstation-order
WordPress plugin for ordering workstations

Todo:
* Create lead and approval email system for new orders
* Implement Bundles
* Create custom theme similar to old one
* Create navigation menu similar to old app
* Ensure users can edit their own orders but cannot change who they are assigned to

* Once user submits order ->
	subject: Workstation Order Received
	to: end user
	body:
	<p>Howdy,</p>
  <p>Liberal Arts IT has received your order.</p>

  <p>Your {$program_name} order will be reviewed to ensure all necessary information and funding is in place.</p>
  <p>
    Following review, your workstation request will be combined with others from your department to create a consolidated {$program_name} purchase. Consolidated orders are placed to maximize efficiency. Your order will be processed and received by IT Logistics in 4-6 weeks, depending on how early in the order cycle you make your selection. Once received, your workstation will be released to departmental IT staff who will then image your workstation, install software and prepare the device for delivery. These final steps generally take one to two days.
  </p>
  <p>You may view your order online at any time using this link: {$order_url}.</p>

  <p>
    Have a great day!
    <em>-Liberal Arts IT</em>
  </p>

	subject: Workstation Order Received
	to: it rep
	body:
	<p>
    <strong>There is a new {$program_name} order that requires your attention.</strong>
  </p>
  <p>
    Please review this order carefully for any errors or omissions, then confirm it to pass along in the ordering workflow, or return it to the customer with your feedback and ask that they correct the order.
  </p>
  <p>
    You can view the order at this link: {$admin_order_url}.
  </p>
  <p>
    Get back to work,<br />
    <em>- &lt;3 Garrett</em>
  </p>

* Once IT Rep has confirmed, if business approval needed ->
  subject: [{$order_id}] Workstation Order Approval - {$department_abbreviation} - {$end_user}
  to: business user
  body:
	<p>
    Howdy<br />
    <strong>There is a new {$program_name} order that requires your attention for financial resolution.</strong></p>
  <p>
    {$user_name} elected to contribute additional funds toward their order in the amount of {$addfund_amount}. An account reference of "{$addfund_account}" needs to be confirmed or replaced with the correct account number that will be used on the official requisition.
  </p>
  <p>
    You can view the order at this link: {$admin_order_url}.
  </p>
  <p>
    Have a great day!<br />
    <em>-Liberal Arts IT</em>
  </p>

* If business approval needed, once Business Staff has confirmed ->
	subject: [{$order_id}] Workstation Order Approval - {$department_abbreviation} - {$end_user}
	to: logistics user
	body:
	<p><strong>There is a new {$program_name} order that requires your approval.</strong></p>
  <p>
    Please review this order carefully for any errors or omissions, then approve order for purchasing.
  </p>
  <p>
    You can view the order at this link: {$admin_order_url}.
  </p>
  <p>
    Get back to work,<br />
    <em>- &lt;3 Garrett</em>
  </p>

* Once logistics has confirmed ->
	subject: [{$order_id}] Workstation Order Approval - {$department_abbreviation} - {$end_user}
	to: end user
	body: confirmation of approval.

* If status changed to "Returned" ->
	subject: [{$order_id}] Returned Workstation Order - {$order.department.abbreviation} - {$order.user_name}
	to: end user
	cc: whoever set it to return
	body:
	<p>
    Howdy,
  </p>
  <p>
    Your {$program_name} order has been returned by {$actor_name}. This could be because it was missing some required information, missing a necessary part, or could not be fulfilled as is. An explanation should appear below in the comments.
  </p>
  <p>
    Comments from {$actor_name}: {$returned_comment}
  </p>
  <p>
    Next step is to resolve your order's issue with the person who returned it (who has been copied on this email for your convenience), then correct the existing order. You may access your order online at any time using this link: {$order_url}.
  </p>

  <p>Have a great day!</p>
  <p><em>-Liberal Arts IT</em></p>

* If status changed to "Returned" ->
	subject: [{$order_id}] Returned Workstation Order - {$order.department.abbreviation} - {$order.user_name}
	to: if it_rep is assigned and approved, email them; if business_admin is assigned and approved, email them
  body:
  <p>
    Howdy,
  </p>
  <p>
    The {$program_name} order for {$user_name} has been returned by {$actor_name}. An explanation should appear below in the comments.
  </p>
  <p>
    Comments from {$actor_name}: {$returned_comment}
  </p>
  <p>
    {$user_name} will correct the order and resubmit.
  </p>
  <p>
    You can view the order at this link: {$admin_order_url}.
  </p>
  <p>
    Have a great day!<br />
    <em>-Liberal Arts IT</em>
  </p>
