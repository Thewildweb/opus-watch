<?php
/**
 * Plugin Name: Opus Catalogue
 * Description: The "Database" side of Opus — watch_model CPT, brand/watch_type
 *              taxonomies, spec meta, and the parts custom table. Theme-independent.
 * Version:     0.1.0
 *
 * DESIGN NOTE: postmeta here is for STORAGE + DISPLAY only. All search and
 * filtering goes through ElasticSearch — we never run a meta_query against the
 * catalogue, which is what lets it scale to tens of thousands of model pages.
 *
 * @package opus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'OPUS_CATALOGUE_VERSION', '0.1.0' );

/* ─────────────────────────────────────────────────────────────
 * Custom post type: watch_model  (the catalogue / "Database")
 * ───────────────────────────────────────────────────────────── */
function opus_register_watch_model() {
	register_post_type(
		'watch_model',
		array(
			'labels'       => array(
				'name'               => 'Watch Models',
				'singular_name'      => 'Watch Model',
				'add_new_item'       => 'Add Watch Model',
				'edit_item'          => 'Edit Watch Model',
				'search_items'       => 'Search Watch Models',
				'not_found'          => 'No watch models found',
				'menu_name'          => 'Database',
			),
			'public'       => true,
			'has_archive'  => true,
			'menu_icon'    => 'dashicons-clock',
			'menu_position' => 26,
			'rewrite'      => array( 'slug' => 'watches', 'with_front' => false ),
			'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields' ),
			'show_in_rest' => true, // Block editor + ElasticPress indexing.
		)
	);
}
add_action( 'init', 'opus_register_watch_model' );

/* ─────────────────────────────────────────────────────────────
 * Taxonomies: brand, watch_type
 * brand term page == the brand landing page.
 * ───────────────────────────────────────────────────────────── */
function opus_register_taxonomies() {
	register_taxonomy(
		'brand',
		array( 'watch_model', 'product' ), // shared with WooCommerce products for filtering.
		array(
			'labels'            => array(
				'name'          => 'Brands',
				'singular_name' => 'Brand',
				'menu_name'     => 'Brands',
			),
			'public'            => true,
			'hierarchical'      => true, // category-style checkbox UI + clean term pages.
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'brand', 'with_front' => false ),
		)
	);

	register_taxonomy(
		'watch_type',
		array( 'watch_model' ),
		array(
			'labels'            => array(
				'name'          => 'Watch Types',
				'singular_name' => 'Watch Type',
				'menu_name'     => 'Types',
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'type', 'with_front' => false ),
		)
	);
}
add_action( 'init', 'opus_register_taxonomies' );

/* ─────────────────────────────────────────────────────────────
 * Spec fields — registered as post meta (show_in_rest so the block
 * editor & ElasticPress can see them). Display/storage only.
 * ───────────────────────────────────────────────────────────── */
function opus_spec_fields() {
	return array(
		'opus_reference'         => array( 'label' => 'Reference', 'type' => 'string' ),
		'opus_movement'          => array( 'label' => 'Movement / Caliber', 'type' => 'string' ),
		'opus_movement_type'     => array( 'label' => 'Movement type', 'type' => 'string', 'choices' => array( 'Automatic', 'Manual', 'Quartz' ) ),
		'opus_case_material'     => array( 'label' => 'Case material', 'type' => 'string' ),
		'opus_case_size_mm'      => array( 'label' => 'Case size (mm)', 'type' => 'number' ),
		'opus_water_resist_m'    => array( 'label' => 'Water resistance (m)', 'type' => 'number' ),
		'opus_year_introduced'   => array( 'label' => 'Year introduced', 'type' => 'integer' ),
		'opus_dial'              => array( 'label' => 'Dial', 'type' => 'string' ),
		'opus_bracelet'          => array( 'label' => 'Bracelet / strap', 'type' => 'string' ),
		'opus_production_status' => array( 'label' => 'Production status', 'type' => 'string', 'choices' => array( 'In production', 'Discontinued' ) ),
	);
}

function opus_register_meta() {
	foreach ( opus_spec_fields() as $key => $def ) {
		register_post_meta(
			'watch_model',
			$key,
			array(
				'type'              => $def['type'],
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'number' === $def['type'] || 'integer' === $def['type']
					? ( 'integer' === $def['type'] ? 'absint' : 'floatval' )
					: 'sanitize_text_field',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
}
add_action( 'init', 'opus_register_meta' );

/* ─────────────────────────────────────────────────────────────
 * Spec metabox
 * ───────────────────────────────────────────────────────────── */
function opus_add_spec_metabox() {
	add_meta_box( 'opus_specs', 'Specifications', 'opus_render_spec_metabox', 'watch_model', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'opus_add_spec_metabox' );

function opus_render_spec_metabox( $post ) {
	wp_nonce_field( 'opus_save_specs', 'opus_specs_nonce' );
	echo '<style>.opus-specs{display:grid;grid-template-columns:1fr 1fr;gap:12px 24px}.opus-specs label{display:block;font-weight:600;margin-bottom:4px}.opus-specs input,.opus-specs select{width:100%}</style>';
	echo '<div class="opus-specs">';
	foreach ( opus_spec_fields() as $key => $def ) {
		$value = get_post_meta( $post->ID, $key, true );
		echo '<div><label for="' . esc_attr( $key ) . '">' . esc_html( $def['label'] ) . '</label>';
		if ( ! empty( $def['choices'] ) ) {
			echo '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '">';
			echo '<option value="">—</option>';
			foreach ( $def['choices'] as $choice ) {
				echo '<option value="' . esc_attr( $choice ) . '" ' . selected( $value, $choice, false ) . '>' . esc_html( $choice ) . '</option>';
			}
			echo '</select>';
		} else {
			$input_type = ( 'number' === $def['type'] || 'integer' === $def['type'] ) ? 'number' : 'text';
			echo '<input type="' . esc_attr( $input_type ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';
		}
		echo '</div>';
	}
	echo '</div>';
}

function opus_save_specs( $post_id ) {
	if ( ! isset( $_POST['opus_specs_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['opus_specs_nonce'] ), 'opus_save_specs' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( opus_spec_fields() as $key => $def ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}
		$raw = wp_unslash( $_POST[ $key ] );
		if ( 'integer' === $def['type'] ) {
			$value = '' === $raw ? '' : absint( $raw );
		} elseif ( 'number' === $def['type'] ) {
			$value = '' === $raw ? '' : floatval( $raw );
		} else {
			$value = sanitize_text_field( $raw );
		}
		if ( '' === $value ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}
}
add_action( 'save_post_watch_model', 'opus_save_specs' );

/* ─────────────────────────────────────────────────────────────
 * Parts: custom table (high-volume relational child data).
 * Created once; bumped via the version option.
 * ───────────────────────────────────────────────────────────── */
function opus_parts_table_name() {
	global $wpdb;
	return $wpdb->prefix . 'opus_parts';
}

function opus_maybe_install_parts_table() {
	$installed = get_option( 'opus_parts_db_version' );
	if ( OPUS_CATALOGUE_VERSION === $installed ) {
		return;
	}
	global $wpdb;
	$table           = opus_parts_table_name();
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$table} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		model_id BIGINT UNSIGNED NOT NULL,
		part_ref VARCHAR(120) NOT NULL DEFAULT '',
		part_name VARCHAR(255) NOT NULL DEFAULT '',
		category VARCHAR(120) NOT NULL DEFAULT '',
		notes TEXT NULL,
		PRIMARY KEY (id),
		KEY model_id (model_id),
		KEY part_ref (part_ref)
	) {$charset_collate};";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
	update_option( 'opus_parts_db_version', OPUS_CATALOGUE_VERSION );
}
add_action( 'admin_init', 'opus_maybe_install_parts_table' );

/* ─────────────────────────────────────────────────────────────
 * One-time setup: seed the eight brands + flush rewrites so the
 * CPT/taxonomy permalinks work. Guarded by an option.
 * ───────────────────────────────────────────────────────────── */
function opus_maybe_bootstrap() {
	if ( get_option( 'opus_catalogue_bootstrapped' ) === OPUS_CATALOGUE_VERSION ) {
		return;
	}

	$brands = array( 'Rolex', 'Cartier', 'Omega', 'Audemars Piguet', 'Tudor', 'IWC', 'Patek Philippe', 'Vacheron Constantin' );
	foreach ( $brands as $brand ) {
		if ( ! term_exists( $brand, 'brand' ) ) {
			wp_insert_term( $brand, 'brand' );
		}
	}

	flush_rewrite_rules();
	update_option( 'opus_catalogue_bootstrapped', OPUS_CATALOGUE_VERSION );
}
add_action( 'init', 'opus_maybe_bootstrap', 99 );
