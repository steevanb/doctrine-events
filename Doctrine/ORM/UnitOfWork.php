<?php

namespace steevanb\DoctrineEvents\Doctrine\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\NotifyPropertyChanged;
use Doctrine\Common\Persistence\ObjectManagerAware;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\ORM\Query;
use Doctrine\ORM\UnitOfWork as DoctrineUnitOfWork;
use Doctrine\ORM\Utility\IdentifierFlattener;
use steevanb\DoctrineEvents\Behavior\ReflectionTrait;
use steevanb\DoctrineEvents\Doctrine\ORM\Event\OnCreateEntityOverrideLocalValuesEventArgs;

class UnitOfWork extends DoctrineUnitOfWork
{
    use ReflectionTrait;

    /** @var EntityManagerInterface */
    protected $em;

    /** @var IdentifierFlattener */
    protected $identifierFlattener;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em);

        $this->em = $em;
        $this->identifierFlattener = $this->getParentPrivatePropertyValue('identifierFlattener');
    }

    /**
     * Mostly copied from Doctrine\ORM\UnitOfWork, cause everything is on a single method
     * @param string $className
     * @param array $data
     * @param array $hints
     * @return object
     */
    public function createEntity($className, array $data, &$hints = array())
    {
        $class = $this->em->getClassMetadata($className);
        //$isReadOnly = isset($hints[Query::HINT_READ_ONLY]);

        $id = $this->identifierFlattener->flattenIdentifier($class, $data);
        $idHash = implode(' ', $id);

        $identityMap = $this->getParentPrivatePropertyValue('identityMap');
        if (isset($identityMap[$class->rootEntityName][$idHash])) {
            $entity = $identityMap[$class->rootEntityName][$idHash];
            $oid = spl_object_hash($entity);

            if (
                isset($hints[Query::HINT_REFRESH])
                && isset($hints[Query::HINT_REFRESH_ENTITY])
                && ($unmanagedProxy = $hints[Query::HINT_REFRESH_ENTITY]) !== $entity
                && $unmanagedProxy instanceof Proxy
                && $this->callParentPrivateMethod('isIdentifierEquals', $unmanagedProxy, $entity)
            ) {
                // DDC-1238 - we have a managed instance, but it isn't the provided one.
                // Therefore we clear its identifier. Also, we must re-fetch metadata since the
                // refreshed object may be anything

                foreach ($class->identifier as $fieldName) {
                    $class->reflFields[$fieldName]->setValue($unmanagedProxy, null);
                }

                return $unmanagedProxy;
            }

            if ($entity instanceof Proxy && ! $entity->__isInitialized()) {
                $entity->__setInitialized(true);

                $overrideLocalValues = true;

                if ($entity instanceof NotifyPropertyChanged) {
                    $entity->addPropertyChangedListener($this);
                }
            } else {
                $overrideLocalValues = isset($hints[Query::HINT_REFRESH]);

                // If only a specific entity is set to refresh, check that it's the one
                if (isset($hints[Query::HINT_REFRESH_ENTITY])) {
                    $overrideLocalValues = $hints[Query::HINT_REFRESH_ENTITY] === $entity;
                }
            }

            if ($overrideLocalValues) {
                // inject ObjectManager upon refresh.
                if ($entity instanceof ObjectManagerAware) {
                    $entity->injectObjectManager($this->em, $class);
                }

                $this->setParentOriginalEntityData($oid, $data);
            }
        } else {
            $entity = $this->newInstance($class);
            $oid    = spl_object_hash($entity);

            $this->setParentEntityIdentifiers($oid, $id);
            $this->setParentEntityStates($oid, static::STATE_MANAGED);
            $this->setParentOriginalEntityData($oid, $data);

            $this->setParentIdentityMap($class->rootEntityName, $idHash, $entity);

            if ($entity instanceof NotifyPropertyChanged) {
                $entity->addPropertyChangedListener($this);
            }

            $overrideLocalValues = true;
        }

        $eventArgs = $this->dispatchOnCreateEntityOverrideLocalValues($overrideLocalValues, $className, $data, $hints);

        if ($eventArgs->getOverrideLocalValues() === false) {
            d('ovveride false');
            return $entity;
        }

        foreach ($data as $field => $value) {
            if (isset($class->fieldMappings[$field])) {
                $class->reflFields[$field]->setValue($entity, $value);
            }
        }

        // Loading the entity right here, if its in the eager loading map get rid of it there.
        $this->unsetParentEagerLoadingEntities($class->rootEntityName, $idHash);

        $eagerLoadingEntities = $this->getParentPrivatePropertyValue('eagerLoadingEntities');
        if (isset($eagerLoadingEntities[$class->rootEntityName]) && ! $eagerLoadingEntities[$class->rootEntityName]) {
            $this->unsetParentEagerLoadingEntities($class->rootEntityName);
        }

        // Properly initialize any unfetched associations, if partial objects are not allowed.
        if (isset($hints[Query::HINT_FORCE_PARTIAL_LOAD])) {
            return $entity;
        }

        foreach ($class->associationMappings as $field => $assoc) {
            // Check if the association is not among the fetch-joined associations already.
            if (isset($hints['fetchAlias']) && isset($hints['fetched'][$hints['fetchAlias']][$field])) {
                continue;
            }

            $targetClass = $this->em->getClassMetadata($assoc['targetEntity']);

            switch (true) {
                case ($assoc['type'] & ClassMetadata::TO_ONE):
                    if ( ! $assoc['isOwningSide']) {

                        // use the given entity association
                        if (
                            isset($data[$field]) && is_object($data[$field])
                            && isset($this->getParentPrivatePropertyValue('entityStates')[spl_object_hash($data[$field])])
                        ) {

                            $this->setParentOriginalEntityDataField($oid, $field, $data[$field]);

                            $class->reflFields[$field]->setValue($entity, $data[$field]);
                            $targetClass->reflFields[$assoc['mappedBy']]->setValue($data[$field], $entity);

                            continue 2;
                        }

                        // Inverse side of x-to-one can never be lazy
                        $class
                            ->reflFields[$field]
                            ->setValue(
                                $entity,
                                $this->getEntityPersister($assoc['targetEntity'])->loadOneToOneEntity($assoc, $entity)
                            );

                        continue 2;
                    }

                    // use the entity association
                    if (
                        isset($data[$field]) && is_object($data[$field])
                        && isset($this->getParentPrivatePropertyValue('entityStates')[spl_object_hash($data[$field])])
                    ) {
                        $class->reflFields[$field]->setValue($entity, $data[$field]);
                        $this->setParentOriginalEntityDataField($oid, $field, $data[$field]);

                        continue;
                    }

                    $associatedId = array();

                    // TODO: Is this even computed right in all cases of composite keys?
                    foreach ($assoc['targetToSourceKeyColumns'] as $targetColumn => $srcColumn) {
                        $joinColumnValue = isset($data[$srcColumn]) ? $data[$srcColumn] : null;

                        if ($joinColumnValue !== null) {
                            if ($targetClass->containsForeignIdentifier) {
                                $associatedId[$targetClass->getFieldForColumn($targetColumn)] = $joinColumnValue;
                            } else {
                                $associatedId[$targetClass->fieldNames[$targetColumn]] = $joinColumnValue;
                            }
                        } elseif ($targetClass->containsForeignIdentifier
                            && in_array($targetClass->getFieldForColumn($targetColumn), $targetClass->identifier, true)
                        ) {
                            // the missing key is part of target's entity primary key
                            $associatedId = array();
                            break;
                        }
                    }

                    if ( ! $associatedId) {
                        // Foreign key is NULL
                        $class->reflFields[$field]->setValue($entity, null);
                        $this->setParentOriginalEntityDataField($oid, $field, null);

                        continue;
                    }

                    if ( ! isset($hints['fetchMode'][$class->name][$field])) {
                        $hints['fetchMode'][$class->name][$field] = $assoc['fetch'];
                    }

                    // Foreign key is set
                    // Check identity map first
                    // FIXME: Can break easily with composite keys if join column values are in
                    //        wrong order. The correct order is the one in ClassMetadata#identifier.
                    $relatedIdHash = implode(' ', $associatedId);

                    $identityMap = $this->getParentPrivatePropertyValue('identityMap');
                    switch (true) {
                        case (isset($identityMap[$targetClass->rootEntityName][$relatedIdHash])):
                            $newValue = $identityMap[$targetClass->rootEntityName][$relatedIdHash];

                            // If this is an uninitialized proxy, we are deferring eager loads,
                            // this association is marked as eager fetch, and its an uninitialized proxy (wtf!)
                            // then we can append this entity for eager loading!
                            if ($hints['fetchMode'][$class->name][$field] == ClassMetadata::FETCH_EAGER &&
                                isset($hints[self::HINT_DEFEREAGERLOAD]) &&
                                !$targetClass->isIdentifierComposite &&
                                $newValue instanceof Proxy &&
                                $newValue->__isInitialized__ === false) {

                                $this->setParentEagerLoadingEntities(
                                    $targetClass->rootEntityName,
                                    $relatedIdHash,
                                    current($associatedId)
                                );
                            }

                            break;

                        case ($targetClass->subClasses):
                            // If it might be a subtype, it can not be lazy. There isn't even
                            // a way to solve this with deferred eager loading, which means putting
                            // an entity with subclasses at a *-to-one location is really bad! (performance-wise)
                            $newValue = $this
                                ->getEntityPersister($assoc['targetEntity'])
                                ->loadOneToOneEntity($assoc, $entity, $associatedId);
                            break;

                        default:
                            switch (true) {
                                // We are negating the condition here. Other cases will assume it is valid!
                                case ($hints['fetchMode'][$class->name][$field] !== ClassMetadata::FETCH_EAGER):
                                    $newValue = $this
                                        ->em
                                        ->getProxyFactory()
                                        ->getProxy($assoc['targetEntity'], $associatedId);
                                    break;

                                // Deferred eager load only works for single identifier classes
                                case (isset($hints[self::HINT_DEFEREAGERLOAD]) && ! $targetClass->isIdentifierComposite):
                                    // TODO: Is there a faster approach?
                                    $this->setParentEagerLoadingEntities(
                                        $targetClass->rootEntityName,
                                        $relatedIdHash,
                                        current($associatedId)
                                    );

                                    $newValue = $this
                                        ->em
                                        ->getProxyFactory()
                                        ->getProxy($assoc['targetEntity'], $associatedId);
                                    break;

                                default:
                                    // TODO: This is very imperformant, ignore it?
                                    $newValue = $this->em->find($assoc['targetEntity'], $associatedId);
                                    break;
                            }

                            // PERF: Inlined & optimized code from UnitOfWork#registerManaged()
                            $newValueOid = spl_object_hash($newValue);
                            $this->setParentEntityIdentifiers($newValueOid, $associatedId);
                            $this->setParentIdentityMap($targetClass->rootEntityName, $relatedIdHash, $newValue);

                            if (
                                $newValue instanceof NotifyPropertyChanged &&
                                ( ! $newValue instanceof Proxy || $newValue->__isInitialized())
                            ) {
                                $newValue->addPropertyChangedListener($this);
                            }
                            $this->setParentEntityStates($oid, static::STATE_MANAGED);
                            // make sure that when an proxy is then finally loaded, $this->originalEntityData is set also!
                            break;
                    }

                    $this->setParentOriginalEntityDataField($oid, $field, $newValue);
                    $class->reflFields[$field]->setValue($entity, $newValue);

                    if ($assoc['inversedBy'] && $assoc['type'] & ClassMetadata::ONE_TO_ONE) {
                        $inverseAssoc = $targetClass->associationMappings[$assoc['inversedBy']];
                        $targetClass->reflFields[$inverseAssoc['fieldName']]->setValue($newValue, $entity);
                    }

                    break;

                default:
                    // Ignore if its a cached collection
                    if (
                        isset($hints[Query::HINT_CACHE_ENABLED])
                        && $class->getFieldValue($entity, $field) instanceof PersistentCollection
                    ) {
                        break;
                    }

                    // use the given collection
                    if (isset($data[$field]) && $data[$field] instanceof PersistentCollection) {

                        $data[$field]->setOwner($entity, $assoc);

                        $class->reflFields[$field]->setValue($entity, $data[$field]);
                        $this->setParentOriginalEntityDataField($oid, $field, $data[$field]);

                        break;
                    }

                    // Inject collection
                    $pColl = new PersistentCollection($this->em, $targetClass, new ArrayCollection());
                    $pColl->setOwner($entity, $assoc);
                    $pColl->setInitialized(false);

                    $reflField = $class->reflFields[$field];
                    $reflField->setValue($entity, $pColl);

                    if ($assoc['fetch'] == ClassMetadata::FETCH_EAGER) {
                        $this->loadCollection($pColl);
                        $pColl->takeSnapshot();
                    }

                    $this->setParentOriginalEntityDataField($oid, $field, $pColl);
                    break;
            }
        }

        if ($overrideLocalValues) {
            // defer invoking of postLoad event to hydration complete step
            $this->getParentPrivatePropertyValue('hydrationCompleteHandler')->deferPostLoadInvoking($class, $entity);
        }

        return $entity;
    }

    /**
     * @param string $name
     * @return mixed
     */
    protected function callParentPrivateMethod($name)
    {
        $reflectionMethod = new \ReflectionMethod(get_parent_class($this), $name);
        $reflectionMethod->setAccessible(true);
        $args = func_get_args();
        unset($args[0]);
        $args = array_values($args);
        $return = $reflectionMethod->invokeArgs($this, $args);
        $reflectionMethod->setAccessible(false);

        return $return;
    }

    /**
     * @param string $rootEntityName
     * @param string $idHash
     * @param object $entity
     * @return $this
     */
    protected function setParentIdentityMap($rootEntityName, $idHash, $entity)
    {
        $identityMap = $this->getParentPrivatePropertyValue('identityMap');
        $identityMap[$rootEntityName][$idHash] = $entity;
        $this->setParentPrivatePropertyValue('identityMap', $identityMap);

        return $this;
    }

    /**
     * @param string $oid
     * @param mixed $data
     * @return $this
     */
    protected function setParentOriginalEntityData($oid, $data)
    {
        $originalEntityData = $this->getParentPrivatePropertyValue('originalEntityData');
        $originalEntityData[$oid] = $data;
        $this->setParentPrivatePropertyValue('originalEntityData', $originalEntityData);

        return $this;
    }

    /**
     * @param string $oid
     * @param string $field
     * @param mixed $data
     * @return $this
     */
    protected function setParentOriginalEntityDataField($oid, $field, $data)
    {
        $originalEntityData = $this->getParentPrivatePropertyValue('originalEntityData');
        $originalEntityData[$oid][$field] = $data;
        $this->setParentPrivatePropertyValue('originalEntityData', $originalEntityData);

        return $this;
    }

    /**
     * @param string $oid
     * @param string $id
     * @return $this
     */
    protected function setParentEntityIdentifiers($oid, $id)
    {
        $entityIdentifiers = $this->getParentPrivatePropertyValue('entityIdentifiers');
        $entityIdentifiers[$oid] = $id;
        $this->setParentPrivatePropertyValue('entityIdentifiers', $entityIdentifiers);

        return $this;
    }

    /**
     * @param string $oid
     * @param int $state
     * @return $this
     */
    protected function setParentEntityStates($oid, $state)
    {
        $entityStates = $this->getParentPrivatePropertyValue('entityStates');
        $entityStates[$oid] = $state;
        $this->setParentPrivatePropertyValue('entityStates', $entityStates);

        return $this;
    }

    /**
     * @param string $className
     * @param string $idHash
     * @param string $id
     * @return $this
     */
    protected function setParentEagerLoadingEntities($className, $idHash, $id)
    {
        $eagerLoadingEntities = $this->getParentPrivatePropertyValue('eagerLoadingEntities');
        $eagerLoadingEntities[$className][$idHash] = current($id);
        $this->setParentPrivatePropertyValue('eagerLoadingEntities', $eagerLoadingEntities);

        return $this;
    }

    /**
     * @param string $className
     * @param string|null $idHash
     * @return $this
     */
    protected function unsetParentEagerLoadingEntities($className, $idHash = null)
    {
        $eagerLoadingEntities = $this->getParentPrivatePropertyValue('eagerLoadingEntities');
        if ($idHash === null) {
            unset($eagerLoadingEntities[$className]);
        } else {
            unset($eagerLoadingEntities[$className][$idHash]);
        }
        $this->setParentPrivatePropertyValue('eagerLoadingEntities', $eagerLoadingEntities);

        return $this;
    }

    /**
     * @param ClassMetadata $class
     * @return ObjectManagerAware|object
     */
    protected function newInstance($class)
    {
        return $this->callParentPrivateMethod('newInstance', $class);
    }

    /**
     * @param bool $override
     * @param string $className
     * @param array $data
     * @param array $hints
     * @return OnCreateEntityOverrideLocalValuesEventArgs
     */
    protected function dispatchOnCreateEntityOverrideLocalValues($override, $className, array $data, array $hints)
    {
        $eventArgs = new OnCreateEntityOverrideLocalValuesEventArgs(
            $this->em,
            $override,
            $className,
            $data,
            $hints
        );
        $this->em->getEventManager()->dispatchEvent(OnCreateEntityOverrideLocalValuesEventArgs::EVENT_NAME, $eventArgs);

        return $eventArgs;
    }
}
