<?php
namespace Riemann;

class EventBuilderFactory
{
    public function create($service)
    {
        return new EventBuilder(
            $service,
            php_uname('n'),
            array('php')
        );
    }
}
