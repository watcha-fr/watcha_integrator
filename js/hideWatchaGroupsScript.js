/**
 * @copyright Copyright (c) 2020, Watcha SAS
 *
 * @author Kevin ICOL <kevin@watcha.fr>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

// echo -n watcha | md5sum
const watchaGroupPrefix = "c4d96a06b758a7ed12f897690828e414_";

window.addEventListener("load", hideLoadedElementOnSettingsPage);
window.addEventListener("load", hideBodyMutation);

function hideBodyMutation() {
    var callback = function (mutationsList) {
        for (let mutation of mutationsList) {
            let addedNodes = mutation["addedNodes"];

            if (addedNodes.length <= 0) {
                continue;
            }
            let addedNode = addedNodes[0];

            if (document.getElementById("sharing")) {
                hideMutationsOnFilesPage(addedNode);
            }

            if (
                addedNode.parentElement &&
                addedNode.parentElement["className"] === "user-list-grid"
            ) {
                hideMutationsOnSettingsPage(addedNode);
            }
        }
    };

    var observer = new MutationObserver(callback);
    observer.observe(document.body, { childList: true, subtree: true });
}

function hideLoadedElementOnSettingsPage() {
    let groupsFromPersonnalInformationsTab = document.getElementById(
        "groups-groups"
    );
    let groupsFromUsersTab = document.getElementsByClassName(
        "app-navigation-entry"
    );

    if (groupsFromPersonnalInformationsTab) {
        updateGroupsNames(groupsFromPersonnalInformationsTab);
        groupsFromPersonnalInformationsTab.style.fontWeight = "bold";
    }

    if (groupsFromUsersTab) {
        for (let group of groupsFromUsersTab) {
            let groupName = group.getAttribute("title");

            if (groupName.startsWith(watchaGroupPrefix)) {
                group.style.display = "none";
            }
        }
    }
}

function hideMutationsOnFilesPage(addedNode) {
    // Case of group search on file:
    if (addedNode["className"] === "multiselect__element") {
        if (addedNode.innerText.includes(watchaGroupPrefix)) {
            addedNode.style.display = "none";
        }
    }

    // Case of shares list on file:
    if (addedNode["className"] === "sharing-sharee-list") {
        let sharingList = addedNode.childNodes;

        for (let sharingEntry of sharingList) {
            if (sharingEntry.innerText.includes(watchaGroupPrefix)) {
                sharingEntry.style.display = "none";
            }
        }
    }
}

function hideMutationsOnSettingsPage(addedNode) {
    // Case of groups tags on user rows:
    if (addedNode["className"] === "row") {
        let groupList = addedNode.querySelector(".groups");
        updateGroupsNames(groupList);

        let subAdminsGroupsList = addedNode.querySelector(".subAdminsGroups");
        updateGroupsNames(subAdminsGroupsList);
    }

    // Case of groups tags on editable user rows:
    if (addedNode["className"] === "row row--editable") {
        let grouTags = addedNode.getElementsByClassName("multiselect__tag");
        for (let groupTag of grouTags) {
            if (groupTag.innerText.includes(watchaGroupPrefix)) {
                groupTag.style.display = "none";
            }
        }

        let subAdminsGroupTags = addedNode.getElementsByClassName(
            "multiselect__element"
        );
        for (let subAdminsGroupTag of subAdminsGroupTags) {
            let groupName = subAdminsGroupTag
                .querySelector(".name-parts")
                .getAttribute("title");
            if (groupName.includes(watchaGroupPrefix)) {
                subAdminsGroupTag.style.display = "none";
            }
        }
    }
}

function updateGroupsNames(groupsNamesSpan) {
    let groupsNames = groupsNamesSpan.innerText.split(", ");
    for (let groupName of groupsNames) {
        if (groupName.includes(watchaGroupPrefix)) {
            i = groupsNames.indexOf(groupName);
            groupsNames.splice(i, 1);
        }
    }
    groupsNamesSpan.innerText = groupsNames.join(", ");
}
