var timeouts = {
    keydown: null
}

var entryForm = {
    form: function(){return $("#entry_form");},
    fields: {
        url: function(){return $('input#entry_url');},
        title: function(){return $('input#entry_title');},
        categories: function(){return $('input[name="entry[category]"]');},
        notificationSpan: function(){return $('span#metainfo-notification');}
    }
}

var application = {
    getMetaInfo: function($urlElement){
        entryForm.fields.notificationSpan().html('trying to retrieve URL meta data ...');
        $.ajax({
            url: mentalNote.route.url_metainfo,
            dataType: 'json',
            data: {
                url: $urlElement.val()
            },
            success: function(data){
                entryForm.fields.notificationSpan().html('');

                if (entryForm.fields.title().val() == '') {
                    entryForm.fields.title().val(data.title);
                }

                var id = entryForm.fields.categories().filter('[value=' + data.category + ']').attr('id');
                if (id) {
                    $('[for=' + id + ']').trigger('click');
                }
            },
            error: function() {
                entryForm.fields.notificationSpan().html('error retrieving URL meta data');
            }
        });
    },
    getMetaInfoDelayed: function(){
        var $element = $(this);
        clearTimeout(timeouts.keydown);
        timeouts.keydown = setTimeout(function(){application.getMetaInfo($element)}, 500);
    },
    registerEvents: function($domElement) {

        entryForm.fields.url().keydown(application.getMetaInfoDelayed);
        entryForm.form().keydown(function(event) {
            if (event.ctrlKey && event.keyCode == 13) {
                entryForm.form().submit();
            }
        });

        $domElement.find('.modal-ajax-form').modalAjaxForm({
            onComplete: function($element){
                application.registerEvents($element);
                $element.find(':text').first().focus();
            }
        });

        var tagInput = $("#entry_tags");
        if (tagInput.length > 0) {

            tagInput.select2({
                tokenSeperator: [','],
                tags: application.searchTags()
            });
        }

        $('.visit-link').mousedown(function(e){
            $.ajax($(this).data('link'),{
                  type: 'POST'
            });
        });

    },
    searchTags: function(query) {
        var tags = null;
        $.ajax({
            url: mentalNote.route.tag_search,
            dataType: 'json',
            success: function(data) {
                tags = data;
            },
            async: false
        });

        return tags;
    }
}

// all JS is already loading defered
// therefore document.ready und window.load already happened
// and won't be triggered again

application.registerEvents($(document));
$('.deferred-image').imageloader();

