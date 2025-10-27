<?php

use CleaniqueCoders\Eligify\Support\ModelDataExtractor;
use Illuminate\Database\Eloquent\Model;

class TestModelForExtraction extends Model
{
    protected $fillable = ['name', 'email', 'age', 'income', 'credit_score', 'status'];

    protected $casts = [
        'age' => 'integer',
        'income' => 'integer',
        'credit_score' => 'integer',
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function orders()
    {
        return $this->hasMany(TestOrder::class, 'user_id');
    }

    public function profile()
    {
        return $this->hasOne(TestProfile::class, 'user_id');
    }
}

class TestOrder extends Model
{
    protected $fillable = ['user_id', 'total', 'status'];

    protected $casts = [
        'total' => 'decimal:2',
        'created_at' => 'datetime',
    ];
}

class TestProfile extends Model
{
    protected $fillable = ['user_id', 'bio', 'preferences'];

    protected $casts = [
        'preferences' => 'array',
    ];
}

test('model data extractor extracts basic attributes', function () {
    $model = new TestModelForExtraction([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'age' => 30,
        'income' => 50000,
        'credit_score' => 750,
        'status' => 'active',
    ]);

    $extractor = new ModelDataExtractor;
    $data = $extractor->extract($model);

    expect($data)->toHaveKey('name');
    expect($data)->toHaveKey('email');
    expect($data)->toHaveKey('age');
    expect($data)->toHaveKey('income');
    expect($data)->toHaveKey('credit_score');
    expect($data)->toHaveKey('status');

    expect($data['name'])->toBe('John Doe');
    expect($data['age'])->toBe(30);
});

test('model data extractor includes computed timestamp fields', function () {
    $model = new TestModelForExtraction([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    // Set timestamps manually for testing
    $model->created_at = now()->subDays(30);
    $model->updated_at = now()->subDays(5);

    $extractor = new ModelDataExtractor;
    $data = $extractor->extract($model);

    expect($data)->toHaveKey('created_days_ago');
    expect($data)->toHaveKey('updated_days_ago');
    expect($data)->toHaveKey('account_age_days');
    expect($data)->toHaveKey('last_activity_days');

    expect($data['created_days_ago'])->toBe(30);
    expect($data['updated_days_ago'])->toBe(5);
    expect($data['account_age_days'])->toBe(30); // Alias for created_days_ago
});

test('model data extractor excludes sensitive fields', function () {
    $model = new TestModelForExtraction([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'secret123',
        'remember_token' => 'token123',
        'api_token' => 'api123',
    ]);

    $extractor = new ModelDataExtractor;
    $data = $extractor->extract($model);

    expect($data)->toHaveKey('name');
    expect($data)->toHaveKey('email');
    expect($data)->not()->toHaveKey('password');
    expect($data)->not()->toHaveKey('remember_token');
    expect($data)->not()->toHaveKey('api_token');
});

test('model data extractor can apply custom field mappings', function () {
    $model = new TestModelForExtraction([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'age' => 30,
    ]);

    $extractor = new ModelDataExtractor;
    $extractor->setFieldMappings([
        'name' => 'full_name',
        'email' => 'email_address',
    ]);

    $data = $extractor->extract($model);

    expect($data)->toHaveKey('full_name');
    expect($data)->toHaveKey('email_address');
    expect($data)->not()->toHaveKey('name');
    expect($data)->not()->toHaveKey('email');
    expect($data['full_name'])->toBe('John Doe');
    expect($data['email_address'])->toBe('john@example.com');
});

test('model data extractor can apply custom computed fields', function () {
    $model = new TestModelForExtraction([
        'name' => 'John Doe',
        'age' => 30,
        'income' => 50000,
    ]);

    $extractor = new ModelDataExtractor;
    $extractor->setComputedFields([
        'income_category' => function ($model, $data) {
            return match (true) {
                $data['income'] >= 100000 => 'high',
                $data['income'] >= 50000 => 'medium',
                default => 'low'
            };
        },
        'is_adult' => function ($model, $data) {
            return $data['age'] >= 18;
        },
        'profile_completeness' => function ($model, $data) {
            $required = ['name', 'email', 'age'];
            $present = array_filter($required, fn ($field) => ! empty($data[$field]));

            return (count($present) / count($required)) * 100;
        },
    ]);

    $data = $extractor->extract($model);

    expect($data)->toHaveKey('income_category');
    expect($data)->toHaveKey('is_adult');
    expect($data)->toHaveKey('profile_completeness');

    expect($data['income_category'])->toBe('medium');
    expect($data['is_adult'])->toBeTrue();
    expect($data['profile_completeness'])->toBe(100.0);
});

test('model data extractor can be preconfigured for specific models', function () {
    $model = new TestModelForExtraction([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $extractor = ModelDataExtractor::forModel('App\Models\User');
    $data = $extractor->extract($model);

    // The forModel method should have configured User-specific fields
    expect($data)->toHaveKey('registration_date'); // Mapped from created_at
});

test('model data extractor handles configuration options', function () {
    $model = new TestModelForExtraction([
        'name' => 'John Doe',
        'password' => 'secret',
    ]);

    $model->created_at = now()->subDays(10);

    // Test with timestamps disabled
    $extractor = new ModelDataExtractor([
        'include_timestamps' => false,
        'include_computed_fields' => false,
        'exclude_sensitive_fields' => false,
    ]);

    $data = $extractor->extract($model);

    expect($data)->not()->toHaveKey('created_days_ago');
    expect($data)->not()->toHaveKey('account_age_days');
    expect($data)->toHaveKey('password'); // Sensitive fields not excluded
});

test('model data extractor handles relationship data extraction', function () {
    // Create a mock relationship scenario
    $model = new TestModelForExtraction([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    // Mock some relationship data
    $orders = collect([
        ['id' => 1, 'total' => 100.50, 'status' => 'completed'],
        ['id' => 2, 'total' => 250.00, 'status' => 'completed'],
        ['id' => 3, 'total' => 75.25, 'status' => 'pending'],
    ]);

    $profile = ['bio' => 'Software Developer', 'preferences' => ['email' => true]];

    // Manually set relations for testing
    $model->setRelation('orders', $orders);
    $model->setRelation('profile', $profile);

    $extractor = new ModelDataExtractor;
    $data = $extractor->extract($model);

    expect($data)->toHaveKey('orders_count');
    expect($data)->toHaveKey('orders_exists');
    expect($data)->toHaveKey('profile_exists');

    expect($data['orders_count'])->toBe(3);
    expect($data['orders_exists'])->toBeTrue();
    expect($data['profile_exists'])->toBeTrue();
});

test('model data extractor provides collection summaries for numeric fields', function () {
    $model = new TestModelForExtraction(['name' => 'John Doe']);

    // Mock orders with numeric fields
    $orders = collect([
        ['total' => 100.50, 'quantity' => 2],
        ['total' => 250.00, 'quantity' => 5],
        ['total' => 75.25, 'quantity' => 1],
    ]);

    $model->setRelation('orders', $orders);

    $extractor = new ModelDataExtractor;
    $data = $extractor->extract($model);

    expect($data)->toHaveKey('orders_total_sum');
    expect($data)->toHaveKey('orders_total_avg');
    expect($data)->toHaveKey('orders_total_max');
    expect($data)->toHaveKey('orders_total_min');
    expect($data)->toHaveKey('orders_quantity_sum');

    expect($data['orders_total_sum'])->toBe(425.75);
    expect($data['orders_total_max'])->toBe(250.00);
    expect($data['orders_total_min'])->toBe(75.25);
    expect($data['orders_quantity_sum'])->toBe(8);
});
