<?php
/*
 Plugin Name: WP QRecipeWriter
 Plugin URI: https://gite.flo-art.fr/cooking/wp-qrecipewriter
 Description: A wordpress plugin used by QRecipeWriter to send cooking recipes to your blog.
 Version: 0.1
 Author: Floréal Cabanettes
 Author URI: https://www.flo-art.fr
*/

require_once dirname( __FILE__ ) ."/api.php";

wp_register_style('qrecipewriter_style', plugins_url('style.css',__FILE__ ));
wp_enqueue_style('qrecipewriter_style');
