<?php

namespace App\Config;

use Exception;
use ReflectionClass;
use ReflectionParameter;

/**
 * Class Container
 * 
 * A lightweight PSR-11 compliant Dependency Injection Container with autowiring support.
 */
class Container {
    private static $instance = null;
    private $bindings = [];
    private $instances = [];

    private function __construct() {
        // Register default shared services
        $this->singleton(Database::class, function () {
            return Database::getInstance();
        });
        
        $this->singleton(\PDO::class, function () {
            return Database::getInstance()->getConnection();
        });
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Bind a resolver to the container.
     */
    public function bind(string $id, callable $concrete) {
        $this->bindings[$id] = $concrete;
    }

    /**
     * Bind a singleton resolver to the container.
     */
    public function singleton(string $id, callable $concrete) {
        $this->bindings[$id] = function () use ($concrete, $id) {
            if (!isset($this->instances[$id])) {
                $this->instances[$id] = $concrete($this);
            }
            return $this->instances[$id];
        };
    }

    /**
     * Resolve a service or class from the container.
     */
    public function get(string $id) {
        if (isset($this->bindings[$id])) {
            return $this->bindings[$id]($this);
        }

        return $this->autowire($id);
    }

    /**
     * Check if the service or class is bound.
     */
    public function has(string $id): bool {
        return isset($this->bindings[$id]) || class_exists($id);
    }

    /**
     * Autowires concrete classes by resolving constructor dependencies dynamically using Reflection.
     */
    private function autowire(string $id) {
        if (!class_exists($id)) {
            throw new Exception("Target class [$id] does not exist.");
        }

        $reflector = new ReflectionClass($id);

        if (!$reflector->isInstantiable()) {
            throw new Exception("Target class [$id] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $id();
        }

        $parameters = $constructor->getParameters();
        $dependencies = $this->resolveDependencies($parameters);

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Helper to resolve parameter dependencies.
     */
    private function resolveDependencies(array $parameters): array {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if ($type === null) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new Exception("Cannot resolve parameter [{$parameter->getName()}] without type hint.");
                }
            } else {
                $typeName = $type->getName();
                $dependencies[] = $this->get($typeName);
            }
        }

        return $dependencies;
    }
}
