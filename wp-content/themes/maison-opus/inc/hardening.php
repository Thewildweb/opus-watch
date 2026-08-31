<?php
/**
 * Hardening — close off the things that make a WordPress shop easy to attack.
 *
 * Server-side hardening (SSH, firewall, fail2ban, security headers) lives in
 * deploy/. This file covers what only WordPress can do.
 *
 * @package maison-opus
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stop username enumeration through the REST API.
 *
 * /wp-json/wp/v2/users listed every account to anyone who asked, which handed
 * out the administrator's login name. Half of a brute-force attempt is knowing
 * the username. Editors still get the endpoint once they are logged in, which
 * is when the block editor needs it.
 */
add_filter(
	'rest_endpoints',
	function ( $endpoints ) {
		if ( is_user_logged_in() ) {
			return $endpoints;
		}
		unset( $endpoints['/wp/v2/users'] );
		unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
		return $endpoints;
	}
);

/**
 * Author archives are the other half of the same leak: /?author=1 redirects to
 * /author/{login}/ and prints the login name in a URL. The site has no author
 * pages to speak of, so send them home.
 *
 * Priority 1 so this runs before redirect_canonical, which would otherwise
 * put the login name in a Location header on the way past.
 */
add_action(
	'template_redirect',
	function () {
		if ( is_author() ) {
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}
	},
	1
);

/** Don't advertise the WordPress version to anyone deciding which CVE to try. */
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

/**
 * WPML prints its own generator tag — `WPML ver:4.9.7 stt:37,1;` — which
 * names a paid plugin and its exact version. Same reasoning, but it is a
 * method on the SitePress instance, so it can only be unhooked once that
 * object exists.
 */
add_action(
	'wp_head',
	function () {
		global $sitepress;
		if ( $sitepress && method_exists( $sitepress, 'meta_generator_tag' ) ) {
			remove_action( 'wp_head', array( $sitepress, 'meta_generator_tag' ) );
		}
	},
	0
);
