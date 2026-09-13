<?php

declare(strict_types=1);

namespace Splinter\Access;

/**
 * Contract for a single access rule.
 *
 * Implementations are pure checks — no side effects, and no exceptions
 * on access denial (that responsibility belongs to the caller, see
 * AccessGuard::authorize()). A rule may depend on anything relevant to
 * the check (current user, IP address, time of day, feature flags,
 * rate limits, request signatures, etc.) — this interface is neutral
 * to what is being checked.
 */
interface AccessRuleInterface
{
    /**
     * Evaluates the access condition.
     *
     * @param array<int|string, mixed> $arguments Rule-specific check parameters
     *        (a permission string, a resource ID, etc.)
     */
    public function handle(array $arguments = []): bool;
}