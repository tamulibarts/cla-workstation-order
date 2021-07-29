# cla-workstation-order
WordPress plugin for ordering workstations

Features:
1. AJAX form submission using nonces which WordPress uses to authenticate the call, for security
2. PDF rendering using the FPDF library
3. A gated approval flow that renders an order’s “approval” template if the user viewing the order needs to perform an action at that point in time, and renders its “view” template if not
4. Only allows users who submitted an order or who must approve an order to see it
5. Has custom Dashboard widgets, which are nice to use sometimes to give users information
6. Custom user roles and permissions
7. Custom post types
8. Uses Advanced Custom Fields

Todo:
1. Allow Logistics user to change the order account number, office location, and current asset number from the public page during the confirmation process
2. Custom icon
3. SVG logo in header
4. Add continuous deployment pipeline
