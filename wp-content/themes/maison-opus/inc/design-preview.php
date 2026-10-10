<?php
/**
 * Design preview — lets logged-in users switch the site to a work-in-progress
 * design while the public keeps seeing heritage.
 *
 * A design ("skin") is a stylesheet at assets/css/styles/<skin>.css, loaded
 * ON TOP of heritage.css, so a skin only has to restyle what it changes —
 * pages it doesn't touch keep working. An optional
 * template-parts/home-<skin>.php replaces the homepage markup.
 *
 * Switch via the "Design" menu in the admin bar (or ?opus_skin=<skin>); the
 * choice lives in a cookie and is ignored for anyone not logged in.
 *
 * @package maison-opus
 */

/** Skins on offer: slug => admin-bar label. heritage is the live design. */
function maison_opus_skins() {
	return array(
		'heritage' => 'Heritage (live)',
		'v2'       => 'Nieuw ontwerp (v2)',
	);
}

/** The skin for this request — always heritage for visitors. */
function maison_opus_skin() {
	if ( ! is_user_logged_in() || empty( $_COOKIE['opus_skin'] ) ) {
		return 'heritage';
	}
	$skin = sanitize_key( wp_unslash( $_COOKIE['opus_skin'] ) );
	return array_key_exists( $skin, maison_opus_skins() ) ? $skin : 'heritage';
}

/** ?opus_skin=<slug> → store the choice and bounce back to the clean URL. */
function maison_opus_skin_switch() {
	if ( ! isset( $_GET['opus_skin'] ) || ! is_user_logged_in() ) {
		return;
	}
	$skin = sanitize_key( wp_unslash( $_GET['opus_skin'] ) );
	if ( array_key_exists( $skin, maison_opus_skins() ) ) {
		setcookie( 'opus_skin', $skin, time() + YEAR_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true );
	}
	wp_safe_redirect( remove_query_arg( 'opus_skin' ) );
	exit;
}
add_action( 'init', 'maison_opus_skin_switch' );

/** Skin fonts (assets/css/fonts-<skin>.css, optional) + stylesheet after heritage. */
function maison_opus_skin_assets() {
	$skin = maison_opus_skin();
	$rel  = '/assets/css/styles/' . $skin . '.css';
	if ( 'heritage' === $skin || ! file_exists( get_template_directory() . $rel ) ) {
		return;
	}
	$deps  = array( 'maison-opus-skin' );
	$fonts = '/assets/css/fonts-' . $skin . '.css';
	if ( file_exists( get_template_directory() . $fonts ) ) {
		wp_enqueue_style( 'maison-opus-fonts-' . $skin, get_template_directory_uri() . $fonts, array(), maison_opus_asset_ver( $fonts ) );
		$deps[] = 'maison-opus-fonts-' . $skin;
	}
	wp_enqueue_style(
		'maison-opus-skin-' . $skin,
		get_template_directory_uri() . $rel,
		$deps,
		maison_opus_asset_ver( $rel )
	);
	$js = '/assets/js/' . $skin . '.js';
	if ( file_exists( get_template_directory() . $js ) ) {
		wp_enqueue_script( 'maison-opus-skin-' . $skin, get_template_directory_uri() . $js, array(), maison_opus_asset_ver( $js ), array( 'strategy' => 'defer', 'in_footer' => true ) );
	}
}
add_action( 'wp_enqueue_scripts', 'maison_opus_skin_assets', 15 );

/** body.skin-<slug> — for scoping rules to one design. */
function maison_opus_skin_body_class( $classes ) {
	$classes[] = 'skin-' . maison_opus_skin();
	return $classes;
}
add_filter( 'body_class', 'maison_opus_skin_body_class' );

/** Homepage template part for the active skin, falling back to heritage. */
function maison_opus_home_part() {
	$skin = maison_opus_skin();
	return locate_template( 'template-parts/home-' . $skin . '.php' ) ? $skin : 'heritage';
}

/** Admin-bar "Design" menu with one entry per skin. */
function maison_opus_skin_admin_bar( $bar ) {
	if ( is_admin() ) {
		return;
	}
	$skins  = maison_opus_skins();
	$active = maison_opus_skin();
	$bar->add_node(
		array(
			'id'    => 'opus-skin',
			'title' => 'Design: ' . esc_html( $skins[ $active ] ),
		)
	);
	foreach ( $skins as $slug => $label ) {
		$bar->add_node(
			array(
				'parent' => 'opus-skin',
				'id'     => 'opus-skin-' . $slug,
				'title'  => ( $slug === $active ? '✓ ' : '' ) . esc_html( $label ),
				'href'   => add_query_arg( 'opus_skin', $slug ),
			)
		);
	}
}
add_action( 'admin_bar_menu', 'maison_opus_skin_admin_bar', 100 );

/** Make the Design menu stand out: gold for live, orange while previewing. */
function maison_opus_skin_admin_bar_style() {
	if ( ! is_admin_bar_showing() ) {
		return;
	}
	$bg = 'heritage' === maison_opus_skin() ? '#b08d3c' : '#d9480f';
	wp_add_inline_style(
		'admin-bar',
		"#wpadminbar #wp-admin-bar-opus-skin > .ab-item { background: {$bg}; color: #fff; font-weight: 600; }
		#wpadminbar #wp-admin-bar-opus-skin:hover > .ab-item,
		#wpadminbar #wp-admin-bar-opus-skin.hover > .ab-item { background: {$bg}; color: #fff; filter: brightness(1.1); }"
	);
}
add_action( 'wp_enqueue_scripts', 'maison_opus_skin_admin_bar_style' );
