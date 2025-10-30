# Authorization

Access control and authorization for Eligify features.

## Overview

Implement robust authorization to control who can create criteria, evaluate eligibility, and access audit logs.

## Basic Authorization

### Protect UI Routes

```php
// config/eligify.php
return [
    'ui' => [
        'enabled' => true,
        'middleware' => ['web', 'auth', 'can:manage-eligibility'],
        'prefix' => 'eligify',
    ],
];
```

### Custom Middleware

```php
// app/Http/Middleware/EligifyAuthorization.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EligifyAuthorization
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            abort(401, 'Unauthenticated');
        }

        if (!auth()->user()->can('access-eligify')) {
            abort(403, 'Unauthorized to access Eligify');
        }

        return $next($request);
    }
}
```

## Gates and Policies

### Define Gates

```php
// app/Providers/AuthServiceProvider.php
use Illuminate\Support\Facades\Gate;
use CleaniqueCoders\Eligify\Models\Criteria;

public function boot(): void
{
    // Manage eligibility system
    Gate::define('manage-eligibility', function ($user) {
        return $user->hasRole('admin') || $user->hasRole('eligibility-manager');
    });

    // Create criteria
    Gate::define('create-criteria', function ($user) {
        return $user->can('manage-eligibility');
    });

    // Update criteria
    Gate::define('update-criteria', function ($user, Criteria $criteria) {
        return $user->can('manage-eligibility') && $criteria->is_active;
    });

    // Delete criteria
    Gate::define('delete-criteria', function ($user, Criteria $criteria) {
        return $user->hasRole('admin');
    });

    // Evaluate eligibility
    Gate::define('evaluate-eligibility', function ($user) {
        return $user->hasRole('admin')
            || $user->hasRole('eligibility-manager')
            || $user->hasRole('evaluator');
    });

    // View audit logs
    Gate::define('view-audits', function ($user) {
        return $user->can('manage-eligibility');
    });

    // View specific entity's audits
    Gate::define('view-entity-audits', function ($user, $entity) {
        // Users can view their own audits
        if ($user->id === $entity->id) {
            return true;
        }

        return $user->can('view-audits');
    });
}
```

### Use Gates in Controllers

```php
namespace App\Http\Controllers;

use CleaniqueCoders\Eligify\Facades\Eligify;
use CleaniqueCoders\Eligify\Models\Criteria;
use Illuminate\Http\Request;

class EligibilityController extends Controller
{
    public function evaluate(Request $request)
    {
        $this->authorize('evaluate-eligibility');

        $validated = $request->validate([
            'criteria' => 'required|string',
            'entity_type' => 'required|string',
            'entity_id' => 'required|integer',
        ]);

        $entity = $validated['entity_type']::findOrFail($validated['entity_id']);

        $result = Eligify::criteria($validated['criteria'])
            ->loadFromDatabase()
            ->evaluate($entity);

        return response()->json($result);
    }

    public function createCriteria(Request $request)
    {
        $this->authorize('create-criteria');

        $criteria = Criteria::create($request->validated());

        return response()->json($criteria, 201);
    }

    public function updateCriteria(Request $request, Criteria $criteria)
    {
        $this->authorize('update-criteria', $criteria);

        $criteria->update($request->validated());

        return response()->json($criteria);
    }

    public function deleteCriteria(Criteria $criteria)
    {
        $this->authorize('delete-criteria', $criteria);

        $criteria->delete();

        return response()->json(null, 204);
    }
}
```

### Create Policies

```php
// app/Policies/CriteriaPolicy.php
namespace App\Policies;

use App\Models\User;
use CleaniqueCoders\Eligify\Models\Criteria;

class CriteriaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view-criteria');
    }

    public function view(User $user, Criteria $criteria): bool
    {
        return $user->hasPermission('view-criteria');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('create-criteria');
    }

    public function update(User $user, Criteria $criteria): bool
    {
        // Only admins can update inactive criteria
        if (!$criteria->is_active) {
            return $user->hasRole('admin');
        }

        return $user->hasPermission('update-criteria');
    }

    public function delete(User $user, Criteria $criteria): bool
    {
        // Can't delete active criteria
        if ($criteria->is_active) {
            return false;
        }

        return $user->hasRole('admin');
    }

    public function evaluate(User $user, Criteria $criteria): bool
    {
        // Only evaluate active criteria
        if (!$criteria->is_active) {
            return false;
        }

        return $user->hasPermission('evaluate-eligibility');
    }
}
```

### Register Policies

```php
// app/Providers/AuthServiceProvider.php
use CleaniqueCoders\Eligify\Models\Criteria;
use App\Policies\CriteriaPolicy;

protected $policies = [
    Criteria::class => CriteriaPolicy::class,
];
```

## Role-Based Access Control (RBAC)

### Define Roles

```php
// database/seeders/RoleSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'manage-eligibility',
            'create-criteria',
            'update-criteria',
            'delete-criteria',
            'evaluate-eligibility',
            'view-audits',
            'view-all-audits',
            'export-audits',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Admin role (all permissions)
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        // Eligibility Manager
        $manager = Role::create(['name' => 'eligibility-manager']);
        $manager->givePermissionTo([
            'manage-eligibility',
            'create-criteria',
            'update-criteria',
            'evaluate-eligibility',
            'view-audits',
        ]);

        // Evaluator (can only evaluate)
        $evaluator = Role::create(['name' => 'evaluator']);
        $evaluator->givePermissionTo([
            'evaluate-eligibility',
        ]);

        // Auditor (read-only)
        $auditor = Role::create(['name' => 'auditor']);
        $auditor->givePermissionTo([
            'view-audits',
            'view-all-audits',
            'export-audits',
        ]);
    }
}
```

### Check Permissions

```php
use Illuminate\Support\Facades\Gate;

// Check if user has permission
if (auth()->user()->can('create-criteria')) {
    // Allow
}

// Check if user has role
if (auth()->user()->hasRole('admin')) {
    // Allow
}

// Check if user has any role
if (auth()->user()->hasAnyRole(['admin', 'eligibility-manager'])) {
    // Allow
}

// Check if user has all roles
if (auth()->user()->hasAllRoles(['admin', 'eligibility-manager'])) {
    // Allow
}
```

## API Authorization

### Sanctum Token Abilities

```php
// app/Http/Controllers/AuthController.php
use Laravel\Sanctum\HasApiTokens;

public function login(Request $request)
{
    // Authenticate user...

    $abilities = [];

    if ($user->hasRole('admin')) {
        $abilities = ['*']; // All abilities
    } elseif ($user->hasRole('eligibility-manager')) {
        $abilities = ['evaluate', 'create-criteria', 'update-criteria', 'view-audits'];
    } elseif ($user->hasRole('evaluator')) {
        $abilities = ['evaluate'];
    }

    $token = $user->createToken('eligify-token', $abilities)->plainTextToken;

    return response()->json(['token' => $token]);
}
```

### Check Token Abilities

```php
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'ability:evaluate'])->post('/evaluate', function () {
    // User has 'evaluate' ability
});

Route::middleware(['auth:sanctum', 'abilities:create-criteria,update-criteria'])
    ->post('/criteria', function () {
        // User has both abilities
    });
```

## Request Authorization

### Form Requests with Authorization

```php
// app/Http/Requests/CreateCriteriaRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCriteriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create-criteria');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:eligify_criteria',
            'description' => 'nullable|string',
            'rules' => 'required|array|min:1',
            'rules.*.field' => 'required|string',
            'rules.*.operator' => 'required|string',
            'rules.*.value' => 'required',
            'rules.*.weight' => 'nullable|numeric|min:0|max:1',
            'scoring_method' => 'required|in:weighted,pass_fail,percentage',
            'is_active' => 'boolean',
        ];
    }
}
```

### Use in Controller

```php
public function store(CreateCriteriaRequest $request)
{
    // Authorization already handled by request
    $criteria = Criteria::create($request->validated());

    return response()->json($criteria, 201);
}
```

## Multi-Tenant Authorization

### Tenant Isolation

```php
use App\Models\Tenant;

// Middleware to set tenant context
class SetTenantContext
{
    public function handle($request, Closure $next)
    {
        $tenant = auth()->user()->tenant;

        app()->instance('tenant', $tenant);

        return $next($request);
    }
}

// Global scope for tenant isolation
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (app()->has('tenant')) {
            $builder->where('tenant_id', app('tenant')->id);
        }
    }
}

// Apply to Criteria model
class Criteria extends Model
{
    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
}
```

### Tenant-Specific Authorization

```php
Gate::define('access-tenant-criteria', function ($user, $criteria) {
    return $user->tenant_id === $criteria->tenant_id;
});

// In controller
public function view(Criteria $criteria)
{
    $this->authorize('access-tenant-criteria', $criteria);

    return response()->json($criteria);
}
```

## Field-Level Authorization

### Restrict Sensitive Fields

```php
class Criteria extends Model
{
    public function toArray()
    {
        $data = parent::toArray();

        // Hide sensitive fields for non-admins
        if (!auth()->user()->hasRole('admin')) {
            unset($data['internal_notes']);
            unset($data['created_by']);
        }

        return $data;
    }
}
```

## Audit Authorization

### Who Can View Audits

```php
use CleaniqueCoders\Eligify\Models\Audit;

// Users can only view their own evaluations
Route::get('/my-evaluations', function () {
    return Audit::where('entity_type', User::class)
        ->where('entity_id', auth()->id())
        ->get();
});

// Admins can view all
Route::middleware('can:view-all-audits')->get('/audits', function () {
    return Audit::with('user', 'entity')->paginate();
});
```

## Best Practices

### 1. Principle of Least Privilege

```php
// Give minimum required permissions
$user->givePermissionTo('evaluate-eligibility');

// Not
$user->assignRole('admin'); // Too broad
```

### 2. Check Authorization Early

```php
public function evaluate(Request $request)
{
    // Check authorization first
    $this->authorize('evaluate-eligibility');

    // Then process request
    $result = Eligify::criteria($request->criteria)
        ->evaluate($entity);

    return response()->json($result);
}
```

### 3. Use Policies for Complex Logic

```php
// Instead of complex Gate definitions
Gate::define('update-criteria', function ($user, $criteria) {
    if (!$criteria->is_active) {
        return $user->hasRole('admin');
    }

    if ($criteria->created_by === $user->id) {
        return true;
    }

    return $user->hasPermission('update-any-criteria');
});

// Use a Policy
class CriteriaPolicy
{
    public function update(User $user, Criteria $criteria): bool
    {
        // Clean, testable, reusable
    }
}
```

### 4. Log Authorization Failures

```php
Gate::after(function ($user, $ability, $result, $arguments) {
    if ($result === false) {
        Log::warning('Authorization failed', [
            'user_id' => $user->id,
            'ability' => $ability,
            'arguments' => $arguments,
        ]);
    }
});
```

### 5. Test Authorization

```php
test('only admins can delete criteria', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $evaluator = User::factory()->create();
    $evaluator->assignRole('evaluator');

    $criteria = Criteria::factory()->create();

    // Admin can delete
    $this->actingAs($admin);
    expect($admin->can('delete-criteria', $criteria))->toBeTrue();

    // Evaluator cannot
    $this->actingAs($evaluator);
    expect($evaluator->can('delete-criteria', $criteria))->toBeFalse();
});
```

## Related Documentation

- [Security Best Practices](best-practices.md)
- [Input Validation](input-validation.md)
- [Vulnerability Reporting](vulnerability-reporting.md)
