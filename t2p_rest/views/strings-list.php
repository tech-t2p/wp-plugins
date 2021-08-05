<?php
global $wpdb;
$langs = string_languages();

$blocks = (new \T2P\BlockType)->blockTypes();
// if we need to insert a new key we should do it before getting all the strings from db
// blockTypes, postsSelect2, menusSelect2
if (isset($_POST['stringFormType']) && $_POST['stringFormType'] == 'add' && isset($_POST['stringKey']) && !empty($_POST['stringKey']) && isset($_POST['phrase']) && !empty($_POST['phrase'])) {
    $key = slugify("custom_" . $_POST['stringKey']);
    insert_key_phrase($key, $_POST['phrase']);

    $insertedID = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM `" . strings_table_name() . "` WHERE `name` = %s AND LOWER(`language`) = %s", $key, 'en'
        ), object);

    insert_translations_for_child_languages($_POST['phrase'], 'en-US', intval($insertedID->id));

    if (isset($_POST['blockTypes'])) {
        insert_block_types_translations_relations($_POST['blockTypes'], intval($insertedID->id));
    } else {
        insert_block_types_translations_relations(null, intval($insertedID->id));
    }

    if (isset($_POST['postTypesSelect2'])) {
        insert_post_types_translations_relations($_POST['postTypesSelect2'], intval($insertedID->id));
    } else {
        insert_post_types_translations_relations(null, intval($insertedID->id));
    }

    if (isset($_POST['postsSelect2'])) {
        insert_posts_translations_relations($_POST['postsSelect2'], intval($insertedID->id));
    } else {
        insert_posts_translations_relations(null, intval($insertedID->id));
    }

}

if (isset($_POST['stringFormType']) && $_POST['stringFormType'] == 'edit' && isset($_POST['stringEditId']) && !empty($_POST['stringEditId']) && isset($_POST['phrase']) && !empty($_POST['phrase'])
    && (isset($_POST['stringKey']) && !empty($_POST['stringKey']) || isset($_POST['hiddenStringKey']) && !empty($_POST['hiddenStringKey']))
) {

    if (isset($_POST['stringKey']) && !empty($_POST['stringKey'])) {
        $key = slugify($_POST['stringKey']);
    } else {
        $key = slugify($_POST['hiddenStringKey']);
    }

    $wpdb->update(strings_table_name(), array(
        "name" => $key,
        "value" => $_POST['phrase'],
        "domain_name_context_md5" => md5('common-strings' . $key)
    ), array(
        "id" => $_POST['stringEditId']
    ));

    if (isset($_POST['enOverrideChildLanguages'])) {
        //delete translations for that key
        $langs = apply_filters('wpml_active_languages', null);


        delete_child_translations_for_parent_languages('en-US', intval($_POST['stringEditId']));

        insert_translations_for_child_languages($_POST['phrase'], 'en-US', intval($_POST['stringEditId']));
    }

    if (isset($_POST['postTypesSelect2'])) {
        insert_post_types_translations_relations($_POST['postTypesSelect2'], intval($_POST['stringEditId']));
    } else {
        insert_post_types_translations_relations(null, intval($_POST['stringEditId']));
    }

    if (isset($_POST['blockTypes'])) {
        insert_block_types_translations_relations($_POST['blockTypes'], intval($_POST['stringEditId']));
    } else {
        insert_block_types_translations_relations(null, intval($_POST['stringEditId']));
    }

    if (isset($_POST['postsSelect2'])) {
        insert_posts_translations_relations($_POST['postsSelect2'], intval($_POST['stringEditId']));
    } else {
        insert_posts_translations_relations(null, intval($_POST['stringEditId']));
    }

}

if (isset($_POST['stringFormType']) && $_POST['stringFormType'] == 'delete' && isset($_POST['stringEditId']) && !empty($_POST['stringEditId'])) {
    $string_id = intval($_REQUEST['stringEditId']);
    //delete the key
    $wpdb->delete(strings_table_name(), array(
        "id" => $string_id
    ));
    //delete translations for that key
    $wpdb->delete(strings_translations_table_name(), array(
        "string_id" => $string_id
    ));

    //delete relations for that key
    $wpdb->delete(strings_translations_relations_table_name(), array(
        "string_id" => $string_id
    ));
}

if (isset($_POST['relatedMasterDataBlocksModal'])) {
    if (isset($_POST['masterDataBlockTypes'])) {
        insert_master_data_blocks_relations($_POST['masterDataBlockTypes']);
    } else {
        insert_master_data_blocks_relations(null);
    }

    insertAndUpdateNewMasterDataKeys();
}
$all_strings = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * from " . strings_table_name() . " WHERE context = %s ORDER BY `id` DESC", 'common-strings'
    ), ARRAY_A
);

if (isset($_POST['updateMasterData'])) {
    insertAndUpdateNewMasterDataKeys();
}

?>

<div style="margin-top: 20px;" class="container">
    <div class="row">
        <div class="alert alert-info">
            <h5>Manage Strings and translations</h5>
        </div>
        <div class="panel panel-primary">
            <div style="font-size: large;padding: 10px;" class="panel-heading">Strings List</div>
            <div class="panel-body">
                <?php if (current_user_can('administrator')) { ?>
                    <div style="display: flex;flex-direction: row;justify-content: space-between;align-items: center;">
                        <button style="margin-bottom: 20px;font-size: medium;padding: 10px;" type="button"
                                class="btn btn-success" id="addNewString">
                            Add a new String
                        </button>
                        <div style="display: flex;flex-direction: row;">
                            <form method="post" action="<?php echo(admin_url("admin.php?page=" . $_GET["page"])); ?>">
                                <input type="hidden" id="updateMasterData" name="updateMasterData"
                                       value="updateMasterData">
                                <button type="submit" style="margin-bottom: 20px;font-size: small;padding: 10px;"
                                        class="btn btn-primary" id="updateTranslations">
                                    Update System Translations
                                </button>
                            </form>
                            <button
                                    data-blocks='<?php
                                    echo json_encode(get_master_data_blocks());
                                    ?>'
                                    style="margin-left:10px;margin-bottom: 20px;font-size: small;padding: 10px;"
                                    type="button"
                                    class="btn btn-success" id="relatedMasterDataBlocks">
                                Auto-Sync Master Data Blocks
                            </button>
                        </div>

                    </div>

                <?php } ?>

                <!-- NEW KEY Modal -->
                <div style="margin-top: 20px;" class="modal fade stringsModal">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="stringsModalLabel">Modal title</h5>
                                <button type="button" class="close dismissModal" data-dismiss="modal"
                                        aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form action="<?php echo(admin_url("admin.php?page=" . $_GET["page"])); ?>"
                                      id="stringsForm"
                                      method="post">

                                    <input type="hidden" id="stringFormType" name="stringFormType" value="">
                                    <input type="hidden" id="stringEditId" name="stringEditId" value="">
                                    <input type="hidden" id="hiddenStringKey" name="hiddenStringKey" value="">

                                    <div class="form-group">
                                        <label for="formGroupKeyInput">key</label>
                                        <input id="stringKey" type="text" class="form-control" name="stringKey"
                                               placeholder="should not contain spaces">
                                    </div>
                                    <div class="form-group">
                                        <label for="formGroupPhraseInput">Phrase</label>
                                        <input id="stringPhrase" type="text" class="form-control" name="phrase"
                                               placeholder="Phrase">


                                    </div>

                                    <div style="margin-bottom: 10px;" class="form-group">
                                        <input style="margin-top: 9px;" class="col-md-3" type="checkbox"
                                               id="enOverrideChildLanguages"
                                               name="enOverrideChildLanguages">
                                        <label class="col-md-9" id="enOverrideChildLanguagesLabel"
                                               for="enOverrideChildLanguages"><h5>Override Child
                                                Languages</h5>
                                        </label>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3"
                                               for="blockTypesSelect2"><h5>Block Types:</h5>
                                        </label>
                                        <select class="col-md-9" style="width: 70%" id="blockTypesSelect2"
                                                name="blockTypes[]" multiple="multiple">
                                            <?php foreach ($blocks as $key => $value) { ?>
                                                <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-md-3"
                                               for="postTypesSelect2"><h5>Post Types:</h5>
                                        </label>
                                        <select class="col-md-9" style="width: 70%" id="postTypesSelect2"
                                                name="postTypesSelect2[]" multiple="multiple">
                                            <option value="page">Page</option>
                                            <option value="post">Post</option>
                                            <option value="casinos">Casinos</option>
                                            <option value="casino_reviews">Casino Reviews</option>
                                            <option value="sports">Sports</option>
                                            <option value="authors">Authors</option>
                                            <option value="contacts">Contacts</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-md-3"
                                               for="postsSelect2"><h5>Posts:</h5>
                                        </label>
                                        <select class="col-md-9" style="width: 70%" id="postsSelect2"
                                                name="postsSelect2[]" multiple="multiple">

                                        </select>
                                    </div>

                                    <div style="background: lightgrey; padding: 10px;margin: 10px;">
                                        <p style="font-size: medium">Automatic Relations:</p>
                                        <div class="form-group">
                                            <label class="col-md-3"
                                                   for="automatic_blockTypesSelect2"><h5>Block Types:</h5>
                                            </label>
                                            <select disabled class="col-md-9" style="width: 70%"
                                                    id="automatic_blockTypesSelect2"
                                                    name="automatic_blockTypes[]" multiple="multiple">
                                                <?php foreach ($blocks as $key => $value) { ?>
                                                    <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label class="col-md-3"
                                                   for="automatic_postTypesSelect2"><h5>Post Types:</h5>
                                            </label>
                                            <select disabled class="col-md-9" style="width: 70%"
                                                    id="automatic_postTypesSelect2"
                                                    name="automatic_postTypesSelect2[]" multiple="multiple">
                                                <option value="page">Page</option>
                                                <option value="post">Post</option>
                                                <option value="casinos">Casinos</option>
                                                <option value="casino_reviews">Casino Reviews</option>
                                                <option value="sports">Sports</option>
                                                <option value="authors">Authors</option>
                                                <option value="contacts">Contacts</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label class="col-md-3"
                                                   for="automatic_postsSelect2"><h5>Posts:</h5>
                                            </label>
                                            <select disabled class="col-md-9" style="width: 70%"
                                                    id="automatic_postsSelect2"
                                                    name="automatic_postsSelect2[]" multiple="multiple">

                                            </select>
                                        </div>
                                    </div>


                                    <div style="display: flex;flex-direction: row;justify-content: space-between;margin-top: 20px;"
                                         class="form-group">
                                        <button type="button" class="btn btn-secondary dismissModal"
                                                data-dismiss="modal">
                                            Close
                                        </button>
                                        <button id="addNewString" type="submit" class="btn btn-primary">Save changes
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Modal Finished -->

                <!-- Related MasterData Blocks Modal -->
                <div style="margin-top: 20px;" class="modal fade relatedMasterDataBlocksModal">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="stringsModalLabel">Modal title</h5>
                                <button type="button" class="close dismissModal" data-dismiss="modal"
                                        aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form action="<?php echo(admin_url("admin.php?page=" . $_GET["page"])); ?>"
                                      id="masterDataBlocksForm"
                                      method="post">

                                    <input type="hidden" id="FormType" name="relatedMasterDataBlocksModal"
                                           value="relatedMasterDataBlocksModal">

                                    <div class="form-group">
                                        <label class="col-md-3"
                                               for="masterDataBlockTypesSelect2"><h5>Block Types:</h5>
                                        </label>
                                        <select class="col-md-9" style="width: 70%" id="masterDataBlockTypesSelect2"
                                                name="masterDataBlockTypes[]" multiple="multiple">
                                            <?php foreach ($blocks as $key => $value) { ?>
                                                <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>


                                    <div style="display: flex;flex-direction: row;justify-content: space-between;margin-top: 20px;"
                                         class="form-group">
                                        <button type="button" class="btn btn-secondary dismissModal"
                                                data-dismiss="modal">
                                            Close
                                        </button>
                                        <button id="addNewString" type="submit" class="btn btn-primary">Save changes
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Modal Finished -->

                <!-- Translate Modal -->
                <div style="margin-top: 20px;" class="modal fade" id="translateModal">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="translateModalLabel">Modal title</h5>
                                <button type="button" class="close dismissTranslateModal" data-dismiss="modal"
                                        aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form action="<?php echo(admin_url("admin.php?page=" . $_GET["page"])); ?>"
                                      id="translationForm" method="post">
                                    <input type="hidden" id="stringId" name="stringId" value="">
                                    <input type="hidden" id="langIsoCode" name="langIsoCode" value="">
                                    <input type="hidden" id="langTagCode" name="langTagCode" value="">
                                    <input type="hidden" id="stringTranslationId" name="stringTranslationId" value="">
                                    <input type="hidden" id="translationType" name="translationType" value="">
                                    <div class="form-row">
                                        <div class="form-group col-md-12">
                                            <label for="englishPhrase">English Phrase</label>
                                            <textarea disabled class="form-control" id="englishPhrase"
                                                      rows="3"></textarea>
                                        </div>
                                    </div>

                                    <div class="form-group col-md-12">
                                        <label for="translatedPhrase"><h5>Translated Phrase</h5></label>
                                        <textarea class="form-control" id="translatedPhrase" name="translatedPhrase"
                                                  rows="3"></textarea>
                                    </div>

                                    <div class="form-group col-md-12">
                                        <input style="margin-top: 9px;" class="col-md-3" type="checkbox"
                                               id="overrideChildLanguages"
                                               name="overrideChildLanguages">
                                        <label class="col-md-6" id="overrideChildLanguagesLabel"
                                               for="overrideChildLanguages"><h5>Override Child
                                                Languages</h5>
                                        </label>

                                    </div>

                                    <button style="margin-left: 20px;" type="submit" class="btn btn-primary btn-lg">Save
                                        Translation
                                    </button>

                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Modal Finished -->

                <table id="my-strings" class="display" cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        <th>id</th>
                        <th>Language</th>
                        <th>key</th>
                        <th>English Phrase</th>
                        <th>action</th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php
                    if (count($all_strings) > 0) {
                        $i = 1;
                        foreach ($all_strings as $key => $value) {
                            ?>
                            <tr>
                                <td><?php echo $value['id']; ?></td>
                                <td><?php echo $value['language']; ?></td>
                                <td><?php echo $value['name']; ?></td>
                                <td>
                                    <p><?php echo $value['value']; ?></p>
                                    <div>
                                        <?php foreach ($langs as $langKey => $lang) {
                                            if ($lang['code'] != 'en' && is_parent_language($lang['tag'])) { ?>

                                                <a class="translationPerLang" data-toggle="tooltip" data-placement="top"
                                                   title="<?php echo $lang['native_name'] . ' - ' . $lang['tag']; ?>"
                                                   href="javascript:void(0)"
                                                   data-lang-is-parent="1"
                                                   data-lang-native_name="<?php echo $lang['native_name']; ?>"
                                                   data-english-phrase="<?php echo $value['value']; ?>"
                                                   data-string-id="<?php echo $value['id']; ?>"
                                                   data-lang-iso-code="<?php echo $lang['code']; ?>"
                                                   data-lang-tag-code="<?php echo $lang['tag']; ?>"><img
                                                            style="margin: 3px;margin-top: 6px;padding: 1px;border-width: 2px;border-style: dotted;border-color: green;"
                                                            src="<?php echo $lang['country_flag_url']; ?>"/></a>
                                            <?php }
                                        } ?>

                                    </div>

                                    <div>
                                        <?php foreach ($langs as $langKey => $lang) {
                                            if ($lang['code'] != 'en' && !is_parent_language($lang['tag'])) { ?>

                                                <a class="translationPerLang" data-toggle="tooltip" data-placement="top"
                                                   title="<?php echo $lang['native_name'] . ' - ' . $lang['tag']; ?>"
                                                   href="javascript:void(0)"
                                                   data-lang-native_name="<?php echo $lang['native_name']; ?>"
                                                   data-english-phrase="<?php echo $value['value']; ?>"
                                                   data-lang-is-parent="0"
                                                   data-string-id="<?php echo $value['id']; ?>"
                                                   data-lang-iso-code="<?php echo $lang['code']; ?>"
                                                   data-lang-tag-code="<?php echo $lang['tag']; ?>"><img
                                                            style="margin: 3px;margin-top: 6px;padding: 1px;border-width: 2px;border-style: dotted;border-color: green;"
                                                            src="<?php echo $lang['country_flag_url']; ?>"/></a>
                                            <?php }
                                        } ?>

                                    </div>
                                </td>
                                <td class="col-md-2">

                                    <?php if (current_user_can('administrator')) { ?>
                                        <a class="btn btn-info btnStringEdit"
                                           data-phrase="<?php echo $value['value']; ?>"
                                           data-key="<?php echo $value['name']; ?>"
                                           data-id="<?php echo $value['id']; ?>"
                                           data-blocks='<?php
                                           echo json_encode(get_block_types_translations_relations(intval($value['id'])));
                                           ?>'
                                           data-post-types='<?php
                                           echo json_encode(get_post_types_translations_relations(intval($value['id'])));
                                           ?>'
                                           data-posts='<?php
                                           echo json_encode(get_posts_translations_relations(intval($value['id'])));
                                           ?>'
                                           data-menus=''

                                           data-automatic-blocks='<?php
                                           echo json_encode(get_block_types_translations_relations(intval($value['id']), true));
                                           ?>'
                                           data-automatic-post-types='<?php
                                           echo json_encode(get_post_types_translations_relations(intval($value['id']), true));
                                           ?>'
                                           data-automatic-posts='<?php
                                           echo json_encode(get_posts_translations_relations(intval($value['id']), true));
                                           ?>'

                                           href="javascript:void(0)">Edit</a>

                                        <a class="btn btn-danger btnTranslationDelete" href="javascript:void(0)"
                                           data-id="<?php echo $value['id']; ?>">Delete</a> <br/>
                                    <?php } ?>


                                </td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>