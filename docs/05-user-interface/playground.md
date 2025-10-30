# Playground Guide

## Overview

The Playground is an interactive testing environment where you can evaluate criteria against sample data in real-time.

## Features

### 🎯 Smart Sample Data Generation

When you select a criteria, the playground can automatically generate appropriate test data based on your rules.

**Example:** If your criteria has rules like:

- `applicant.income >= 3000`
- `applicant.age >= 18`
- `applicant.not_bankrupt == true`

Click **"✨ Generate from Rules"** and it will create:

```json
{
  "applicant": {
    "income": 3010,
    "age": 28,
    "not_bankrupt": true
  }
}
```

### 📝 Manual Data Entry

You can also manually enter test data. The system supports both:

**Flat structure with dot notation:**

```json
{
  "applicant.income": 2500,
  "applicant.age": 20,
  "applicant.not_bankrupt": true
}
```

**Nested object structure:**

```json
{
  "applicant": {
    "income": 2500,
    "age": 20,
    "not_bankrupt": true
  }
}
```

Both formats work identically!

### 🚀 Quick Examples

For generic testing, use the pre-filled examples:

- **Numeric** - Fields with numbers (age, income, scores)
- **String** - Text fields (status, names, emails)
- **Boolean** - True/false fields
- **Mixed** - Combination of all types

### ✅ Evaluation Results

After clicking "Evaluate", you'll see:

1. **Pass/Fail Status** - Visual indicator with color coding
2. **Score** - Numerical score out of 100
3. **Decision** - Final decision text
4. **Rules Breakdown** - Each rule showing:
   - ✓/✗ Pass or fail indicator
   - Field name
   - Operator and expected value
   - Actual value from your test data
   - Weight contribution
   - Execution time in milliseconds

### 🛠️ Helper Buttons

- **✨ Generate from Rules** - Auto-creates sample data from your criteria
- **Format** - Prettifies your JSON for better readability
- **Clear** - Resets the entire playground
- **Show Quick Examples** - Displays pre-filled generic examples

## Use Cases

### Testing Loan Approval Criteria

```json
{
  "applicant": {
    "income": 5000,
    "credit_score": 720,
    "employment_status": "employed",
    "years_employed": 3,
    "existing_loans": 1,
    "debt_to_income_ratio": 0.25
  }
}
```

### Testing Scholarship Eligibility

```json
{
  "student": {
    "gpa": 3.8,
    "financial_need": true,
    "extracurricular_activities": 5,
    "community_service_hours": 120,
    "essay_submitted": true
  }
}
```

### Testing Job Candidate Screening

```json
{
  "candidate": {
    "years_experience": 5,
    "education_level": "bachelors",
    "skills": ["php", "laravel", "vue", "mysql"],
    "certifications": 2,
    "background_check_passed": true
  }
}
```

## Tips

1. **Use the auto-generator first** - Click "✨ Generate from Rules" to see what fields your criteria expects
2. **Modify the generated data** - Adjust values to test different scenarios (passing and failing cases)
3. **Test edge cases** - Try values at the threshold (e.g., if minimum income is 3000, test with 2999 and 3000)
4. **Check execution log** - Use the raw JSON viewer to see detailed execution information
5. **Link to criteria details** - Click "View details →" to see the full rule configuration

## Keyboard Shortcuts

- **Format JSON** - Clean up your JSON structure
- **Tab in textarea** - Indents properly (browser dependent)

## Troubleshooting

### "Invalid JSON" Error

Make sure your JSON is properly formatted:

- Use double quotes for keys and string values
- No trailing commas
- Properly closed brackets

### "Please select a criteria first"

Choose a criteria from the dropdown before evaluating.

### Rules not generating expected data

Check that your rules have proper field types and operators configured.
