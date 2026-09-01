<?php
/**
 * Watch database × Elasticsearch — typo-tolerant, relevance-ranked text
 * search over the wp_opus_watches reference table.
 *
 * The ES index is a *search accelerator*, not a datastore: MySQL stays the
 * source of truth, opus_watch_query() fetches the rows; ES only supplies a
 * ranked id list for the free-text part. No ES (local dev, container down,
 * request error) → the caller falls back to the original SQL LIKE search.
 *
 * Reindex after an import: `wp opus watchdb-reindex` (or call
 * opus_watch_es_index_all() from wp eval).
 *
 * @package maison-opus
 */

defined( 'ABSPATH' ) || exit;

const OPUS_WATCH_ES_INDEX = 'opus_watches';

/** ES endpoint (EP_HOST, shared with ElasticPress) or '' when unavailable. */
function opus_watch_es_host() {
	return defined( 'EP_HOST' ) ? rtrim( EP_HOST, '/' ) : '';
}

/** Low-level ES request. Returns decoded array or WP_Error. */
function opus_watch_es_request( $method, $path, $body = null, $timeout = 2 ) {
	$host = opus_watch_es_host();
	if ( ! $host ) {
		return new WP_Error( 'no_es', 'EP_HOST not defined' );
	}
	$args = array(
		'method'  => $method,
		'timeout' => $timeout,
		'headers' => array( 'Content-Type' => strpos( $path, '_bulk' ) !== false ? 'application/x-ndjson' : 'application/json' ),
	);
	if ( null !== $body ) {
		$args['body'] = is_string( $body ) ? $body : wp_json_encode( $body );
	}
	$res = wp_remote_request( $host . $path, $args );
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	$code = wp_remote_retrieve_response_code( $res );
	$data = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( $code >= 400 ) {
		return new WP_Error( 'es_http_' . $code, wp_remote_retrieve_body( $res ) );
	}
	return is_array( $data ) ? $data : array();
}

/**
 * Ranked watch ids for a free-text query, or null when ES can't answer
 * (caller then falls back to SQL LIKE).
 *
 * @return int[]|null
 */
function opus_watch_es_search( $search, $limit = 500 ) {
	static $cache = array();
	$key = $search . '|' . $limit;
	if ( array_key_exists( $key, $cache ) ) {
		return $cache[ $key ];
	}

	// A long digit run means a reference number — fuzziness would rank
	// near-miss references (26612 vs 126610) above true prefix matches.
	$fuzziness = preg_match( '/\d{3,}/', $search ) ? 0 : 'AUTO';

	$query = array(
		'size'    => $limit,
		'_source' => false,
		'query'   => array(
			'bool' => array(
				'should' => array(
					// Exact-ish reference hits rank on top.
					array( 'term' => array( 'reference.keyword' => array( 'value' => strtoupper( $search ), 'boost' => 10 ) ) ),
					array( 'match_phrase_prefix' => array( 'reference' => array( 'query' => $search, 'boost' => 6 ) ) ),
					// Fuzzy text over the descriptive fields.
					array(
						'multi_match' => array(
							'query'     => $search,
							'fields'    => array( 'name^3', 'family^2', 'brand^2', 'reference', 'material', 'dial_color' ),
							'fuzziness' => $fuzziness,
							'operator'  => 'and',
						),
					),
					// Looser OR variant so multi-word queries still match partially.
					array(
						'multi_match' => array(
							'query'     => $search,
							'fields'    => array( 'name^2', 'family', 'brand' ),
							'fuzziness' => $fuzziness,
						),
					),
				),
				'minimum_should_match' => 1,
			),
		),
	);

	$res = opus_watch_es_request( 'POST', '/' . OPUS_WATCH_ES_INDEX . '/_search', $query );
	if ( is_wp_error( $res ) || ! isset( $res['hits']['hits'] ) ) {
		return $cache[ $key ] = null;
	}
	$ids = array();
	foreach ( $res['hits']['hits'] as $hit ) {
		$ids[] = (int) $hit['_id'];
	}
	return $cache[ $key ] = $ids;
}

/**
 * (Re)build the ES index from the wp_opus_watches table. Returns the number
 * of documents indexed, or WP_Error.
 */
function opus_watch_es_index_all() {
	global $wpdb;
	$host = opus_watch_es_host();
	if ( ! $host ) {
		return new WP_Error( 'no_es', 'EP_HOST not defined' );
	}

	// Fresh index with explicit settings (single node → no replicas).
	opus_watch_es_request( 'DELETE', '/' . OPUS_WATCH_ES_INDEX, null, 10 );
	$created = opus_watch_es_request(
		'PUT',
		'/' . OPUS_WATCH_ES_INDEX,
		array(
			'settings' => array( 'number_of_shards' => 1, 'number_of_replicas' => 0 ),
			'mappings' => array(
				'properties' => array(
					'name'       => array( 'type' => 'text' ),
					'family'     => array( 'type' => 'text' ),
					'brand'      => array( 'type' => 'text', 'fields' => array( 'keyword' => array( 'type' => 'keyword' ) ) ),
					'reference'  => array( 'type' => 'text', 'fields' => array( 'keyword' => array( 'type' => 'keyword' ) ) ),
					'material'   => array( 'type' => 'text' ),
					'dial_color' => array( 'type' => 'text' ),
				),
			),
		),
		15
	);
	if ( is_wp_error( $created ) ) {
		return $created;
	}

	$table = opus_watch_table();
	$rows  = $wpdb->get_results( "SELECT id, brand, family, reference, name, material, dial_color FROM {$table}", ARRAY_A );
	$count = 0;

	foreach ( array_chunk( $rows, 500 ) as $chunk ) {
		$ndjson = '';
		foreach ( $chunk as $row ) {
			$ndjson .= wp_json_encode( array( 'index' => array( '_id' => (int) $row['id'] ) ) ) . "\n";
			$ndjson .= wp_json_encode(
				array(
					'name'       => (string) $row['name'],
					'family'     => (string) $row['family'],
					'brand'      => (string) $row['brand'],
					'reference'  => (string) strtoupper( $row['reference'] ),
					'material'   => (string) $row['material'],
					'dial_color' => (string) $row['dial_color'],
				)
			) . "\n";
		}
		$res = opus_watch_es_request( 'POST', '/' . OPUS_WATCH_ES_INDEX . '/_bulk?refresh=false', $ndjson, 30 );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$count += count( $chunk );
	}

	opus_watch_es_request( 'POST', '/' . OPUS_WATCH_ES_INDEX . '/_refresh', null, 10 );
	return $count;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'opus watchdb-reindex',
		function () {
			$n = opus_watch_es_index_all();
			if ( is_wp_error( $n ) ) {
				WP_CLI::error( $n->get_error_message() );
			}
			WP_CLI::success( "Indexed $n watches into Elasticsearch." );
		}
	);
}
