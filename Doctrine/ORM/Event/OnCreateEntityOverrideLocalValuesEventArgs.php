<?php

namespace steevanb\DoctrineEvents\Doctrine\ORM\Event;

use Doctrine\ORM\EntityManagerInterface;

class OnCreateEntityOverrideLocalValuesEventArgs extends AbstractOnCreateEntityEventArgs
{
    const EVENT_NAME = 'onCreateEntityOverrideLocalValues';

    /** @var bool */
    protected $overrideLocalValues;

    /**
     * @param EntityManagerInterface $em
     * @param string $className
     * @param array $data
     * @param array $hints
     * @param bool $overrideLocalValues
     */
    public function __construct(
        EntityManagerInterface $em,
        $className,
        array $data,
        array $hints,
        $overrideLocalValues
    ) {
        parent::__construct($em, $className, $data, $hints);
        $this->setOverrideLocalValues($overrideLocalValues);
    }

    /**
     * @param bool $override
     * @return $this
     */
    public function setOverrideLocalValues($override)
    {
        $this->overrideLocalValues = boolval($override);

        return $this;
    }

    /** @return bool */
    public function getOverrideLocalValues()
    {
        return $this->overrideLocalValues;
    }
}
