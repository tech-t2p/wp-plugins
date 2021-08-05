<?php

function insert_key_phrase($key, $phrase)
{
    global $wpdb;
    $wpdb->insert(strings_table_name(), array(
        "language" => 'en',
        "context" => 'common-strings',
        "name" => $key,
        "value" => $phrase,
        "status" => 0,
        "domain_name_context_md5" => md5('common-strings' . $key)
    ));
}

function insert_translations_for_child_languages($phrase, $sourceLanguageTag, $string_id)
{
    global $wpdb;
    $languageTagSplit = explode("-", $sourceLanguageTag);
    $langs = apply_filters('wpml_active_languages', null);

    foreach ($langs as $lang) {
        $targetSplitedLangs = explode("-", $lang['tag']);
        $isoCode = $lang['code'];

        if (!is_parent_language($lang['tag'])) {
            if ((strtolower($languageTagSplit[0]) == strtolower($targetSplitedLangs[0])) && $isoCode != 'en') {
                $translated_object = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM `" . strings_translations_table_name() . "` WHERE `string_id` = %d AND LOWER(`language`) = %s", $string_id, $lang['code']
                    ), object);

                if (!isset($translated_object->id)) {
                    $wpdb->insert(strings_translations_table_name(), array(
                        "string_id" => $string_id,
                        "language" => $isoCode,
                        "status" => '10',
                        "value" => $phrase,
                    ));
                    usleep(100000);
                }
            }
        }
    }
}

function delete_child_translations_for_parent_languages($sourceLanguageTag, $string_id)
{
    global $wpdb;
    $languageTagSplit = explode("-", $sourceLanguageTag);
    $langs = apply_filters('wpml_active_languages', null);

    foreach ($langs as $lang) {
        $targetSplitedLangs = explode("-", $lang['tag']);
        $isoCode = $lang['code'];

        if (!is_parent_language($lang['tag'])) {
            if ((strtolower($languageTagSplit[0]) == strtolower($targetSplitedLangs[0])) && $isoCode != 'en') {
                $wpdb->delete(strings_translations_table_name(), array(
                    "string_id" => $string_id,
                    "language" => $isoCode,
                ));
            }
        }
    }
}

function insert_block_types_translations_relations($blockTypes, $string_id)
{
    global $wpdb;

    //first delete associated blockTypes and then re insert the list again to avoid duplications and unnecessary queries
    $wpdb->delete(strings_translations_relations_table_name(), array(
        "string_id" => $string_id,
        "relation_type" => 0,
    ));

    if ($blockTypes) {
        foreach ($blockTypes as $block) {
            $wpdb->insert(strings_translations_relations_table_name(), array(
                "string_id" => $string_id,
                "relation_type" => 0,
                "relation_name" => $block
            ));
        }
    }
}

function insert_post_types_translations_relations($postTypes, $string_id)
{
    global $wpdb;

    //first delete associated blockTypes and then re insert the list again to avoid duplications and unnecessary queries
    $wpdb->delete(strings_translations_relations_table_name(), array(
        "string_id" => $string_id,
        "relation_type" => 2,
    ));

    if ($postTypes) {
        foreach ($postTypes as $postType) {
            $wpdb->insert(strings_translations_relations_table_name(), array(
                "string_id" => $string_id,
                "relation_type" => 2,
                "relation_name" => $postType
            ));
        }
    }

}

function insert_posts_translations_relations($posts, $string_id)
{
    global $wpdb;

    //first delete associated blockTypes and then re insert the list again to avoid duplications and unnecessary queries
    $wpdb->delete(strings_translations_relations_table_name(), array(
        "string_id" => $string_id,
        "relation_type" => 1,
    ));

    if ($posts) {
        foreach ($posts as $post) {
            $wpdb->insert(strings_translations_relations_table_name(), array(
                "string_id" => $string_id,
                "relation_type" => 1,
                "relation_id" => intval($post)
            ));
        }
    }
}

function insert_master_data_blocks_relations($blocks)
{
    global $wpdb;

    //first delete associated blockTypes and then re insert the list again to avoid duplications and unnecessary queries
    $wpdb->query('DELETE FROM ' . master_data_blocks_table_name());

    if ($blocks) {
        foreach ($blocks as $block) {
            $wpdb->insert(master_data_blocks_table_name(), array(
                "block_name" => $block,
            ));
        }
    }
}

function insertAndUpdateNewMasterDataKeys()
{
    global $wpdb;

    $postType = new T2P\PostType();

    $postTypesFields = $postType->filterFields($postType->filterPostTypesByNameSubString('_md_'), '_', false);


    foreach ($postTypesFields as $key => $value) {
        $translation_results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * from " . strings_table_name() . " WHERE `name` = %s ORDER BY `id` ASC", $key
            ), ARRAY_A
        );
        if (count($translation_results) < 1) {
            insert_key_phrase($key, $value);

            $insertedID = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM `" . strings_table_name() . "` WHERE `name` = %s AND LOWER(`language`) = %s", $key, 'en'
                ), object);

            insert_translations_for_child_languages($value, 'en-US', intval($insertedID->id));

            $translation_results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * from " . strings_table_name() . " WHERE `name` = %s ORDER BY `id` ASC", $key
                ), ARRAY_A
            );
        }

        foreach ($translation_results as $translation_result) {
            $blocks = get_master_data_blocks();

            // we should update related blockTypes each time for each key..
            $wpdb->query("DELETE FROM " . strings_translations_relations_table_name() . " WHERE `string_id` = " . $translation_result['id'] . " AND `relation_type` = 3");
            foreach ($blocks as $block) {
                $wpdb->insert(strings_translations_relations_table_name(), array(
                    "string_id" => $translation_result['id'],
                    "relation_type" => 3,
                    "relation_name" => $block['block_name']
                ));
            }


        }
    }


    // Inserting New Keys from our json file:
    // relation types-> 0 ->custom relation to a block type
    // relation types-> 1 ->custom relation to a Post ID (Page ID)
    // relation types-> 2 -> relation to a custom post type-> post , page
    // relation types-> 3 -> automatic relation to master data keys
    $keys_json_file = file_get_contents(ABSPATH . 'wp-content/themes/time2play/languages/keys.json');
    $KeysJson = json_decode($keys_json_file, true);

    if (isset($KeysJson['keys'])) {
        foreach ($KeysJson['keys'] as $keyIndex) {

            if (isset($keyIndex['key']) && isset($keyIndex['label'])) {
                $key = 'system_' . slugify($keyIndex['key']);
                $value = $keyIndex['label'];

                $translation_results = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * from " . strings_table_name() . " WHERE `name` = %s ORDER BY `id` ASC", $key
                    ), ARRAY_A
                );

                if (count($translation_results) < 1) {
                    insert_key_phrase($key, $value);

                    $insertedID = $wpdb->get_row(
                        $wpdb->prepare(
                            "SELECT * FROM `" . strings_table_name() . "` WHERE `name` = %s AND LOWER(`language`) = %s", $key, 'en'
                        ), object);

                    insert_translations_for_child_languages($value, 'en-US', intval($insertedID->id));

                    $translation_results = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT * from " . strings_table_name() . " WHERE `name` = %s ORDER BY `id` ASC", $key
                        ), ARRAY_A
                    );
                }

                // manual blockTypes: 0 , automatic blockTypes => 4
                if (count($translation_results) > 0 && isset($keyIndex['block_types']) && count($keyIndex['block_types']) > 0) {

                    foreach ($translation_results as $translation_result) {
                        // we should update related blockTypes each time for each key..
                        $wpdb->query("DELETE FROM " . strings_translations_relations_table_name() . " WHERE `string_id` = " . $translation_result['id'] . " AND `relation_type` = 4");

                        foreach ($keyIndex['block_types'] as $block) {
                            $wpdb->insert(strings_translations_relations_table_name(), array(
                                "string_id" => $translation_result['id'],
                                "relation_type" => 4,
                                "relation_name" => $block
                            ));
                        }
                    }
                }

                // manual posts: 1 , automatic postTypes => 5
                if (count($translation_results) > 0 && isset($keyIndex['posts'])) {

                    foreach ($translation_results as $translation_result) {
                        // we should update related blockTypes each time for each key..
                        $wpdb->query("DELETE FROM " . strings_translations_relations_table_name() . " WHERE `string_id` = " . $translation_result['id'] . " AND `relation_type` = 5");

                        foreach ($keyIndex['posts'] as $post) {
                            $wpdb->insert(strings_translations_relations_table_name(), array(
                                "string_id" => $translation_result['id'],
                                "relation_type" => 5,
                                "relation_id" => $post
                            ));
                        }
                    }
                }

                // manual postTypes: 2 , automatic postTypes => 6
                if (count($translation_results) > 0 && isset($keyIndex['post_types'])) {

                    foreach ($translation_results as $translation_result) {
                        // we should update related blockTypes each time for each key..
                        $wpdb->query("DELETE FROM " . strings_translations_relations_table_name() . " WHERE `string_id` = " . $translation_result['id'] . " AND `relation_type` = 6");

                        foreach ($keyIndex['post_types'] as $post_type) {

                            $wpdb->insert(strings_translations_relations_table_name(), array(
                                "string_id" => $translation_result['id'],
                                "relation_type" => 6,
                                "relation_name" => $post_type
                            ));
                        }
                    }
                }

                if (count($translation_results) > 0 && isset($keyIndex['menus'])) {
                    // undefined for now...
                }
            }
        }
    }
}
