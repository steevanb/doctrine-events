<?php

namespace steevanb\DoctrineEvents\Behavior;

trait ReflectionTrait
{
    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    protected function setParentPrivatePropertyValue($name, $value)
    {
        $reflectionProperty = new \ReflectionProperty(get_parent_class($this), $name);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this, $value);
        $reflectionProperty->setAccessible(false);

        return $this;
    }

    /**
     * @param string $name
     * @return mixed
     */
    protected function getParentPrivatePropertyValue($name)
    {
        $reflectionProperty = new \ReflectionProperty(get_parent_class($this), $name);
        $reflectionProperty->setAccessible(true);
        $return = $reflectionProperty->getValue($this);
        $reflectionProperty->setAccessible(false);

        return $return;
    }
}
