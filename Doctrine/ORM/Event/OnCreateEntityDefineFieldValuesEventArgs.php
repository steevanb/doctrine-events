<?php

namespace steevanb\DoctrineEvents\Doctrine\ORM\Event;

use Doctrine\ORM\EntityManagerInterface;

class OnCreateEntityDefineFieldValuesEventArgs extends AbstractOnCreateEntityEventArgs
{
    const EVENT_NAME = 'onCreateEntityDefineFieldValues';

    /** @var object */
    protected $entity;

    /** @var string */
    protected $definedFieldValues = [];

    /**
     * @param EntityManagerInterface $em
     * @param string $className
     * @param array $data
     * @param array $hints
     * @param object $entity
     */
    public function __construct(
        EntityManagerInterface $em,
        $className,
        array $data,
        array $hints,
        $entity
    ) {
        parent::__construct($em, $className, $data, $hints);
        $this->entity = $entity;
    }

    /** @return object */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param string $field
     * @return $this
     */
    public function addDefinedFieldValue($field)
    {
        $this->definedFieldValues[$field] = true;

        return $this;
    }

    /** @return string[] */
    public function getDefinedFieldValues()
    {
        return array_keys($this->definedFieldValues);
    }

    /**
     * @param string $field
     * @return bool
     */
    public function isValueDefined($field)
    {
        return isset($this->definedFieldValues[$field]);
    }
}
