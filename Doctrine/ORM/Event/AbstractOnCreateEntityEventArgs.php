<?php

namespace steevanb\DoctrineEvents\Doctrine\ORM\Event;

use Doctrine\Common\EventArgs;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractOnCreateEntityEventArgs extends EventArgs
{
    /** @var EntityManagerInterface */
    protected $em;

    /** @var string */
    protected $className;

    /** @var array */
    protected $data;

    /** @var array */
    protected $hints;

    /**
     * @param EntityManagerInterface $em
     * @param string $className
     * @param array $data
     * @param array $hints
     */
    public function __construct(
        EntityManagerInterface $em,
        $className,
        array $data,
        array $hints
    ) {
        $this->em = $em;
        $this->className = $className;
        $this->data = $data;
        $this->hints = $hints;
    }

    /** @return EntityManagerInterface */
    public function getEntityManager()
    {
        return $this->em;
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
