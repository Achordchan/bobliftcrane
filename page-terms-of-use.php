<?php
/**
 * Template Name: Terms of Use
 */
get_header();
boblift_render_legal_page(function_exists('boblift_terms_of_use_page_options') ? boblift_terms_of_use_page_options() : [], '使用条款页', 'images/factory/hall-workstations.webp');
get_footer();
