<?php
/**
 * Watch database — the deep reference catalogue (distinct from the Woo shop).
 *
 * Watches imported from the WatchBase scrape live in a lean, indexed custom
 * table `{prefix}opus_watches` (not WP posts). This file owns:
 *   - the table schema + creation
 *   - a WP-CLI importer (`wp opus import-watches`)
 *   - query/facet helpers used by the page template and REST endpoint
 *   - a REST endpoint for the faceted search UI
 *   - a pretty detail route: /database/watch/{id}/
 *
 * @package maison-opus
 */

defined( 'ABSPATH' ) || exit;

/** Fully-qualified table name. */
function opus_watch_table() {
	global $wpdb;
	return $wpdb->prefix . 'opus_watches';
}

/** Create the watches table if it does not yet exist. */
function opus_watch_create_table() {
	global $wpdb;
	$table   = opus_watch_table();
	$collate = $wpdb->get_charset_collate();

	// Direct CREATE (dbDelta is finicky with FULLTEXT); idempotent.
	$wpdb->query(
		"CREATE TABLE IF NOT EXISTS {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			url VARCHAR(255) NOT NULL,
			brand VARCHAR(80) NOT NULL DEFAULT '',
			family VARCHAR(190) NOT NULL DEFAULT '',
			family_url VARCHAR(255) NOT NULL DEFAULT '',
			reference VARCHAR(190) NOT NULL DEFAULT '',
			name VARCHAR(255) NOT NULL DEFAULT '',
			movement VARCHAR(255) NOT NULL DEFAULT '',
			caliber_url VARCHAR(255) DEFAULT NULL,
			produced VARCHAR(40) NOT NULL DEFAULT '',
			produced_year SMALLINT UNSIGNED DEFAULT NULL,
			limited TINYINT(1) NOT NULL DEFAULT 0,
			diameter_mm DECIMAL(5,2) DEFAULT NULL,
			material VARCHAR(80) NOT NULL DEFAULT '',
			dial_color VARCHAR(80) NOT NULL DEFAULT '',
			specs_json LONGTEXT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY url (url),
			KEY brand (brand),
			KEY family (family),
			KEY material (material),
			KEY produced_year (produced_year),
			KEY diameter_mm (diameter_mm),
			FULLTEXT KEY ft_search (name, reference, family)
		) ENGINE=InnoDB {$collate}"
	);
}
add_action( 'after_switch_theme', 'opus_watch_create_table' );

/* ─────────────────────────────────────────────────────────────
 * Query helpers
 * ───────────────────────────────────────────────────────────── */

/** Whitelisted sort modes → ORDER BY clause. */
function opus_watch_sorts() {
	return array(
		'relevance' => 'brand ASC, family ASC, name ASC',
		'name'      => 'name ASC',
		'year_desc' => 'produced_year DESC, name ASC',
		'year_asc'  => 'produced_year ASC, name ASC',
		'size_asc'  => 'diameter_mm ASC, name ASC',
		'size_desc' => 'diameter_mm DESC, name ASC',
	);
}

/**
 * Search/browse the watch table.
 *
 * @param array $args search, brands[], materials[], size_min, size_max,
 *                    year_min, year_max, limited, sort, page, per_page.
 * @return array { items: row[], total: int, page, per_page, pages }
 */
function opus_watch_query( $args = array() ) {
	global $wpdb;
	$table = opus_watch_table();

	$args = wp_parse_args(
		$args,
		array(
			'search'    => '',
			'brands'    => array(),
			'materials' => array(),
			'size_min'  => 0,
			'size_max'  => 0,
			'year_min'  => 0,
			'year_max'  => 0,
			'limited'   => 0,
			'sort'      => 'relevance',
			'page'      => 1,
			'per_page'  => 24,
		)
	);

	$where  = array( '1=1' );
	$params = array();

	$search = trim( (string) $args['search'] );
	if ( '' !== $search ) {
		$like     = '%' . $wpdb->esc_like( $search ) . '%';
		$where[]  = '(name LIKE %s OR reference LIKE %s OR family LIKE %s)';
		$params[] = $like;
		$params[] = $like;
		$params[] = $like;
	}

	$brands = array_filter( array_map( 'trim', (array) $args['brands'] ) );
	if ( $brands ) {
		$ph       = implode( ',', array_fill( 0, count( $brands ), '%s' ) );
		$where[]  = "brand IN ($ph)";
		$params   = array_merge( $params, $brands );
	}

	$materials = array_filter( array_map( 'trim', (array) $args['materials'] ) );
	if ( $materials ) {
		$ph      = implode( ',', array_fill( 0, count( $materials ), '%s' ) );
		$where[] = "material IN ($ph)";
		$params  = array_merge( $params, $materials );
	}

	if ( (float) $args['size_min'] > 0 ) {
		$where[]  = 'diameter_mm >= %f';
		$params[] = (float) $args['size_min'];
	}
	if ( (float) $args['size_max'] > 0 ) {
		$where[]  = 'diameter_mm <= %f';
		$params[] = (float) $args['size_max'];
	}
	if ( (int) $args['year_min'] > 0 ) {
		$where[]  = 'produced_year >= %d';
		$params[] = (int) $args['year_min'];
	}
	if ( (int) $args['year_max'] > 0 ) {
		$where[]  = 'produced_year <= %d';
		$params[] = (int) $args['year_max'];
	}
	if ( (int) $args['limited'] ) {
		$where[] = 'limited = 1';
	}

	$where_sql = implode( ' AND ', $where );

	// Total.
	$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
	$total     = (int) $wpdb->get_var( $params ? $wpdb->prepare( $count_sql, $params ) : $count_sql );

	// Page window.
	$sorts    = opus_watch_sorts();
	$order    = isset( $sorts[ $args['sort'] ] ) ? $sorts[ $args['sort'] ] : $sorts['relevance'];
	$per_page = max( 1, min( 60, (int) $args['per_page'] ) );
	$page     = max( 1, (int) $args['page'] );
	$offset   = ( $page - 1 ) * $per_page;

	$sql         = "SELECT id, url, brand, family, reference, name, produced, produced_year, limited, diameter_mm, material, dial_color
		FROM {$table} WHERE {$where_sql} ORDER BY {$order} LIMIT %d OFFSET %d";
	$page_params = array_merge( $params, array( $per_page, $offset ) );
	$items       = $wpdb->get_results( $wpdb->prepare( $sql, $page_params ), ARRAY_A );

	return array(
		'items'    => $items ? $items : array(),
		'total'    => $total,
		'page'     => $page,
		'per_page' => $per_page,
		'pages'    => (int) ceil( $total / $per_page ),
	);
}

/** Fetch a single watch row by id. */
function opus_watch_get( $id ) {
	global $wpdb;
	$table = opus_watch_table();
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $id ), ARRAY_A );
}

/** Facet option lists for the filter UI (whole-table counts). */
function opus_watch_facets() {
	global $wpdb;
	$table = opus_watch_table();
	$cache = wp_cache_get( 'opus_watch_facets' );
	if ( false !== $cache ) {
		return $cache;
	}
	$facets = array(
		'brands'    => $wpdb->get_results( "SELECT brand AS value, COUNT(*) AS n FROM {$table} WHERE brand<>'' GROUP BY brand ORDER BY n DESC", ARRAY_A ),
		'materials' => $wpdb->get_results( "SELECT material AS value, COUNT(*) AS n FROM {$table} WHERE material<>'' GROUP BY material ORDER BY n DESC LIMIT 12", ARRAY_A ),
		'year_min'  => (int) $wpdb->get_var( "SELECT MIN(produced_year) FROM {$table} WHERE produced_year IS NOT NULL" ),
		'year_max'  => (int) $wpdb->get_var( "SELECT MAX(produced_year) FROM {$table} WHERE produced_year IS NOT NULL" ),
		'total'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ),
	);
	wp_cache_set( 'opus_watch_facets', $facets, '', 300 );
	return $facets;
}

/**
 * Detail-page URL for a watch.
 *
 * Accepts a row (cheap — the caller usually has one) or an id, which costs a
 * lookup because the readable slug is built from brand, name and reference.
 * See opus_watch_slug() in watchdb-seo.php.
 */
function opus_watch_permalink( $watch ) {
	if ( ! is_array( $watch ) ) {
		$watch = opus_watch_get( (int) $watch );
	}
	if ( ! $watch ) {
		return home_url( '/database/' );
	}
	return home_url( '/database/' . opus_watch_slug( $watch ) . '/' );
}

/** Render a single result card (used for the server-rendered first page). */
function opus_watch_card_html( $it ) {
	$meta = array();
	if ( ! empty( $it['family'] ) ) {
		$meta[] = $it['family'];
	}
	if ( ! empty( $it['diameter_mm'] ) ) {
		$meta[] = rtrim( rtrim( number_format( (float) $it['diameter_mm'], 2 ), '0' ), '.' ) . ' mm';
	}
	if ( ! empty( $it['material'] ) ) {
		$meta[] = $it['material'];
	}
	if ( ! empty( $it['produced_year'] ) ) {
		$meta[] = $it['produced_year'];
	}
	ob_start();
	?>
	<a class="wdb-card" href="<?php echo esc_url( opus_watch_permalink( $it ) ); ?>">
		<?php if ( ! empty( $it['limited'] ) ) : ?><span class="wdb-badge"><?php esc_html_e( 'Gelimiteerd', 'maison-opus' ); ?></span><?php endif; ?>
		<span class="wdb-card-brand"><?php echo esc_html( $it['brand'] ); ?></span>
		<span class="wdb-card-name"><?php echo esc_html( $it['name'] ); ?></span>
		<?php if ( ! empty( $it['reference'] ) ) : ?>
			<span class="wdb-card-ref"><?php echo esc_html( $it['reference'] ); ?></span>
		<?php endif; ?>
		<span class="wdb-card-meta"><?php echo esc_html( implode( ' · ', $meta ) ); ?></span>
	</a>
	<?php
	return ob_get_clean();
}

/** If a watch's reference is in stock in Woo, return that product permalink. */
function opus_watch_instock_link( $reference ) {
	if ( '' === $reference || ! function_exists( 'wc_get_products' ) ) {
		return '';
	}
	// References in the scrape can carry "(aka: …)"; match the leading token.
	$ref = trim( preg_split( '/\s*\(/', $reference )[0] );
	if ( '' === $ref ) {
		return '';
	}
	$ids = wc_get_products(
		array(
			'limit'      => 1,
			'status'     => 'publish',
			'return'     => 'ids',
			'sku'        => $ref,
		)
	);
	return $ids ? get_permalink( $ids[0] ) : '';
}

/* ─────────────────────────────────────────────────────────────
 * REST endpoint — /wp-json/opus/v1/watches
 * ───────────────────────────────────────────────────────────── */
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'opus/v1',
			'/watches',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => function ( WP_REST_Request $req ) {
					$csv = function ( $v ) {
						return array_filter( array_map( 'sanitize_text_field', explode( ',', (string) $v ) ) );
					};
					$result = opus_watch_query(
						array(
							'search'    => sanitize_text_field( $req->get_param( 'search' ) ),
							'brands'    => $csv( $req->get_param( 'brands' ) ),
							'materials' => $csv( $req->get_param( 'materials' ) ),
							'size_min'  => (float) $req->get_param( 'size_min' ),
							'size_max'  => (float) $req->get_param( 'size_max' ),
							'year_min'  => (int) $req->get_param( 'year_min' ),
							'year_max'  => (int) $req->get_param( 'year_max' ),
							'limited'   => (int) $req->get_param( 'limited' ),
							'sort'      => sanitize_key( $req->get_param( 'sort' ) ),
							'page'      => (int) $req->get_param( 'page' ),
							'per_page'  => (int) $req->get_param( 'per_page' ),
						)
					);
					// Decorate items with their detail URL.
					foreach ( $result['items'] as &$it ) {
						$it['permalink'] = opus_watch_permalink( $it );
					}
					return rest_ensure_response( $result );
				},
			)
		);
	}
);

/* ─────────────────────────────────────────────────────────────
 * Suggest endpoint — /wp-json/opus/v1/suggest
 * Powers the autocomplete on the zoekopdracht form (model/family names,
 * optionally scoped to a brand).
 * ───────────────────────────────────────────────────────────── */
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'opus/v1',
			'/suggest',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => function ( WP_REST_Request $req ) {
					$q = trim( (string) $req->get_param( 'q' ) );
					if ( mb_strlen( $q ) < 2 ) {
						return rest_ensure_response( array() );
					}
					$brand = sanitize_text_field( $req->get_param( 'brand' ) );
					$res   = opus_watch_query(
						array(
							'search'   => $q,
							'brands'   => '' !== $brand ? array( $brand ) : array(),
							'per_page' => 8,
							'sort'     => 'relevance',
						)
					);
					$out = array();
					foreach ( $res['items'] as $it ) {
						// Clean reference for autofill (drop trailing "(aka: …)").
						$ref   = trim( preg_split( '/\s*\(/', (string) $it['reference'] )[0] );
						$out[] = array(
							'brand'     => $it['brand'],
							'family'    => $it['family'],
							'name'      => $it['name'],
							'reference' => $ref,
						);
					}
					return rest_ensure_response( $out );
				},
			)
		);
	}
);

/* ─────────────────────────────────────────────────────────────
 * Pretty routes — /database/watch/{id}/ (detail) and
 * /database/{brand-slug}/ (the database pre-scoped to one brand).
 * ───────────────────────────────────────────────────────────── */
add_action(
	'init',
	function () {
		add_rewrite_rule( '^database/watch/([0-9]+)/?$', 'index.php?opus_watch_id=$matches[1]', 'top' );
		add_rewrite_rule( '^database/(?!watch/)([a-z0-9-]+)/?$', 'index.php?pagename=database&opus_db_brand=$matches[1]', 'top' );
	}
);
add_filter(
	'query_vars',
	function ( $vars ) {
		$vars[] = 'opus_watch_id';
		$vars[] = 'opus_db_brand';
		return $vars;
	}
);

/**
 * Resolve a /database/{slug}/ brand slug to the brand name as stored in the
 * watches table ('rolex' => 'Rolex'). Returns '' for unknown slugs.
 */
function opus_db_brand_from_slug( $slug ) {
	if ( '' === $slug ) {
		return '';
	}
	foreach ( opus_watch_facets()['brands'] as $b ) {
		if ( sanitize_title( $b['value'] ) === $slug ) {
			return $b['value'];
		}
	}
	return '';
}

/** URL of the database scoped to one brand, or '' if the brand isn't in it. */
function opus_db_brand_url( $term ) {
	return opus_db_brand_from_slug( $term->slug ) ? home_url( '/database/' . $term->slug . '/' ) : '';
}
add_filter(
	'template_include',
	function ( $template ) {
		// Either route: the readable slug, or the legacy numeric id that
		// template_redirect sends on to it.
		if ( get_query_var( 'opus_watch_id' ) || get_query_var( 'opus_watch_slug' ) ) {
			$detail = get_template_directory() . '/inc/watch-detail.php';
			if ( file_exists( $detail ) ) {
				return $detail;
			}
		}
		return $template;
	}
);

/* ─────────────────────────────────────────────────────────────
 * WP-CLI importer — `wp opus import-watches [--reset]`
 * Reads the JSON exported by scripts/export-watchbase.py.
 * Idempotent (upsert on url) and re-runnable as the crawl grows.
 * ───────────────────────────────────────────────────────────── */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'opus import-watches',
		function ( $args, $assoc ) {
			global $wpdb;
			opus_watch_create_table();
			$table = opus_watch_table();

			$file = ! empty( $args[0] ) ? $args[0] : get_template_directory() . '/data/watches.json';
			if ( ! file_exists( $file ) ) {
				WP_CLI::error( "JSON not found: {$file} (run scripts/export-watchbase.py first)" );
			}

			if ( isset( $assoc['reset'] ) ) {
				$wpdb->query( "TRUNCATE TABLE {$table}" );
				WP_CLI::log( 'Table truncated.' );
			}

			$rows = json_decode( file_get_contents( $file ), true );
			if ( ! is_array( $rows ) ) {
				WP_CLI::error( 'Could not parse JSON.' );
			}

			$total   = count( $rows );
			$done    = 0;
			$batch   = 200;
			$cols    = array( 'url', 'brand', 'family', 'family_url', 'reference', 'name', 'movement', 'caliber_url', 'produced', 'produced_year', 'limited', 'diameter_mm', 'material', 'dial_color', 'specs_json' );
			$progress = \WP_CLI\Utils\make_progress_bar( 'Importing watches', $total );

			$nullable = array( 'produced_year', 'diameter_mm', 'caliber_url' );
			foreach ( array_chunk( $rows, $batch ) as $chunk ) {
				$values = array();
				$params = array();
				foreach ( $chunk as $r ) {
					$cells = array();
					foreach ( $cols as $c ) {
						$v = isset( $r[ $c ] ) ? $r[ $c ] : null;
						if ( in_array( $c, $nullable, true ) && ( null === $v || '' === $v ) ) {
							$cells[] = 'NULL'; // keep missing numerics/URLs as real NULL, not 0/''.
						} else {
							$cells[]  = '%s';
							$params[] = ( null === $v ) ? '' : $v;
						}
					}
					$values[] = '(' . implode( ',', $cells ) . ')';
				}
				$set = array();
				foreach ( $cols as $c ) {
					if ( 'url' === $c ) {
						continue;
					}
					$set[] = "{$c}=VALUES({$c})";
				}
				$sql = "INSERT INTO {$table} (" . implode( ',', $cols ) . ') VALUES '
					. implode( ',', $values )
					. ' ON DUPLICATE KEY UPDATE ' . implode( ',', $set );
				$wpdb->query( $wpdb->prepare( $sql, $params ) );
				$done += count( $chunk );
				$progress->tick( count( $chunk ) );
			}
			$progress->finish();
			$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
			WP_CLI::success( "Imported/updated {$done} rows. Table now holds {$count} watches." );
		}
	);
}
