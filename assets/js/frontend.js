jQuery(document).ready(function ($) {
    var bloom = $('#bloom');

    // =========================
    // Preferences
    // =========================
    $('#pref_submit').click(function (event) {
        "use strict";
        event.preventDefault();
        var preference = $('#preferences').find(':radio:checked').val();
        if (preference) {
            $.post(
                POM_BLOOM.ajax_url,
                {
                    user:       POM_BLOOM.current_user,
                    preference: preference,
                    route: 'preferences',
                    action: 'pom_bloom'
                },
                function (result) {
                    if(result && result.success) {
                        window.location.search = "page=overview";
                    }
                },
                'json'
            )
        }
        console.log(preference);
    });
    $('#pref_cancel').click(function (event) {
        "use strict";
        event.preventDefault();
        window.location.search = "page=overview";
    });

    // ===============================
    // Assessments
    // ===============================

    $('form.assessment_create').submit(function(event) {
        "use strict";
        event.preventDefault();
        var assessment = $(this).serializeArray(),
            radios = jQuery('input:radio', bloom),
            groups = [],
            bad = [];

        radios.each(function(idx,item) {
            if(groups.indexOf(item.name) === -1) {
                groups.push(item.name);
                if(!_.find(assessment, function(result) {
                        return result.name === item.name;
                    })) {
                    bad.push(item.name);
                }
            }
        });
        $('.qgroup').each(function(idx,item) {
            if(bad.indexOf(item.id.replace('_group','')) === -1) {
                $(item).removeClass('missing');
            } else {
                $(item).addClass('missing');
            }
        });
        if(bad.length > 0) {
            $('#missing_warning').text("It looks like you're missing "+bad.length+" answers. Please check your answers try submitting again.").show();
        } else {
            $('#missing_warning').hide();
            $.post(
                POM_BLOOM.ajax_url,
                {
                    user:       POM_BLOOM.current_user,
                    assessment: assessment,
                    route: 'assessments',
                    action: 'pom_bloom'
                },
                function (result) {
                    if(result && result.success) {
                        window.location.search = "page=overview";
                    }
                },
                'json'
            );

        }
    });
    window.fillform = function(theVal) {
        jQuery('input[type="radio"]').each(function(item,input){
            if(theVal < 0) {
                input.checked = false;
            } else {
                if (parseInt(input.value) === parseInt(theVal)) {
                    input.checked = true;
                }
            }
        });
    }
});