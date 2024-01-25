=== Paid Memberships Pro - Add Member From Admin ===
Contributors: strangerstudios
Tags: paid memberships pro, pmpro, memberships
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 0.7.2

Adds a form to the admin dashboard under Memberships -> Add Member.

== Description ==

Adds a form to the admin dashboarsd under Memberships -> Add Member. This form will create a new user, give them the chosen level along with an optional expiration date, and create an order based on the price entered.

Extra fields can be added to the form through the PMPro Regsiter Helper add on. Be sure to give your fields an extra parameter addmember=>true.

It is not possible at this time to also accept credit card or PayPal payment while creating users through the add member form. This feature would be great and is something we want to happen but don't have immediate plans for now.

== Installation ==

1. Upload the `pmpro-add-member-admin` directory to the `/wp-content/plugins/` directory of your site.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Navigate to Memberships -> Add Member to add a new member.
1. When viewing the member's list or user's list in the dashboard a "+ order" link will show up under the username that can be used to add a new order for an existing user through the same form.

== Changelog ==
= 0.7.2 - 2024-01-25 =
* SECURITY: Improved sanitization and escaping throughout the plugin. #56 (@JarrydLong)
* ENHANCEMENT: Added a link to the Edit Member page if using PMPro v3.0+. #60 (@dparker1005)
* BUG FIX/ENHANCEMENT: Added some missing email template variables to the "add member added admin" email. #55 (@andrewlimaza)
* BUG FIX/ENHANCEMENT: Marking plugin as incompatible with Multiple Memberships Per User for the PMPro v3.0 update. #59 (@dparker1005)

= 0.7.1 - 2022-04-14 =
* BUG FIX: Fixed admin email template data showing incorrect user information for recently added member.
* BUG FIX: Reverted changes for the "+order" link URL, this would require further thought and plans to adjust in the future than anticipated.

= 0.7 - 2022-04-06 =
* ENHANCEMENT: General improvements to localization and escaping strings on output.
* ENHANCEMENT: Added custom email templates for the add member that sends to admin and the user created.
* BUG FIX: Minor tweak to the wording of the Send Password option to make it clearer what this means.
* BUG FIX: Fixed logic where blank password would not trigger the password reset email.
* BUG FIX: The "+order" button goes to the default add order page of Paid Memberships Pro.

= .6 =
* BUG FIX: Fixed issue where expiration dates could save incorrectly with WP 5.3+.
* BUG FIX/ENHANCEMENT: Fixed compatibility with PMPro MailChimp.

= .5 =
* BUG FIX: Fixed admin menu code to work with PMPro 2.0
* FEATURE: Sending admin change emails to the site admin when a new user is added.

= .4. =
* ENHANCEMENT: Wrapped strings for localization and added French translation. (Thanks, Thibaut Ninove)

= .3 =
* BUG FIX/ENHANCEMENT: Add Member button switches to say Add Order when in that context.
* ENHANCEMENT: Translation ready.
= 
= .2 =
* Fixed some issues with the PMPro Register Helper integration.

= .1 =
* Initial release
