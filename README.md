# splinter/access

Access rule authorization for the [Splinter](https://github.com/splinter-php/framework) micro-framework.

This package provides a small, framework-agnostic mechanism for checking
access rules — permission checks, IP restrictions, rate limits, feature
flags, or anything else expressible as a pure boolean condition. It does
**not** handle authentication (who the current user is, sessions,
tokens, login) — that is the application's concern, or a separate
`splinter/auth` module.

## Non-goals

- No role/permission storage or management — that lives in your own
  `AccessRuleInterface` implementations.
- No legacy-style string-to-class rule mapping — rules are invoked
  directly by class name.
- No dependency on any specific user/authentication mechanism.
- No built-in composite rules (`AnyOfRule`/`AllOfRule`) or caching — see
  [Composing rules](#composing-rules) and [Performance](#performance)
  below for why, and how to add them yourself if you need them.

## Installation

```bash
composer require splinter/access
```

Add the provider to `config/app.php`:

```php
'providers' => [
    // ...
    \Splinter\Access\AccessServiceProvider::class,
],
```

No config files are published or required.

## Writing a rule

Implement `AccessRuleInterface` in your application:

```php
namespace App\Access\Rules;

use Splinter\Access\AccessRuleInterface;
use App\Application\Identity\Auth\Services\AuthService;
use DomainException;

final readonly class MemberAccessRule implements AccessRuleInterface
{
    public function __construct(
        private AuthService $authService,
    ) {
    }

    public function handle(array $arguments = []): bool
    {
        try {
            $currentUser = $this->authService->user();

            if (!$currentUser->memberId || empty($arguments)) {
                return false;
            }

            return $this->authService->can($arguments[0]);
        } catch (DomainException) {
            return false;
        }
    }
}
```

Rules are resolved via the DI container, so constructor dependencies are
autowired as usual — no separate registration needed if every dependency
is a typed class.

## Usage

Halt execution if access is denied (route, middleware, a `before()`
hook):

```php
$app->get(AccessGuard::class)->authorize(MemberAccessRule::class, ['library.manage']);
```

Check without halting — for conditional rendering or branching logic:

```php
$canManage = $app->get(AccessGuard::class)->can(MemberAccessRule::class, ['library.manage']);
```

`authorize()` throws a `DomainException` with code `403` if the rule
returns `false`. Both methods throw a `RuntimeException` if the resolved
class does not implement `AccessRuleInterface`.

## Composing rules

There's no `AnyOfRule`/`AllOfRule` built into this package. If you need
"pass if any of these rules pass" or "pass only if all of these do",
write it as a regular rule in your application:

```php
namespace App\Access\Rules;

use Psr\Container\ContainerInterface;
use Splinter\Access\AccessRuleInterface;

final readonly class AnyOfRule implements AccessRuleInterface
{
    /**
     * @param class-string<AccessRuleInterface>[] $rules
     */
    public function __construct(
        private ContainerInterface $container,
        private array $rules,
    ) {
    }

    public function handle(array $arguments = []): bool
    {
        foreach ($this->rules as $ruleClass) {
            if ($this->container->get($ruleClass)->handle($arguments)) {
                return true;
            }
        }

        return false;
    }
}
```

A single named rule (e.g. `CanManageLibraryRule`) is often clearer at
the call site than a generic composition, so prefer that where it fits.

## Performance

`AccessGuard::can()` does not cache results. Most rules are cheap (an IP
check, a rate-limit lookup) and some must never be cached (a rate-limit
rule, by definition). If a specific rule is genuinely expensive, memoize
it inside that rule's own implementation rather than at the guard level.

## Testing

`AccessGuard` takes a `ContainerInterface`, so it can be tested with a
mock container — no need to boot a real PHP-DI instance. Cases worth
covering:

- `authorize()` does not throw when the rule returns `true`.
- `authorize()` throws `DomainException` (code 403) when the rule
  returns `false`.
- `authorize()`/`can()` throw `RuntimeException` when the resolved class
  does not implement `AccessRuleInterface`.
- `can()` returns a `bool` with no side effects in either case.

## Requirements

- PHP ^8.2
- splinter/framework