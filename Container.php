<?php

namespace Bangpound\Pimple;

use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Api\EventListenerProviderInterface;
use Silex\CallbackResolver;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Container extends \Pimple\Container
{
    protected $providers = array();
    protected $booted = false;

    public function __construct(array $values = array())
    {
        $values['dispatcher_class'] = 'Symfony\\Component\\EventDispatcher\\EventDispatcher';
        $values['dispatcher'] = function ($c) {
            $dispatcher = new $c['dispatcher_class'()]();

            return $dispatcher;
        };
        $values['callback_resolver'] = function ($c) {
            return new CallbackResolver($c);
        };
        parent::__construct($values);
    }

    /**
     * Registers a service provider.
     *
     * @param ServiceProviderInterface $provider A ServiceProviderInterface instance
     * @param array                    $values   An array of values that customizes the provider
     *
     * @return Container
     */
    public function register(ServiceProviderInterface $provider, array $values = array())
    {
        $this->providers[] = $provider;

        parent::register($provider, $values);

        return $this;
    }

    /**
     * Boots all service providers.
     *
     * This method is automatically called by handle(), but you can use it
     * to boot all service providers when not handling a request.
     */
    public function boot()
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;

        foreach ($this->providers as $provider) {
            if ($provider instanceof EventListenerProviderInterface) {
                $provider->subscribe($this, $this['dispatcher']);
            }

            if ($provider instanceof BootableProviderInterface) {
                $provider->boot($this);
            }
        }
    }

    /**
     * Adds an event listener that listens on the specified events.
     *
     * @param string   $eventName The event to listen on
     * @param callable $callback  The listener
     * @param int      $priority  The higher this value, the earlier an event
     *                            listener will be triggered in the chain (defaults to 0)
     */
    public function on($eventName, $callback, $priority = 0)
    {
        if ($this->booted) {
            $this['dispatcher']->addListener($eventName, $this['callback_resolver']->resolveCallback($callback), $priority);

            return;
        }

        $this->extend('dispatcher', function (EventDispatcherInterface $dispatcher, $c) use ($callback, $priority, $eventName) {
            $dispatcher->addListener($eventName, $c['callback_resolver']->resolveCallback($callback), $priority);

            return $dispatcher;
        });
    }
}

class_alias('Bangpound\\Pimple\\Container', 'Silex\\Application');
