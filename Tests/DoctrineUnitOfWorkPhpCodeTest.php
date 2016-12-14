<?php

namespace steevanb\DoctrineEvents\Tests;

use Doctrine\ORM\UnitOfWork;

class DoctrineUnitOfWorkPhpCodeTest extends \PHPUnit_Framework_TestCase
{
    use AssertPhpCodeTrait;

    /**
     * Compare doctrine UnitOfWork::createEntity() code with code was copied into
     * steevanb\DoctrineEvents\Doctrine\ORM\UnitOfWork
     * Works for Doctrine 2.5.0, 2.5.1, 2.5.2, 2.5.3, 2.5.4, 2.5.5, and maybe next releases
     */
    public function testCreateEntityPhpCode()
    {
        $this->assertPhpCode(UnitOfWork::class, 'createEntity', 'DoctrineUnitOfWorkCreateEntityPhpCode.txt');
    }
}
