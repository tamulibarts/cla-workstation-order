# Workstation Ordering Application for WordPress
For the Information Technology unit of the Dean's Office at the Texas A&M University College of Liberal Arts

Features:
1. AJAX form submission using nonces which WordPress uses to authenticate the call, for security
2. AJAX file uploads to the WordPress media library converted to attachment post types
3. PDF rendering using the FPDF library
4. A gated approval flow that renders an order’s “approval” template if the user viewing the order needs to perform an action at that point in time, and renders its “view” template if not
5. Only allows users who submitted an order or who must approve an order to see it
6. Has custom Dashboard widgets, which are nice to use sometimes to give users information
7. Custom user roles and permissions
8. Custom post types
9. Uses Advanced Custom Fields
10. Affiliated business staff can view orders, will be CC'd on emails sent to the business staff responsible for approving an order.

Notes:
1. The application sends emails to users from the email address in the General Settings page's Administration Email Address field.

