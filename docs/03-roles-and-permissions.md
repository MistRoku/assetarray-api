# Roles and Permissions

AssetArray API uses three roles:

| Role | Description |
|---|---|
| `super_admin` | Full system access; bypasses every policy via `before()` hooks |
| `branch_manager` | Manages branch operations, products, inventory, transfers, POs |
| `staff` | Limited operational access, such as submitting stock take counts |

## Deactivated Accounts

`is_active = false` blocks login (`AuthService`) and returns `403` on any
authenticated request (the `active` middleware). Tokens issued before
deactivation stop working — there is no grace period.

## Authorisation Strategy

Authorisation is enforced using Laravel Policies and Gates, plus two
role gates for cross-model concerns:

| Ability | Who | Used by |
|---|---|---|
| `manager-or-above` | Managers and super-admins | Reports, exports |
| `super-admin` | Super-admins only | Audit log listing |

Controllers should never manually check roles unless necessary. Instead, use:

```php
$this->authorize('create', Product::class);
```

or Form Request:

```php
public function authorize(): bool
{
    return $this->user()->can('create', Product::class);
}
```

Branch comparisons in policies cast both sides to `int` — strict `===`
against a string-hydrated id would fail closed and strand workflows.
