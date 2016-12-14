<?php

namespace steevanb\DoctrineEvents\Doctrine\ORM;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager as DoctrineEntityManager;
use Doctrine\ORM\ORMException;
use steevanb\DoctrineEvents\Behavior\ReflectionTrait;

class EntityManager extends DoctrineEntityManager
{
    use ReflectionTrait;

    /**
     * Copied from Doctrine\ORM\EntityManager, cause return use new EntityManager() instead of new static()
     *
     * @param mixed $conn
     * @param Configuration $config
     * @param EventManager|null $eventManager
     * @return $this
     * @throws ORMException
     * @throws \InvalidArgumentException
     */
    public static function create($conn, Configuration $config, EventManager $eventManager = null)
    {
        if ( ! $config->getMetadataDriverImpl()) {
            throw ORMException::missingMappingDriverImpl();
        }

        switch (true) {
            case (is_array($conn)):
                $conn = \Doctrine\DBAL\DriverManager::getConnection(
                    $conn, $config, ($eventManager ?: new EventManager())
                );
                break;

            case ($conn instanceof Connection):
                if ($eventManager !== null && $conn->getEventManager() !== $eventManager) {
                    throw ORMException::mismatchedEventManager();
                }
                break;

            default:
                throw new \InvalidArgumentException("Invalid argument: " . $conn);
        }

        return new static($conn, $config, $conn->getEventManager());
    }

    /**
     * @param Connection $conn
     * @param Configuration $config
     * @param EventManager $eventManager
     */
    protected function __construct(Connection $conn, Configuration $config, EventManager $eventManager)
    {
        parent::__construct($conn, $config, $eventManager);

        $this->setParentPrivatePropertyValue('unitOfWork', new UnitOfWork($this));
    }
}
