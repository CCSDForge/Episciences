function __initMCE(selectorName, context, options) {
    let f = __initEditor(selectorName, context, options);
    __initAction(f);

    $(document).on('click', 'input[type="submit"], button[type="submit"]', function (event) {
        $(this).closest("form").find(".glyphicon-ok").parent().trigger("click");
    })
};

function __initEditor(selectorName, context, options) {

    const tinyMCE_image_upload_handler = (blobInfo, progress) => new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.withCredentials = false;
        xhr.open('POST', 'tinyMcePostAcceptor.php');

        xhr.upload.onprogress = (e) => {
            progress(e.loaded / e.total * 100);
        };

        xhr.onload = () => {
            if (xhr.status === 403) {
                reject({ message: 'HTTP Error: ' + xhr.status, remove: true });
                return;
            }

            if (xhr.status < 200 || xhr.status >= 300) {
                reject('HTTP Error: ' + xhr.status);
                return;
            }

            console.log(xhr.responseText);

            const json = JSON.parse(xhr.responseText);

            if (!json || typeof json.location != 'string') {
                reject('Invalid JSON: ' + xhr.responseText);
                return;
            }

            resolve(json.location);
        };

        xhr.onerror = () => {
            reject('Image upload failed due to a XHR Transport error. Code: ' + xhr.status);
        };

        const formData = new FormData();
        formData.append('file', blobInfo.blob(), blobInfo.filename());

        xhr.send(formData);
    });



    // see https://www.tiny.cloud/docs-4x/configure/url-handling/#domainabsoluteurls
    let domainAbsoluteURLsOptions = {
        convert_urls: false,
        relative_urls: false,
        remove_script_host: false,
        document_base_url: window.location.origin
    }

    if (options === undefined) {
        options = $.extend(domainAbsoluteURLsOptions, {
            theme: 'silver',
            plugins: "link image code fullscreen table",
            toolbar1: "bold italic underline | forecolor backcolor | styleselect | undo redo | alignleft aligncenter alignright alignjustify | bullist numlist | link image  | fullscreen",
            menubar: false,
            height : 200,
            resize:true,
        });

    } else {
        options = $.extend(options, domainAbsoluteURLsOptions);
    }

    options = $.extend(options, {
        //images_upload_credentials : true,
        images_upload_handler: tinyMCE_image_upload_handler
    })

    console.log(options)

    if (context) {
        $(selectorName, $(context)).tinymce(options);
    } else {
        options = $.extend(options, {selector: selectorName});
        tinymce.init(options);
    }

    tinyMCE.DOM.setStyle(tinyMCE.DOM.get('content'), 'height', '500px');

    return $(selectorName).closest('.form-group');
}

function __pasteContentMCE() {
    tiny = tinyMCE.activeEditor;

    tiny.getElement().value = tiny.getContent({format: 'html'});
};

function __destroyActiveMCE() {
    tiny = tinyMCE.activeEditor;
    $(tiny.getElement()).tinymce().remove();
};

function __removeAttrOnclick(e) {
    let fct = $(e).attr('onclick');
    $(e).removeAttr('onclick');
    return fct;
};

function __initAction(context) {

    $('.glyphicon-plus', context).parent().each(function (i) {
        let fct = __removeAttrOnclick(this);

        $(this).click(function (event) {
            __pasteContentMCE();
            eval(fct);
            $(this).closest('.textarea-group').parent().find('.textarea-group:not(:last-child)').each(function (i) {
                __initModifications(this);
            });
        });
    });

    $(context).find('.textarea-group:not(:last-child)').each(function (i) {
        __initModifications(this);
    });

    function __initModifications(context) {
        let textarea = $(context).find('textarea');
        $('.glyphicon-pencil', context).parent().each(function (i) {
            let fct = __removeAttrOnclick(this);
            $(this).click(function (event) {
                tinyMCE.activeEditor.setContent(textarea[0].value);
                eval(fct);
                $('.glyphicon-ok', $(context).parent().find('.textarea-group:last')).parent().each(function (i) {
                    let fct = __removeAttrOnclick(this);
                    $(this).click(function (event) {
                        __pasteContentMCE();
                        eval(fct);
                        if (!$.isEmptyObject(tinyMCE.activeEditor)) {
                            tinyMCE.activeEditor.setContent("");
                        }
                    });
                });
            });
        });

        if (!$.isEmptyObject(tinyMCE.activeEditor)) {
            tinyMCE.activeEditor.setContent("");
        }
    };
};