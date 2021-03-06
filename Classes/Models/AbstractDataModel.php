<?php

namespace Zeroseven\Semantilizer\Models;

use TYPO3\CMS\Core\Utility\MathUtility;

abstract class AbstractDataModel
{

    /** @var array */
    protected $data = [];

    /** @var array */
    public const REQUIRED_FIELDS = [];

    public function __construct(array $data)
    {
        foreach (self::REQUIRED_FIELDS as $property) {
            if (!isset($data[$property])) {
                throw new \Exception(sprintf('Key "%s" is missing in data array', $property));
            }
        }

        $this->data = $data;
    }

    public function getData(string $property): ?string
    {
        if (isset($this->data[$property])) {
            return trim((string)$this->data[$property]);
        }

        return null;
    }

    public function getInt(string $property): int
    {
        $value = $this->getData($property);

        // Check if the value is an integer
        if (MathUtility::canBeInterpretedAsInteger($value)) {
            return (int)$value;
        }

        throw new \Exception(sprintf('Property "%s" can not be interpreted as integer. Value: "%s"', $property, $value));
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
