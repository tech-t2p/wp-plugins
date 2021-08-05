jQuery(document).ready(function () {
    var validator = jQuery("#stringsForm").validate({
        rules: {
            stringKey: {
                required: true,
                minlength: 1
            },
            stringEditId: {
                required: true,
                number: true,
            },
            phrase: {
                required: true,
            }
        },
        messages: {
            stringKey: {
                required: "Please enter the Key",
                minlength: "Key should be at least 1 characters"
            },
            stringEditId: {
                required: "ID is missing",
                number: "Id should be a number value"
            },
            phrase: {
                required: "Please enter the phrase",
            }
        }
    });

    var translationValidator = jQuery("#translationForm").validate({
        rules: {
            langIsoCode: {
                required: true,
                minlength: 1
            },
            langTagCode: {
                required: true,
                minlength: 1
            },
            stringId: {
                required: true,
                number: true,
            },
            stringTranslationId: {
                required: false,
                number: true,
            },
            translatedPhrase: {
                required: true,
            }
        },
        messages: {
            stringId: {
                required: "Please enter the string ID",
                minlength: "Key should be at least 1 characters"
            },
            langTagCode: {
                required: "langTagCode is missing"
            },
            langIsoCode: {
                required: "langIsoCode is missing"
            },
            stringTranslationId: {
                required: "stringId is missing",
                number: "stringTranslationId is a number value"
            },
            translatedPhrase: {
                required: "Please enter the translated phrase",
            }
        },
        submitHandler: function () {
            var translatedPhrase = jQuery("#translatedPhrase").val();
            var stringId = jQuery("#stringId").val()
            var langIsoCode = jQuery("#langIsoCode").val()
            var langTagCode = jQuery("#langTagCode").val()
            var stringTranslationId = jQuery("#stringTranslationId").val()
            var translationType = jQuery("#translationType").val()
            var overrideChildLanguages = jQuery("#overrideChildLanguages").prop('checked')

            var data = {
                'action': 'set_translation_for_string',
                'lang_tag': langTagCode,
                'lang_iso_code': langIsoCode,
                'string_id': stringId,
                'stringTranslationId': stringTranslationId,
                'translatedPhrase': translatedPhrase,
                'translationType': translationType,
                'overrideChildLanguages': overrideChildLanguages
            };

            // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
            jQuery.post(ajaxurl, data, function (response) {
                var data = jQuery.parseJSON(response);
                if (data.status === 1) {
                    jQuery("#translateModal").toggle('modal').modal('hide');
                    translationValidator.resetForm();
                    jQuery("#stringEditId").val('');
                    jQuery("#stringKey").val('');
                    jQuery("#stringPhrase").val('');
                    jQuery("#parentPhrase").val('');
                }
            });
        }
    });

    jQuery(".dismissModal").on("click", function () {
        jQuery("#stringsModal").toggle('modal').modal('hide');
        validator.resetForm();
        translationValidator.resetForm();
    });

    jQuery(".dismissTranslateModal").on("click", function () {
        jQuery("#translateModal").toggle('modal').modal('hide');
        validator.resetForm();
        translationValidator.resetForm();
    });

    jQuery(".btnStringEdit").on("click", function () {
        jQuery('.modal-title').text('Edit String');
        jQuery("#stringFormType").val('edit');

        var id = jQuery(this).attr("data-id");
        var key = jQuery(this).attr("data-key");
        var phrase = jQuery(this).attr("data-phrase");

        jQuery("#stringEditId").val(id);
        jQuery("#stringKey").val(key);
        jQuery("#hiddenStringKey").val(key);
        if (!key.includes('custom_')) {
            jQuery("#stringKey").prop("disabled", true);
        } else {
            jQuery("#stringKey").prop("disabled", false);
        }
        jQuery("#stringPhrase").val(phrase);

        jQuery("#enOverrideChildLanguages").prop("checked", false).show();
        jQuery("#enOverrideChildLanguagesLabel").show();

        jQuery('#blockTypesSelect2').val(null).trigger('change');
        jQuery('#automatic_blockTypesSelect2').val(null).trigger('change');

        jQuery('#postTypesSelect2').val(null).trigger('change');
        jQuery('#automatic_postTypesSelect2').val(null).trigger('change');

        jQuery('#postsSelect2').val(null).trigger('change');
        jQuery('#automatic_postsSelect2').val(null).trigger('change');

        jQuery('#menusSelect2').val(null).trigger('change');

        var blocks = jQuery.parseJSON(jQuery(this).attr("data-blocks"));
        var chosenBlocks = [];
        blocks.forEach((data, index) => {
            chosenBlocks.push(data.relation_name)
        });
        jQuery('#blockTypesSelect2').val(chosenBlocks).trigger('change');

        var posts = jQuery.parseJSON(jQuery(this).attr("data-posts"));
        posts.forEach((data, index) => {
            if (jQuery('#postsSelect2').find("option[value='" + data.relation_id + "']").length) {
                jQuery('#postsSelect2').val(data.relation_id).trigger('change');
            } else {
                // Create a DOM Option and pre-select by default
                var newOption = new Option(data.title, data.relation_id, true, true);
                // Append it to the select
                jQuery('#postsSelect2').append(newOption).trigger('change');
            }
        });

        var postTypes = jQuery.parseJSON(jQuery(this).attr("data-post-types"));
        var chosenTypes = [];
        postTypes.forEach((data, index) => {
            chosenTypes.push(data.relation_name)
        });
        jQuery('#postTypesSelect2').val(chosenTypes).trigger('change');


        //automatic blocks:
        var automatic_blocks = jQuery.parseJSON(jQuery(this).attr("data-automatic-blocks"));
        var automatic_chosenBlocks = [];
        automatic_blocks.forEach((data, index) => {
            automatic_chosenBlocks.push(data.relation_name)
        });
        jQuery('#automatic_blockTypesSelect2').val(automatic_chosenBlocks).trigger('change');

        var automatic_posts = jQuery.parseJSON(jQuery(this).attr("data-automatic-posts"));
        automatic_posts.forEach((data, index) => {
            if (jQuery('#automatic_postsSelect2').find("option[value='" + data.relation_id + "']").length) {
                jQuery('#automatic_postsSelect2').val(data.relation_id).trigger('change');
            } else {
                // Create a DOM Option and pre-select by default
                var newOption = new Option(data.title, data.relation_id, true, true);
                // Append it to the select
                jQuery('#automatic_postsSelect2').append(newOption).trigger('change');
            }
        });

        var automatic_postTypes = jQuery.parseJSON(jQuery(this).attr("data-automatic-post-types"));
        var automatic_chosenTypes = [];
        automatic_postTypes.forEach((data, index) => {
            automatic_chosenTypes.push(data.relation_name)
        });
        jQuery('#automatic_postTypesSelect2').val(automatic_chosenTypes).trigger('change');

        jQuery(".stringsModal").modal();
    });

    jQuery("#addNewString").on("click", function () {
        jQuery('.modal-title').text('Add New String');
        jQuery('#formGroupLanguageInput option[value="en"]').attr("selected", "selected");
        jQuery("#stringFormType").val('add');

        jQuery("#stringEditId").val('');
        jQuery("#stringKey").val('').prop("disabled", false);
        jQuery("#hiddenStringKey").val('');
        jQuery("#stringPhrase").val('');

        jQuery('#postTypesSelect2').val(null).trigger('change');
        jQuery('#menusSelect2').val(null).trigger('change');
        jQuery('#blockTypesSelect2').val(null).trigger('change');
        jQuery('#postsSelect2').val(null).trigger('change');

        jQuery("#enOverrideChildLanguages").prop("checked", false);
        jQuery("#enOverrideChildLanguages").hide();
        jQuery("#enOverrideChildLanguagesLabel").hide();

        jQuery(".stringsModal").modal();
    });

    jQuery("#relatedMasterDataBlocks").on("click", function () {
        jQuery('.modal-title').text('Auto-Sync Master Data Blocks Translations');

        var blocks = jQuery.parseJSON(jQuery(this).attr("data-blocks"));
        var chosenBlocks = [];
        blocks.forEach((data, index) => {
            chosenBlocks.push(data.block_name)
        });
        jQuery('#masterDataBlockTypesSelect2').val(chosenBlocks).trigger('change');

        jQuery(".relatedMasterDataBlocksModal").modal();
    });

    jQuery(".translationPerLang").on("click", function () {
        var id = jQuery(this).attr("data-string-id");
        var langTag = jQuery(this).attr("data-lang-tag-code");
        var langCode = jQuery(this).attr("data-lang-iso-code");
        var english_phrase = jQuery(this).attr("data-english-phrase");
        var lang_native_name = jQuery(this).attr("data-lang-native_name");
        var lang_is_parent = jQuery(this).attr("data-lang-is-parent");

        jQuery("#englishPhrase").val(english_phrase);
        jQuery("#stringId").val(id)
        jQuery("#langIsoCode").val(langCode)
        jQuery("#langTagCode").val(langTag)

        var data = {
            'action': 'get_translation_for_string',
            'lang_tag': langTag,
            'lang_iso_code': langCode,
            'string_id': id
        };

        // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
        jQuery.post(ajaxurl, data, function (response) {
            jQuery('#translateModalLabel').text('Translation for ' + lang_native_name + ' ( ' + langTag + ' ) ');

            var obj = jQuery.parseJSON(response);

            if (obj.this) {
                jQuery("#translatedPhrase").val(obj.this.value)

                jQuery("#stringTranslationId").val(obj.this.id)
                jQuery("#translationType").val('translationEdit')
                jQuery("#overrideChildLanguages").prop("checked", false);
                jQuery("#overrideChildLanguages").show();
                jQuery("#overrideChildLanguagesLabel").show();
            } else {
                jQuery("#translatedPhrase").val("")
                jQuery("#stringTranslationId").val("")
                jQuery("#translationType").val('translationAdd')
                jQuery("#overrideChildLanguages").prop("checked", false);
                jQuery("#overrideChildLanguages").hide();
                jQuery("#overrideChildLanguagesLabel").hide();
            }

            if (lang_is_parent !== "1") {
                jQuery("#overrideChildLanguages").prop("checked", false);
                jQuery("#overrideChildLanguages").hide();
                jQuery("#overrideChildLanguagesLabel").hide();
            }

            jQuery("#translateModal").toggle('modal').modal('show');
        });
    });

    jQuery('#my-strings').DataTable({
        order: [[0, 'desc']],
    });

    jQuery(document).on("click", ".btnTranslationDelete", function () {
        var conf = confirm("Are you sure want to delete?");
        if (conf) { //if(true)

            var string_id = jQuery(this).attr("data-id");
            jQuery(".btnAddStringModal").attr('name', 'deleteString');
            jQuery("#stringEditId").val(string_id);
            jQuery("#stringFormType").val('delete');
            jQuery("#stringsForm").submit();
        }
    });

    jQuery('#automatic_blockTypesSelect2').select2();
    jQuery('#automatic_postTypesSelect2').select2();
    jQuery('#automatic_postsSelect2').select2();

    jQuery('#blockTypesSelect2').select2();
    jQuery('#masterDataBlockTypesSelect2').select2();
    jQuery('#postsSelect2').select2({
        ajax: {
            url: ajaxurl, // AJAX URL is predefined in WordPress admin
            dataType: 'json',
            delay: 250, // delay in ms while typing when to perform a AJAX search
            data: function (params) {
                return {
                    q: params.term, // search query
                    action: 'get_custom_posts' // AJAX action for admin-ajax.php
                };
            },
            processResults: function (data) {
                return data
            },
            cache: true
        },
        minimumInputLength: 3
    });
    jQuery('#postTypesSelect2').select2();
    jQuery('#menusSelect2').select2();


});