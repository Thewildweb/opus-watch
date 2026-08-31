<?php
/**
 * Compact language switcher (WPML) — NL · EN in the utility nav.
 *
 * Renders nothing when WPML is inactive or only one language is configured.
 *
 * @package maison-opus
 */

$opus_langs = apply_filters( 'wpml_active_languages', null, array( 'skip_missing' => 0 ) );

if ( empty( $opus_langs ) || count( $opus_langs ) < 2 ) {
	return;
}
?>
<?php
$opus_lang_items = array();
foreach ( $opus_langs as $opus_lang ) {
	$opus_label = strtoupper( $opus_lang['language_code'] );
	if ( ! empty( $opus_lang['active'] ) ) {
		$opus_lang_items[] = '<span class="lang current" aria-current="true">' . esc_html( $opus_label ) . '</span>';
	} else {
		$opus_lang_items[] = sprintf(
			'<a class="lang" href="%s" lang="%s" hreflang="%s" title="%s">%s</a>',
			esc_url( $opus_lang['url'] ),
			esc_attr( $opus_lang['language_code'] ),
			esc_attr( $opus_lang['language_code'] ),
			esc_attr( $opus_lang['native_name'] ),
			esc_html( $opus_label )
		);
	}
}
?>
<div class="lang-switcher" role="navigation" aria-label="<?php esc_attr_e( 'Taal', 'maison-opus' ); ?>">
	<?php echo implode( '<span class="lang-sep" aria-hidden="true">&middot;</span>', $opus_lang_items ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
