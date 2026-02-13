$(document).ready(function () {
    if ($('#_gestor-interface-visualizar-dados').length > 0) {


        // ===== Codemirror 

        var codemirrors_instances = new Array();
        const codermirrorHeight = 800;

        var codemirror_json = document.getElementsByClassName("codemirror-json");

        if (codemirror_json.length > 0) {
            for (var i = 0; i < codemirror_json.length; i++) {
                var CodeMirrorJson = CodeMirror.fromTextArea(codemirror_json[i], {
                    lineNumbers: true,
                    lineWrapping: true,
                    styleActiveLine: true,
                    matchBrackets: true,
                    mode: "application/json",
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

                CodeMirrorJson.setSize('100%', codermirrorHeight);
                codemirrors_instances.push(CodeMirrorJson);
            }
        }

        var codemirror_json_info = document.getElementsByClassName("codemirror-json-info");

        if (codemirror_json_info.length > 0) {
            for (var i = 0; i < codemirror_json_info.length; i++) {
                var CodeMirrorJsonInfo = CodeMirror.fromTextArea(codemirror_json_info[i], {
                    lineNumbers: true,
                    lineWrapping: true,
                    styleActiveLine: true,
                    matchBrackets: true,
                    mode: "application/json",
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

                CodeMirrorJsonInfo.setSize('100%', codermirrorHeight);
                codemirrors_instances.push(CodeMirrorJsonInfo);
            }
        }

        // ===== Tabs Fomantic UI

        const tabIdContent = 'tabFormsActive';

        const tabActive = localStorage.getItem(gestor.moduloId + tabIdContent);

        if (tabActive !== null) {
            $('.menuForms .item').tab('change tab', tabActive);

            tabsHandler(tabActive);
        }

        $('.menuForms .item').tab({
            onLoad: function (tabPath, parameterArray, historyEvent) {
                tabsHandler(tabPath);

                localStorage.setItem(gestor.moduloId + tabIdContent, tabPath);
            }
        });

        function tabsHandler(tabPath) {
            switch (tabPath) {
                case 'data':
                    CodeMirrorJson.refresh();
                    break;
                case 'info':
                    CodeMirrorJsonInfo.refresh();
                    break;
            }
        }

        // ===== Accordion Fomantic UI

        $('.ui.accordion')
            .accordion()
            ;
    }
});
