# Workstation Ordering Application for WordPress

A GPL-2.0+ WordPress Plugin that provides a user-accessible interface for ordering workstations and accessories and a robust multi-role return / approve workflows and team-based settings. This application is developed, maintained, and operated by the Information Technology unit within the Dean's Office at the Texas A&M University College of Liberal Arts.

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

## WordPress Requirements

1. Single site install support only at this time.
2. WordPress Genesis Framework
3. [WSOrder Genesis Child Theme](https://github.tamu.edu/liberalarts-web/cla-wsorder)
4. [Advanced Custom Fields Pro](https://www.advancedcustomfields.com/pro/) Plugin

## WordPress Recommendations

1. TAMU NetID Authentication Plugin of some kind
2. [Post SMTP Plugin](https://wordpress.org/plugins/post-smtp/) - Send emails by SMTP with a logger and one-click resend button in case of failed delivery
3. [User Switching Plugin](https://github.com/johnbillion/user-switching) - For imitating user accounts in case that is desired functionality. The github.com repository is updated more frequently than the WordPress repository.
4. [Yoast Duplicate Post Plugin](https://wordpress.org/plugins/duplicate-post/) - Copy custom post types. 
5. [Simple History Plugin](https://wordpress.org/plugins/simple-history/) - For debugging and user support.

## To Do

1. Implement Active Directory user authentication, onboarding, and offboarding using either the WordPress SAML SSO plugin from OneLogin, the TAMU directory REST API [https://mqs.tamu.edu/rest/](https://mqs.tamu.edu/rest/), or both and one or more WordPress Cron tasks or manual functions.
2. Remove the following form fields from the `edit-user.php` administrative UI since they are not used: Visual Editor, Keyboard Shortcuts, Website, Biographical Info, Profile Picture, New Password, Password Reset.

# Developer Notes

## Features

This repository uses [PHP CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer/) with [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/) checks, but is NOT fully compliant with that tool yet. A pre-commit hook file is included in this repository to enforce code commits to meet this standard. I have also configured Git and Visual Studio Code files in this repository to help improve compatibility between Mac (terminal) and Windows (powershell) environments.

It also uses the FPDF library [https://packagist.org/packages/setasign/fpdf](https://packagist.org/packages/setasign/fpdf) to provide secure and data-driven PDF documents on demand without storing them on the server.

It relies on Advanced Custom Fields Pro for ease of implementation, modification, and hooks for custom fields in custom post types.

It uses SASS for CSS preprocessing and Zurb Foundation 6 as a CSS framework.

### Code Conventions

This repository applies code checks using the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/). Please refer to their documentation when you have questions about how to format your code to meet their guidelines.

Line endings are enforced as Linux-style LF "\n". This is what WordPress requires for its Subversion version control system, and Subversion is how developers submit WordPress plugins and themes to the official WordPress.org library.

## Custom User Capability Slugs

1. manage_acf_options: Determines who can see the Advanced Custom Fields options page.
2. manage_wso_options: Determines who can see the Workstation Ordering App options page.
3. All custom post types have each of their capabilities namespaced using their post type slug. Example: `create_wsorders`

## Command Line Tasks

1. `$ npm start` - Used to initialize the repository; should only be run after downloading the repository for the first time.
2. `$ npm run configphpcs` - Configure the PHP Code Sniffer module to use WordPress Coding Standards.
3. `$ npm run checkwp` - Checks the repository's WordPress files against the WordPress Coding Standards and outputs a report into your terminal.
4. `$ npm run fixwp` - Automatically fixes syntax and whitespace issues with the repository's files according to WordPress Coding Standards.
5. `$ npm run windows-configphpcs` - The same as `$ npm run configphpcs` but with Powershell-friendly syntax.
6. `$ npm run windows-start` - The same as `$ npm start` but with Powershell-friendly syntax.
7. `$ npm run windows-checkwp` - The same as `$ npm run checkwp` but with Powershell-friendly syntax.
8. `$ npm run windows-fixwp` - The same as `$ npm run fixwp` but with Powershell-friendly syntax.
9. `$ grunt` - Compile SASS files into compressed, production-ready CSS files.
10. `$ grunt develop` - Compile SASS files into expanded, sourcemapped CSS files.
11. `$ grunt watch` - Continuously watch SASS files for changes and compile them into expanded, sourcemapped CSS files every time they are saved.

### Git Tips

To add an executable file to git version control, do this: `git add --chmod=+x hooks/pre-commit && git commit -m "Add pre-commit executable hook"`

## Legacy Support

Legacy support is and will continue to be an ever-present responsibility of Information Technology professionals and this subject should be discussed with respect and understanding. One approach to legacy support which I have attempted here is to have a dedicated PHP file for targeting code that is any combination of temporary, scheduled for deprecation, or external. Currently it only targets WordPress plugins that may be replaced with other solutions eventually. Temporary code will be removed at some known or unknown point in the future. Deprecated code is scheduled for removal in a future version by the party who maintains the code. External code is provided from a source outside of one's own organization. Such code may be other WordPress plugins or third party APIs. The legacy support file is located at `src/class-legacy-support.php`.

### Potential Installation Issues

#### Windows 10 application issue: "Local" by Flywheel (local virtual server)

[https://localwp.com/help-docs/advanced/router-mode/](https://localwp.com/help-docs/advanced/router-mode/)
On two different models of Dell Windows 10 machines (Inspiron and Latitude) a Windows service was occupying Port 80 on IP 0.0.0.0:80. This interferes with the "Local" application's router functionality which uses the same port and is not configurable. It is possible that this becomes an issue as a direct result of a change made by the NodeJS *.exe file's installation process since I did not have this issue with the application until after installing NodeJS this way. ON MY PERSONAL COMPUTER I installed NodeJS using Chocolatey CLI and did not experience these conflicts with Port 80 and "Local". NOTE: SEE YOUR SYSTEM ADMINISTRATOR FOR POLICY GUIDANCE REGARDING THE INSTALLATION OF SOFTWARE ON TAMU SYSTEM DEVICES.

## Credits

1. This WordPress plugin was programmed by Zachary Watkins <zwatkins2@tamu.edu>.
2. The business process workflow and a portion of the UI specifications were designed by Pamela Luckenbill <luckenbill@tamu.edu>.
3. The original application used very different technology, was programmed by Joseph Rafferty <jrafferty@tamu.edu>, and was authored by Joseph Rafferty and Pamela Luckenbill: [https://github.tamu.edu/liberalarts-web/workstation-order](https://github.tamu.edu/liberalarts-web/workstation-order).
4. The majority of the workflow requirements for this version were preserved from the original application. However, the product, bundle, program, and department data creation and management interface is superseded by this application as it is provided by WordPress Core.
5. The visual design at the start of the project was copied from the original application, and then the icons were replaced.