# CLI Commands

This section covers all command-line tools provided by Eligify.

## Overview

Eligify provides Artisan commands for:

- Managing criteria
- Generating code
- Cache management
- Testing and debugging
- Data import/export

## Documentation in this Section

- **[Commands Reference](commands.md)** - All available commands
- **[Playground CLI](playground-cli.md)** - Sample data generation
- **[Cache Management](cache-management.md)** - Cache commands

## Available Commands

### Criteria Management

```bash
# List all criteria
php artisan eligify:criteria:list

# Show criteria details
php artisan eligify:criteria:show loan_approval

# Create new criteria
php artisan eligify:criteria:create

# Delete criteria
php artisan eligify:criteria:delete loan_approval
```

### Code Generation

```bash
# Generate model mapper
php artisan eligify:make:mapper UserMapper

# Generate criteria class
php artisan eligify:make:criteria LoanApprovalCriteria
```

### Cache Commands

```bash
# Warm cache for specific criteria
php artisan eligify:cache:warm --criteria=loan_approval

# Clear all eligify caches
php artisan eligify:cache:clear

# Show cache statistics
php artisan eligify:cache:stats
```

### Playground & Testing

```bash
# Generate sample data
php artisan eligify:playground:generate --criteria=loan_approval

# Run evaluation test
php artisan eligify:evaluate loan_approval --user=123
```

### Data Management

```bash
# Export criteria definitions
php artisan eligify:export --criteria=loan_approval --format=json

# Import criteria
php artisan eligify:import criteria.json

# Audit log export
php artisan eligify:audit:export --from=2025-01-01 --to=2025-12-31
```

## Interactive Mode

Many commands support interactive mode:

```bash
php artisan eligify:criteria:create

# Interactive prompts:
# Criteria name: loan_approval
# Description: Loan approval eligibility
# Scoring method: weighted
# ...
```

## Related Sections

- [Configuration](../06-configuration/) - Command configuration
- [Testing](../09-testing/) - Testing commands
- [Deployment](../10-deployment/) - Production command usage
