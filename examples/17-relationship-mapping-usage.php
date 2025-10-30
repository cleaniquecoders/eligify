<?php

/**
 * Example 17: Using Relationships with Existing Model Mappings
 *
 * Demonstrates how to leverage existing model mappings when working with relationships.
 * This shows the three main patterns for handling related models.
 */

require_once __DIR__.'/bootstrap.php';

echo "=== Using Relationships with Existing Model Mappings ===\n\n";

// ============================================================================
// Pattern 1: Using Spread Operator to Include Related Model Fields
// ============================================================================

echo "Pattern 1: Spread Operator for Complete Relationship Data\n";
echo str_repeat('-', 70)."\n";

// Scenario: You have a User model with a Profile relationship
// Both User and Profile have their own mapping classes

/**
 * ProfileModelMapping defines how to extract Profile data
 */
class ProfileModelMapping extends \CleaniqueCoders\Eligify\Mappings\AbstractModelMapping
{
    protected ?string $prefix = 'profile';

    protected array $fieldMappings = [
        'bio' => 'biography',
        'employment_status' => 'employed',
    ];

    protected array $computedFields = [];

    public function __construct()
    {
        $this->computedFields = [
            'profile.has_complete_bio' => fn ($model) => ! empty($model->bio) && strlen($model->bio) > 50,
        ];
    }

    public function getModelClass(): string
    {
        return 'App\Models\Profile';
    }

    public function getName(): string
    {
        return 'Profile';
    }

    public function getDescription(): string
    {
        return 'User profile information';
    }
}

/**
 * UserModelMapping uses the ProfileModelMapping for the relationship
 */
class UserModelMapping extends \CleaniqueCoders\Eligify\Mappings\AbstractModelMapping
{
    protected ?string $prefix = 'user';

    protected array $fieldMappings = [
        'email' => 'email_address',
    ];

    public function getModelClass(): string
    {
        return 'App\Models\User';
    }

    public function getName(): string
    {
        return 'User';
    }

    public function getDescription(): string
    {
        return 'User account data';
    }

    /**
     * Configure extractor to include Profile mapping
     */
    public function configure(\CleaniqueCoders\Eligify\Data\Extractor $extractor): \CleaniqueCoders\Eligify\Data\Extractor
    {
        // First apply own mappings
        $extractor = parent::configure($extractor);

        // Then add relationship mappings using existing ProfileModelMapping
        // ProfileModelMapping defines: 'bio' => 'biography', 'employment_status' => 'employed'
        // We reference those mapped field names here
        $extractor->setRelationshipMappings([
            'profile' => [
                'biography' => 'user_bio',        // Uses ProfileMapping's 'biography' field
                'employed' => 'is_employed',       // Uses ProfileMapping's 'employed' field
            ],
        ]);

        return $extractor;
    }
}

echo "UserModelMapping configured to use ProfileModelMapping\n";
echo "- User fields: user.email_address\n";
echo "- Profile fields (via relationship): user_bio, is_employed\n";
echo "- Profile computed: profile.has_complete_bio\n";
echo "\nHow it works:\n";
echo "1. ProfileModelMapping maps 'bio' -> 'biography'\n";
echo "2. UserModelMapping references 'biography' and remaps to 'user_bio'\n";
echo "3. Result: profile.bio -> biography -> user_bio\n\n";

// ============================================================================
// Pattern 1B: Using All Fields from Related Mapping (Spread Operator)
// ============================================================================

echo "\nPattern 1B: Include ALL Fields Using Spread Operator\n";
echo str_repeat('-', 70)."\n";

/**
 * Alternative UserModelMapping that includes ALL ProfileMapping fields
 */
class UserModelMappingWithSpread extends \CleaniqueCoders\Eligify\Mappings\AbstractModelMapping
{
    protected ?string $prefix = 'user';

    protected array $fieldMappings = [
        'email' => 'email_address',
    ];

    public function getModelClass(): string
    {
        return 'App\Models\User';
    }

    public function getName(): string
    {
        return 'User (with spread)';
    }

    public function getDescription(): string
    {
        return 'User with complete profile data';
    }

    /**
     * Configure extractor to include ALL fields from ProfileModelMapping
     */
    public function configure(\CleaniqueCoders\Eligify\Data\Extractor $extractor): \CleaniqueCoders\Eligify\Data\Extractor
    {
        $extractor = parent::configure($extractor);

        // Get ProfileMapping instance and use its field mappings directly
        $profileMapping = app(ProfileModelMapping::class);

        // Use ALL field mappings from ProfileMapping for the relationship
        // This automatically includes ALL fields: 'biography', 'employed', etc.
        $extractor->setRelationshipMappings([
            'profile' => $profileMapping->getFieldMappings(), // ← Dynamic! Gets all fields
        ]);

        return $extractor;
    }
}

echo "UserModelMappingWithSpread uses ProfileMapping dynamically\n";
echo "- Calls: \$profileMapping->getFieldMappings()\n";
echo "- Gets: ['bio' => 'biography', 'employment_status' => 'employed']\n";
echo "- Sets: \$extractor->setRelationshipMappings(['profile' => \$mappings])\n";
echo "- Result: ALL ProfileMapping fields automatically included!\n";
echo "- Benefit: When ProfileMapping adds/changes fields, this automatically follows\n\n";

// ============================================================================
// Pattern 2: Merging Complete Relationship Mapping
// ============================================================================

echo "\nPattern 2: Merge All Fields from Related Model Mapping\n";
echo str_repeat('-', 70)."\n";

/**
 * AddressModelMapping for address data
 */
class AddressModelMapping extends \CleaniqueCoders\Eligify\Mappings\AbstractModelMapping
{
    protected ?string $prefix = 'address';

    protected array $fieldMappings = [
        'street' => 'street_address',
        'city' => 'city_name',
        'postal_code' => 'zip_code',
    ];

    public function getModelClass(): string
    {
        return 'App\Models\Address';
    }

    public function getName(): string
    {
        return 'Address';
    }

    public function getDescription(): string
    {
        return 'Address information';
    }
}

/**
 * ApplicantModelMapping that fully merges AddressModelMapping
 */
class ApplicantModelMapping extends \CleaniqueCoders\Eligify\Mappings\AbstractModelMapping
{
    protected ?string $prefix = 'applicant';

    protected array $fieldMappings = [
        'full_name' => 'name',
        'income' => 'annual_income',
    ];

    public function getModelClass(): string
    {
        return 'App\Models\Applicant';
    }

    public function getName(): string
    {
        return 'Applicant';
    }

    public function getDescription(): string
    {
        return 'Loan applicant data';
    }

    /**
     * Configure extractor to merge address mapping completely
     */
    public function configure(\CleaniqueCoders\Eligify\Data\Extractor $extractor): \CleaniqueCoders\Eligify\Data\Extractor
    {
        $extractor = parent::configure($extractor);

        // Get AddressMapping and use its field mappings
        $addressMapping = app(AddressModelMapping::class);
        $addressFields = $addressMapping->getFieldMappings();

        // Remap AddressMapping fields with applicant prefix
        $remappedAddressFields = [];
        foreach ($addressFields as $original => $mapped) {
            $remappedAddressFields[$mapped] = 'applicant_'.$original;
        }

        // Merge address field mappings from AddressModelMapping
        $extractor->setRelationshipMappings([
            'address' => $remappedAddressFields, // Dynamic! Uses AddressMapping fields
        ]);

        return $extractor;
    }
}

echo "ApplicantModelMapping merges all AddressModelMapping fields dynamically\n";
echo "- Gets: \$addressMapping->getFieldMappings()\n";
echo "- Remaps each field with 'applicant_' prefix\n";
echo "- Result: applicant_street, applicant_city, applicant_zip\n";
echo "- All address mapping logic is reused dynamically!\n\n";

// ============================================================================
// Pattern 3: Using Relationship Data in Computed Fields
// ============================================================================

echo "\nPattern 3: Using Relationship Data in Computed Fields\n";
echo str_repeat('-', 70)."\n";

/**
 * CompanyModelMapping for company data
 */
class CompanyModelMapping extends \CleaniqueCoders\Eligify\Mappings\AbstractModelMapping
{
    protected ?string $prefix = 'company';

    protected array $fieldMappings = [
        'name' => 'company_name',
        'employee_count' => 'employees',
    ];

    public function getModelClass(): string
    {
        return 'App\Models\Company';
    }

    public function getName(): string
    {
        return 'Company';
    }

    public function getDescription(): string
    {
        return 'Company information';
    }
}

/**
 * EmployeeModelMapping with conditional company data
 */
class EmployeeModelMapping extends \CleaniqueCoders\Eligify\Mappings\AbstractModelMapping
{
    protected ?string $prefix = 'employee';

    protected array $fieldMappings = [
        'name' => 'employee_name',
        'position' => 'job_title',
    ];

    protected array $computedFields = [];

    public function __construct()
    {
        $this->computedFields = [
            // Use company mapping data in computed fields
            'works_at_large_company' => function ($model) {
                if (! $model->relationLoaded('company')) {
                    return false;
                }

                // We can reference company fields through the mapping
                return ($model->company->employee_count ?? 0) > 100;
            },
        ];
    }

    public function getModelClass(): string
    {
        return 'App\Models\Employee';
    }

    public function getName(): string
    {
        return 'Employee';
    }

    public function getDescription(): string
    {
        return 'Employee data';
    }

    public function configure(\CleaniqueCoders\Eligify\Data\Extractor $extractor): \CleaniqueCoders\Eligify\Data\Extractor
    {
        $extractor = parent::configure($extractor);

        // Get CompanyMapping and use its field mappings
        $companyMapping = app(CompanyModelMapping::class);
        $companyFields = $companyMapping->getFieldMappings();

        // Remap company fields with employer prefix
        // CompanyMapping has: 'name' => 'company_name', 'employee_count' => 'employees'
        $remappedCompanyFields = [];
        foreach ($companyFields as $original => $mapped) {
            // Remap to employer_ prefix
            if ($mapped === 'company_name') {
                $remappedCompanyFields[$mapped] = 'employer_name';
            } elseif ($mapped === 'employees') {
                $remappedCompanyFields[$mapped] = 'employer_size';
            } else {
                $remappedCompanyFields[$mapped] = 'employer_'.$original;
            }
        }

        // Include company fields using CompanyModelMapping field definitions
        $extractor->setRelationshipMappings([
            'company' => $remappedCompanyFields, // Dynamic! Uses CompanyMapping's fields
        ]);

        return $extractor;
    }
}

echo "EmployeeModelMapping includes CompanyModelMapping via relationship\n";
echo "- Gets: \$companyMapping->getFieldMappings()\n";
echo "- Remaps: 'company_name' -> 'employer_name', 'employees' -> 'employer_size'\n";
echo "- Employee fields: employee.employee_name, employee.job_title\n";
echo "- Company fields: employer_name, employer_size\n";
echo "- Computed field uses company data: works_at_large_company\n\n";

// ============================================================================
// Pattern 4: Nested Relationships (Multi-Level)
// ============================================================================

echo "\nPattern 4: Multi-Level Relationship Mapping\n";
echo str_repeat('-', 70)."\n";

// Scenario: Order -> Customer -> Address
// Each has its own mapping

class OrderModelMapping extends \CleaniqueCoders\Eligify\Mappings\AbstractModelMapping
{
    protected ?string $prefix = 'order';

    protected array $fieldMappings = [
        'total' => 'order_total',
        'status' => 'order_status',
    ];

    public function getModelClass(): string
    {
        return 'App\Models\Order';
    }

    public function getName(): string
    {
        return 'Order';
    }

    public function getDescription(): string
    {
        return 'Order information';
    }

    public function configure(\CleaniqueCoders\Eligify\Data\Extractor $extractor): \CleaniqueCoders\Eligify\Data\Extractor
    {
        $extractor = parent::configure($extractor);

        // Get UserMapping and AddressMapping
        $userMapping = app(UserModelMapping::class);
        $addressMapping = app(AddressModelMapping::class);

        // Include customer data using UserModelMapping
        // UserModelMapping already includes ProfileModelMapping
        $extractor->setRelationshipMappings([
            'customer' => [
                'email_address' => 'customer_email', // From UserMapping
            ],
            // Nested: customer's address using AddressMapping
            'customer.address' => [
                'street_address' => 'shipping_street', // From AddressMapping
                'city_name' => 'shipping_city',         // From AddressMapping
                'zip_code' => 'shipping_zip',           // From AddressMapping
            ],
        ]);

        return $extractor;
    }
}

echo "OrderModelMapping uses nested mappings\n";
echo "- References: UserMapping and AddressMapping\n";
echo "- Order fields: order.order_total, order.order_status\n";
echo "- Customer fields: customer_email (from UserMapping)\n";
echo "- Customer address: shipping_street, shipping_city, shipping_zip (from AddressMapping)\n";
echo "- Three levels of relationships, all using existing mappings!\n\n";

// ============================================================================
// Visual Flow: How Mappings Are Reused
// ============================================================================

echo "\nVisual Flow: How One Mapping Uses Another\n";
echo str_repeat('=', 70)."\n\n";

echo "Step-by-step breakdown:\n\n";

echo "1️⃣  ProfileModelMapping defines field mappings:\n";
echo "   protected array \$fieldMappings = [\n";
echo "       'bio' => 'biography',\n";
echo "       'employment_status' => 'employed',\n";
echo "   ];\n\n";

echo "2️⃣  UserModelMapping references those mapped fields:\n";
echo "   \$extractor->setRelationshipMappings([\n";
echo "       'profile' => [\n";
echo "           'biography' => 'user_bio',  // ← uses ProfileMapping's 'biography'\n";
echo "           'employed' => 'is_employed', // ← uses ProfileMapping's 'employed'\n";
echo "       ],\n";
echo "   ]);\n\n";

echo "3️⃣  Data transformation flow:\n";
echo "   Database: profile.bio\n";
echo "   ↓ ProfileMapping transforms to\n";
echo "   'biography'\n";
echo "   ↓ UserMapping transforms to\n";
echo "   'user_bio'\n";
echo "   ↓ Final result\n";
echo "   ['user_bio' => 'Software engineer with 5 years experience']\n\n";

echo "✅ Key Point: You reference the OUTPUT field names from the related mapping,\n";
echo "   not the database column names. This is how mappings are reused!\n\n";

// ============================================================================
// Usage Example
// ============================================================================

echo "\nPractical Usage Example\n";
echo str_repeat('=', 70)."\n";

echo <<<'USAGE'

// In your code, using configured mappings:

$userMapping = app(UserModelMapping::class);
$extractor = new Extractor(['include_relationships' => true]);
$extractor = $userMapping->configure($extractor);

$userData = $extractor->extract($user);

// Result includes:
// - user.email_address (from UserModelMapping)
// - user_bio (from ProfileModelMapping via relationship)
// - is_employed (from ProfileModelMapping via relationship)
// - profile.has_complete_bio (computed from ProfileModelMapping)

// Use in eligibility rules:
$result = Eligify::criteria('user_verification')
    ->addRule('user.email_address', 'not_null')
    ->addRule('is_employed', '=', true)
    ->addRule('profile.has_complete_bio', '=', true)
    ->evaluate($userData);

USAGE;

echo "\n\n";

// ============================================================================
// Key Takeaways
// ============================================================================

echo "Key Takeaways\n";
echo str_repeat('=', 70)."\n";
echo <<<'TAKEAWAYS'

1. **Reuse existing mappings**: Don't duplicate field mapping logic
   - Create a mapping class for each model
   - Reference other mappings in relationships

2. **Use relationship mappings**: Configure how related data should be extracted
   - setRelationshipMappings(['relation' => ['field' => 'alias']])
   - Nested relationships: 'customer.address'

3. **Prefix organization**: Each mapping has its own prefix
   - Prevents field name collisions
   - Makes rules more readable
   - Example: user.email vs applicant.email

4. **Computed fields can reference relationships**:
   - Access related model data in computed fields
   - Combine data from multiple sources
   - Complex calculations across relationships

5. **Commands generate proper relationship code**:
   - eligify:make-mapping detects existing mappings
   - Generates spread operator syntax automatically
   - Adds helpful comments about which mapping is used

TAKEAWAYS;

echo "\n\n✅ Relationship mapping patterns demonstrated!\n";
