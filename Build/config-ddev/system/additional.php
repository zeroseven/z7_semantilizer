<?php

declare(strict_types=1);

/**
 * DDEV: allow *.ddev.site hostnames (trustedHostsPattern).
 * Copied to .build/config/system/additional.php during ddev init.
 */
$GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] = '.*\\.ddev\\.site$';
