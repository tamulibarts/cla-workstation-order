# Workstation Ordering Application for WordPress

Created by Zachary Watkins, zwatkins2@tamu.edu

A GPL-2.0+ WordPress Plugin that facilitates workstation ordering operations overseen by Information Technology Logistics with the Dean's Office at the Texas A&M University College of Liberal Arts.

The original application was created by Joseph Rafferty using Ruby on Rails: [https://github.tamu.edu/liberalarts-web/workstation-order](https://github.tamu.edu/liberalarts-web/workstation-order)

## Features

1. AJAX form submission using nonces which WordPress uses to authenticate the call, for security
2. AJAX file uploads to the WordPress media library converted to attachment post types
3. PDF rendering using the FPDF library
4. A gated approval flow that renders an order’s “approval” template if the user viewing the order needs to perform an action at that point in time, and renders its “view” template if not
5. Only allows users who submitted an order or who must approve an order to see it
6. Has custom Dashboard widgets, which are nice to use sometimes to give users information
7. Custom user roles and permissions
8. Custom post types
9. Uses Advanced Custom Fields for order fields and the Settings page
10. Affiliated business staff can view orders, will be CC'd on emails sent to the business staff responsible for approving an order.

## Custom User Capabilities

1. manage_acf_options: Determines who can see the Advanced Custom Fields options page.
2. manage_wso_options: Determines who can see the Workstation Ordering App options page.

## Notes

1. The application sends emails to users based on configurations in the SMTP settings page.

## To Do

1. Implement Active Directory user authentication, onboarding, and offboarding using either the WordPress SAML SSO plugin from OneLogin, the TAMU directory REST API [https://mqs.tamu.edu/rest/](https://mqs.tamu.edu/rest/), or both and one or more WordPress Cron tasks or manual functions.

## WordPress Requirements

1. Single site install support only at this time.
2. [https://github.tamu.edu/liberalarts-web/cla-wsorder](WSOrder Genesis Child Theme)
3. TAMU CAS Authentication Plugin
4. [https://www.advancedcustomfields.com/pro/](Advanced Custom Fields Pro) Plugin
5. [https://wordpress.org/plugins/post-smtp/](Post SMTP) Plugin for secure email delivery with an email log and one-click resending of failed emails
6. [https://github.com/johnbillion/user-switching](User Switching) Plugin (Github.com link, updated more frequently than WordPress repository plugin)
7. [https://wordpress.org/plugins/duplicate-post/](Yoast Duplicate Post) Plugin
8. [https://wordpress.org/plugins/simple-history/](Simple History) Plugin for debugging and user support

## Installation

1. Download the latest release here: [https://github.tamu.edu/liberalarts-web/cla-workstation-order/releases/latest/](https://github.tamu.edu/liberalarts-web/cla-workstation-order/releases/)
2. Upload the plugin to your site via the admin dashboard plugin upload panel.

## Developer Notes

Please refer to the [https://developer.wordpress.org/coding-standards/wordpress-coding-standards/](WordPress Coding Standards) when you have questions about how to format your code.

[https://make.wordpress.org/core/handbook/tutorials/installing-a-vcs/](Installing a Version Control System)

### Developer Features

This repository uses [PHP CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer/) with WordPress coding standards checks but is NOT fully compliant with that tool yet. A pre-commit hook file is included in this repository to enforce code commits to meet this standard. I have also configured Git and Visual Studio Code files in this repository to help improve compatibility between Mac (terminal) and Windows (powershell) environments.

It also uses the FPDF library [https://packagist.org/packages/setasign/fpdf](https://packagist.org/packages/setasign/fpdf) to provide secure and data-driven PDF documents on demand without storing them on the server.

### Code Conventions

Line endings are enforced as LF "\n". This is what WordPress requires for its Subversion version control system, which is what developers must use to submit their WordPress plugins and themes to the official WordPress public extension library.

### Tips Learned From This Project

To add an executable file to git version control, do this: `git add --chmod=+x hooks/pre-commit && git commit -m "Add pre-commit executable hook"`

### Potential Installation Issues

#### Windows 10 IIS Server Port 80 Conflict with "Local" application by Flywheel

[https://localwp.com/help-docs/advanced/router-mode/](https://localwp.com/help-docs/advanced/router-mode/)
On one (but not all) Dell Windows 10 machine I had to disable the Windows IIS service which was running on IP 0.0.0.0:80 and interfered with my local development environment application's router functionality. The application name is Local, by Flywheel, which is owned by WP Engine.
