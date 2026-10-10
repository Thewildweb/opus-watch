<?php
/**
 * Front page — the active skin's homepage (heritage for visitors).
 *
 * @package maison-opus
 */

get_header();
get_template_part( 'template-parts/home', maison_opus_home_part() );
get_footer();
