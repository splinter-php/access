<?php

declare(strict_types=1);

namespace Splinter\Access;

use DomainException;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Resolves AccessRuleInterface implementations from the DI container and
 * evaluates them, either returning a boolean result or halting execution
 * with an exception on denial.
 */
final readonly class AccessGuard
{
    public function __construct(
        private ContainerInterface $container,
    ) {
    }

    /**
     * Evaluates the given rule and halts execution if access is denied.
     *
     * @param class-string<AccessRuleInterface> $ruleClass
     *
     * @throws DomainException (code 403) if the rule returned false
     * @throws RuntimeException if the resolved class does not implement AccessRuleInterface
     */
    public function authorize(string $ruleClass, array $arguments = []): void
    {
        if (!$this->can($ruleClass, $arguments)) {
            throw new DomainException('Access denied', 403);
        }
    }

    /**
     * Evaluates the given rule without side effects — for cases where the
     * calling code decides what to do with the result (conditional
     * rendering, branching logic), rather than the guard itself.
     *
     * @param class-string<AccessRuleInterface> $ruleClass
     *
     * @throws RuntimeException if the resolved class does not implement AccessRuleInterface
     */
    public function can(string $ruleClass, array $arguments = []): bool
    {
        $rule = $this->container->get($ruleClass);

        if (!$rule instanceof AccessRuleInterface) {
            throw new RuntimeException(sprintf(
                'Class [%s] must implement %s.',
                $ruleClass,
                AccessRuleInterface::class
            ));
        }

        return $rule->handle($arguments);
    }
}