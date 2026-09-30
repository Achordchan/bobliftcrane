<?php
/**
 * Template Name: Privacy Policy
 */
get_header();
boblift_render_legal_page(function_exists('boblift_privacy_policy_page_options') ? boblift_privacy_policy_page_options() : [], '隐私政策页');
get_footer();
