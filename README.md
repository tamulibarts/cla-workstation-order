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

## WordPress Requirements
1. Single site install support only at this time.

## Installation
1. Download the latest release here: [https://github.tamu.edu/liberalarts-web/cla-workstation-order/releases/latest/](https://github.tamu.edu/liberalarts-web/cla-workstation-order/releases/)
2. Upload the plugin to your site via the admin dashboard plugin upload panel.

## Developer Notes
Please refer to the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/) when you have questions about how to format your code.
(Installing a Version Control System)[https://make.wordpress.org/core/handbook/tutorials/installing-a-vcs/]

### Github REST API
Eventually Github will deprecate the REST API v2 used in Gruntfile.coffee to upload a Github Release for the repository. The next version is v3 and requires using the same access token in a different connection approach. These tokens are temporary and used in case they are compromised. Example: `curl -H "Authorization: token OAUTH-TOKEN" -H "Accept: application/vnd.github.v3+json" https://api.github.com`

### Features
This repository uses [PHP CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer/) with WordPress coding standards checks and a pre-commit hook. Pretty neat! I have made efforts to make this repository work between my Mac (terminal) and Windows (powershell) environments with the VSCode editor.

### Conventions
Line endings are enforced as WordPress-style CRLF "\r\n". This is what WordPress requires for its Subversion version control system, which is what developers must use to submit their WordPress plugins and themes to the official WordPress public extension library.

The Github Release Key is stored in a file within this repository's root directory on my computer in a file named "env.json". The contents of that file are: {"RELEASE_KEY":"##########################"}

### Lessons Learned
To add an executable file to git version control, do this: `git add --chmod=+x hooks/pre-commit && git commit -m "Add pre-commit executable hook"`

### Developer Potential Installation Issues
#### Windows 10
On Windows 10 I had to disable the Windows IIS service which was running on IP 0.0.0.0:80 and interfered with my local development environment application's router functionality. The application name is Local, by Flywheel, which is owned by WP Engine.
