<?php

/**
 * @copyright Copyright (c) 2020, Watcha SAS
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

$config = \OC::$server->getConfig();

if ($config->getSystemValue('enable_notifications', false)) {
    \OCP\Util::connectHook('OC_Filesystem', 'post_create', '\OCA\Watcha_Integrator\FilesHooksStatic', 'fileCreate');
    \OCP\Util::connectHook('OC_Filesystem', 'post_update', '\OCA\Watcha_Integrator\FilesHooksStatic', 'fileUpdate');
    \OCP\Util::connectHook('OC_Filesystem', 'delete', '\OCA\Watcha_Integrator\FilesHooksStatic', 'fileDelete');
    \OCP\Util::connectHook('OC_Filesystem', 'rename', '\OCA\Watcha_Integrator\FilesHooksStatic', 'fileMove');
    \OCP\Util::connectHook('OC_Filesystem', 'post_rename', '\OCA\Watcha_Integrator\FilesHooksStatic', 'fileMovePost');
    \OCP\Util::connectHook('\OCA\Files_Trashbin\Trashbin', 'post_restore', '\OCA\Watcha_Integrator\FilesHooksStatic', 'fileRestore');
}
