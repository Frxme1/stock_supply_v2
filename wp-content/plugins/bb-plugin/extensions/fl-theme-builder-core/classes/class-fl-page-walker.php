<?php

if ( ! class_exists( 'FLPageWalker' ) ) {
	/**
	 * Walks through each page (or hierarchical post type) to build JSON data.
	 * Used in FLThemeBuilderRulesLocation::get_post_type_posts() below.
	 *
	 * @since 1.4
	 */
	class FLPageWalker extends Walker_Page {
		/**
		 * Starts the element output.
		 *
		 * @param string  $output       (passed by reference) Used to create a JSON element out of the $page (WP_Post) object.
		 * @param WP_Post $page         Page data object.
		 * @param int     $depth        Optional. Depth of page. Used for padding. Default 0.
		 * @param array   $args         Optional. Array of arguments. Default empty array.
		 * @param int     $current_page Optional. Page ID. Default 0.
		 */
		public function start_el( &$output, $page, $depth = 0, $args = array(), $current_page = 0 ) {
			$mdash   = str_repeat( '&mdash; ', $depth );
			$title   = wp_slash( esc_attr( FLThemeBuilderRulesLocation::sanitize_title( $page->post_title ) ) );
			$title   = empty( $title ) ? __( 'No Title', 'fl-builder' ) : $title;
			$output .= '{'
				. '"id": ' . $page->ID . ','
				. '"name": "' . $title . '",'
				. '"label": "' . $mdash . $title . '",'
				. '"depth": ' . $depth
				. '},';
		}
		public function end_el( &$output, $page, $depth = 0, $args = array() ) {
			$output .= '';
		}
		public function start_lvl( &$output, $depth = 0, $args = array() ) {
			$output .= '';
		}
		public function end_lvl( &$output, $depth = 0, $args = array() ) {
			$output .= '';
		}
	}
}
