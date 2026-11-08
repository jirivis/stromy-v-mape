"use strict";

window.TreeApp = {
    map: null,
    treeLayer: null,
    dataTable: null,

    markersById: {},
    rowsById: {},
    treesById: {},

    selectedTreeId: null,
    selectedMarker: null,

    editingTreeId: null
};

console.log("app.js načten");

TreeApp.getProjectRole = function () {

    return (
        window.TreeAppUser?.projectRole
        ?? "viewer"
    );
};


TreeApp.canEditTrees = function () {

    return [
        "owner",
        "admin",
        "editor"
    ].includes(
        TreeApp.getProjectRole()
    );
};


TreeApp.canManageProject = function () {

    return [
        "owner",
        "admin"
    ].includes(
        TreeApp.getProjectRole()
    );
};