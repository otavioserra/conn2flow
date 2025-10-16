$(document).ready(function () {
    // ===== Codemirror 

    var codemirrors_instances = new Array();

    var codemirror = document.getElementsByClassName("codemirror");

    if (codemirror.length > 0) {
        for (var i = 0; i < codemirror.length; i++) {
            var codeMirror = CodeMirror.fromTextArea(codemirror[i], {
                lineNumbers: true,
                lineWrapping: true,
                styleActiveLine: true,
                matchBrackets: true,
                mode: "markdown",
                htmlMode: true,
                indentUnit: 4,
                theme: "tomorrow-night-bright",
                extraKeys: {
                    "F11": function (cm) {
                        cm.setOption("fullScreen", !cm.getOption("fullScreen"));
                    },
                    "Esc": function (cm) {
                        if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
                    }
                }
            });

            codeMirror.setSize('100%', 500);
            codemirrors_instances.push(codeMirror);
        }
    }
});
