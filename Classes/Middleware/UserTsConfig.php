<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

class UserTsConfig extends AbstractMiddleware
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Disable the admin panel
        $this->isSemantilizerRequest($request, true)
        && ExtensionManagementUtility::isLoaded('adminpanel')
        && ExtensionManagementUtility::addUserTSConfig('admPanel.hide = 1');

        // Go your way …
        return $handler->handle($request);
    }
}
