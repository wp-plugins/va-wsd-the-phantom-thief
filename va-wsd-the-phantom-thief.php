<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}
/*
Plugin Name: VA WSD the phantom thief
Plugin URI: http://visualive.jp/
Description: This is a WordPress plugin that helps create previews of a url based on the OGP of the page, similar to a url preview in a Facebook post.
Author: KUCKLU
Version: 1.1.7
Author URI: http://visualive.jp/
Text Domain: va-wsd-the-phantom-thief
Domain Path: /langs
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

VisuAlive WordPress Plugin, Copyright (C) 2015 VisuAlive and KUCKLU.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
/**
 * VA WSD the phantom thief.
 *
 * @package    WordPress
 * @subpackage VA WSD the phantom thief
 * @author     KUCKLU <kuck1u@visualive.jp>
 * @copyright  Copyright (c) 2015 KUCKLU, VisuAlive.
 * @license    GPLv2 http://opensource.org/licenses/gpl-2.0.php
 * @link       http://visualive.jp/
 */
$va_wsd_the_phantom_thief_plugin_data = get_file_data( __FILE__, array( 'ver' => 'Version', 'langs' => 'Domain Path', 'mo' => 'Text Domain' ) );
define( 'VA_WSD_THE_PHANTOM_THIEF_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'VA_WSD_THE_PHANTOM_THIEF_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'VA_WSD_THE_PHANTOM_THIEF_DOMAIN',      dirname( plugin_basename( __FILE__ ) ) );
define( 'VA_WSD_THE_PHANTOM_THIEF_VERSION',     $va_wsd_the_phantom_thief_plugin_data['ver'] );
define( 'VA_WSD_THE_PHANTOM_THIEF_TEXTDOMAIN',  $va_wsd_the_phantom_thief_plugin_data['mo'] );
define( 'VA_WSD_THE_PHANTOM_THIEF_LANGS',       $va_wsd_the_phantom_thief_plugin_data['langs'] );
define( 'VA_WSD_THE_PHANTOM_THIEF_NONCE',       'va_wsd_the_phantom_thief_nonce_field' );
define( 'VA_WSD_THE_PHANTOM_THIEF_POSTTYPE',    'vawsdtpt_website_db' );


class VA_WSD_THE_PHANTOM_THIEF {
	/**
	 * Holds the singleton instance of this class
	 */
	static $instance = false;

	private static $plugin_prefix = 'vawsdtpt';

	/**
	 * Singleton
	 *
	 * @static
	 */
	public static function init() {
		if ( !self::$instance ) {
			self::$instance = new VA_WSD_THE_PHANTOM_THIEF;
		}

		return self::$instance;
	}

	/**
	 * This hook is called once any activated plugins have been loaded.
	 */
	public function __construct() {
		load_plugin_textdomain( VA_WSD_THE_PHANTOM_THIEF_TEXTDOMAIN, false, VA_WSD_THE_PHANTOM_THIEF_DOMAIN . VA_WSD_THE_PHANTOM_THIEF_LANGS );

		add_image_size( sprintf( '%s-thumbnail', self::$plugin_prefix ), '150', '150', true );

		add_action( 'init',                        array( &$this, 'register_post_types') );
		add_action( 'load-post.php',               array( &$this, 'user_can_richedit' ) );
		add_action( 'load-post-new.php',           array( &$this, 'user_can_richedit' ) );
		add_action( 'wp_enqueue_scripts',          array( &$this, 'wp_enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts',       array( &$this, 'admin_enqueue_scripts') );
		add_action( 'wp_ajax_vawsdtpt_get',        array( &$this, 'wp_ajax_vawsdtpt_get' ) );
		add_action( 'wp_ajax_nopriv_vawsdtpt_get', array( &$this, 'wp_ajax_vawsdtpt_get' ) );
		add_action( 'admin_menu',                  function () {
			remove_submenu_page( 'edit.php?post_type=' . VA_WSD_THE_PHANTOM_THIEF_POSTTYPE, 'post-new.php?post_type=' . VA_WSD_THE_PHANTOM_THIEF_POSTTYPE );
		} );

		add_filter( 'wp_insert_post_data',         array( &$this, 'wp_insert_post_data' ) );
		add_filter( 'the_content',                 array( &$this, 'content_replace' ), 0 );
		add_filter( 'quicktags_settings',          array( &$this, 'quicktags_settings' ) );
		// add_filter( 'screen_layout_columns',       array( &$this, 'screen_layout_columns' ) );
		// add_filter( 'get_user_option_screen_layout_' . VA_WSD_THE_PHANTOM_THIEF_POSTTYPE, array( &$this, 'screen_layout' ) );

		add_filter( 'manage_' . VA_WSD_THE_PHANTOM_THIEF_POSTTYPE . '_posts_columns',       array( &$this, 'manage_posts_columns' ) );
		add_filter( 'manage_' . VA_WSD_THE_PHANTOM_THIEF_POSTTYPE . '_posts_custom_column', array( &$this, 'manage_posts_custom_column' ), 10, 2 );
	}

	/**
	 * カスタム投稿タイプを登録する
	 */
	public function register_post_types() {
		global $wp_rewrite;

		$rewrite_tag = sprintf( '%s_site_url', self::$plugin_prefix );
		$show = false;

		if ( current_user_can( 'manage_options' ) )
			$show = true;

		register_post_type( VA_WSD_THE_PHANTOM_THIEF_POSTTYPE, array(
			'labels' => array(
				'name'           => __( 'Web Site Data', VA_WSD_THE_PHANTOM_THIEF_TEXTDOMAIN ),
				'name_admin_bar' => _x( 'Web Site Data', 'add new on admin bar', VA_WSD_THE_PHANTOM_THIEF_TEXTDOMAIN ),
				'singular_name'  => __( 'Web Site Data', VA_WSD_THE_PHANTOM_THIEF_TEXTDOMAIN ),
			),
			'public'               => false,
			'capability_type'      => 'post',
			'map_meta_cap'         => true,
			'hierarchical'         => false,
			'has_archive'          => false,
			'query_var'            => false,
			'show_ui'              => $show,
			'show_in_menu'         => $show,
			'menu_position'        => 80,
			'menu_icon'            => 'dashicons-welcome-view-site', // https://developer.wordpress.org/resource/dashicons/
			'supports'             => array( 'title', 'editor', 'thumbnail' ),
			'register_meta_box_cb' => array( &$this, 'register_meta_boxes' )
		) );

		$wp_rewrite->add_rewrite_tag( '%' . $rewrite_tag . '%', '([^/]+)', 'post_type=' . VA_WSD_THE_PHANTOM_THIEF_POSTTYPE . '&p=' );
		$wp_rewrite->add_permastruct( VA_WSD_THE_PHANTOM_THIEF_POSTTYPE, '/website/detail/%' . $rewrite_tag . '%', false );
		add_filter( 'post_type_link', function ( $post_link, $post_id, $leavename ) use ( $rewrite_tag ) {
			global $wp_rewrite;

			$post = get_post( $post_id );

			if ( !is_object( $post ) || VA_WSD_THE_PHANTOM_THIEF_POSTTYPE !== $post->post_type ) return $post_link;

			$permalink = $wp_rewrite->get_extra_permastruct( VA_WSD_THE_PHANTOM_THIEF_POSTTYPE );
			$permalink = str_replace( '%' . $rewrite_tag . '%', $post->ID, $permalink );
			$permalink = home_url( user_trailingslashit( $permalink ) );

			return $permalink;
		}, 1, 3 );
	}

	/**
	 * メタボックス登録する
	 */
	public function register_meta_boxes( $post ) {
		add_meta_box( sprintf( '%s_site_url', self::$plugin_prefix ), 'URL', function () use ( $post ) {
			$url     = get_post_meta( $post->ID, sprintf( '%s_site_url', self::$plugin_prefix ), true );
			printf( '<input type="text" value="%s" readonly="true" class="regular-text">', esc_url_raw( $url ) );
		}, $post->post_type, 'normal', 'high' );

		add_meta_box( sprintf( '%s_site_attachment_id', self::$plugin_prefix ), 'Attachment ID', function () use ( $post ) {
			$image   = get_post_meta( $post->ID, sprintf( '%s_site_attachment_id', self::$plugin_prefix ), true );
			printf( '<input type="text" value="%d" readonly="true">', esc_attr( $image ) );
		}, $post->post_type, 'normal', 'high' );

		// remove_meta_box( 'submitdiv', VA_WSD_THE_PHANTOM_THIEF_POSTTYPE, 'side' );
		remove_meta_box( 'slugdiv', VA_WSD_THE_PHANTOM_THIEF_POSTTYPE, 'normal' );
	}

	/**
	 * 正規表現検索パターン
	 *
	 * @return string
	 */
	protected static function regex_pattern_url() {
		return '`https?+:(?://(?:(?:[-.0-9_a-z~]|%[0-9a-f][0-9a-f]' .
			'|[!$&-,:;=])*+@)?+(?:\[(?:(?:[0-9a-f]{1,4}:){6}(?:' .
			'[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:\d|[1-9]\d|1\d{2}|2' .
			'[0-4]\d|25[0-5])\.(?:\d|[1-9]\d|1\d{2}|2[0-4]\d|25' .
			'[0-5])\.(?:\d|[1-9]\d|1\d{2}|2[0-4]\d|25[0-5])\.(?' .
			':\d|[1-9]\d|1\d{2}|2[0-4]\d|25[0-5]))|::(?:[0-9a-f' .
			']{1,4}:){5}(?:[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:\d|[1' .
			'-9]\d|1\d{2}|2[0-4]\d|25[0-5])\.(?:\d|[1-9]\d|1\d{' .
			'2}|2[0-4]\d|25[0-5])\.(?:\d|[1-9]\d|1\d{2}|2[0-4]\\' .
			'd|25[0-5])\.(?:\d|[1-9]\d|1\d{2}|2[0-4]\d|25[0-5])' .
			')|(?:[0-9a-f]{1,4})?+::(?:[0-9a-f]{1,4}:){4}(?:[0-' .
			'9a-f]{1,4}:[0-9a-f]{1,4}|(?:\d|[1-9]\d|1\d{2}|2[0-' .
			'4]\d|25[0-5])\.(?:\d|[1-9]\d|1\d{2}|2[0-4]\d|25[0-' .
			'5])\.(?:\d|[1-9]\d|1\d{2}|2[0-4]\d|25[0-5])\.(?:\d' .
			'|[1-9]\d|1\d{2}|2[0-4]\d|25[0-5]))|(?:(?:[0-9a-f]{' .
			'1,4}:)?+[0-9a-f]{1,4})?+::(?:[0-9a-f]{1,4}:){3}(?:' .
			'[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:\d|[1-9]\d|1\d{2}|2' .
			'[0-4]\d|25[0-5])\.(?:\d|[1-9]\d|1\d{2}|2[0-4]\d|25' .
			'[0-5])\.(?:\d|[1-9]\d|1\d{2}|2[0-4]\d|25[0-5])\.(?' .
			':\d|[1-9]\d|1\d{2}|2[0-4]\d|25[0-5]))|(?:(?:[0-9a-' .
			'f]{1,4}:){0,2}[0-9a-f]{1,4})?+::(?:[0-9a-f]{1,4}:)' .
			'{2}(?:[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:\d|[1-9]\d|1\\' .
			'd{2}|2[0-4]\d|25[0-5])\.(?:\d|[1-9]\d|1\d{2}|2[0-4' .
			']\d|25[0-5])\.(?:\d|[1-9]\d|1\d{2}|2[0-4]\d|25[0-5' .
			'])\.(?:\d|[1-9]\d|1\d{2}|2[0-4]\d|25[0-5]))|(?:(?:' .
			'[0-9a-f]{1,4}:){0,3}[0-9a-f]{1,4})?+::[0-9a-f]{1,4' .
			'}:(?:[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:\d|[1-9]\d|1\d' .
			'{2}|2[0-4]\d|25[0-5])\.(?:\d|[1-9]\d|1\d{2}|2[0-4]' .
			'\d|25[0-5])\.(?:\d|[1-9]\d|1\d{2}|2[0-4]\d|25[0-5]' .
			')\.(?:\d|[1-9]\d|1\d{2}|2[0-4]\d|25[0-5]))|(?:(?:[' .
			'0-9a-f]{1,4}:){0,4}[0-9a-f]{1,4})?+::(?:[0-9a-f]{1' .
			',4}:[0-9a-f]{1,4}|(?:\d|[1-9]\d|1\d{2}|2[0-4]\d|25' .
			'[0-5])\.(?:\d|[1-9]\d|1\d{2}|2[0-4]\d|25[0-5])\.(?' .
			':\d|[1-9]\d|1\d{2}|2[0-4]\d|25[0-5])\.(?:\d|[1-9]\\' .
			'd|1\d{2}|2[0-4]\d|25[0-5]))|(?:(?:[0-9a-f]{1,4}:){' .
			'0,5}[0-9a-f]{1,4})?+::[0-9a-f]{1,4}|(?:(?:[0-9a-f]' .
			'{1,4}:){0,6}[0-9a-f]{1,4})?+::|v[0-9a-f]++\.[!$&-.' .
			'0-;=_a-z~]++)\]|(?:\d|[1-9]\d|1\d{2}|2[0-4]\d|25[0' .
			'-5])\.(?:\d|[1-9]\d|1\d{2}|2[0-4]\d|25[0-5])\.(?:\\' .
			'd|[1-9]\d|1\d{2}|2[0-4]\d|25[0-5])\.(?:\d|[1-9]\d|' .
			'1\d{2}|2[0-4]\d|25[0-5])|(?:[-.0-9_a-z~]|%[0-9a-f]' .
			'[0-9a-f]|[!$&-,;=])*+)(?::\d*+)?+(?:/(?:[-.0-9_a-z' .
			'~]|%[0-9a-f][0-9a-f]|[!$&-,:;=@])*+)*+|/(?:(?:[-.0' .
			'-9_a-z~]|%[0-9a-f][0-9a-f]|[!$&-,:;=@])++(?:/(?:[-' .
			'.0-9_a-z~]|%[0-9a-f][0-9a-f]|[!$&-,:;=@])*+)*+)?+|' .
			'(?:[-.0-9_a-z~]|%[0-9a-f][0-9a-f]|[!$&-,:;=@])++(?' .
			':/(?:[-.0-9_a-z~]|%[0-9a-f][0-9a-f]|[!$&-,:;=@])*+' .
			')*+)?+(?:\?+(?:[-.0-9_a-z~]|%[0-9a-f][0-9a-f]|[!$&' .
			'-,/:;=?+@])*+)?+(?:#(?:[-.0-9_a-z~]|%[0-9a-f][0-9a' .
			'-f]|[!$&-,/:;=?+@])*+)?+`iu';
	}

	/**
	 * URL が正しい型か確認する
	 *
	 * @param  string  $url
	 * @return boolean
	 */
	protected static function validate_url( $url ) {
		if ( preg_match( self::regex_pattern_url(), $url ) )
			return true;

		return false;
	}

	/**
	 * JSON か確認する
	 *
	 * @param  string  $string
	 * @return boolean
	 */
	protected static function is_json( $string ) {
		json_decode( $string );
		return ( json_last_error() == JSON_ERROR_NONE );
	}

	/**
	 * $url の値を持つ key があるか確認する。
	 *
	 * @param  string  $url
	 * @return boolean
	 */
	protected static function url_exists( $url ) {
		global $wpdb;

		if ( !self::validate_url( $url ) ) return false;

		$result   = false;
		$response = $wpdb->get_var( $wpdb->prepare( "SELECT post_id, meta_id FROM $wpdb->postmeta WHERE meta_key = '%s' AND meta_value = '%s'", self::$plugin_prefix . '_site_url', esc_url_raw( $url ) ) );

		if ( !is_null( $response ) )
			$result = true;

		return $result;
	}

	/**
	 * バイナリーデータから画像タイプを調べる。
	 *
	 * @param  null $binary
	 * @return null|array
	 */
	protected static function check_binarytype( $binary = null ) {
		$result = null;

		if ( !is_null( $binary ) ) :
			if ( strncmp( "\x89PNG\x0d\x0a\x1a\x0a", $binary, 8 ) == 0 ) :
				$result = array(
					'mime' => 'image/png',
					'ext'  => 'png'
				);
			elseif ( strncmp( 'GIF87a', $binary, 6 ) == 0 || strncmp( 'GIF89a', $binary, 6 ) == 0 ) :
				$result =  array(
					'mime' => 'image/gif',
					'ext'  => 'gif'
				);
			elseif ( strncmp( "\xff\xd8", $binary, 2 ) == 0 ) :
				$result = array(
					'mime' => 'image/jpeg',
					'ext'  => 'jpg'
				);
			endif;
		endif;

		return $result;
	}

	/**
	 * YQL のエンドポイントを作成。
	 *
	 * @param  $url
	 * @return bool|string
	 */
	protected static function get_yql_request_url( $url ) {
		if ( !self::validate_url( $url ) ) return false;

		$yql    = 'https://query.yahooapis.com/v1/public/yql?diagnostics=true&env=store%3A%2F%2Fdatatables.org%2Falltableswithkeys&q=';
		$query  = sprintf( "select * from html where url='%s' and xpath='*' and compat='html5'", esc_url_raw( $url ) );
		$format = '&format=json';

		return $yql . rawurlencode( $query ) . $format;
	}

	/**
	 * YQL の返り値から、サイト情報を配列にする。
	 *
	 * @param  $url
	 * @return array|bool
	 */
	protected static function get_website_meta( $url ) {
		if ( !self::validate_url( $url ) ) return false;

		$yql_url  = self::get_yql_request_url( $url );
		$response = $yql_url ? wp_remote_retrieve_body( wp_remote_get( $yql_url ) ) : false;

		if ( self::is_json( $response ) && !is_null( json_decode( $response )->query->results ) ) :
			$response = json_decode( $response );
			$title    = isset( $response->query->results->html->head->title ) ? $response->query->results->html->head->title : 'No Title';
			$metas    = isset( $response->query->results->html->head->meta ) ? $response->query->results->html->head->meta : false;
			$result   = array(
				'title'       => wp_strip_all_tags( $title ),
				'description' => '',
				'image'       => ''
			);

			if ( $metas ) :
				foreach ( $metas as $value ) :
					if ( isset( $value->property ) && 'og:description' === $value->property )
						$result['description'] = wp_strip_all_tags( $value->content );
					elseif ( isset( $value->name ) && 'description' === $value->name )
						$result['description'] = wp_strip_all_tags( $value->content );

					if ( isset( $value->property ) && 'og:title' === $value->property )
						$result['title'] = wp_strip_all_tags( $value->content );

					if ( isset( $value->property ) && 'og:image' === $value->property )
						$result['image'] = esc_url_raw( $value->content );
				endforeach;
			endif;

			return $result;
		endif;

		return false;
	}

	/**
	 * Get post
	 *
	 * @param  string $url
	 * @return object
	 */
	protected static function get_post( $url ) {
		return get_posts( array(
			'posts_per_page' => 1 ,
			'meta_key'       => sprintf( '%s_site_url', self::$plugin_prefix ),
			'meta_value'     => sprintf( '%s', esc_url_raw( $url ) ),
			'post_type'      => VA_WSD_THE_PHANTOM_THIEF_POSTTYPE
		) );
	}

	/**
	 * 投稿データの作成。
	 *
	 * @param  string  $url
	 * @return integer Post ID
	 */
	public static function website_insert_post( $url ) {
		if ( !self::validate_url( $url ) || self::url_exists( $url ) ) return false;

		ignore_user_abort( true );
		set_time_limit( 0 );
		usleep( 100 );
		clearstatcache();

		wp_defer_term_counting( true );
		wp_suspend_cache_invalidation( true );

		$result = false;
		$metas  = self::get_website_meta( $url );
		$post   = self::get_post( $url );

		if ( !empty( $post ) ) return false;

		if ( false !== $metas && empty( $post ) ) :
			$post_id = intval( wp_insert_post( array(
				'post_title'     => sprintf( '%s', $metas['title'] ),
				'post_content'   => sprintf( '%s', $metas['description'] ),
				'post_status'    => 'publish',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
				'post_type'      => VA_WSD_THE_PHANTOM_THIEF_POSTTYPE
			) ) );

			if ( 0 < $post_id )
				update_post_meta( $post_id, sprintf( '%s_site_url', self::$plugin_prefix ), sprintf( '%s', esc_url_raw( $url ) ) );

			if ( 0 < $post_id && !empty( $metas['image'] ) && apply_filters( sprintf( '%s_save_image', self::$plugin_prefix ), true ) )
				self::insert_attachment( $post_id, $metas['image'] );

			$result = $post_id;
		endif;

		wp_suspend_cache_invalidation( false );
		wp_cache_flush();
		wp_defer_term_counting( false );

		return $result;
	}

	/**
	 * アタッチメントの作成。
	 *
	 * @param  int     $post_id
	 * @param  string  $url
	 */
	protected static function insert_attachment( $post_id = 0, $url ) {
		if ( !self::validate_url( $url ) || self::url_exists( $url ) || 0 >= $post_id ) return false;

		$upload_dir   = wp_upload_dir();
		$remote_file  = wp_remote_get( $url );
		$file_body    = wp_remote_retrieve_body( $remote_file );
		$file_types   = self::check_binarytype( $file_body );
		$file_mime    = null;
		$file_ext     = null;

		if ( !is_null( $file_types ) ) {
			$file_mime = $file_types['mime'];
			$file_ext  = $file_types['ext'];
		}

		if ( is_null( $file_ext ) || is_null( $file_mime ) ) return false;

		$file_name = sprintf( '%s-%d.%s', self::$plugin_prefix, intval( $post_id ), $file_ext );

		if ( wp_mkdir_p( $upload_dir['path'] ) )
			$file = $upload_dir['path'] . '/' . $file_name;
		else
			$file = $upload_dir['basedir'] . '/' . $file_name;

		file_put_contents( $file, $file_body );

		$attachment  = array(
			'post_mime_type' => $file_mime,
			'post_title'     => sanitize_file_name( $file_name ),
			'post_content'   => '',
			'post_status'    => 'inherit'
		);
		$attach_id   = wp_insert_attachment( $attachment, $file, $post_id );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file );

		wp_update_attachment_metadata( $attach_id, $attach_data );

		set_post_thumbnail( $post_id, $attach_id );
		update_post_meta( $post_id, sprintf( '%s_site_attachment_id', self::$plugin_prefix ), $attach_id );
	}

	/**
	 * 管理画面で編集した時、一部のステータスを改変する。
	 *
	 * @param  array $data
	 * @return array
	 */
	public function wp_insert_post_data( $data ) {
		if ( VA_WSD_THE_PHANTOM_THIEF_POSTTYPE === $data['post_type'] ) {
			$data['post_title']     = esc_sql( apply_filters( 'the_title',    $data['post_title'] ) );
			$data['post_content']   = esc_sql( apply_filters( 'post_content', $data['post_content'] ) );
			$data['post_excerpt']   = "";
			$data['comment_status'] = 'closed';
			$data['ping_status']    = 'closed';
			$data['post_password']  = "";

			if ( 'trash' !== $data['post_status'] )
				$data['post_status'] = 'publish';
		}

		return $data;
	}

	/**
	 * Content replace.
	 *
	 * @param  string $content
	 * @return string
	 */
	public function content_replace( $content ) {
		$content_protect_pattern = '/<(a|pre|code|s(?:cript|tyle)|xmp)(?:\s*|(?:\s+[^>]+))>(.*?)<\/\\1\s*>|<(img)(?:\s*|(?:\s+[^>]+))>/is';
		$content_url_pattern     = self::regex_pattern_url();
		$i                       = 0;
		$tmpName                 = "__TMP__";

		do {
			if ( !isset( $GLOBALS[$tmpName . $i] ) ) {
				$tmpName .= $i;
				break;
			}
			if ($i > 10) {
				$tmpName .= md5( mt_rand() ) . md5( mt_rand() );
				break;
			}
			$i++;
		} while ( true );

		$GLOBALS[$tmpName] = array();
		$content           = preg_replace_callback( $content_protect_pattern, create_function( '$matches', '$tmp =& $GLOBALS["' . $tmpName . '"]; $tmp[] = $matches[0];' . 'return "<\\x00," . count( $tmp ) . ",\\x01>";'), $content );
		$content           = preg_replace_callback( $content_url_pattern, array( &$this, 'content_replace_cb' ), $content );
		$content           = preg_replace_callback( "/<\\x00,(\d+),\\x01>/", create_function( '$matches', '$tmp =& $GLOBALS["' . $tmpName . '"];' . 'return $tmp[$matches[1] - 1];' ), $content );

		return sprintf( '<div id="va-wsd-the-phantom-thief-%d">%s</div>', get_the_ID(), $content );
	}

	/**
	 * Content replace callback.
	 *
	 * @param  array  $matches
	 * @return string
	 */
	public function content_replace_cb( $matches ) {
		$target = ( 0 === url_to_postid( $matches[0] ) ) ? '': ' target="_blank"';

		return sprintf( '<p class="va-wsd-the-phantom-thief" data-url="%1$s"><a href="%1$s" rel="nofollow"%2$s>%1$s</a></p>', esc_url_raw( $matches[0] ), $target );
	}

	/**
	 * Ajax の処理。
	 *
	 * @return string json
	 */
	public function wp_ajax_vawsdtpt_get() {
//		check_ajax_referer( VA_WSD_THE_PHANTOM_THIEF_NONCE, sprintf( '%s_nonce', self::$plugin_prefix ) );

//		$nonce  = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING );
		$url    = filter_input( INPUT_POST, 'url', FILTER_SANITIZE_STRING );
		$my_url = url_to_postid( $url );
		$target = ( 0 === $my_url ) ? true: false;

		if ( !self::validate_url( $url ) ) {
			wp_send_json_error( __( 'URL has not been set.', VA_WSD_THE_PHANTOM_THIEF_TEXTDOMAIN ) );
			exit;
		}

//		if ( false === wp_verify_nonce( $nonce, sprintf( '%s_nonce', self::$plugin_prefix ) ) ) {
//			wp_send_json_error( __( 'Nonce error.', VA_WSD_THE_PHANTOM_THIEF_TEXTDOMAIN ) );
//			exit;
//		}

		if ( !( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			wp_send_json_error( __( 'Is not ajax.', VA_WSD_THE_PHANTOM_THIEF_TEXTDOMAIN ) );
			exit;
		}

		if ( false === $target ) {
			$post = get_post( $my_url );
		} else {
			$post = self::get_post( $url );

			if ( isset( $post[0] ) && is_object( $post[0] ) ) {
				$post = $post[0];
			}
		}

		if ( !isset( $post ) || !is_object( $post ) ) {
			self::website_insert_post( $url );
			wp_send_json_error( __( 'Post data has not been set.', VA_WSD_THE_PHANTOM_THIEF_TEXTDOMAIN ) );
			exit;
		} else {
			$site_url     = get_post_meta( $post->ID, sprintf( '%s_site_url', self::$plugin_prefix ), true );
			$site_image   = "";
			$site_favicon = sprintf( 'http://www.google.com/s2/favicons?domain_url=%s', esc_url_raw( $url ) );
			$post_title   = apply_filters( 'the_title',   $post->post_title );
			$post_content = apply_filters( 'the_content', $post->post_content );

			if ( empty( $site_url ) && 0 !== $my_url ) {
				$site_url = $url;
			}

			if ( has_post_thumbnail( $post->ID ) ) {
				$site_image = esc_url_raw( wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), sprintf( '%s-thumbnail', self::$plugin_prefix ) )[0] );
			}

			wp_send_json_success( array(
				'post_title'    => wp_trim_words( wp_strip_all_tags( $post_title, true ), 55, '…' ),
				'post_content'  => wp_trim_words( wp_strip_all_tags( $post_content, true ), 140, '…' ),
				'post_url'      => esc_url_raw( $site_url ),
				'post_domain'   => parse_url( $site_url )['host'],
				'post_image'    => $site_image,
				'post_favicon'  => $site_favicon,
				'anchor_target' => $target,
			) );
			exit;
		}
	}

	/**
	 * ビジュアルエディターの禁止。
	 */
	public function user_can_richedit() {
		global $typenow;

		if ( VA_WSD_THE_PHANTOM_THIEF_POSTTYPE === $typenow )
			add_filter( 'user_can_richedit', '__return_false' );
	}

	/**
	 * テキストエディターから、クイックタグボタンを削除する。
	 *
	 * @param  array $qt_init
	 * @return array
	 */
	public function quicktags_settings( $qt_init ) {
		global $typenow;

		if ( VA_WSD_THE_PHANTOM_THIEF_POSTTYPE === $typenow )
			$qt_init['buttons'] = ',';

		return $qt_init;
	}


	/**
	 * 編集画面のカラムを 1 カラムに変更する。
	 * @return int
	 */
	function screen_layout() {
		global $typenow;

		if ( VA_WSD_THE_PHANTOM_THIEF_POSTTYPE === $typenow )
			return 1;
	}

	/**
	 * 編集画面のカラムを 1 カラムに変更する。
	 *
	 * @param  array $columns
	 * @return array
	 */
	function screen_layout_columns( $columns ) {
		global $typenow;

		if ( VA_WSD_THE_PHANTOM_THIEF_POSTTYPE === $typenow )
			$columns[VA_WSD_THE_PHANTOM_THIEF_POSTTYPE] = 1;

		return $columns;
	}

	/**
	 * 記事一覧テーブルにカラムを追加する。
	 *
	 * @param  array $defaults
	 * @return array
	 */
	public function manage_posts_columns( $defaults ) {
		$mode = get_user_setting( 'posts_list_mode', 'list' );

		$new_args['cb']        = $defaults['cb'];
		$new_args['title']     = $defaults['title'];

		if ( 'excerpt' !== $mode )
			$new_args['description'] = __( 'Description', VA_WSD_THE_PHANTOM_THIEF_TEXTDOMAIN );

		$new_args['url']       = __( 'URL',       VA_WSD_THE_PHANTOM_THIEF_TEXTDOMAIN );
		$new_args['thumbnail'] = __( 'Thumbnail', VA_WSD_THE_PHANTOM_THIEF_TEXTDOMAIN );
		$new_args['date']      = $defaults['date'];

		return $new_args;
	}

	/**
	 * 記事一覧テーブルのカラムの中身を作成。
	 *
	 * @param string  $column
	 * @param integer $post_id
	 */
	public function manage_posts_custom_column( $column, $post_id ) {
		if ( $column === 'thumbnail' ) {
			if ( has_post_thumbnail( $post_id ) ) {
				$thumb = esc_url_raw( wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), sprintf( '%s-thumbnail', self::$plugin_prefix ) )[0] );
				printf( '<img src="%s" width="75">', $thumb );
			} else {
				echo 'N/A';
			}
		}

		if ( $column === 'description' ) {
			$description = get_the_content( $post_id );
			if ( "" !== $description ) {
				echo $description;
			} else {
				echo 'N/A';
			}
		}

		if ( $column === 'url' ) {
			$url = get_post_meta( $post_id, sprintf( '%s_site_url', self::$plugin_prefix ), true );
			if ( $url ) {
				printf( '<a href="%1$s" target="_blank">%1$s</a>', esc_url_raw( $url ) );
			} else {
				echo 'N/A';
			}
		}
	}

	/**
	 * CSS と JS の読み込み。
	 */
	public function wp_enqueue_scripts() {
		wp_enqueue_style(   'va-wsd-the-phantom-thief-style', VA_WSD_THE_PHANTOM_THIEF_PLUGIN_URL . 'assets/css/style.css' );
		wp_enqueue_script(  'va-wsd-the-phantom-thief-ajax', VA_WSD_THE_PHANTOM_THIEF_PLUGIN_URL . 'assets/js/ajax.min.js', array( 'jquery' ) );
		wp_localize_script( 'va-wsd-the-phantom-thief-ajax', 'VAWSDTPT', array(
			'endpoint' => admin_url( 'admin-ajax.php' ),
			'action'   => sprintf( '%s_get', self::$plugin_prefix ),
			'nonce'    => wp_create_nonce( VA_WSD_THE_PHANTOM_THIEF_NONCE )
		) );
	}

	public function admin_enqueue_scripts( $hook ) {
		global $typenow;

		if ( is_admin() && ( $hook === 'edit.php' || $hook === 'post.php' ) &&  VA_WSD_THE_PHANTOM_THIEF_POSTTYPE === $typenow ) {
			wp_enqueue_style(  'va-wsd-the-phantom-thief-admin', VA_WSD_THE_PHANTOM_THIEF_PLUGIN_URL . 'assets/css/admin.css' );
			wp_enqueue_script( 'va-wsd-the-phantom-thief-admin', VA_WSD_THE_PHANTOM_THIEF_PLUGIN_URL . 'assets/js/admin.js' );
		}
	}

	/**
	 * Uninstall.
	 */
	public static function uninstall() {
		global $wpdb;

		ignore_user_abort( true );
		set_time_limit( 0 );

		$table = $wpdb->prefix . 'posts';
		$sql   = sprintf( "SELECT ID FROM %s WHERE post_type = '%s'", $table, VA_WSD_THE_PHANTOM_THIEF_POSTTYPE );
		$ids   = $wpdb->get_results( $sql, ARRAY_N );

		if ( !isset( $ids ) || empty( $ids ) ) return;

		foreach ( $ids as $key => $id ) {
			usleep( 100 );
			clearstatcache();

			$images = get_children( array(
				'post_parent'    => $id[0],
				'post_type'      => 'attachment',
				'post_mime_type' => 'image'
			) );

			foreach ( $images as $attachment_id => $attachment ) {
				wp_delete_attachment( $attachment_id, true );
			}

			wp_delete_post( $id[0], true );
		}
	}
}
add_action( 'plugins_loaded', array( 'VA_WSD_THE_PHANTOM_THIEF', 'init') );

/**
 * Uninstall.
 */
if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
	register_deactivation_hook( __FILE__, array( 'VA_WSD_THE_PHANTOM_THIEF', 'uninstall' ) );
} else {
	register_uninstall_hook( __FILE__,    array( 'VA_WSD_THE_PHANTOM_THIEF', 'uninstall' ) );
}
