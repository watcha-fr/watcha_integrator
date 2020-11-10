<?php

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Watcha_Integrator\Extension;

class Synapse
{
	const LOGIN_ENDPOINT = '/_matrix/client/r0/login';
	const NOTIFICATION_ENDPOINT = '/_matrix/client/r0/watcha_room_nextcloud_activity';
	const SERVICE_ACCOUNT_NAME = 'watcha_service_account';
}