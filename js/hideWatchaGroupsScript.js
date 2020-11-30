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

window.addEventListener("load", hideWatchaGroup);

function hideWatchaGroup() {
    // Update DOM elements which are already loaded:
    let groupsFromPersonalPage = document.getElementById("groups-groups");
    let groupsFromLeftTabOnUsersPage = document.getElementsByClassName(
        "app-navigation-entry"
    );

    if (groupsFromPersonalPage) {
        updateGroupsNames(groupsFromPersonalPage);
        groupsFromPersonalPage.style.fontWeight = "bold";
    }

    if (groupsFromLeftTabOnUsersPage) {
        for (let group of groupsFromLeftTabOnUsersPage) {
            let groupName = group.getAttribute("title");

            if (groupName.startsWith(watchaGroupPrefix)) {
                group.style.display = "none";
            }
        }
    }

    // Update DOM elements which will be loaded after body mutations:
    observeBodyMutation();
}

function observeBodyMutation() {
    var callback = function (mutationsList) {
        for (let mutation of mutationsList) {
            let addedNodes = mutation["addedNodes"];

            if (addedNodes.length <= 0) {
                continue;
            }
            let addedNode = addedNodes[0];

            // On share tab:
            if (document.getElementById("sharing")) {
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
                        if (
                            sharingEntry.innerText.includes(watchaGroupPattern)
                        ) {
                            sharingEntry.style.display = "none";
                        }
                    }
                }
            }

            // On users page:
            if (
                addedNode.parentElement &&
                addedNode.parentElement["className"] === "user-list-grid"
            ) {
                // Case of groups tags on user rows:
                if (addedNode["className"] === "row") {
                    let groupList = addedNode.getElementsByClassName(
                        "groups"
                    )[0];
                    updateGroupsNames(groupList);
                }

                // Case of groups tags on editable user rows:
                if (addedNode["className"] === "row row--editable") {
                    let tags = addedNode.getElementsByClassName(
                        "multiselect__tag"
                    );
                    for (let tag of tags) {
                        if (tag.innerText.includes(watchaGroupPattern)) {
                            tag.style.display = "none";
                        }
                    }

                    let subadminsFieldValues = addedNode.getElementsByClassName(
                        "multiselect__element"
                    );
                    for (let groupName of subadminsFieldValues) {
                        let group = groupName
                            .querySelector(".name-parts")
                            .getAttribute("title");
                        if (group.includes(watchaGroupPattern)) {
                            groupName.style.display = "none";
                        }
                    }
                }
            }
        }
    };

    var observer = new MutationObserver(callback);
    observer.observe(document.body, { childList: true, subtree: true });
}

function updateGroupsNames(groupsNamesSpan) {
    let groupsNames = groupsNamesSpan.innerText.split(", ");
    for (let groupName of groupsNames) {
        if (groupName.includes(watchaGroupPattern)) {
            i = groupsNames.indexOf(groupName);
            groupsNames.splice(i, 1);
        }
    }
    groupsNamesSpan.innerText = groupsNames.join(", ");
}
