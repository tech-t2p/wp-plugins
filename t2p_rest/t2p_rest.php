<?php
if ( ! defined( "ABSPATH" ) ) {
	exit;
}
if ( ! defined( "MY_PLUGIN_DIR_PATH" ) ) {
	define( "MY_PLUGIN_DIR_PATH", plugin_dir_path( __FILE__ ) );
}
if ( ! defined( "MY_PLUGIN_URL" ) ) {
	define( "MY_PLUGIN_URL", plugins_url() . "/t2p_rest" );
}

global $wpdb, $table_prefix;

if ( ! isset( $wpdb ) ) {
	require_once( '../../../../wp-config.php' );
	require_once( '../../../../wp-includes/wp-db.php' );
	require_once( '../../../../wp-content/themes/time2play/includes/BlockType.php' );
	require_once( '../../../../wp-content/themes/time2play/includes/PostType.php' );
}

require_once( MY_PLUGIN_DIR_PATH . 'includes/functions.php' );
require_once( MY_PLUGIN_DIR_PATH . 'includes/sqlHelper.php' );

/**
 * plugin name: T2P Custom Translations APIs
 * Description: A Custom Plugin For Fixed Strings which Extend WPML Capabilities and provides RestApis for TranslationStrings Table too
 * version: 1.0
 * author: KafeRocks
 */

function string_translations() {
	global $wpdb;
	$wpdb->wp_icl_strings             = 'wp_icl_strings';
	$wpdb->wp_icl_string_translations = 'wp_icl_string_translations';
	$query                            = "SELECT * FROM " . strings_table_name() . " WHERE context = 'common-strings'";
	$results                          = $wpdb->get_results( $query );

	$words = array();
	foreach ( $results as $key => $row ) {
		if ( strlen( $row->name ) > 0 ) {
			$word  = array();
			$trans = apply_filters( 'wpml_active_languages', null );
			foreach ( $trans as $tran ) {
				if ( $tran['code'] == 'en' ) {
					$word[ $tran['code'] ] = $row->value;
				} else {
					$query   = "SELECT * FROM " . strings_translations_table_name() . " WHERE string_id = " . $row->id . " AND language Like '" . $tran['code'] . "'";
					$results = $wpdb->get_results( $query );
					if ( count( $results ) > 0 ) {
						$word[ $tran['code'] ] = $results[0]->value;
					} else {
						$word[ $tran['code'] ] = null;
					}
				}
			}
			$words[ $row->value ] = $word;
		}
	}

	return $words;
}


function my_include_assets() {
	$pages_includes = array( "string-translations" );

	if ( isset( $_GET['page'] ) ) {
		$currentPage = $_GET['page'];
		if ( in_array( $currentPage, $pages_includes ) ) {

			//styles
			wp_enqueue_style( "bootstrap", MY_PLUGIN_URL . "/assets/css/bootstrap.css", '' );
			wp_enqueue_style( "datatable", MY_PLUGIN_URL . "/assets/css/jquery.dataTables.min.css", '' );

			//scripts
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'bootstrap.min.js', MY_PLUGIN_URL . '/assets/js/bootstrap.min.js', '', true );
			wp_enqueue_script( 'validation.min.js', MY_PLUGIN_URL . '/assets/js/jquery.validate.min.js', '', true );
			wp_enqueue_script( 'datatable.min.js', MY_PLUGIN_URL . '/assets/js/jquery.dataTables.min.js', '', true );

			wp_enqueue_style( 'select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css' );
			wp_enqueue_script( 'select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js', array( 'jquery' ) );

			wp_enqueue_script( 'script.js', MY_PLUGIN_URL . '/assets/js/script.js', '', true );
			wp_localize_script( "script.js", "myajaxurl", array( 'ajaxurl' => admin_url( "admin-ajax.php" ) ) );
		}
	}
}

function get_translation_for_string() {
	global $wpdb;

	if ( isset( $_POST['string_id'] ) && ! empty( $_POST['string_id'] ) && isset( $_POST['lang_tag'] ) && ! empty( $_POST['lang_tag'] ) && isset( $_POST['lang_iso_code'] ) && ! empty( $_POST['lang_iso_code'] ) ) {
		$string_id     = intval( $_POST['string_id'] );
		$lang          = $_POST['lang_tag'];
		$lang_iso_code = $_POST['lang_iso_code'];

		// Fetch Current language has translation
		$this_lang_translation = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `" . strings_translations_table_name() . "` WHERE `string_id` = %d AND `language` LIKE %s", $string_id, $lang_iso_code
				, ARRAY_A )
		);

		// Fetch parent language has translation
		$splitedLangs = explode( "-", $lang );
		if ( count( $splitedLangs ) > 1 && strtolower( $splitedLangs[0] ) != strtolower( $splitedLangs[1] ) && strtolower( $lang ) != get_lang_parent_tag_name_from_first_part( strtolower( $splitedLangs[0] ) ) ) {

			$parent_tag_name = get_lang_parent_tag_name_from_first_part( strtolower( $splitedLangs[0] ) );
			$parent_iso_code = get_iso_code_for_language_tag( $parent_tag_name );

			if ( $parent_iso_code == 'en' ) {
				$parent_lang_translation = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT * FROM `" . strings_table_name() . "` WHERE `id` = %d AND LOWER(`language`) = %s", $string_id, $parent_iso_code
					), ARRAY_A );
			} else {
				$parent_lang_translation = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT * FROM `" . strings_translations_table_name() . "` WHERE `string_id` = %d AND LOWER(`language`) = %s", $string_id, $parent_iso_code
					), ARRAY_A );
			}


		} else if ( strtolower( $splitedLangs[0] ) == strtolower( $splitedLangs[1] ) || strtolower( $lang ) == get_lang_parent_tag_name_from_first_part( strtolower( $splitedLangs[0] ) ) ) {
			$parent_tag_name = get_lang_parent_tag_name_from_first_part( strtolower( $splitedLangs[0] ) );
			$parent_iso_code = get_iso_code_for_language_tag( $parent_tag_name );
			// en is main language so it will be in string key table
			if ( $parent_iso_code == 'en' ) {
				$parent_lang_translation = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT * FROM `" . strings_table_name() . "` WHERE `id` = %d AND LOWER(`language`) = %s", $string_id, $parent_iso_code
					), ARRAY_A );
			}
		}

		// making the response include it's own translation if exists and also parent translation if exists
		$response           = array();
		$response['this']   = $this_lang_translation;
		$response['parent'] = $parent_lang_translation;
		echo json_encode( $response );
	}
	wp_die();
}


function set_translation_for_string() {
	global $wpdb;

	// stringTranslationId
	if ( isset( $_POST['string_id'] ) && ! empty( $_POST['string_id'] )
	     && isset( $_POST['lang_tag'] ) && ! empty( $_POST['lang_tag'] )
	     && isset( $_POST['lang_iso_code'] ) && ! empty( $_POST['lang_iso_code'] )
	     && isset( $_POST['translationType'] ) && ! empty( $_POST['translationType'] )
	     && isset( $_POST['translatedPhrase'] ) && ! empty( $_POST['translatedPhrase'] ) ) {

		$string_id        = intval( $_POST['string_id'] );
		$lang_tag         = $_POST['lang_tag'];
		$lang_iso_code    = $_POST['lang_iso_code'];
		$translatedPhrase = $_POST['translatedPhrase'];
		$translationType  = $_POST['translationType'];

		if ( $translationType == 'translationAdd' ) {
			$wpdb->insert( strings_translations_table_name(), array(
				"string_id" => $string_id,
				"language"  => $lang_iso_code,
				"status"    => '10',
				"value"     => $translatedPhrase,
			) );
			if ( is_parent_language( $lang_tag ) ) {
				insert_translations_for_child_languages( $translatedPhrase, $lang_tag, $string_id );
			}

			echo json_encode( array( "status" => 1 ) );
		} else if ( $translationType == 'translationEdit' && isset( $_POST['stringTranslationId'] ) && ! empty( $_POST['stringTranslationId'] ) ) {
			$stringTranslationId = intval( $_POST['stringTranslationId'] );
			$wpdb->update( strings_translations_table_name(), array(
				"language" => $lang_iso_code,
				"value"    => $translatedPhrase,
				"status"   => 10
			), array(
				"id" => $stringTranslationId
			) );


			if ( isset( $_POST['overrideChildLanguages'] ) && $_POST['overrideChildLanguages'] == "true" && is_parent_language( $lang_tag ) ) {
				// delete childs translations and insert again:
				delete_child_translations_for_parent_languages( $lang_tag, $string_id );
				insert_translations_for_child_languages( $translatedPhrase, $lang_tag, $string_id );
			}

			echo json_encode( array( "status" => 1 ) );
		}
	}
	wp_die();
}

function get_custom_posts() {
	if ( isset( $_GET['q'] ) ) {
		$pages = get_posts( [
			's'              => $_GET['q'],
			'post_type'      => array( 'page', 'casinos', 'casino_reviews', 'sports', 'authors', 'contacts' ),
			'status'         => 'publish',
			'posts_per_page' => 50,
			'order'          => 'DESC'
		] );

		$postTypes = [];
		$results   = [];
		foreach ( $pages as $page ) {
			if ( ! in_array( $page->post_type, $postTypes ) ) {
				$postTypes[] = $page->post_type;
			}

			$index = array_search( $page->post_type, $postTypes );

			$results[ $index ]['text'] = ucfirst( $page->post_type );
			if ( ! isset( $results[ $index ]['children'] ) ) {
				$results[ $index ]['children'] = [];
			}

			$results[ $index ]['children'][] = [ 'id' => $page->ID, 'text' => $page->post_title ];
		}

		die( json_encode( [ 'results' => $results ] ) );
	}
	die( json_encode( [ 'results' => [] ] ) );
}

function get_custom_menus() {
	$menus = wp_get_nav_menus();
	die( json_encode( $menus ) );
}

function my_plugin_menus() {
	add_menu_page( "Strings Translations", "Strings Translations", "manage_options", "string-translations", "strings_list", "dashicons-book-alt", 30 );
}

function strings_list() {
	include_once MY_PLUGIN_DIR_PATH . "views/strings-list.php";
}

function string_languages() {
	return apply_filters( 'wpml_active_languages', null );
}

function generate_tables() {
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$sql = "CREATE TABLE `wp_translations_relations` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `string_id` bigint(20) NOT NULL,
  `relation_type` tinyint(4) NOT NULL,
  `relation_id` bigint(20) DEFAULT NULL,
  `relation_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX(`relation_name`),
  INDEX(`relation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	dbDelta( $sql );

	$masterdatatable = "CREATE TABLE `wp_translations_master_data_blocks` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `block_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX(`block_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	dbDelta( $masterdatatable );
}

///////// actions
add_action( 'rest_api_init', function () {
	register_rest_route( 'translations', 'strings', [
		'methode'             => 'GET',
		'callback'            => 'string_translations',
		'permission_callback' => '__return_true'
	] );

	register_rest_route( 'translations', 'languages', [
		'methode'             => 'GET',
		'callback'            => 'string_languages',
		'permission_callback' => '__return_true'
	] );
} );

add_action( "init", "my_include_assets" );
add_action( "admin_menu", "my_plugin_menus" );
add_action( 'wp_ajax_get_translation_for_string', 'get_translation_for_string' );
add_action( 'wp_ajax_set_translation_for_string', 'set_translation_for_string' );
add_action( 'wp_ajax_get_custom_posts', 'get_custom_posts' );
add_action( 'wp_ajax_get_custom_menus', 'get_custom_menus' );
register_activation_hook( __FILE__, "generate_tables" );

?>