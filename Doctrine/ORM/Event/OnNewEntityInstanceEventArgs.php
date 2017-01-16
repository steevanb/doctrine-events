<?php

namespace steevanb\DoctrineEvents\Doctrine\ORM\Event;

use Doctrine\Common\EventArgs;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;

class OnNewEntityInstanceEventArgs extends EventArgs
{
    const EVENT_NAME = 'onNewEntityInstance';

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var ClassMetadata */
    protected $classMetadata;

    /** @var object */
    protected $entity;

    /**
     * @param EntityManagerInterface $entityManager
     * @param ClassMetadata $classMetadata
     * @param object $entity
     * @internal param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $entityManager, ClassMetadata $classMetadata, $entity)
    {
        $this->entityManager = $entityManager;
        $this->classMetadata = $classMetadata;
        $this->entity = $entity;
    }

    /** @return EntityManagerInterface */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /** @return ClassMetadata */
    public function getClassMetadata()
    {
        return $this->classMetadata;
    }

    /** @return object */
    public function getEntity()
    {
        return $this->entity;
    }
}
