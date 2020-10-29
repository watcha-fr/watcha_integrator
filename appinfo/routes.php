<?php

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

return [
	'ocs' => [
		[
			'name' => 'WatchaShareAPI#createWatchaShare',
			'url'  => '/api/v1/shares',
			'verb' => 'POST',
		],
		[
			'name' => 'WatchaShareAPI#getWatchaShares',
			'url'  => '/api/v1/shares',
			'verb' => 'GET',
		],
		[
			'name' => 'WatchaShareAPI#deleteWatchaShare',
			'url'  => '/api/v1/shares/{id}',
			'verb' => 'DELETE',
		],
	],
];
