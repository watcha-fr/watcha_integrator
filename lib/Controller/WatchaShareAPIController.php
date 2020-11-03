<?php

declare(strict_types=1);

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

namespace OCA\Watcha_Integrator\Controller;

use OC\Watcha_Integrator\Extension\Synapse;
use OCA\Files_Sharing\Controller\ShareAPIController;
use OCP\App\IAppManager;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IServerContainer;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Share\IManager;

class WatchaShareAPIController extends ShareAPIController
{

    /**
     * Share20OCS constructor.
     *
     * @param string $appName
     * @param IRequest $request
     * @param IManager $shareManager
     * @param IGroupManager $groupManager
     * @param IUserManager $userManager
     * @param IRootFolder $rootFolder
     * @param IURLGenerator $urlGenerator
     * @param string $userId
     * @param IL10N $l10n
     * @param IConfig $config
     * @param IAppManager $appManager
     * @param IServerContainer $serverContainer
     * @var IConfig private $config;
     */

    public function __construct(
        string $appName,
        IRequest $request,
        IManager $shareManager,
        IGroupManager $groupManager,
        IUserManager $userManager,
        IRootFolder $rootFolder,
        IURLGenerator $urlGenerator,
        string $userId = null,
        IL10N $l10n,
        IConfig $config,
        IAppManager $appManager,
        IServerContainer $serverContainer
    ) {
        $this->userId = $userId;
        $this->config = $config;
        $this->l = $l10n;

        $requester = $request->getParam("requester");
        parent::__construct(
            $appName,
            $request,
            $shareManager,
            $groupManager,
            $userManager,
            $rootFolder,
            $urlGenerator,
            $requester,
            $l10n,
            $config,
            $appManager,
            $serverContainer
        );
    }

    /**
     * @NoAdminRequired
     * 
     * @param string $path
     * @param int $permissions
     * @param int $shareType
     * @param string $shareWith
     * 
     * @throws OCSForbiddenException
     */
    public function createWatchaShare(
        string $path = null,
        int $permissions = null,
        int $shareType = -1,
        string $shareWith = null
    ) {
        if ($this->userId !== Synapse::SERVICE_ACCOUNT_NAME) {
            throw new OCSForbiddenException($this->l->t('Only the Synapse account service can create a share.'));
        }

        $response = $this->createShare(
            $path,
            $permissions,
            $shareType,
            $shareWith,
        );

        return $response;
    }

    /**
	 * Delete a share
	 *
	 * @NoAdminRequired
	 *
	 * @param string $id
     * @throws OCSForbiddenException
	 */
    public function deleteWatchaShare(
        string $id
    ) {
        if ($this->userId !== Synapse::SERVICE_ACCOUNT_NAME) {
            throw new OCSForbiddenException($this->l->t('Only the Synapse account service can create a share.'));
        }

        $response = $this->deleteShare(
            $id
        );

        return $response;
    }

    /**
	 * The getShares function.
	 *
	 * @NoAdminRequired
	 *
	 * @param string $shared_with_me
	 * @param string $reshares
	 * @param string $subfiles
	 * @param string $path
     * @param string $include_tags
	 * @throws OCSForbiddenException
	 */
    public function getWatchaShares(
        string $shared_with_me = 'false',
        string $reshares = 'false',
        string $subfiles = 'false',
        string $path = '',
        string $include_tags = 'false'
    ){
        if ($this->userId !== Synapse::SERVICE_ACCOUNT_NAME) {
            throw new OCSForbiddenException($this->l->t('Only the Synapse account service can create a share.'));
        }

        $response = $this->getShares(
            $shared_with_me,
            $reshares,
            $subfiles,
            $path,
            $include_tags
        );

        return $response;
    }
}
