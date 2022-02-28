<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\ViewHelpers;

use Psr\Http\Message\RequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

class AbstractHeadlineViewHelper extends AbstractTagBasedViewHelper
{
    /** @var BackendUserAuthentication|null */
    protected $backendUser;

    /** @var array */
    protected $dataAttributes;

    public function __construct()
    {
        parent::__construct();

        $this->backendUser = $GLOBALS['BE_USER'] ?? null;
        $this->dataAttributes = [];
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        parent::registerUniversalTagAttributes();

        $this->registerArgument('content', 'string', 'Header content');
        $this->registerArgument('referenceId', 'string', 'Reference fot child and sibling viewhelpers');
    }

    protected function addSemantilizerData(array $data): void
    {
        $this->dataAttributes = array_merge($this->dataAttributes, $data);
    }

    protected function storeReference(string $referenceId, int $type): void
    {
        if (!is_array($GLOBALS['USER']['z7_semantilizer']['temporary_structure'] ?? null)) {
            $GLOBALS['USER']['z7_semantilizer']['temporary_structure'] = [];
        }

        $GLOBALS['USER']['z7_semantilizer']['temporary_structure'][$referenceId] = $type;
    }

    protected function getReference(string $referenceId): ?int
    {
        return $GLOBALS['USER']['z7_semantilizer']['temporary_structure'][$referenceId] ?? null;
    }

    protected function renderHeadline(int $type, string $referenceId = null): string
    {
        // Set content or abort if empty
        if ($content = trim((string)($this->arguments['content'] ?: $this->renderChildren()))) {
            $this->tag->setContent($content);
        } else {
            return '';
        }

        // Set header type (fallback to a "div" element)
        if (in_array($type, [1, 2, 3, 4, 5, 6], true)) {
            $this->tag->setTagName('h' . $type);
        } else {
            $this->tag->setTagName('div');
            $this->tag->addAttribute('role', 'heading');
        }

        // Checks if the user is logged in and the Semantilizer has accessed the page
        if ($this->backendUser && $GLOBALS['TYPO3_REQUEST'] instanceof RequestInterface && $GLOBALS['TYPO3_REQUEST']->getHeader('X-Semantilizer')) {

            // Store the reference for sibling and child viewHelpers
            if ($referenceId !== null || $referenceId = $this->arguments['referenceId']) {
                $this->addSemantilizerData(['referenceId' => $referenceId]);
                $this->storeReference($referenceId, $type);
            }

            // Add data attributes
            if (!empty($this->dataAttributes)) {
                $this->tag->addAttribute('data-semantilizer', json_encode($this->dataAttributes));
            }
        }

        // Ciao …
        return $this->tag->render();
    }
}
