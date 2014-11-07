jQuery(document).ready(function ($) {
    var bloom = $('#bloom');
    console.log(bloom);
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
});