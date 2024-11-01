<?php

define('QRECIPEWRITER_CUSTOM_URL', WP_CONTENT_DIR . "/qrecipewriter/api.custom.php");

if (file_exists(QRECIPEWRITER_CUSTOM_URL)) {
	require_once QRECIPEWRITER_CUSTOM_URL;
}

class qrecipewriter_functions {
    static function tempdir($dir=NULL,$prefix="wpfile") {
        $template = "{$prefix}XXXXXX";
        if (($dir) && (is_dir($dir))) { $tmpdir = "--tmpdir=$dir"; }
        else { $tmpdir = '--tmpdir=' . sys_get_temp_dir(); }
        return exec("mktemp -d $tmpdir $template");
    }

    static function is_assoc($var)
    {
        return is_array($var) && array_diff_key($var,array_keys(array_keys($var)));
    }

    /* Import media from url
     *
     * @param string $file_url URL of the existing file from the original site
     * @param int $post_id The post ID of the post to which the imported media is to be attached
     *
     * @return boolean True on success, false on failure
     */
    static function add_media_to_post($media_file, $post_id, $user_id=1) {
        // Check the type of file. We'll use this as the 'post_mime_type'.
        $filetype = wp_check_filetype( basename( $media_file ), null );

        // Get the path to the upload directory.
        $wp_upload_dir = wp_upload_dir();

        // Prepare an array of post data for the attachment.
        $attachment = array(
            'guid'           => $wp_upload_dir['url'] . '/' . basename( $media_file ),
            'post_mime_type' => $filetype['type'],
            'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $media_file ) ),
            'post_content'   => '',
            'post_status'    => 'inherit',
            'post_author' => $user_id
        );

        // Insert the attachment.
        $attach_id = wp_insert_attachment( $attachment, $media_file, $post_id );

        // Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
        require_once( ABSPATH . 'wp-admin/includes/image.php' );

        // Generate the metadata for the attachment, and update the database record.
        $attach_data = wp_generate_attachment_metadata( $attach_id, $media_file );
        wp_update_attachment_metadata( $attach_id, $attach_data );

        return $attach_id;
    }

    static function restore_filters() {
        add_filter('content_save_pre', 'wp_filter_post_kses');
        add_filter('content_filtered_save_pre', 'wp_filter_post_kses');
    }

    static function replace_files($files_replaces, $htmlCode) {
        $wp_upload_dir = wp_upload_dir()["url"];
        foreach ($files_replaces as $orig => $final) {
            $htmlCode = str_replace("[###IMG-SRC###|$orig]", $wp_upload_dir . "/" . $final, $htmlCode);
        }
        return $htmlCode;
    }

    static function get_value($post, $key) {
        if (array_key_exists($key, $post)) {
            return $post[$key];
        }
        return null;
    }
}

function qrecipewriter_getPosts($data) {
	$ignoreCats = array("Conseils & Techniques", "Infos", "Non classé");

	$user = get_user_by("slug", $data["user"])->ID;
	if (empty($user)) {
		return new WP_Error( 'awesome_no_author', 'Invalid author', array( 'status' => 404 ) );
	}
	$plats = get_posts("author=$user&posts_per_page=-1&post_status=any");

	$posts = array();

	foreach ($plats as $post) {
                $cats = wp_get_post_categories($post->ID, array('fields' => 'names'));
	        $isGood = true;
        	foreach ($cats as &$cat) {
                	$cat = str_replace("&amp;", "&", $cat);
                        if (in_array($cat, $ignoreCats)) {
                        	$isGood = false;
	        	}
        	}
                if ($isGood) {
		       	$post_it = array(
		    	        "title" => $post->post_title,
		                "id" => $post->ID,
		        	"categories" => $cats
		       	);
	        	array_push($posts, $post_it);
		}
	}

	if (function_exists("qrecipewriter_getPosts_custom")) {
		$posts = qrecipewriter_getPosts_custom( $posts, $data );
	}

	return $posts;

}

function qrecipewriter_getPost($data) {
	$id = $data["id"];
	$post = get_post($id);
	if (empty($post)) {
                return new WP_Error( 'awesome_no_post', 'Invalid post', array( 'status' => 404 ) );
        }

  	$thumbnailId = get_post_thumbnail_id($post);
  	$thumbnailFile = wp_get_attachment_url($thumbnailId);
  	$tags = wp_get_post_tags($id);

	$response = array(
    		"content" => $post->post_content,
    		"title" => $post->post_title,
    		"status" => $post->post_status,
    		"published" => $post->post_date,
    		"thumbnailFile" => $thumbnailFile,
    		"thumbnailFilename" => basename($thumbnailFile),
    		"categories" => wp_get_post_categories($post->ID, array('fields' => 'names')),
			"id" => $post->ID,
			"tags" => $tags
  	);

	if (function_exists("qrecipewriter_getPost_custom")) {
		$response += qrecipewriter_getPost_custom($post, $data);
	}

	return $response;
}

function qrecipewriter_new_post() {
        $params = array();
		$files = $_FILES;

		$files_replaces = array();

        $params["title"] = sanitize_text_field(qrecipewriter_functions::get_value($_POST, "title"));
        $params["content"] = filter_var(qrecipewriter_functions::get_value($_POST, "content"), FILTER_UNSAFE_RAW);
        $params["excerpt"] = sanitize_textarea_field(qrecipewriter_functions::get_value($_POST, "excerpt"));
        $params["published"] = sanitize_text_field(qrecipewriter_functions::get_value($_POST, "published"));
        $params["categories"] = sanitize_text_field(qrecipewriter_functions::get_value($_POST, "categories"));
        $params["tags"] = sanitize_text_field(qrecipewriter_functions::get_value($_POST, "tags"));
        $params["author"] = sanitize_user(qrecipewriter_functions::get_value($_POST, "author"));
        $params["id"] = filter_var(qrecipewriter_functions::get_value($_POST, "id"), FILTER_SANITIZE_NUMBER_INT);
        if (filter_var($params["id"], FILTER_VALIDATE_INT) === false) {
            unset($params["id"]);
        }

        if (array_key_exists("tps_prep", $_POST) && is_numeric($_POST["tps_prep"])) {
            $params["tps_prep"] = $_POST["tps_prep"];
        }
        if (array_key_exists("tps_cuis", $_POST) && is_numeric($_POST["tps_cuis"])) {
            $params["tps_cuis"] = $_POST["tps_cuis"];
        }
        if (array_key_exists("tps_rep", $_POST) && is_numeric($_POST["tps_rep"])) {
            $params["tps_rep"] = $_POST["tps_rep"];
        }

		$required_keys = array( "title", "content", "excerpt", "published", "categories", "tags");
		foreach ($required_keys as $key) {
				if (is_null($params[$key])) {
					return array(
						"success" => false,
						"message" => "Key not found: " . $key
					);
				}
		}
		if (!array_key_exists("main_picture", $files) && (!array_key_exists("id", $params) || $params["id"] == -1)) {
			return array(
				"success" => false,
				"message" => "No main picture given"
			);
		}

		$user = get_user_by("login", $params["author"]);
		$user_id = $user->ID;

		remove_filter('content_save_pre', 'wp_filter_post_kses');
		remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');

		$mainPicture = null;
		$media_path = wp_upload_dir()["path"];

		if (array_key_exists("main_picture", $files)) {
				$mainPicture = $media_path . "/" . $files["main_picture"]["name"];
				$posExt = strrpos($mainPicture, ".");
				$mainPicture_base = substr($mainPicture, 0, $posExt);
				$ext = substr($mainPicture, $posExt);
				$nb=2;
				while(file_exists($mainPicture)) {
						$mainPicture = $mainPicture_base . "-$nb" . $ext;
						$nb++;
				}
				$files_replaces[$files["main_picture"]["name"]] = basename($mainPicture);
		}

		$otherPictures_final = array();

		if (array_key_exists("other_pictures", $files)) {
				$otherPictures = $files["other_pictures"];
				if (qrecipewriter_functions::is_assoc($otherPictures)) {
						$otherPictures = array($otherPictures);
				}
				foreach ($otherPictures as $otherPicture) {
						$oPict = $media_path . "/" . $otherPicture["name"];
						$posExt = strrpos($oPict, ".");
						$oPict_base = substr($oPict, 0, $posExt);
						$ext = substr($oPict, $posExt);
						$nb=2;
						while(file_exists($oPict)) {
								$oPict = $oPict_base . "-$nb" . $ext;
								$nb++;
						}
						$files_replaces[$otherPicture["name"]] = basename($oPict);
						$otherPictures_final[$otherPicture["tmp_name"]] = $oPict;
				}
		}

		$post = array();
		if (!array_key_exists("id", $params) || $params["id"] == -1) {
				$post["post_author"] = $user_id;
		}
		else {
				$post = get_post($params["id"], "ARRAY_A");
		}
		$categories = array();
		foreach (explode(",", $params["categories"]) as $cat_name) {
				$cat_id = get_cat_ID($cat_name);
				if ($cat_id !== 0) {
						$categories[] = $cat_id;
				}
		}

		$htmlCode = qrecipewriter_functions::replace_files($files_replaces, $params["content"]);

		$post["post_title"] = $params["title"];
		$post["post_content"] = $htmlCode;
		$post["post_excerpt"] = $params["excerpt"];
		$post["post_category"] = $categories;
		$post["tags_input"] = $params["tags"];
		$post["post_status"] = $params["published"] ? "publish" : "draft";
		$error = false;
		$id = wp_insert_post($post, $error);
		if ($error) {
				qrecipewriter_functions::restore_filters();
				return array(
					"success" => false,
					"message" => "Unable to add post: " . $id
				);
		}
		$post = get_post($id, "ARRAY_A");
		$uploadDir = wp_upload_dir()["path"];
		$tmp = qrecipewriter_functions::tempdir();
		if (!is_null($mainPicture)) {
				if (move_uploaded_file($files["main_picture"]["tmp_name"], $mainPicture)) {
						$attach_id = qrecipewriter_functions::add_media_to_post($mainPicture, $post["ID"], $user_id);
						set_post_thumbnail($post["ID"], $attach_id);
				}
				else {
					wp_delete_post($post["id"], true);
					qrecipewriter_functions::restore_filters();
					return array(
						"success" => false,
						"message" => "Unable to add post: upload pictures failed (1)"
					);
				}
		}

		foreach ($otherPictures_final as $oPict_tmp => $oPict) {
			if (move_uploaded_file($oPict_tmp, $oPict)) {
					$attach_id = qrecipewriter_functions::add_media_to_post($oPict, $post["ID"], $user_id);
			}
			else {
				wp_delete_post($post["id"], true);
				qrecipewriter_functions::restore_filters();
				return array(
					"success" => false,
					"message" => "Unable to add post: upload pictures failed (1)"
				);
			}
		}

		if (function_exists("qrecipewriter_new_post_custom")) {
            qrecipewriter_new_post_custom($post, $params);
		}

		qrecipewriter_functions::restore_filters();
		return array(
			"success" => true,
			"post_id" => $post["ID"],
			"url" => get_permalink($post["ID"])
		);
}

function qrecipewriter_check_authentication( WP_REST_Request $request ) {

		$serverparams = apache_request_headers();
		$authorization = $serverparams['Authorization'];

    if($authorization!='')
    {
        $authvalues = explode(' ',$authorization);
        $usernamepass = $authvalues[1];
        $usernamepassdecode = base64_decode($usernamepass);
        $creds = explode(':',$usernamepassdecode);
        $username = $creds[0];

        $pass = $creds[1];
        $user = get_user_by('login',$username);
        if(wp_check_password($pass,$user->user_pass,$user->ID))
            return true;
        else
            return false;

    }
    else{
				error_log("Wordpress QRecipeWriter API: No headers found on POST login");
        return false;
    }

}

add_action( 'rest_api_init', function () {
	register_rest_route( 'qrecipewriter/v1', '/posts/(?P<user>[\w-]+)', array(
		'methods' => 'GET',
		'callback' => 'qrecipewriter_getPosts',
	) );

	register_rest_route( 'qrecipewriter/v1', '/posts/', array(
		'methods' => 'POST',
		'callback' => 'qrecipewriter_new_post',
		//'permission_callback' => array( $this, 'create_item_permissions_check' ),
		'permission_callback' => function($request) {
      			return qrecipewriter_check_authentication($request);
		}
	) );

	register_rest_route( 'qrecipewriter/v1', '/post/(?P<id>\d+)', array(
      'methods' => 'GET',
      'callback' => 'qrecipewriter_getPost',
  ) );
} );
?>
