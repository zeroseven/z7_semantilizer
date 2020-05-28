<?php

namespace Zeroseven\Semantilizer\Models;

abstract class AbstractData
{

    /** @var array */
    protected $data = [];

    /** @var array */
    public const REQUIRED_FIELDS = [];

    public function __construct(array $data)
    {
        foreach (self::REQUIRED_FIELDS as $property) {
            if(!isset($data[$property])) {
                throw new \Exception(sprintf('Key "%s" is missing in data array', $property));
            }
        }

        $this->data = $data;
    }
}
