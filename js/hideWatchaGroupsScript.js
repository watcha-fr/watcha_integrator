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

const watchaGroupPattern = "c4d96a06b758a7ed12f897690828e414_";

window.addEventListener("load", hideWatchaGroup);

function hideWatchaGroup() {
    if (document.body.id === "body-user") {
        // Case of sharing tab:

        observeBodyMutation();
    } else if (document.body.id === "body-settings") {
        // Case of user settings

        let memberOfGroups = document.getElementById("groups-groups");
        let groupList = document.getElementsByClassName("app-navigation-entry");

        // Remove Watcha group from personal informations page:
        if (memberOfGroups) {
            let groupNameList = memberOfGroups.innerText.split(", ");
            for (let groupName of groupNameList) {
                if (groupName.startsWith(watchaGroupPattern)) {
                    i = groupNameList.indexOf(groupName);
                    groupNameList.splice(i, 1);
                }
            }
            groupNameList = groupNameList.join(", ");
            memberOfGroups.innerText = groupNameList;
            memberOfGroups.style.fontWeight = "bold";
        }

        // Remove Watcha group from users page:
        if (groupList) {
            for (let entry of groupList) {
                let entryTitle = entry.getAttribute("title");

                if (entryTitle.startsWith(watchaGroupPattern)) {
                    entry.style.display = "none";
                }
            }
        }
    }
}

function observeBodyMutation() {
    var callback = function (mutationsList) {
        for (let mutation of mutationsList) {
            let addedNodes = mutation["addedNodes"];

            if (addedNodes.length <= 0) {
                continue;
            }
            let addedNode = addedNodes[0];

            if (addedNode["className"] === "multiselect__element") {
                if (addedNode.innerText.includes(watchaGroupPattern)) {
                    addedNode.style.display = "none";
                }
            }

            if (addedNode["className"] === "sharing-sharee-list") {
                let sharingList = addedNode.childNodes;

                for (let sharingEntry of sharingList) {
                    if (sharingEntry.innerText.includes(watchaGroupPattern)) {
                        sharingEntry.style.display = "none";
                    }
                }
            }
        }
    };

    var observer = new MutationObserver(callback);
    observer.observe(document.body, { childList: true, subtree: true });
}
