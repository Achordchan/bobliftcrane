<?php
if (!defined('ABSPATH')) exit;

/**
 * Get the list of translatable block attribute names.
 * 
 * This generic list covers the most common translatable attributes
 * across core WordPress blocks and popular third-party blocks like Spectra (UAGB).
 * 
 * @return array List of attribute names that should be translated
 */
function ptai_get_translatable_attributes()
{
  // Generic text content attributes (used by core and most plugins)
  $generic_text_attrs = array(
    // Core block attributes
    'content',        // paragraph, heading, quote, verse, etc.
    'text',           // button, list item, etc.
    'placeholder',    // input placeholders, empty block hints
    'caption',        // image, video, audio, gallery captions
    'alt',            // image alt text
    'title',          // various block titles
    'value',          // form inputs, custom values
    'label',          // form labels, button labels
    'description',    // descriptions in various blocks
    'help',           // help text
    'question',       // FAQ blocks
    'answer',         // FAQ blocks
    'citation',       // quote citations
    'pre_text',       // prefix text
    'post_text',      // suffix text
    'summary',        // details/summary blocks
    'body',           // table cell content
    'rich_text',      // rich text content
    'message',        // message blocks
    'notice',         // notice content
    'heading',        // heading text
    'subheading',     // subheading text
    'excerpt',        // excerpt text
    'quote',          // quote text
    'author',         // author names
    'byline',         // author byline
    'credit',         // credits
    'copyright',      // copyright text
    'disclaimer',     // disclaimer text
    'copyright_text', // copyright text
    'button_text',    // button text variants
    'link_text',      // link text
    'tooltip',        // tooltip text
    ' aria_label',    // accessibility labels (note the leading space)
    'aria-label',     // accessibility labels
    'headingTitle',    // common pattern for block titles
  );

  // Spectra (UAGB) specific attributes
  // These follow naming patterns like headingTitle, headingDesc, etc.
  $spectra_attrs = array(
    // Heading block
    'headingTitle',
    'headingDesc',
    'tempDesc',
    'tempHeadingDesc',
    'subHeadingTitle',
    'headingTag',

    // Advanced Heading
    'heading_content',
    'sub_heading_content',

    // Info Box
    'infobox_title',
    'infobox_description',
    'infobox_cta_text',
    'infobox_icon_text',

    // Call to Action
    'cta_title',
    'cta_description',
    'cta_btn_text',
    'cta_btn_link',

    // Buttons
    'btn_text',
    'btn_link',
    'btn2_text',
    'btn2_link',

    // Icon List
    'icon_list_title',

    // FAQ
    'question_text',
    'answer_text',
    'faq_question',
    'faq_answer',

    // Testimonial
    'testimonial_author',
    'testimonial_company',
    'testimonial_description',
    'author_name',
    'company_name',

    // Team Member
    'team_member_name',
    'team_member_desig',
    'team_member_desc',

    // Price List
    'price_list_title',
    'price_list_description',
    'price_list_price',

    // Review
    'review_title',
    'review_desc',
    'reviewer_name',
    'reviewer_company',

    // Notice
    'notice_title',
    'notice_content',
    'notice_dismiss_text',

    // Table of Contents
    'toc_title',

    // Timeline
    'timeline_heading',
    'timeline_desc',
    'time_desc',

    // How To
    'how_to_title',
    'how_to_desc',
    'step_title',
    'step_desc',

    // Counter
    'counter_title',
    'counter_desc',

    // Progress Bar
    'progress_bar_title',

    // Social Share
    'share_text',

    // Login Form
    'name',
    'login_label',
    'register_label',
    'lost_password_label',

    // Contact Form
    'form_label',
    'submit_btn_text',
    'success_message',
    'error_message',

    // Advanced Forms
    'field_label',
    'field_placeholder',
    'field_help',
    'validation_message',

    // Content Timeline
    't_heading',
    't_desc',

    // Inline Notice
    'notice_content_inline',

    // Content Toggle
    'toggle_label',
    'toggle_content',

    // Image Box
    'imagebox_title',
    'imagebox_desc',

    // Google Map
    'map_marker_text',
    'map_marker_desc',

    // Icon
    'icon_text',

    // Blockquote
    'quote_text_uagb',
    'quote_author',
    'quote_tweet_text',

    // Mark
    'marked_text',

    // Star Rating
    'rating_text',

    // Divider
    'divider_text',

    // Lottie
    'lottie_caption',

    // Video Popup
    'video_caption',

    // Post Grid/Carousel meta
    'read_more_text',
    'load_more_text',
    'no_posts_text',

    // Taxonomy List
    'taxonomy_text',

    // Author Box
    'author_box_name',
    'author_box_desc',

    // Restaurant Menu
    'menu_item_name',
    'menu_item_desc',
    'menu_item_price',

    // Tabs
    'tab_title',
    'tab_content',

    // Accordion
    'accordion_title',
    'accordion_content',

    // Columns/Container labels
    'column_label',
    'container_label',

    // uagb/blockquote
    'descriptionText',
  );

  // Other popular plugins (Kadence, GenerateBlocks, Stackable, etc.)
  $other_plugins_attrs = array(
    // Kadence Blocks
    'kadence_text',
    'kadence_title',
    'kadence_description',
    'kt_text',
    'kt_title',
    'kt_description',

    // GenerateBlocks
    'gb_text',
    'gb_title',
    'gb_description',
    'headline_text',
    'headline_subtitle',

    // Stackable
    'stk_text',
    'stk_title',
    'stk_description',
    'stk_button_text',

    // CoBlocks
    'coblocks_text',
    'coblocks_title',

    // Generic patterns used by multiple plugins
    'btnText',
    'buttonText',
    'button_text',
    'linkText',
    'link_text',
    'headline',
    'subtitle',
    'subTitle',
    'sub_title',
    'intro_text',
    'outro_text',
    'prefix',
    'suffix',
    'primary_text',
    'secondary_text',
    'primary_label',
    'secondary_label',
    'more_text',
    'less_text',
    'show_more_text',
    'show_less_text',
    'read_more',
    'view_more',
    'learn_more_text',
    'continue_text',
    'back_text',
    'close_text',
    'submit_text',
    'cancel_text',
    'confirm_text',
    'error_text',
    'success_text',
    'warning_text',
    'info_text',
    'empty_text',
    'no_results_text',
    'loading_text',
    'saving_text',
    'saved_text',
    'deleted_text',
    'updated_text',
    'added_text',
    'removed_text',
  );

  // Merge all and remove duplicates
  $all_attrs = array_unique(array_merge(
    $generic_text_attrs,
    $spectra_attrs,
    $other_plugins_attrs
  ));


  return $all_attrs;
}