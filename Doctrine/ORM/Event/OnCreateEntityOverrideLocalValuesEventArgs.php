<?php

namespace steevanb\DoctrineEvents\Doctrine\ORM\Event;

use Doctrine\Common\EventArgs;
use Doctrine\ORM\EntityManagerInterface;

class OnCreateEntityOverrideLocalValuesEventArgs extends EventArgs
{
    const EVENT_NAME = 'onCreateEntityOvverideLocalValues';

    /** @var EntityManagerInterface */
    protected $em;

    /** @var bool */
    protected $overrideLocalValues;

    /** @var string */
    protected $className;

    /** @var array */
    protected $data;

    /** @var array */
    protected $hints;

    /**
     * @param EntityManagerInterface $em
     * @param bool $overrideLocalValues
     * @param string $className
     * @param array $data
     * @param array $hints
     */
    public function __construct(
        EntityManagerInterface $em,
        $overrideLocalValues,
        $className,
        array $data,
        array $hints = []
    ) {
        $this->em = $em;
        $this->setOverrideLocalValues($overrideLocalValues);
        $this->className = $className;
        $this->data = $data;
        $this->hints = $hints;
    }

    /** @return EntityManagerInterface */
    public function getEntityManager()
    {
        return $this->em;
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

    /** @return string */
    public function getClassName()
    {
        return $this->className;
    }

    /** @return array */
    public function getData()
    {
        return $this->data;
    }

    /** @return array */
    public function getHints()
    {
        return $this->hints;
    }
}
