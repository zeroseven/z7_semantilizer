<?php
defined('TYPO3_MODE') || die('Access denied.');

// Register hooks
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['drawHeaderHook'][$_EXTKEY] = \Zeroseven\Semantilizer\Hooks\DrawHeaderHook::class . '->render';
