1.2.0 (2017-02-15)
------------------

- Fix a Doctrine UnitOfwork bug with extraUpdates, who are not removed when you add and remove your entity before calling flush()

1.1.1 (2017-01-17)
------------------

- Remove UnitOfWork::newInstance() parameter type, to fix PHP < 5.6.11 bug with private / protected

1.1.0 (2017-01-16)
------------------

- Add onNewEntityInstance event
- Fix UnitOfWork::dispatchOnCreateEntityOverrideLocalValues() PHPDoc

1.0.1 (2016-12-14)
------------------

- Fix setParentEntityStates() call

1.0.0 (2016-12-14)
------------------

- Create DoctrineEvents
