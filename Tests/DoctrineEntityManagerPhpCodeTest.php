<?php

namespace steevanb\DoctrineEvents\Tests;

use Doctrine\ORM\EntityManager;

class DoctrineUnitOfWorkTest extends \PHPUnit_Framework_TestCase
{
    use AssertPhpCodeTrait;

    /**
     * Compare doctrine EntityManager::create() code with code was copied into
     * steevanb\DoctrineEvents\Doctrine\ORM\EntityManager
     * Works for Doctrine 2.5.0, 2.5.1, 2.5.2, 2.5.3, 2.5.4, 2.5.5, and maybe next releases
     */
    public function testCreatePhpCode()
    {
        $this->assertPhpCode(EntityManager::class, 'create', 'DoctrineEntityManagerCreatePhpCode.txt');
    }

    /**
     * Compare doctrine EntityManager::__construct() code with code was copied into
     * steevanb\DoctrineEvents\Doctrine\ORM\EntityManager
     * Works for Doctrine 2.5.0, 2.5.1, 2.5.2, 2.5.3, 2.5.4, 2.5.5, and maybe next releases
     */
    public function testConstructPhpCode()
    {
        $this->assertPhpCode(EntityManager::class, '__construct', 'DoctrineEntityManagerConstructPhpCode.txt');
    }
}
