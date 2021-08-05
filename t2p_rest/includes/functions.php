<?php


function strings_table_name()
{
    global $wpdb;
    return $wpdb->prefix . "icl_strings";
}

function strings_translations_table_name()
{
    global $wpdb;
    return $wpdb->prefix . "icl_string_translations";
}

function strings_translations_relations_table_name()
{
    global $wpdb;
    return $wpdb->prefix . "translations_relations";
}

function master_data_blocks_table_name()
{
    global $wpdb;
    return $wpdb->prefix . 'translations_master_data_blocks';
}

function is_parent_language($tag): bool
{
    $splitedLangs = explode("-", $tag);

    if (count($splitedLangs) > 1 && strtolower($splitedLangs[0]) != strtolower($splitedLangs[1]) && strtolower($tag) != get_lang_parent_tag_name_from_first_part(strtolower($splitedLangs[0]))) {
        return false;

    } else if ((isset($splitedLangs[1]) && strtolower($splitedLangs[0]) == strtolower($splitedLangs[1])) || strtolower($tag) == get_lang_parent_tag_name_from_first_part(strtolower($splitedLangs[0]))) {
        return true;
    }

    return false;
}

function get_lang_parent_tag_name_from_first_part($lang)
{
    if ($lang == 'en') {
        return 'en-us';
    } else if ($lang == 'sv') {
        return 'sv-se';
    } else if ($lang == 'ja') {
        return 'ja-jp';
    } else {
        return $lang . '-' . $lang;
    }
}

function get_iso_code_for_language_tag($tag)
{
    $langs = apply_filters('wpml_active_languages', null);
    foreach ($langs as $lang) {
        if (strtolower($lang['tag']) == strtolower($tag)) {
            return $lang['code'];
        }
    }
    return '';
}


function slugify($text)
{
    // trim
    $text = trim($text);

    // replace non letter or digits by -
    $text = preg_replace('~[^\pL\d]+~u', '_', $text);

    // remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);

    // trim
    $text = trim($text);

    // remove duplicate -
    $text = preg_replace('~-+~', '_', $text);

    // lowercase
    $text = strtolower($text);

    return $text;
}

function get_block_types_translations_relations($string_id, $is_automatic = false)
{
    global $wpdb;

    if (!$is_automatic) {
        $blockTypes = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * from " . strings_translations_relations_table_name() . " WHERE `string_id` = %d AND `relation_type` = %d ORDER BY `id` ASC", $string_id, 0
            ), ARRAY_A
        );
    } else {
        $blockTypes = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * from " . strings_translations_relations_table_name() . " WHERE `string_id` = %d AND `relation_type` = %d ORDER BY `id` ASC", $string_id, 4
            ), ARRAY_A
        );
    }

    $blocks = (new \T2P\BlockType)->blockTypes();


    foreach ($blockTypes as $key => $blockType) {
        foreach ($blocks as $key_index => $value) {
            if ($blockType['relation_name'] == $key_index) {
                $blockType['title'] = $value;
                $blockTypes[$key] = $blockType;
            }
        }
    }

    return $blockTypes;
}

function get_master_data_blocks()
{
    global $wpdb;

    $blockTypes = $wpdb->get_results(
        "SELECT * from " . master_data_blocks_table_name()
        , ARRAY_A
    );
    $blocks = (new \T2P\BlockType)->blockTypes();


    foreach ($blockTypes as $key => $blockType) {
        foreach ($blocks as $key_index => $value) {
            if ($blockType['block_name'] == $key_index) {
                $blockType['title'] = $value;
                $blockTypes[$key] = $blockType;
            }
        }
    }

    return $blockTypes;
}

function get_posts_translations_relations($string_id, $is_automatic = false)
{
    global $wpdb;

    if (!$is_automatic) {
        $posts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * from " . strings_translations_relations_table_name() . " WHERE `string_id` = %d AND `relation_type` = %d ORDER BY `id` ASC", $string_id, 1
            ), ARRAY_A
        );
    } else {
        $posts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * from " . strings_translations_relations_table_name() . " WHERE `string_id` = %d AND `relation_type` = %d ORDER BY `id` ASC", $string_id, 5
            ), ARRAY_A
        );
    }


    foreach ($posts as $key => $post) {
        $post['title'] = get_the_title($post['relation_id']);
        $posts[$key] = $post;
    }

    return $posts;
}

function get_post_types_translations_relations($string_id, $is_automatic = false)
{
    global $wpdb;

    if (!$is_automatic) {
        $postTypes = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * from " . strings_translations_relations_table_name() . " WHERE `string_id` = %d AND `relation_type` = %d ORDER BY `id` ASC", $string_id, 2
            ), ARRAY_A
        );
    } else {
        $postTypes = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * from " . strings_translations_relations_table_name() . " WHERE `string_id` = %d AND `relation_type` = %d ORDER BY `id` ASC", $string_id, 6
            ), ARRAY_A
        );
    }

    return $postTypes;
}
