# cla-workstation-order
WordPress plugin for ordering workstations

Todo:
* Implement Bundles
* Create custom theme similar to old one
* Create navigation menu similar to old app
* Sanitize inputs for form submission -> create post process
* Remove public view template from some custom post types like programs, orders, and products
* Change which products show when selecting post objects using this demo:
  https://www.advancedcustomfields.com/resources/acf-fields-post_object-query/
  add_filter('acf/fields/post_object/query', 'my_acf_fields_post_object_query', 10, 3);
  function my_acf_fields_post_object_query( $args, $field, $post_id ) {

      // Show 40 posts per AJAX call.
      $args['posts_per_page'] = 40;

      // Restrict results to children of the current post only.
      $args['post_parent'] = $post_id;

      return $args;
  }

[Emails]
1. Order placed: email user who placed the order with "order in progress email" and the IT rep assigned to the order "order in need of attention email"
2. IT Rep checks their "Confirmed" checkbox and saves the order. If business approval needed: email department's business admin for the program
3. Business office checks their "Confirmed" box and is required to enter the account code in the following format (02-CLLA-123456-12345); email Logistics "order in need of attention email"
4. IT Rep checks their "Confirmed" checkbox and saves the order. If business approval not needed: email logistics "order in need of attention email"
5. IT Logistics checks their "Confirmed" checkbox and user is emailed with "order approval completed email"
6. Lets discuss the returned options when you are ready.... I would ideally like to change it up.
