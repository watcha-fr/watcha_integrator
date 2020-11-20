window.addEventListener("load", hideWatchaGroup);

window.addEventListener("unload", function () {
    console.log("WINDOW UNLOAD");
});

function hideWatchaGroup() {
    console.log("CONFIG", OCA.Files.App.getFilesConfig())
    if (document.body.id === "body-user") {
        observeBodyMutation();
    } else if (document.body.id === "body-settings") {
        // Cas des noms de groupe de la page personnelle :
        var groupList = document.getElementById("groups-groups");

        if (groupList) {
            groupList.setAttribute("hidden", true);
        }

        // Cas des entrée de groupe dans la page utilisateurs :
        groupEntries = document.getElementsByClassName("app-navigation-entry");

        for (entry of groupEntries) {
            entry.style.display = "none";
        }
    }
}

function observeBodyMutation() {
    var callback = function (mutationsList) {
        for (var mutation of mutationsList) {
            var addedNodes = mutation["addedNodes"];

            if (
                addedNodes.length > 0 &&
                (addedNodes[0]["className"] === "sharing-sharee-list" ||
                    addedNodes[0]["className"] === "multiselect_element")
            ) {
                addedNodes[0].setAttribute("hidden", true);
            }
        }
    };

    var observer = new MutationObserver(callback);
    observer.observe(document.body, { childList: true, subtree: true });
}
