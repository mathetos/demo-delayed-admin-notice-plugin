# Delayed Admin Notice Demo Plugin
A Demo Plugin to display how you might trigger a delayed admin notice.
____

This plugin demo's a simple way to create delayed admin notices in WordPress. This example specifically does the following:
1. Creates an option with the timestamp of the day/time the plugin was activated plus 30 days
2. When today's timestamp equals or is greater than the activation timestamp, a admin notice will appear only for Admins, and only on the plugins.php page.

The whole function was inspired by [Julien Liabeuf's "WP-Review-Me"](https://github.com/julien731/WP-Review-Me) library which is a far more robust way of doing something similar.

## How to Use it

* **STEP ONE**  
Add the "admin/notice.php" file into your plugin folder
* **STEP TWO**  
Add the code from plugin.php into the root file of your plugin. **NOTE** the `register_activation_hook` will only work in the root file of your plugin.
* **STEP THREE**  
Do a search/replace for "your_prefix_" and change that to whatever you like
* **STEP FOUR**  
Do a search/replace for "your-plugin-textdomain" and change that to whatever you like
* **STEP FIVE**  
Customize the strings in `notice.php` for the following
  * $plugin_name = Your plugins name
  * $donate_url = The url you want to direct your users to in order to donate
  * $review_url = The url you want to direct your users to in order to leave a review

**ALL DONE!**
