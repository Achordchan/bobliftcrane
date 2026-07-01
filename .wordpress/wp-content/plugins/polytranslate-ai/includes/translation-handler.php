<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Handle AJAX translation requests
function polytranslate_ai_translate_content_ajax_handler() {
    if ( isset( $_REQUEST['translate'] ) && $_REQUEST['translate'] == 'true' ) {
        @set_time_limit( 300 );
        register_shutdown_function( 'ptai_boblift_ajax_fatal_error_response' );
        // Check if the request is valid
        if ( !isset( $_REQUEST['_wpnonce'] ) || !wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'polytranslate-ai-nonce' ) ) {
            wp_send_json_error( 'Invalid nonce', 403 );
        }
        // Check if the user has permission to edit posts
        if ( !current_user_can( 'publish_posts' ) ) {
            wp_send_json_error( 'Permission denied', 403 );
        }
        // Validate and sanitize all input variables
        if ( !isset( $_REQUEST['post_type'], $_REQUEST['slug'], $_REQUEST['source'] ) ) {
            wp_send_json_error( 'Missing required parameters', 400 );
        }
        $postType = sanitize_text_field( wp_unslash( $_REQUEST['post_type'] ) );
        $targetLang = sanitize_text_field( wp_unslash( $_REQUEST['slug'] ) );
        $sourceLang = sanitize_text_field( wp_unslash( $_REQUEST['source'] ) );
        $translatedTitle = '';
        $translatedContent = '';
        // Check post type
        if ( isset( $_REQUEST['id'] ) ) {
            // ================== Posts/Pages
            $id = intval( $_REQUEST['id'] );
            $post = get_post( $id );
            if ( !$post ) {
                wp_send_json_error( 'Invalid post ID', 400 );
            }
            // Check for existing translation
            if ( function_exists( 'pll_get_post_translations' ) ) {
                $existingTranslations = pll_get_post_translations( $id );
                if ( !empty( $existingTranslations[$targetLang] ) ) {
                    wp_send_json_error( 'Translation for this language already exists', 409 );
                }
            }
            $sourceTitle = $post->post_title;
            $sourceContent = $post->post_content;
            $sourceExcerpt = $post->post_excerpt;
            $originalDate = $post->post_date;
            // Check if this is an Elementor page
            $is_elementor_page = get_post_meta( $id, '_elementor_edit_mode', true ) === 'builder';
            // For Elementor pages, use special Elementor handler (meta data translation)
            // For regular posts/pages, use the smart content handler
            if ( $is_elementor_page ) {
                // if post type = post or product, translate content normally
                if ( $postType === 'post' || $postType === 'product' ) {
                    $translatedContent = ( !empty( $sourceContent ) ? ptai_translate_content( $sourceContent, $targetLang, $sourceLang ) : '' );
                } else {
                    $translatedContent = '';
                }
            } else {
                // For regular posts/pages, use the smart content handler (JSON array approach)
                $translatedContent = ( !empty( $sourceContent ) ? ptai_translate_content( $sourceContent, $targetLang, $sourceLang ) : '' );
            }
            // Translate title using JSON array format (single item array)
            $translatedTitle = $sourceTitle;
            if ( !empty( $sourceTitle ) ) {
                $titleResult = ptai_call_translation_ai( [$sourceTitle], $targetLang, $sourceLang );
                if ( is_array( $titleResult ) && isset( $titleResult['success'] ) && $titleResult['success'] === false ) {
                    wp_send_json_error( 'Translation failed: ' . $titleResult['error'], 500 );
                }
                if ( is_array( $titleResult ) && isset( $titleResult[0] ) ) {
                    $translatedTitle = $titleResult[0];
                }
            }
            if ( function_exists( 'ptai_boblift_apply_static_title_fallback' ) ) {
                $titleFallback = ptai_boblift_apply_static_title_fallback( $sourceTitle, (string) $translatedTitle, $targetLang, $sourceLang );
                if ( is_wp_error( $titleFallback ) ) {
                    wp_send_json_error( $titleFallback->get_error_message(), 500 );
                }
                $translatedTitle = $titleFallback;
            }
            // Translate excerpt using JSON array format (single item array)
            $translatedExcerpt = $sourceExcerpt;
            if ( !empty( $sourceExcerpt ) ) {
                $excerptResult = ptai_call_translation_ai( [$sourceExcerpt], $targetLang, $sourceLang );
                if ( is_array( $excerptResult ) && isset( $excerptResult['success'] ) && $excerptResult['success'] === false ) {
                    wp_send_json_error( 'Translation failed: ' . $excerptResult['error'], 500 );
                }
                if ( is_array( $excerptResult ) && isset( $excerptResult[0] ) ) {
                    $translatedExcerpt = $excerptResult[0];
                }
            }
            $postStatus = ptai_get_option( 'polytranslate_ai_options', 'post_status', 'publish' );
            $originalPublishDate = ptai_get_option( 'polytranslate_ai_options', 'original_publish_date', 0 );
            $originalPublishAuthor = ptai_get_option( 'polytranslate_ai_options', 'original_publish_author', 0 );
            // Create a new post with the translated content
            $newPost = [
                'post_title'   => $translatedTitle,
                'post_content' => wp_slash( $translatedContent ),
                'post_excerpt' => $translatedExcerpt,
                'post_status'  => $postStatus,
                'post_type'    => $postType,
                'post_author'  => get_current_user_id(),
            ];
            // Preserve original publish date and author if the option is enabled
            if ( $originalPublishDate ) {
                $newPost['post_date'] = $originalDate;
                $newPost['post_date_gmt'] = get_gmt_from_date( $originalDate );
            }
            // Preserve original author if the option is enabled
            if ( $originalPublishAuthor ) {
                $newPost['post_author'] = (int) $post->post_author;
            }
            $newPostId = wp_insert_post( $newPost );
            // Check if the post was created successfully
            if ( is_wp_error( $newPostId ) ) {
                wp_send_json_error( 'Post creation failed: ' . $newPostId->get_error_message(), 500 );
            }
            // 1. Copy all custom fields (metabox data)
            $meta = get_post_meta( $id );
            if ( !empty( $meta ) ) {
                foreach ( $meta as $key => $values ) {
                    // Skip Polylang and WordPress core fields if needed
                    if ( in_array( $key, [
                        '_edit_lock',
                        '_edit_last',
                        '_thumbnail_id',
                        '_wp_old_slug',
                        '_icl_lang_duplicate_of',
                        '_polylang_translation',
                        '_pll_language',
                        '_pll_trid'
                    ] ) ) {
                        continue;
                    }
                    foreach ( $values as $value ) {
                        add_post_meta( $newPostId, $key, maybe_unserialize( $value ) );
                    }
                }
            }
            // Copy featured image
            $thumbnail_id = get_post_thumbnail_id( $id );
            if ( $thumbnail_id && get_post( $thumbnail_id ) ) {
                // Make sure the image exists before setting
                set_post_thumbnail( $newPostId, $thumbnail_id );
            }
            // Set the language for the new post
            if ( function_exists( 'pll_set_post_language' ) ) {
                pll_set_post_language( $newPostId, $targetLang );
                // Get existing translations
                $translations = pll_get_post_translations( $id );
                // Add the new translation
                if ( !isset( $translations[$targetLang] ) ) {
                    $translations[$targetLang] = $newPostId;
                    // Save the updated translations
                    pll_save_post_translations( $translations );
                }
                // 1. Categories relations
                $catRelations = ptai_categories_relations( $id, $targetLang, $postType );
                if ( !empty( $catRelations ) ) {
                    if ( $postType != 'post' && $postType != 'page' ) {
                        foreach ( $catRelations as $cat ) {
                            $catType = get_term( $cat )->taxonomy;
                            wp_set_object_terms(
                                $newPostId,
                                [$cat],
                                $catType,
                                true
                            );
                        }
                    } else {
                        wp_set_post_categories( $newPostId, ptai_categories_relations( $id, $targetLang, $postType ) );
                    }
                }
                // 2. Tags relations
                if ( !empty( ptai_tags_relations( $id, $targetLang ) ) ) {
                    wp_set_post_tags( $newPostId, ptai_tags_relations( $id, $targetLang ) );
                }
                // Set product Type and variations for WooCommerce products
                if ( $postType === 'product' && function_exists( 'wc_get_product' ) ) {
                    ptai_set_product_type( $id, $newPostId );
                }
            }
            if ( function_exists( 'ptai_boblift_translate_post_meta' ) ) {
                $metaResult = ptai_boblift_translate_post_meta( $id, $newPostId, $targetLang, $sourceLang, $postType );
                if ( is_wp_error( $metaResult ) ) {
                    wp_send_json_error( 'BOB-LIFT field translation failed: ' . $metaResult->get_error_message(), 500 );
                }
            }
            /**
             * For Terms Translation
             */
        } elseif ( isset( $_REQUEST['term_id'] ) ) {
            $termId = intval( $_REQUEST['term_id'] );
            $taxonomy = $postType;
            $sourceTerm = get_term( $termId );
            if ( !$sourceTerm || is_wp_error( $sourceTerm ) ) {
                wp_send_json_error( 'Invalid term ID', 400 );
            }
            // Check for existing translation
            if ( function_exists( 'pll_get_term_translations' ) ) {
                $existingTranslations = pll_get_term_translations( $termId );
                if ( !empty( $existingTranslations[$targetLang] ) ) {
                    wp_send_json_error( 'Translation for this language already exists', 409 );
                }
            }
            $sourceTitle = $sourceTerm->name;
            $sourceContent = $sourceTerm->description;
            // Translate title
            $titleResult = ptai_call_translation_ai( [$sourceTitle], $targetLang, $sourceLang );
            if ( is_array( $titleResult ) && isset( $titleResult['success'] ) && $titleResult['success'] === false ) {
                wp_send_json_error( 'Translation failed: ' . $titleResult['error'], 500 );
            }
            $translatedTitle = ( is_array( $titleResult ) && isset( $titleResult[0] ) ? $titleResult[0] : $sourceTitle );
            if ( function_exists( 'ptai_boblift_apply_static_title_fallback' ) ) {
                $titleFallback = ptai_boblift_apply_static_title_fallback( $sourceTitle, (string) $translatedTitle, $targetLang, $sourceLang );
                if ( is_wp_error( $titleFallback ) ) {
                    wp_send_json_error( $titleFallback->get_error_message(), 500 );
                }
                $translatedTitle = $titleFallback;
            }
            // Translate description
            $translatedContent = '';
            if ( !empty( $sourceContent ) ) {
                $contentResult = ptai_call_translation_ai( [$sourceContent], $targetLang, $sourceLang );
                if ( is_array( $contentResult ) && isset( $contentResult['success'] ) && $contentResult['success'] === false ) {
                    wp_send_json_error( 'Translation failed: ' . $contentResult['error'], 500 );
                }
                $translatedContent = ( is_array( $contentResult ) && isset( $contentResult[0] ) ? $contentResult[0] : '' );
            }
            // Create the translated term
            $parentCat = ptai_parent_category_relations( $termId, $targetLang );
            // Check if the Term exists in the target language To append new lang sulg
            $termExists = term_exists( $translatedTitle, $taxonomy, $parentCat );
            // If the term already exists, append the language slug to the title
            $translatedSlug = ( !empty( $termExists ) ? $translatedTitle . '-' . $targetLang : $translatedTitle );
            $newTerm = wp_insert_term( $translatedTitle, $taxonomy, [
                'slug'        => $translatedSlug,
                'description' => $translatedContent,
                'parent'      => $parentCat,
            ] );
            if ( is_wp_error( $newTerm ) ) {
                wp_send_json_error( 'Term creation failed: ' . $newTerm->get_error_message(), 500 );
            }
            if ( !isset( $newTerm['term_id'] ) ) {
                wp_send_json_error( 'Term ID not returned', 500 );
            }
            $newTermId = $newTerm['term_id'];
            // Copy all term meta (taxonomy term data)
            $meta = get_term_meta( $termId );
            if ( !empty( $meta ) ) {
                foreach ( $meta as $key => $values ) {
                    foreach ( $values as $value ) {
                        add_term_meta( $newTermId, $key, maybe_unserialize( $value ) );
                    }
                }
            }
            // Set the language for the new term
            if ( function_exists( 'pll_set_term_language' ) ) {
                pll_set_term_language( $newTermId, $targetLang );
            }
            // Get existing translations
            if ( function_exists( 'pll_get_term_translations' ) && function_exists( 'pll_save_term_translations' ) ) {
                $translations = pll_get_term_translations( $termId );
                if ( !isset( $translations[$targetLang] ) ) {
                    // Add the new translation
                    $translations[$targetLang] = $newTermId;
                    // Save the updated translations
                    pll_save_term_translations( $translations );
                }
            }
            if ( function_exists( 'ptai_boblift_translate_term_meta' ) ) {
                $metaResult = ptai_boblift_translate_term_meta( $termId, $newTermId, $targetLang, $sourceLang, $taxonomy );
                if ( is_wp_error( $metaResult ) ) {
                    wp_send_json_error( 'BOB-LIFT term field translation failed: ' . $metaResult->get_error_message(), 500 );
                }
            }
        }
        //////////////////////////////////////////////////////// For Testing only
        // wp_send_json_success([$sourceTitle, $sourceContent, $translatedTitle, $translatedContent]);
        //===============================================================//
        wp_send_json_success( [
            'message' => 'Translation created successfully',
        ] );
    } else {
        // Redirect to referrer page
        $referrer = wp_get_referer();
        if ( $referrer ) {
            wp_safe_redirect( $referrer );
            exit;
        }
    }
}

add_action( 'wp_ajax_polytranslate_ai_translate_content', 'polytranslate_ai_translate_content_ajax_handler' );

function ptai_boblift_ajax_fatal_error_response() {
    $error = error_get_last();
    if ( !$error || !in_array( $error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true ) ) {
        return;
    }

    $message = sprintf(
        'PHP fatal error: %s in %s:%d',
        $error['message'] ?? 'Unknown fatal error',
        $error['file'] ?? 'unknown file',
        $error['line'] ?? 0
    );

    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( '[PTAI AJAX Fatal] ' . $message );
    }

    if ( headers_sent() ) {
        return;
    }

    while ( ob_get_level() > 0 ) {
        ob_end_clean();
    }

    wp_send_json_error( $message, 500 );
}
