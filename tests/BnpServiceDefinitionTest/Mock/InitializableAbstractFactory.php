<?php

namespace BnpServiceDefinitionTest\Mock;

use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\Stdlib\InitializableInterface;

abstract class InitializableAbstractFactory implements
    InitializableInterface,
    AbstractFactoryInterface
{
}
