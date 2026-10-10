<?php
/**
 * Thema-instellingen — backend editor for the theme's frontend strings and
 * content images (Weergave → Thema-instellingen).
 *
 * Texts: the theme's strings are gettext calls (domain "maison-opus")
 * registered in WPML String Translation. This screen lists them grouped by
 * site area and lets the client edit every language:
 *
 *  - The source language (nl) is stored as an override in the
 *    `opus_text_overrides` option and applied at runtime through the gettext
 *    filter — templates keep their original strings, so WPML's translations
 *    stay linked.
 *  - Other languages are written straight into WPML String Translation (and
 *    the .mo files regenerated), so ST remains the single source of truth.
 *    The language columns follow WPML's active languages — adding a language
 *    in WPML adds a column here.
 *
 * Images: content images (hero, page images) resolve through
 * opus_theme_image_url(), an option-backed lookup that falls back to the
 * bundled theme asset. The Afbeeldingen tab manages them via the media
 * library; images are shared across languages.
 *
 * Strings added to templates later show up after a WPML theme rescan.
 *
 * @package maison-opus
 */

defined( 'ABSPATH' ) || exit;

/* ─────────────────────────────────────────────────────────────
 * Runtime: Dutch text overrides + image lookup.
 * ───────────────────────────────────────────────────────────── */

/**
 * Override source-language strings at render time. Other languages are
 * served by WPML's own MO files.
 */
function opus_text_override( $translation, $text, $domain ) {
	static $overrides = null;
	if ( null === $overrides ) {
		$overrides = get_option( 'opus_text_overrides', array() );
	}
	if ( ! $overrides ) {
		return $translation;
	}
	$lang    = apply_filters( 'wpml_current_language', null );
	$default = apply_filters( 'wpml_default_language', 'nl' );
	if ( $lang && $lang !== $default ) {
		return $translation;
	}
	$key = md5( $text );
	return isset( $overrides[ $key ] ) ? $overrides[ $key ] : $translation;
}
add_filter( 'gettext_maison-opus', 'opus_text_override', 20, 3 );

/**
 * URL for a content image: the attachment chosen in Thema-instellingen, or
 * the bundled theme asset as default.
 *
 * @param string $key         Image key (see opus_theme_image_fields()).
 * @param string $default_rel Theme-relative default, e.g. 'assets/images/hero-rolex.webp'.
 */
function opus_theme_image_url( $key, $default_rel ) {
	$image = opus_theme_image( $key, $default_rel );
	return $image['url'];
}

/**
 * The same image, with its dimensions.
 *
 * Templates need width and height on the tag or the page reflows once the
 * photo loads. Returns array( url, width, height ); width and height are 0
 * when they cannot be determined, and the caller then omits the attributes.
 *
 * @param string $key         Image key (see opus_theme_image_fields()).
 * @param string $default_rel Theme-relative default, e.g. 'assets/images/hero-rolex.webp'.
 */
function opus_theme_image( $key, $default_rel ) {
	$images = get_option( 'opus_theme_images', array() );

	if ( ! empty( $images[ $key ] ) ) {
		$src = wp_get_attachment_image_src( (int) $images[ $key ], 'full' );
		if ( $src ) {
			return array(
				'url'    => $src[0],
				'width'  => (int) $src[1],
				'height' => (int) $src[2],
			);
		}
	}

	static $sizes = array();
	if ( ! isset( $sizes[ $default_rel ] ) ) {
		$path                   = get_template_directory() . '/' . ltrim( $default_rel, '/' );
		$size                   = file_exists( $path ) ? getimagesize( $path ) : false;
		$sizes[ $default_rel ] = $size ? array( (int) $size[0], (int) $size[1] ) : array( 0, 0 );
	}

	return array(
		'url'    => maison_opus_asset_uri( $default_rel ),
		'width'  => $sizes[ $default_rel ][0],
		'height' => $sizes[ $default_rel ][1],
	);
}

/** `width="1920" height="1280"`, or '' when the size is unknown. */
function opus_image_dims( $image ) {
	if ( empty( $image['width'] ) || empty( $image['height'] ) ) {
		return '';
	}
	return sprintf( 'width="%d" height="%d"', $image['width'], $image['height'] );
}

/* ─────────────────────────────────────────────────────────────
 * Registries: sections, image fields, languages.
 * ───────────────────────────────────────────────────────────── */

/** Text sections: label + the template files whose strings belong to it. */
function opus_site_texts_sections() {
	return array(
		'algemeen'     => array(
			'label' => 'Algemeen (header & footer)',
			'files' => array( 'header.php', 'footer.php', 'template-parts/language-switcher.php', 'sidebar.php', 'index.php' ),
		),
		'homepage'     => array(
			'label' => 'Homepage',
			'files' => array( 'front-page.php', 'template-parts/home-heritage.php', 'template-parts/home-v2.php' ),
		),
		'taxatie'      => array(
			'label' => 'Taxatie',
			'files' => array( 'page-taxatie.php', 'inc/taxatie.php' ),
		),
		'zoekopdracht' => array(
			'label' => 'Zoekopdracht',
			'files' => array( 'page-zoekopdracht.php' ),
		),
		'contact'      => array(
			'label' => 'Contact',
			'files' => array( 'page-contact.php', 'inc/contact.php' ),
		),
		'database'     => array(
			'label' => 'Database',
			'files' => array( 'page-database.php', 'inc/watchdb.php', 'inc/watch-detail.php' ),
		),
		'diensten'     => array(
			'label' => 'Diensten',
			'files' => array( 'inc/service-inquiry.php', 'inc/service-single.php', 'woocommerce/taxonomy-product_cat-service.php', 'woocommerce/taxonomy-product_cat-servicing.php' ),
		),
		'shop'         => array(
			'label' => 'Shop & product',
			'files' => array( 'inc/shop-filters.php', 'woocommerce/content-product.php', 'woocommerce/content-single-product.php', 'woocommerce/taxonomy-product_brand.php', 'functions.php' ),
		),
		'overig'       => array(
			'label' => 'Overig',
			'files' => array(),
		),
	);
}

/** Content images managed on the Afbeeldingen tab. */
function opus_theme_image_fields() {
	return array(
		'home_hero'        => array(
			'label'   => 'Homepage — hero',
			'default' => 'assets/images/hero-heritage.webp',
			'hint'    => 'De grote afbeelding bovenaan de homepage.',
		),
		'home_tile_service' => array(
			'label'   => 'Homepage — tegel onderhoud',
			'default' => 'assets/images/tile-movement.webp',
			'hint'    => 'Afbeelding bij het blok "Onderhoud & restauratie".',
		),
		'home_tile_buy'    => array(
			'label'   => 'Homepage — tegel inkoop',
			'default' => 'assets/images/tile-buy.webp',
			'hint'    => 'Afbeelding bij het blok "Wij kopen ook uw horloge".',
		),
		'taxatie_image'    => array(
			'label'   => 'Taxatie — paginafoto',
			'default' => 'assets/images/hero-rolex.webp',
			'hint'    => 'De foto naast de introductie op de taxatiepagina.',
		),
		'zoekopdracht_image' => array(
			'label'   => 'Zoekopdracht — paginafoto',
			'default' => 'assets/images/hero-rolex.webp',
			'hint'    => 'De foto naast de introductie op de zoekopdrachtpagina.',
		),
		'diensten_image'   => array(
			'label'   => 'Diensten — paginafoto',
			'default' => 'assets/images/watch-speedmaster.webp',
			'hint'    => 'De foto naast de introductie op de dienstenpagina.',
		),
		'service_fallback' => array(
			'label'   => 'Dienst — standaardafbeelding',
			'default' => 'assets/images/hero-rolex.webp',
			'hint'    => 'Gebruikt op een dienstpagina zonder eigen video of afbeelding.',
		),
	);
}

/* ─────────────────────────────────────────────────────────────
 * Bedrijfsprofiel — company facts, edited on their own tab and pulled
 * into content (legal pages, footer) via the [opus_bedrijf] shortcode.
 * ───────────────────────────────────────────────────────────── */

function opus_business_profile_fields() {
	return array(
		'kvk'              => array( 'label' => 'KvK-nummer', 'default' => '93934866', 'hint' => 'Getoond in de footer en op de contactpagina.' ),
		'vestigingsnr'     => array( 'label' => 'Vestigingsnummer', 'default' => '', 'hint' => '' ),
		'btw_id'           => array( 'label' => 'Btw-identificatienummer', 'default' => '', 'hint' => 'Gebruikt in de privacyverklaring en algemene voorwaarden.' ),
		'vervoerder'       => array( 'label' => 'Vervoerder', 'default' => '', 'hint' => 'Bijv. PostNL of DHL — genoemd in de privacyverklaring.' ),
		'garantie_uurwerk' => array( 'label' => 'Garantie op het uurwerk', 'default' => '', 'hint' => 'Bijv. "24 maanden" — artikel garantie in de voorwaarden.' ),
		'levertijd'        => array( 'label' => 'Levertijd / verzendbelofte', 'default' => '', 'hint' => 'Bijv. "besteld op werkdagen vóór 15:00 uur, de volgende werkdag verzonden".' ),
		'betaalmethoden'   => array( 'label' => 'Betaalmethoden', 'default' => '', 'hint' => 'Bijv. "iDEAL, creditcard en bankoverschrijving" — invullen zodra Mollie is aangesloten.' ),
		'retourbeleid'     => array( 'label' => 'Retourlabel & kosten', 'default' => '', 'hint' => 'Geeft Opus een retourlabel uit, en wie draagt de kosten? Volledige zin(nen).', 'textarea' => true ),
		'bewaartermijn_service' => array( 'label' => 'Bewaartermijn service-dossiers', 'default' => '', 'hint' => 'Bijv. "de garantieperiode plus twee jaar".' ),
		'umami_id'         => array( 'label' => 'Umami website-ID', 'default' => '', 'hint' => 'Statistieken-ID uit analytics.opus.watches; leeg = geen meting.' ),
	);
}

/**
 * Umami analytics — cookieless, so no consent banner is needed. The script
 * only loads for visitors (not for logged-in staff, whose clicks would
 * pollute the numbers) and only when a website-ID is configured.
 */
add_action(
	'wp_head',
	function () {
		$id = opus_business_get( 'umami_id' );
		if ( ! $id || is_user_logged_in() ) {
			return;
		}
		printf(
			'<script defer src="https://analytics.%1$s/script.js" data-website-id="%2$s"></script>' . "\n",
			esc_attr( wp_parse_url( home_url(), PHP_URL_HOST ) === 'localhost' ? 'opus.watches' : preg_replace( '/^www\./', '', wp_parse_url( home_url(), PHP_URL_HOST ) ) ),
			esc_attr( $id )
		);
	},
	20
);

/** A profile value, or the registry default, or '' — no placeholder text. */
function opus_business_get( $key ) {
	$fields = opus_business_profile_fields();
	if ( ! isset( $fields[ $key ] ) ) {
		return '';
	}
	$profile = get_option( 'opus_business_profile', array() );
	$value   = isset( $profile[ $key ] ) ? trim( (string) $profile[ $key ] ) : '';
	return '' !== $value ? $value : $fields[ $key ]['default'];
}

/**
 * [opus_bedrijf veld="btw_id"] — renders the profile value; an unfilled
 * field renders a clearly visible placeholder so a draft page shows what
 * is still missing (and the placeholder never leaks silently into print).
 */
add_shortcode(
	'opus_bedrijf',
	function ( $atts ) {
		$atts  = shortcode_atts( array( 'veld' => '' ), $atts );
		$value = opus_business_get( $atts['veld'] );
		if ( '' !== $value ) {
			return esc_html( $value );
		}
		$fields = opus_business_profile_fields();
		$label  = isset( $fields[ $atts['veld'] ] ) ? $fields[ $atts['veld'] ]['label'] : $atts['veld'];
		return '<mark class="opus-biz-missing">[nog aan te vullen: ' . esc_html( strtolower( $label ) ) . ']</mark>';
	}
);

/**
 * Languages for the editor: default first, then secondary languages.
 *
 * @return array code => array( 'name' => native name, 'default' => bool )
 */
function opus_site_texts_languages() {
	$default = apply_filters( 'wpml_default_language', 'nl' );
	$active  = apply_filters( 'wpml_active_languages', null, array( 'skip_missing' => 0 ) );
	$out     = array( $default => array( 'name' => 'Nederlands', 'default' => true ) );

	foreach ( (array) $active as $lang ) {
		$code = $lang['language_code'];
		if ( $code === $default ) {
			$out[ $default ]['name'] = $lang['native_name'];
			continue;
		}
		$out[ $code ] = array( 'name' => $lang['native_name'], 'default' => false );
	}
	return $out;
}

/* ─────────────────────────────────────────────────────────────
 * Data access.
 * ───────────────────────────────────────────────────────────── */

/**
 * Map string id → theme files containing the string, by scanning the theme
 * sources for the exact quoted literal. Cached.
 */
function opus_site_texts_file_map( $values_by_id ) {
	$cache = get_transient( 'opus_site_texts_filemap' );
	if ( is_array( $cache ) && ! array_diff_key( $values_by_id, $cache ) ) {
		return $cache;
	}

	$theme_dir = trailingslashit( get_template_directory() );
	$contents  = array();
	$iterator  = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $theme_dir, FilesystemIterator::SKIP_DOTS )
	);
	foreach ( $iterator as $file ) {
		if ( 'php' === $file->getExtension() ) {
			$rel              = str_replace( $theme_dir, '', $file->getPathname() );
			$contents[ $rel ] = (string) file_get_contents( $file->getPathname() );
		}
	}

	$map = array();
	foreach ( $values_by_id as $id => $value ) {
		$needle     = "'" . str_replace( "'", "\\'", $value ) . "'";
		$map[ $id ] = array();
		foreach ( $contents as $rel => $src ) {
			if ( false !== strpos( $src, $needle ) ) {
				$map[ $id ][] = $rel;
			}
		}
	}

	set_transient( 'opus_site_texts_filemap', $map, 12 * HOUR_IN_SECONDS );
	return $map;
}

/**
 * All theme strings with their translations, grouped by section.
 *
 * @return array section_key => array of {id, value (source), tr (lang => text), files}
 */
function opus_site_texts_fetch() {
	global $wpdb;

	$rows = $wpdb->get_results(
		"SELECT s.id, s.value FROM {$wpdb->prefix}icl_strings s
		 WHERE s.context = 'maison-opus' AND s.value <> '' ORDER BY s.id"
	);

	$values_by_id = array();
	foreach ( $rows as $row ) {
		$values_by_id[ $row->id ] = $row->value;
	}

	// Translations for every secondary language in one query.
	$tr = array();
	if ( $values_by_id ) {
		$ids     = implode( ',', array_map( 'intval', array_keys( $values_by_id ) ) );
		$tr_rows = $wpdb->get_results(
			"SELECT string_id, language, value FROM {$wpdb->prefix}icl_string_translations
			 WHERE string_id IN ($ids)"
		);
		foreach ( $tr_rows as $t ) {
			$tr[ $t->string_id ][ $t->language ] = $t->value;
		}
	}

	$file_map = opus_site_texts_file_map( $values_by_id );
	$sections = opus_site_texts_sections();
	$grouped  = array_fill_keys( array_keys( $sections ), array() );

	foreach ( $rows as $row ) {
		$files  = isset( $file_map[ $row->id ] ) ? $file_map[ $row->id ] : array();
		$target = 'overig';
		foreach ( $sections as $key => $section ) {
			if ( array_intersect( $files, $section['files'] ) ) {
				$target = $key;
				break;
			}
		}
		$row->files           = $files;
		$row->tr              = isset( $tr[ $row->id ] ) ? $tr[ $row->id ] : array();
		$grouped[ $target ][] = $row;
	}

	return $grouped;
}

/** Placeholders (%s, %d, …) in an edit must match the original string. */
function opus_site_texts_placeholders_ok( $original, $edited ) {
	preg_match_all( '/%[sdf%]/', $original, $a );
	preg_match_all( '/%[sdf%]/', $edited, $b );
	sort( $a[0] );
	sort( $b[0] );
	return $a[0] === $b[0];
}

/* ─────────────────────────────────────────────────────────────
 * Saving.
 * ───────────────────────────────────────────────────────────── */

/** Handle a save from the screen. Returns array of notice strings. */
function opus_site_texts_save() {
	if ( ! isset( $_POST['opus_site_texts_nonce'] )
		|| ! wp_verify_nonce( wp_unslash( $_POST['opus_site_texts_nonce'] ), 'opus_site_texts' )
		|| ! current_user_can( 'edit_theme_options' ) ) {
		return array();
	}

	global $wpdb;
	$default_lang  = apply_filters( 'wpml_default_language', 'nl' );
	$overrides     = get_option( 'opus_text_overrides', array() );
	$nl_input      = isset( $_POST['nl'] ) ? (array) wp_unslash( $_POST['nl'] ) : array();
	$tr_input      = isset( $_POST['tr'] ) ? (array) wp_unslash( $_POST['tr'] ) : array();
	$img_input     = isset( $_POST['img'] ) ? (array) wp_unslash( $_POST['img'] ) : array();
	$biz_input     = isset( $_POST['biz'] ) ? (array) wp_unslash( $_POST['biz'] ) : array();
	$langs_changed = array();
	$nl_saved      = 0;
	$tr_saved      = 0;
	$errors        = array();

	// Source-language overrides.
	foreach ( $nl_input as $string_id => $value ) {
		$string_id = (int) $string_id;
		$original  = $wpdb->get_var( $wpdb->prepare(
			"SELECT value FROM {$wpdb->prefix}icl_strings WHERE id = %d AND context = 'maison-opus'",
			$string_id
		) );
		if ( null === $original ) {
			continue;
		}
		$nl  = trim( (string) $value );
		$key = md5( $original );
		if ( '' !== $nl && $nl !== $original ) {
			if ( ! opus_site_texts_placeholders_ok( $original, $nl ) ) {
				$errors[] = sprintf( 'NL niet opgeslagen (placeholders %%s/%%d kloppen niet): "%s"', esc_html( mb_strimwidth( $original, 0, 60, '…' ) ) );
			} elseif ( ! isset( $overrides[ $key ] ) || $overrides[ $key ] !== $nl ) {
				$overrides[ $key ] = $nl;
				$nl_saved++;
			}
		} elseif ( isset( $overrides[ $key ] ) ) {
			unset( $overrides[ $key ] );
			$nl_saved++;
		}
	}
	update_option( 'opus_text_overrides', $overrides, true );

	// Translations, per language, into WPML String Translation.
	if ( function_exists( 'icl_add_string_translation' ) ) {
		foreach ( $tr_input as $lang => $fields ) {
			$lang = sanitize_key( $lang );
			if ( $lang === $default_lang ) {
				continue;
			}
			foreach ( (array) $fields as $string_id => $value ) {
				$string_id = (int) $string_id;
				$original  = $wpdb->get_var( $wpdb->prepare(
					"SELECT value FROM {$wpdb->prefix}icl_strings WHERE id = %d AND context = 'maison-opus'",
					$string_id
				) );
				if ( null === $original ) {
					continue;
				}
				$new     = trim( (string) $value );
				$current = $wpdb->get_var( $wpdb->prepare(
					"SELECT value FROM {$wpdb->prefix}icl_string_translations WHERE string_id = %d AND language = %s",
					$string_id,
					$lang
				) );
				if ( '' === $new || $new === $current ) {
					continue;
				}
				if ( ! opus_site_texts_placeholders_ok( $original, $new ) ) {
					$errors[] = sprintf( '%s niet opgeslagen (placeholders %%s/%%d kloppen niet): "%s"', esc_html( strtoupper( $lang ) ), esc_html( mb_strimwidth( $original, 0, 60, '…' ) ) );
					continue;
				}
				icl_add_string_translation( $string_id, $lang, $new, ICL_TM_COMPLETE );
				$langs_changed[ $lang ] = true;
				$tr_saved++;
			}
		}
	}

	// Images.
	$img_saved = 0;
	if ( $img_input ) {
		$images = get_option( 'opus_theme_images', array() );
		$fields = opus_theme_image_fields();
		foreach ( $img_input as $key => $attachment_id ) {
			if ( ! isset( $fields[ $key ] ) ) {
				continue;
			}
			$attachment_id = (int) $attachment_id;
			$current       = isset( $images[ $key ] ) ? (int) $images[ $key ] : 0;
			if ( $attachment_id === $current ) {
				continue;
			}
			if ( $attachment_id > 0 ) {
				$images[ $key ] = $attachment_id;
			} else {
				unset( $images[ $key ] );
			}
			$img_saved++;
		}
		update_option( 'opus_theme_images', $images, true );
	}

	// Bedrijfsprofiel.
	$biz_saved = 0;
	if ( $biz_input ) {
		$profile = get_option( 'opus_business_profile', array() );
		$fields  = opus_business_profile_fields();
		foreach ( $biz_input as $key => $value ) {
			if ( ! isset( $fields[ $key ] ) ) {
				continue;
			}
			$value   = trim( sanitize_textarea_field( (string) $value ) );
			$current = isset( $profile[ $key ] ) ? $profile[ $key ] : '';
			if ( $value === $current ) {
				continue;
			}
			if ( '' === $value ) {
				unset( $profile[ $key ] );
			} else {
				$profile[ $key ] = $value;
			}
			$biz_saved++;
		}
		update_option( 'opus_business_profile', $profile, true );
	}

	// Regenerate the .mo file per changed language.
	if ( $langs_changed && class_exists( '\WPML\ST\TranslationFile\Domains' ) ) {
		global $sitepress;
		\WPML\ST\TranslationFile\Domains::resetCache();
		foreach ( array_keys( $langs_changed ) as $lang ) {
			$locale = ( $sitepress && method_exists( $sitepress, 'get_locale' ) ) ? $sitepress->get_locale( $lang ) : $lang;
			try {
				\WPML\Container\make( \WPML\ST\MO\File\Manager::class )->add( 'maison-opus', $locale );
			} catch ( \Exception $e ) {
				$errors[] = 'Vertaling opgeslagen, maar het vertaalbestand kon niet worden ververst: ' . esc_html( $e->getMessage() );
			}
		}
	}
	wp_cache_flush();

	$notices = array();
	if ( $nl_saved || $tr_saved || $img_saved || $biz_saved ) {
		$parts = array();
		if ( $nl_saved ) {
			$parts[] = sprintf( '%d Nederlandse tekst(en)', $nl_saved );
		}
		if ( $tr_saved ) {
			$parts[] = sprintf( '%d vertaling(en)', $tr_saved );
		}
		if ( $img_saved ) {
			$parts[] = sprintf( '%d afbeelding(en)', $img_saved );
		}
		if ( $biz_saved ) {
			$parts[] = sprintf( '%d bedrijfsgegeven(s)', $biz_saved );
		}
		$notices[] = 'Opgeslagen: ' . implode( ', ', $parts ) . '.';
	}
	return array_merge( $notices, $errors );
}

/* ─────────────────────────────────────────────────────────────
 * Admin screen.
 * ───────────────────────────────────────────────────────────── */

/**
 * URL of the WPML screen that rescans the theme for new strings.
 *
 * The slug has moved between WPML versions — `theme-string-localization`
 * 403s on 4.9 — so find it in the registered menu rather than hardcoding,
 * and fall back to String Translation, then to the WPML menu itself.
 * Returns '' when WPML is not active, and the note is then not shown.
 */
function opus_wpml_localization_url() {
	if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
		return '';
	}

	// Verified on WPML 4.9. Older versions used `theme-string-localization`,
	// which 403s here, so check the menu for either before falling back.
	global $submenu;
	foreach ( (array) $submenu as $items ) {
		foreach ( (array) $items as $item ) {
			$slug = $item[2] ?? '';
			if ( false !== stripos( $slug, 'theme-localization.php' )
				|| false !== stripos( $slug, 'theme-string-localization' ) ) {
				return admin_url( 'admin.php?page=' . $slug );
			}
		}
	}

	return admin_url( 'admin.php?page=sitepress-multilingual-cms/menu/theme-localization.php' );
}

function opus_site_texts_menu() {
	// Top level rather than tucked under Weergave: this is the screen the shop
	// uses most, and nobody looks for site copy under "Appearance".
	$hook = add_menu_page(
		'Teksten & afbeeldingen',
		'Teksten & afbeeldingen',
		'edit_theme_options',
		'opus-site-texts',
		'opus_site_texts_render',
		'dashicons-edit-page',
		3 // directly under Dashboard, above the Woo/posts block.
	);
	add_action( 'load-' . $hook, function () {
		wp_enqueue_media();
	} );
}
add_action( 'admin_menu', 'opus_site_texts_menu' );

/** Admin-bar shortcut on the frontend: jump to the tab for the current view. */
function opus_site_texts_admin_bar( $bar ) {
	if ( is_admin() || ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$tab = 'algemeen';
	if ( is_front_page() ) {
		$tab = 'homepage';
	} elseif ( is_page( array( 'taxatie', 'valuation' ) ) ) {
		$tab = 'taxatie';
	} elseif ( is_page( array( 'zoekopdracht', 'watch-search' ) ) ) {
		$tab = 'zoekopdracht';
	} elseif ( is_page( array( 'contact' ) ) ) {
		$tab = 'contact';
	} elseif ( is_page( array( 'database' ) ) || (int) get_query_var( 'opus_watch_id' ) ) {
		$tab = 'database';
	} elseif ( function_exists( 'is_product_category' ) && is_product_category( array( 'service', 'servicing' ) ) ) {
		$tab = 'diensten';
	} elseif ( function_exists( 'is_woocommerce' ) && is_woocommerce() ) {
		$tab = 'shop';
	}
	$bar->add_node( array(
		'id'    => 'opus-site-texts',
		'title' => 'Teksten bewerken',
		'href'  => admin_url( 'admin.php?page=opus-site-texts&tab=' . $tab ),
	) );
}
add_action( 'admin_bar_menu', 'opus_site_texts_admin_bar', 90 );

/** One text row (all language columns). */
function opus_site_texts_row( $row, $langs, $overrides ) {
	$key      = md5( $row->value );
	$nl_value = isset( $overrides[ $key ] ) ? $overrides[ $key ] : $row->value;
	$longest  = mb_strlen( $row->value );
	foreach ( $row->tr as $t ) {
		$longest = max( $longest, mb_strlen( (string) $t ) );
	}
	$long = $longest > 70;
	echo '<tr>';
	foreach ( $langs as $code => $lang ) {
		$is_default = ! empty( $lang['default'] );
		$name       = $is_default ? 'nl[' . (int) $row->id . ']' : 'tr[' . esc_attr( $code ) . '][' . (int) $row->id . ']';
		$value      = $is_default ? $nl_value : ( isset( $row->tr[ $code ] ) ? $row->tr[ $code ] : '' );
		echo '<td>';
		if ( $long ) {
			printf( '<textarea name="%s" rows="3" style="width:100%%">%s</textarea>', esc_attr( $name ), esc_textarea( (string) $value ) );
		} else {
			printf( '<input type="text" name="%s" value="%s" style="width:100%%" />', esc_attr( $name ), esc_attr( (string) $value ) );
		}
		if ( $is_default && isset( $overrides[ $key ] ) ) {
			printf( '<p class="description">Aangepast — standaard: “%s”</p>', esc_html( mb_strimwidth( $row->value, 0, 90, '…' ) ) );
		}
		echo '</td>';
	}
	echo '</tr>';
}

function opus_site_texts_render() {
	$notices   = opus_site_texts_save();
	$sections  = opus_site_texts_sections();
	$grouped   = opus_site_texts_fetch();
	$langs     = opus_site_texts_languages();
	$overrides = get_option( 'opus_text_overrides', array() );
	$images    = get_option( 'opus_theme_images', array() );
	$search    = isset( $_GET['zoek'] ) ? trim( (string) wp_unslash( $_GET['zoek'] ) ) : '';
	$active    = isset( $_GET['tab'] ) && ( isset( $sections[ $_GET['tab'] ] ) || in_array( $_GET['tab'], array( 'afbeeldingen', 'bedrijfsprofiel' ), true ) )
		? sanitize_key( $_GET['tab'] ) : 'algemeen';
	$base_url  = admin_url( 'admin.php?page=opus-site-texts' );
	?>
	<div class="wrap">
		<h1>Thema-instellingen</h1>
		<p>Alle vaste teksten en afbeeldingen van de website, per onderdeel. Een tekstaanpassing geldt overal waar de tekst voorkomt. Leeg laten of leegmaken = standaard gebruiken.</p>

		<?php $opus_wpml_url = opus_wpml_localization_url(); ?>
		<?php if ( $opus_wpml_url ) : ?>
			<div class="notice notice-info inline" style="margin: 12px 0;">
				<p>
					<strong>Mist u een tekst?</strong>
					Teksten die later aan de website zijn toegevoegd verschijnen hier pas nadat WPML het thema opnieuw heeft doorzocht.
					Ga daarvoor naar <a href="<?php echo esc_url( $opus_wpml_url ); ?>">WPML &rsaquo; Thema- en pluginlokalisatie</a>
					en klik daar op <em>Strings scannen in thema&rsquo;s, plugins en WordPress</em>. Daarna staat de nieuwe tekst in de lijst hieronder.
				</p>
			</div>
		<?php endif; ?>

		<?php foreach ( $notices as $notice ) : ?>
			<div class="notice <?php echo strpos( $notice, 'niet opgeslagen' ) !== false || strpos( $notice, 'kon niet' ) !== false ? 'notice-error' : 'notice-success'; ?>"><p><?php echo wp_kses_post( $notice ); ?></p></div>
		<?php endforeach; ?>

		<form method="get" style="margin: 12px 0;">
			<input type="hidden" name="page" value="opus-site-texts" />
			<input type="search" name="zoek" value="<?php echo esc_attr( $search ); ?>" placeholder="Zoek een tekst van de site…" style="width:320px" />
			<button class="button">Zoeken</button>
			<?php if ( $search ) : ?>
				<a class="button-link" style="margin-left:8px" href="<?php echo esc_url( $base_url ); ?>">Wis zoekopdracht</a>
			<?php endif; ?>
		</form>

		<?php if ( ! $search ) : ?>
		<h2 class="nav-tab-wrapper">
			<?php foreach ( $sections as $key => $section ) : ?>
				<?php if ( empty( $grouped[ $key ] ) ) { continue; } ?>
				<a class="nav-tab <?php echo $key === $active ? 'nav-tab-active' : ''; ?>"
					href="<?php echo esc_url( $base_url . '&tab=' . $key ); ?>">
					<?php echo esc_html( $section['label'] ); ?>
				</a>
			<?php endforeach; ?>
			<a class="nav-tab <?php echo 'afbeeldingen' === $active ? 'nav-tab-active' : ''; ?>"
				href="<?php echo esc_url( $base_url . '&tab=afbeeldingen' ); ?>">Afbeeldingen</a>
			<a class="nav-tab <?php echo 'bedrijfsprofiel' === $active ? 'nav-tab-active' : ''; ?>"
				href="<?php echo esc_url( $base_url . '&tab=bedrijfsprofiel' ); ?>">Bedrijfsprofiel</a>
		</h2>
		<?php endif; ?>

		<form method="post">
			<?php wp_nonce_field( 'opus_site_texts', 'opus_site_texts_nonce' ); ?>

			<?php if ( $search ) : ?>
				<?php
				$matches = array();
				foreach ( $grouped as $section_key => $rows ) {
					foreach ( $rows as $row ) {
						$haystacks = array_merge( array( $row->value ), array_values( $row->tr ) );
						$key       = md5( $row->value );
						if ( isset( $overrides[ $key ] ) ) {
							$haystacks[] = $overrides[ $key ];
						}
						foreach ( $haystacks as $h ) {
							if ( false !== mb_stripos( (string) $h, $search ) ) {
								$matches[ $section_key ][] = $row;
								break;
							}
						}
					}
				}
				?>
				<h2><?php echo esc_html( sprintf( 'Zoekresultaten voor "%s"', $search ) ); ?></h2>
				<?php if ( ! $matches ) : ?>
					<p>Niets gevonden. De tekst kan ook in een pagina of product staan — die bewerkt u onder Pagina’s of Producten.</p>
				<?php endif; ?>
				<?php foreach ( $matches as $section_key => $rows ) : ?>
					<h3><?php echo esc_html( $sections[ $section_key ]['label'] ); ?></h3>
					<table class="widefat striped" style="margin-bottom:20px; table-layout:fixed;">
						<thead><tr>
							<?php foreach ( $langs as $lang ) : ?>
								<th><?php echo esc_html( $lang['name'] ); ?></th>
							<?php endforeach; ?>
						</tr></thead>
						<tbody>
						<?php foreach ( $rows as $row ) { opus_site_texts_row( $row, $langs, $overrides ); } ?>
						</tbody>
					</table>
				<?php endforeach; ?>
				<?php if ( $matches ) { submit_button( 'Opslaan' ); } ?>

			<?php elseif ( 'bedrijfsprofiel' === $active ) : ?>
				<?php $opus_profile = get_option( 'opus_business_profile', array() ); ?>
				<p>Bedrijfsgegevens die op de site worden gebruikt — onder meer in de footer en (via <code>[opus_bedrijf veld="…"]</code>) in de privacyverklaring en algemene voorwaarden. Een leeg veld toont op die pagina&rsquo;s een zichtbare <mark>[nog aan te vullen]</mark>-markering, zodat niets ongemerkt ontbreekt.</p>
				<table class="form-table" role="presentation">
					<?php foreach ( opus_business_profile_fields() as $opus_bkey => $opus_bfield ) : ?>
						<?php $opus_bval = isset( $opus_profile[ $opus_bkey ] ) ? $opus_profile[ $opus_bkey ] : ''; ?>
						<tr>
							<th scope="row"><label for="biz-<?php echo esc_attr( $opus_bkey ); ?>"><?php echo esc_html( $opus_bfield['label'] ); ?></label></th>
							<td>
								<?php if ( ! empty( $opus_bfield['textarea'] ) ) : ?>
									<textarea id="biz-<?php echo esc_attr( $opus_bkey ); ?>" name="biz[<?php echo esc_attr( $opus_bkey ); ?>]" rows="3" style="width:100%;max-width:560px"><?php echo esc_textarea( $opus_bval ); ?></textarea>
								<?php else : ?>
									<input type="text" id="biz-<?php echo esc_attr( $opus_bkey ); ?>" name="biz[<?php echo esc_attr( $opus_bkey ); ?>]" value="<?php echo esc_attr( $opus_bval ); ?>" style="width:100%;max-width:560px" <?php echo $opus_bfield['default'] ? 'placeholder="' . esc_attr( $opus_bfield['default'] ) . '"' : ''; ?> />
								<?php endif; ?>
								<?php if ( $opus_bfield['hint'] || $opus_bfield['default'] ) : ?>
									<p class="description"><?php echo esc_html( $opus_bfield['hint'] ); ?><?php echo $opus_bfield['default'] ? ' Standaard: ' . esc_html( $opus_bfield['default'] ) : ''; ?></p>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
				<?php submit_button( 'Bedrijfsprofiel opslaan' ); ?>

			<?php elseif ( 'afbeeldingen' === $active ) : ?>
				<table class="form-table" role="presentation">
					<?php foreach ( opus_theme_image_fields() as $key => $field ) : ?>
						<?php
						$attachment_id = isset( $images[ $key ] ) ? (int) $images[ $key ] : 0;
						$current_url   = opus_theme_image_url( $key, $field['default'] );
						?>
						<tr>
							<th scope="row"><?php echo esc_html( $field['label'] ); ?></th>
							<td>
								<div class="opus-img-field" data-key="<?php echo esc_attr( $key ); ?>">
									<img src="<?php echo esc_url( $current_url ); ?>" alt=""
										style="max-width:220px; height:auto; display:block; margin-bottom:8px; border:1px solid #ccd0d4;" />
									<input type="hidden" name="img[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $attachment_id ); ?>" />
									<button type="button" class="button opus-img-choose">Kies afbeelding</button>
									<button type="button" class="button opus-img-reset" <?php disabled( ! $attachment_id ); ?>>Standaard herstellen</button>
									<p class="description"><?php echo esc_html( $field['hint'] ); ?><?php echo $attachment_id ? '' : ' (nu: standaardafbeelding van het thema)'; ?></p>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
				<?php submit_button( 'Afbeeldingen opslaan' ); ?>
				<script>
				( function () {
					document.querySelectorAll( '.opus-img-field' ).forEach( function ( field ) {
						var input = field.querySelector( 'input[type="hidden"]' );
						var img   = field.querySelector( 'img' );
						var reset = field.querySelector( '.opus-img-reset' );
						field.querySelector( '.opus-img-choose' ).addEventListener( 'click', function () {
							var frame = wp.media( { title: 'Kies afbeelding', multiple: false, library: { type: 'image' } } );
							frame.on( 'select', function () {
								var att = frame.state().get( 'selection' ).first().toJSON();
								input.value = att.id;
								img.src = ( att.sizes && att.sizes.large ? att.sizes.large.url : att.url );
								reset.disabled = false;
							} );
							frame.open();
						} );
						reset.addEventListener( 'click', function () {
							input.value = '0';
							reset.disabled = true;
						} );
					} );
				} )();
				</script>

			<?php else : ?>
				<table class="widefat striped" style="margin-top:16px; table-layout:fixed;">
					<thead><tr>
						<?php foreach ( $langs as $lang ) : ?>
							<th><?php echo esc_html( $lang['name'] ); ?></th>
						<?php endforeach; ?>
					</tr></thead>
					<tbody>
					<?php foreach ( $grouped[ $active ] as $row ) { opus_site_texts_row( $row, $langs, $overrides ); } ?>
					</tbody>
				</table>
				<?php submit_button( 'Teksten opslaan' ); ?>
			<?php endif; ?>
		</form>
	</div>
	<?php
}
