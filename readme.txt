=== WP QRecipeWriter ===
Contributors: Floreal
Tags: api, recipes, cooking
Requires at least: 4.7
Tested up to: 4.7
Stable tag: trunk
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A wordpress plugin used by QRecipeWriter to send cooking recipes to your blog.

== Description ==

A wordpress plugin used by QRecipeWriter to send cooking recipes to your blog.

QRecipeWriter software: https://gite.flo-art.fr/cooking/qrecipewriter

It extend Wordpress API to be able to talk with QRecipeWriter software, to add
recipes to your blog, or to get recipes from your blog to the software.

Requires QRecipeWriter 4.0 or higher.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress

== Changelog ==

= 0.1 =
* First version of the plugin

== Upgrade Notice ==

= 0.1 =
First version of the plugin

== How to use ==

No special configuration is needed. But you can extend the send or the load of recipes.

To do that, create a folder "qrecipewriter" inside the wp-content folder, and copy the api.custom.php.example
file inside, renaming it api.custom.php.

Then, edit manually the desired functions:

* new_post_custom: to add some code to a send of a new recipe (or a change of an existing recipe).
  Arguments: post (the wordpress post object), params(parameters sent from QRecipeWriter, containing id != -1 if it's an existing post, else it's a new post)
* getPost_custom: to add code to a recipe getted (might not be used for QRecipeWriter)
* getPosts_custom: to add code to a get of the list of recipes (might not be used for QRecipeWriter)

Only the first custom function is expected to be changed with the official version of QRecipeWriter.
