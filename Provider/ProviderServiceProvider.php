<?php

namespace Bangpound\Pimple\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

class ProviderServiceProvider implements ServiceProviderInterface
{
    private $providers;

    public function __construct(array $providers = array())
    {
        $this->providers = $providers;
    }

    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $pimple An Container instance
     */
    public function register(Container $pimple)
    {
        foreach ($this->providers as $className => $values) {
            $pimple->register(new $className(), $values);
        }
    }
}
