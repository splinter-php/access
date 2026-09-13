<?php

declare(strict_types=1);

namespace Splinter\Access;

use Psr\Container\ContainerInterface;
use Splinter\Contracts\ServiceProviderInterface;

/**
 * Registers AccessGuard in the DI container.
 *
 * $config is unused — AccessGuard has no configuration file dependency.
 * The parameter is kept only to satisfy ServiceProviderInterface::register().
 */
final class AccessServiceProvider implements ServiceProviderInterface
{
    public function register(array $config): array
    {
        return [
            AccessGuard::class => fn(ContainerInterface $c) => new AccessGuard(
                container: $c,
            ),
        ];
    }
}