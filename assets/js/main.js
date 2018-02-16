var timeouts = {
    keydown: null
}

var entryForm = {
    form: function(){return $("#entry_form");},
    previewImage: function(){return $("#preview");},
    isModeCreate: function () {
        return (entryForm.form().data('mode') === 'create');
    },
    fields: {
        url: function(){return $('input#entry_url');},
        title: function(){return $('input#entry_title');},
        tags: function(){return $('input#entry_tags');},
        categories: function(){return $('input[name="entry[category]"]');},
        notificationSpan: function(){return $('#metainfo-notification');}
    }
}

var application = {
    loaderHtml: '<i class="fa fa-circle-o-notch fa-spin fa-2x fa-fw"></i><span class="sr-only">Loading...</span>',
    getMetaInfo: function($urlElement){
        entryForm.fields.notificationSpan().html(application.loaderHtml);
        const url = $urlElement.data('metainfo-url');

        $.ajax({
            url: url,
            dataType: 'json',
            data: {
                url: $urlElement.val(),
                edit_id: entryForm.form().data('edit-id')
            },
            success: function(data){
                entryForm.fields.notificationSpan().html('');

                if (entryForm.fields.title().val() == '') {
                    entryForm.fields.title().val(data.title);
                }

                if (data.image_url) {
                    var previewImage = $('<img/>')
                        .attr('src', data.image_url)
                        .addClass('card-img-top');
                    entryForm.previewImage().html(previewImage);
                }

                var id = entryForm.fields.categories().filter('[value=' + data.category + ']').attr('id');
                if (id) {
                    $('[for=' + id + ']').trigger('click');
                }
                entryForm.fields.tags().focus();

                $urlElement.parent().removeClass('has-error');
                if (data.url_duplicate) {
                    $urlElement.parent().addClass('has-error');
                    entryForm.fields.notificationSpan().html('URL already taken');
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
        if (entryForm.isModeCreate()) {
            entryForm.fields.url()
                .on('input', application.getMetaInfoDelayed)
                .trigger('input');
        }

        entryForm.form().keydown(function(event) {
            if (event.ctrlKey && event.keyCode == 13) {
                entryForm.form().submit();
            }
        });

        var $tagInput = entryForm.fields.tags();
        if ($tagInput.length > 0) {
            new Awesomplete($tagInput[0],{
                filter: function(text, input) {
                    return Awesomplete.FILTER_CONTAINS(text, input.match(/[^,]*$/)[0]);
                },
                replace: function(text) {
                    var before = this.input.value.match(/^.+,\s*|/)[0];
                    this.input.value = before + text + ", ";
                }
            });
        }

        $('.visit-link').mousedown(function(e){
            $.ajax($(this).data('link'), {type: 'POST'});
        });

        //$('div.entry-list').jscroll({
            //loadingHtml: application.loaderHtml,
            //padding: 20,
            //nextSelector: 'ul.pagination li.next a',
            //contentSelector: 'div.entry-list',
            //callback: function() {
                //application.registerEvents($(this));
            //}
        //});

        $domElement.find('.deferred-image').imageloader();
    }
}

// all JS is already loading defered
// therefore document.ready und window.load already happened
// and won't be triggered again

application.registerEvents($(document));
