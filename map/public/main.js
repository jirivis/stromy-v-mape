"use strict";

document.addEventListener(
    "DOMContentLoaded",
    function () {

        TreeApp.initMap();
		TreeApp.initTableControl();
        TreeApp.initEditorPanel();
        TreeApp.enableSelectionClearing();
        TreeApp.loadTrees();

    }
);
