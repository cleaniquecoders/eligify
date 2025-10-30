# UI Features

This document covers all the features available in the Eligify web dashboard.

## Overview

The Eligify UI provides a complete web-based interface for managing eligibility criteria, testing evaluations, and viewing audit logs.

## Accessing the Dashboard

### Default Route

```
http://your-app.test/eligify
```

### Custom Route

```php
// config/eligify.php
'ui' => [
    'route_prefix' => 'admin/eligify',  // http://your-app.test/admin/eligify
],
```

## Dashboard Home

The dashboard home page displays:

- **Overview Statistics**
  - Total criteria
  - Evaluations today
  - Pass rate
  - Active rules

- **Recent Evaluations**
  - Latest 10 evaluations
  - Pass/fail status
  - Scores
  - Quick actions

- **Quick Actions**
  - Create new criteria
  - Open playground
  - View audit logs
  - Generate reports

## Criteria Manager

### List View

Displays all criteria with:
- Name and description
- Rule count
- Scoring method
- Status (enabled/disabled)
- Actions (edit, delete, duplicate, test)

**Features:**
- Search by name
- Filter by status, scoring method, tags
- Sort by name, created date, rule count
- Bulk actions (enable, disable, delete)

### Create Criteria

Create new criteria through a form:

**Fields:**
- Name (required, unique)
- Description
- Scoring method (dropdown)
- Passing threshold
- Tags (multiple)
- Status (enabled/disabled)

**Rules Section:**
- Add rules one by one
- Specify field, operator, value, weight
- Reorder rules (drag and drop)
- Delete rules
- Preview rule logic

**Workflows Section:**
- Configure onPass callback
- Configure onFail callback
- Preview workflow code

### Edit Criteria

Modify existing criteria:
- Update basic information
- Add/remove/edit rules
- Change scoring method
- Adjust weights
- Update workflows

**Features:**
- Auto-save drafts
- Version history
- Validation feedback
- Preview changes

### Delete Criteria

Delete criteria with confirmation:
- Shows criteria dependencies
- Option to delete evaluation history
- Soft delete (can be restored)

### Duplicate Criteria

Create a copy of existing criteria:
- Auto-generates new name
- Copies all rules and settings
- Option to modify before saving

## Rule Builder

Visual rule builder interface:

### Rule Form

- **Field Name**: Text input with autocomplete
- **Operator**: Dropdown with all available operators
- **Value**: Dynamic input based on operator
  - Text input for equals, greater than, etc.
  - Multi-select for "in" operator
  - Date picker for date operators
  - Range inputs for "between" operator
- **Weight**: Number input (1-100)
- **Label**: Optional description

### Rule Preview

Shows how the rule will be evaluated:

```
IF income >= 3000 THEN pass (weight: 40)
```

### Validation

Real-time validation:
- Field name required
- Operator must be valid
- Value format validation
- Weight must be positive

## Playground

Interactive testing environment for criteria.

### Features

1. **Criteria Selection**
   - Dropdown to select criteria
   - Or create temporary criteria

2. **Test Data Input**
   - JSON editor
   - Sample data generator
   - Load from model

3. **Evaluation**
   - Run evaluation button
   - Real-time results
   - Score display
   - Rule-by-rule breakdown

4. **Results Display**
   - Pass/fail badge
   - Score meter
   - Passed rules (green)
   - Failed rules (red)
   - Detailed breakdown

5. **Debug Mode**
   - Execution time
   - Memory usage
   - Query count
   - Step-by-step trace

### Example Usage

1. Select "Loan Approval" criteria
2. Click "Generate Sample Data"
3. Edit JSON if needed:

```json
{
  "income": 5000,
  "credit_score": 750,
  "active_loans": 1,
  "employment_years": 5
}
```

4. Click "Evaluate"
5. View results:

```
✓ Passed (Score: 100/100)

✓ income >= 3000 (40 points)
✓ credit_score >= 650 (60 points)
✓ active_loans <= 2 (pass)
✓ employment_years >= 2 (pass)
```

## Audit Viewer

View evaluation history and audit logs.

### Features

1. **List View**
   - All evaluations
   - Pagination
   - Search and filters

2. **Filters**
   - By criteria
   - By date range
   - By result (passed/failed)
   - By subject type
   - By user

3. **Details View**
   - Criteria used
   - Subject information
   - Input data snapshot
   - Evaluation result
   - Execution metadata
   - Related evaluations

4. **Export**
   - Export to CSV
   - Export to JSON
   - Export to PDF
   - Date range selection

### Example View

```
Evaluation #12345
Criteria: Loan Approval
Subject: User #789
Date: 2025-10-30 14:30:25
Result: ✓ Passed (Score: 85/100)

Rules Evaluated:
✓ income >= 3000 (actual: 5000)
✓ credit_score >= 650 (actual: 720)
✗ employment_years >= 5 (actual: 3)

Input Data:
{
  "income": 5000,
  "credit_score": 720,
  "employment_years": 3
}

Evaluated by: Admin User
IP Address: 192.168.1.1
```

## Reports

Generate reports and analytics.

### Available Reports

1. **Evaluation Summary**
   - Total evaluations
   - Pass/fail breakdown
   - Average scores
   - Trends over time

2. **Criteria Performance**
   - Most used criteria
   - Pass rates by criteria
   - Average execution time
   - Rule effectiveness

3. **Subject Analysis**
   - Evaluations by subject type
   - Common failure points
   - Score distribution

4. **Compliance Report**
   - Audit trail completeness
   - Data retention status
   - Regulatory compliance

### Export Options

- PDF
- CSV
- Excel
- JSON

## Settings

Configure Eligify behavior.

### General Settings

- Default scoring method
- Default passing threshold
- Audit logging (on/off)
- Audit retention days

### Operators

- View available operators
- Enable/disable operators
- Add custom operators

### UI Settings

- Theme (light/dark)
- Items per page
- Default filters
- Dashboard widgets

### Advanced

- Cache configuration
- Performance tuning
- Debug mode
- API access

## Search

Global search across:
- Criteria names and descriptions
- Rule fields
- Audit logs
- Tags

**Features:**
- Instant results
- Keyboard shortcuts (Ctrl/Cmd + K)
- Recent searches
- Search suggestions

## Bulk Operations

Perform actions on multiple items:

### Criteria
- Bulk enable/disable
- Bulk delete
- Bulk tag
- Bulk export

### Audit Logs
- Bulk export
- Bulk delete (with confirmation)

## Responsive Design

The UI is fully responsive:
- Desktop (optimized)
- Tablet (adapted)
- Mobile (simplified)

## Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `Ctrl/Cmd + K` | Open search |
| `Ctrl/Cmd + N` | New criteria |
| `Ctrl/Cmd + P` | Open playground |
| `Ctrl/Cmd + S` | Save (in forms) |
| `Esc` | Close modals |

## Access Control

Protect UI with middleware:

```php
// config/eligify.php
'ui' => [
    'middleware' => ['web', 'auth', 'can:manage-eligibility'],
],
```

## Customization

Customize the UI:

```php
// config/eligify.php
'ui' => [
    'brand_name' => 'My Company Eligibility',
    'logo_url' => '/img/logo.png',
    'primary_color' => '#3B82F6',
    'dashboard_widgets' => [
        CustomWidget::class,
    ],
],
```

## Related Documentation

- [Setup Guide](setup.md) - Installation and configuration
- [Dynamic Fields](dynamic-fields.md) - Dynamic field selection
- [Playground](playground.md) - Playground details
- [Customization](customization.md) - Advanced customization
