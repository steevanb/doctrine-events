[![version](https://img.shields.io/badge/version-1.0.0-green.svg)](https://github.com/steevanb/doctrine-events/tree/1.0.0)
[![doctrine](https://img.shields.io/badge/doctrine/orm-^2.5.0-blue.svg)](http://www.doctrine-project.org)
[![php](https://img.shields.io/badge/php-^5.4.6 || ^7.0-blue.svg)](http://www.doctrine-project.org)
![Lines](https://img.shields.io/badge/code lines-XXXX-green.svg)
![Total Downloads](https://poser.pugx.org/steevanb/doctrine-events/downloads)
[![SensionLabsInsight](https://img.shields.io/badge/SensionLabsInsight-platinum-brightgreen.svg)](https://insight.sensiolabs.com/projects/c0ecb586-f4b3-472d-8202-e2e2a6a2f474/analyses/2)
[![Scrutinizer](https://scrutinizer-ci.com/g/steevanb/doctrine-events/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/steevanb/doctrine-events/)

doctrine-events
---------------

Add some events to Doctrine 2.5

onCreateEntityOverrideLocalValues
---------------------------------

Dispacted when UnitOfWork try to know if current entity must be filled with values retrieved from query,
or if this entity is already known and fields are already defined.

onCreateEntityDefineFieldValues
-------------------------------

Dispactched when UnitOfWork defined entity field values

Installation
------------

Add it to your composeR.json :
```yml
{
    "require": {
        "steevanb/doctrine-events": "^1.0",
    }
}

You have to use steevanb\DoctrineEvents\Doctrine\ORM\EntityManager instead of Doctrine\ORM\EntityManager

Internally, it will use steevanb\DoctrineEvents\Doctrine\ORM\UnitOfWork instead of Doctrine\ORM\UnitOfWork

If you are on Symfony2 or Symfony3 project, you can add it to your config :
```yml
# app/config.yml
parameters:
    doctrine.orm.entity_manager.class: steevanb\DoctrineEvents\Doctrine\ORM\EntityManager
```

Some lib who use it
-------------------

[https://github.com/steevanb/doctrine-entity-merger](steevanb/doctrine-entity-merger) : add MERGE_ENTITY hint
to define fields loaded with multiple queries, with PARTIAL, instead of returning first hydrated entity
