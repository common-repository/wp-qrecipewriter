# What is QRecipeWriter plugin?

A wordpress plugin used by QRecipeWriter to send cooking recipes to your blog.

QRecipeWriter software: https://gite.flo-art.fr/cooking/qrecipewriter

It extend Wordpress API to be able to talk with QRecipeWriter software, to add
recipes to your blog, or to get recipes from your blog to the software.

Requires QRecipeWriter 4.0 or higher.

# How to use?

Just install the plugin. 

You can extend the send or the load of recipes.<br/>
To do that, create the a folder "qrecipewriter" into the wp-content folder, and copy
the api.custom.php.example file inside, renaming it api.custom.php.<br/>
Then, edit the desired functions:
* new_post_custom: to add some code to a send of a new recipe (or a change of an existing recipe).
  Arguments: post (the wordpress post object), params(parameters sent from QRecipeWriter, containing id != -1 if it's an existing post, else it's a new post)
* getPost_custom: to add code to a recipe getted (might not be used for QRecipeWriter)
* getPosts_custom: to add code to a get of the list of recipes (might not be used for QRecipeWriter)

Only the first custom function is expected to be changed with the official version of QRecipeWriter.
