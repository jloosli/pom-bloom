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
                    route:      'preferences',
                    action:     'pom_bloom'
                },
                function (result) {
                    if (result && result.success) {
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

    $('form.assessment_create').submit(function (event) {
        "use strict";
        event.preventDefault();
        var assessment = $(this).serializeArray(),
            radios = jQuery('input:radio', bloom),
            groups = [],
            bad = [];

        radios.each(function (idx, item) {
            if (groups.indexOf(item.name) === -1) {
                groups.push(item.name);
                if (!_.find(assessment, function (result) {
                        return result.name === item.name;
                    })) {
                    bad.push(item.name);
                }
            }
        });
        $('.qgroup').each(function (idx, item) {
            if (bad.indexOf(item.id.replace('_group', '')) === -1) {
                $(item).removeClass('missing');
            } else {
                $(item).addClass('missing');
            }
        });
        if (bad.length > 0) {
            $('#missing_warning').text("It looks like you're missing " + bad.length + " answers. Please check your answers try submitting again.").show();
        } else {
            $('#missing_warning').hide();
            $.post(
                POM_BLOOM.ajax_url,
                {
                    user:       POM_BLOOM.current_user,
                    assessment: assessment,
                    route:      'assessments',
                    action:     'pom_bloom'
                },
                function (result) {
                    if (result && result.success) {
                        window.location.search = "page=overview";
                    }
                },
                'json'
            );

        }
    });
    window.fillform = function (theVal) {
        jQuery('input[type="radio"]').each(function (item, input) {
            if (theVal < 0) {
                input.checked = false;
            } else {
                if (parseInt(input.value) === parseInt(theVal)) {
                    input.checked = true;
                }
            }
        });
    };

    // ===============================
    // Goals.Set
    // ===============================

    $('form.goals_set', bloom).find('.subcategories select').change(function () {
        "use strict";
        var self = $(this);
        var subcategory = $('option:selected', self).val();
        $.post(
            POM_BLOOM.ajax_url,
            {
                user:        POM_BLOOM.current_user,
                category_id: subcategory,
                route:       'goal_suggestions',
                action:      'pom_bloom'
            },
            function (result) {
                if (result && result.success) {
                    console.log(result);
                    var li,
                        resultsDiv = self.parents('fieldset').find('.recommendations .results'),
                        list = $('<ul />');

                    if (!result.goals.length) {
                        resultsDiv.text("No suggestions available for this category.");
                        return;
                    }
                    $.each(result.goals, function (item, val) {
                        li = $('<li />').append($('<div></div>', {
                            'class':         'clickable',
                            'data-id':       val.id,
                            'data-per_week': val.per_week,
                            text:            val.suggestion,
                            click:           function () {
                                var me = $(this),
                                    fieldset = me.parents('fieldset'),
                                    textarea = fieldset.find('.goal textarea'),
                                    per_week = fieldset.find('.per_week select'),
                                    goal_id = fieldset.find('.goal input[name^="suggestions"]');
                                me.addClass('selected').delay(2000).queue(function (next) {
                                    me.removeClass('selected');
                                    next();
                                });
                                textarea.val(me.text());
                                goal_id.val(me.data('id'));
                                per_week.val(me.data('per_week'));
                                $('html, body').animate({
                                    scrollTop: textarea.offset().top - 200
                                }, 2000);

                            }
                        }));
                        li.appendTo(list);
                    });
                    resultsDiv.html(list);
                    console.log(resultsDiv);
                }
            },
            'json'
        );

    });

    $('form.goals_set', bloom).submit(function (e) {
        e.preventDefault();
        console.log($(this).serialize());
        $.post(
            POM_BLOOM.ajax_url,
            {
                user:   POM_BLOOM.current_user,
                data:   $(this).serialize(),
                route:  'add_goals',
                action: 'pom_bloom'
            },
            function (result) {
                if (result && result.success) {
                    window.location.search = "page=overview";
                }
            },
            'json'
        );

    });


    if (window.location.search.indexOf('page=goals.set') && window.google) {
        google = window.google;

        google.load('visualization', '1.0', {
            'callback':    function () {
                console.log('loading');
            }, 'packages': ['corechart']
        });
        google.setOnLoadCallback(drawCharts);

        function drawCharts() {
            console.log('Drawing Charts');

            for (var key in window.theCharts) {
                var data = [['Assessments', 'Average']];
                window.theCharts[key].data.forEach(function (item) {
                    "use strict";
                    data.push([item.assessment.split(' ')[0], item.average]);
                });
                var table = google.visualization.arrayToDataTable(data);
                var view = new google.visualization.DataView(table); // @todo figure out why this isn't working
                view.setColumns([0, 1, {
                    calc:         "stringify",
                    sourceColumn: 1,
                    type:         "number",
                    role:         "annotation"
                }]);
                var options = {
                    title:  window.theCharts[key].category,
                    hAxis:  {title: 'Assessments'},
                    vAxis:  {minValue: 0, maxValue: 5},
                    legend: 'none'
                };
                var chart_locations = document.getElementsByClassName(window.theCharts[key].location);

                [].forEach.call(chart_locations, function (loc) {
                    "use strict";
                    var chart = new google.visualization.ColumnChart(loc);
                    chart.draw(table, options);
                });


            }
        }
    }

    // ==========================
    // Goals Update
    // ==========================

    $('#goals_update .clickable', bloom).click(function() {
        "use strict";
        var self = $(this),
            is_set = self.hasClass('set'),
            done_cell =self.siblings('.done'),
            per_week = done_cell.data('per_week'),
            total_set = 0;

        $.post(
            POM_BLOOM.ajax_url,
            {
                user:   POM_BLOOM.current_user,
                set:   !is_set,
                goal:   self.data('goal'),
                day: self.data('day'),
                route:  'update_goals',
                action: 'pom_bloom'
            },
            function (result) {
                if (result && result.success) {
                    self.removeClass('set');
                    done_cell.removeClass('set');
                    total_set = self.siblings('.set').length;
                    if(result.set) {
                        self.addClass('set');
                        total_set += 1;
                    }
                    if(total_set >= per_week) {
                        done_cell.addClass('set');
                    }
                }
            },
            'json'
        )
    });

    var serendipity = $('#goals_update .serendipity', bloom);
    serendipity.on('change keyup paste', debounce(function() {
        "use strict";
        var self = $(this);
        var done_cell = self.parent().siblings('.done');
        done_cell.removeClass('set');
        if($.trim(self.val()) !== '') {
            done_cell.addClass('set');
        }
        $.post(
            POM_BLOOM.ajax_url,
            {
                user:   POM_BLOOM.current_user,
                serendipity:   self.val(),
                id:   self.data('id'),
                route:  'update_serendipity',
                action: 'pom_bloom'
            },
            function (result) {

            },
            'json'
        )

    }, 200));


    $('#goals_update').find('tr.goal th div').click(function(){
        "use strict";
        var self = $(this),
            isTruncated = self.triggerHandler("isTruncated");
        if(isTruncated) {
            this.style.height = '1000px';
            self.trigger('update');
            this.style.height = 'auto';
        } else {
            this.style.height = '';
        }
        self.trigger('update');
    }).dotdotdot({
        callback: function(isTruncated, orgContent) {
            "use strict";
            if(isTruncated) {
                this.title = orgContent[0].data;
            }
        }
    }).trigger('update.dot'); // Not sure why I have to push this trigger

    if(window.location.search.indexOf('goals.update') !== -1){
        $('body').children().addClass('doNotPrint');
        showItem($('#bloom'));
    }

});

function showItem($node) {
    // remove the 'hide' class. This is moot
    // until we loop to the top level node, at
    // which point this takes affect
    $node.removeClass('doNotPrint');
    // we don't want to print the siblings
    // so we will hide those
    $node.siblings().addClass('doNotPrint')
    // now we check to see if this item has a parent
    // and, if so...
    if($node.parent().length){
        // ...we want to show the parent, hide its
        // siblings, and then continue this process
        // back to the top of the node tree
        showItem($node.parent());
    }
}

// Returns a function, that, as long as it continues to be invoked, will not
// be triggered. The function will be called after it stops being called for
// N milliseconds. If `immediate` is passed, trigger the function on the
// leading edge, instead of the trailing.
function debounce(func, wait, immediate) {
    var timeout;
    return function() {
        var context = this, args = arguments;
        var later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        var callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
}

