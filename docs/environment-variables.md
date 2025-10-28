# Environment Variables Reference

This guide lists all environment variables used by Eligify for easy `.env` configuration.

## Quick Reference Table

| Variable | Default | Description | Example |
|----------|---------|-------------|---------|
| **UI Configuration** |
| `ELIGIFY_UI_ENABLED` | `false` | Enable/disable dashboard | `true` |
| `ELIGIFY_UI_ROUTE_PREFIX` | `eligify` | Dashboard URL prefix | `admin/eligibility` |
| `ELIGIFY_UI_GATE` | `viewEligify` | Authorization gate name | `manageEligibility` |
| `ELIGIFY_UI_BRAND_NAME` | `Eligify` | Dashboard branding name | `My Company` |
| `ELIGIFY_UI_BRAND_LOGO` | `null` | Logo URL/path | `/images/logo.svg` |
| `ELIGIFY_UI_ASSETS_USE_CDN` | `true` | Use CDN for CSS/JS | `false` |
| `ELIGIFY_UI_TAILWIND_CDN` | `https://cdn.tailwindcss.com` | Tailwind CSS CDN | Custom URL |
| `ELIGIFY_UI_ALPINE_CDN` | `https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js` | Alpine.js CDN | Custom URL |
| **Scoring Configuration** |
| `ELIGIFY_SCORING_PASS_THRESHOLD` | `65` | Default pass threshold (0-100) | `70` |
| `ELIGIFY_SCORING_MAX_SCORE` | `100` | Maximum possible score | `100` |
| `ELIGIFY_SCORING_MIN_SCORE` | `0` | Minimum possible score | `0` |
| `ELIGIFY_SCORING_METHOD` | `weighted` | Default scoring method | `pass_fail` |
| `ELIGIFY_SCORING_FAILURE_PENALTY` | `5` | Penalty per failed rule | `10` |
| `ELIGIFY_SCORING_EXCELLENCE_BONUS` | `10` | Bonus for high scores | `15` |
| **Audit Configuration** |
| `ELIGIFY_AUDIT_ENABLED` | `true` | Enable audit logging | `false` |
| `ELIGIFY_AUDIT_RETENTION_DAYS` | `90` | Days to keep audit logs | `365` |
| `ELIGIFY_AUDIT_AUTO_CLEANUP` | `true` | Auto-cleanup old logs | `false` |
| `ELIGIFY_AUDIT_CLEANUP_SCHEDULE` | `daily` | Cleanup frequency | `weekly` |
| **Performance Configuration** |
| `ELIGIFY_OPTIMIZE_QUERIES` | `true` | Enable query optimization | `false` |
| `ELIGIFY_CACHE_CRITERIA` | `true` | Cache compiled criteria | `false` |
| `ELIGIFY_CACHE_TTL` | `1440` | Cache TTL (minutes) | `720` |

---

## Configuration Templates

### Development Environment

```env
# .env.local
APP_ENV=local
APP_DEBUG=true

# UI - Auto-allowed in local environment
ELIGIFY_UI_ENABLED=true
ELIGIFY_UI_ROUTE_PREFIX=eligify

# Scoring - Lenient thresholds for testing
ELIGIFY_SCORING_PASS_THRESHOLD=60

# Audit - Full logging for development
ELIGIFY_AUDIT_ENABLED=true
ELIGIFY_AUDIT_RETENTION_DAYS=30

# Performance - Disable caching for real-time updates
ELIGIFY_CACHE_CRITERIA=false
```

---

### Staging Environment

```env
# .env.staging
APP_ENV=staging
APP_DEBUG=false

# UI - Enabled with role-based auth
ELIGIFY_UI_ENABLED=true
ELIGIFY_UI_ROUTE_PREFIX=admin/eligibility
ELIGIFY_UI_GATE=viewEligify
ELIGIFY_UI_BRAND_NAME="MyApp Staging"

# Scoring - Production-like settings
ELIGIFY_SCORING_PASS_THRESHOLD=65
ELIGIFY_SCORING_METHOD=weighted

# Audit - Extended retention for testing
ELIGIFY_AUDIT_ENABLED=true
ELIGIFY_AUDIT_RETENTION_DAYS=180
ELIGIFY_AUDIT_AUTO_CLEANUP=true
ELIGIFY_AUDIT_CLEANUP_SCHEDULE=weekly

# Performance - Enable optimizations
ELIGIFY_OPTIMIZE_QUERIES=true
ELIGIFY_CACHE_CRITERIA=true
ELIGIFY_CACHE_TTL=720
```

---

### Production Environment

```env
# .env.production
APP_ENV=production
APP_DEBUG=false

# UI - Disabled or strictly controlled
ELIGIFY_UI_ENABLED=true
ELIGIFY_UI_ROUTE_PREFIX=admin/eligibility
ELIGIFY_UI_GATE=viewEligify
ELIGIFY_UI_BRAND_NAME="MyApp"
ELIGIFY_UI_BRAND_LOGO="/images/company-logo.svg"

# Assets - Use CDN for performance
ELIGIFY_UI_ASSETS_USE_CDN=true

# Scoring - Production thresholds
ELIGIFY_SCORING_PASS_THRESHOLD=70
ELIGIFY_SCORING_METHOD=weighted
ELIGIFY_SCORING_FAILURE_PENALTY=5
ELIGIFY_SCORING_EXCELLENCE_BONUS=10

# Audit - Compliance retention
ELIGIFY_AUDIT_ENABLED=true
ELIGIFY_AUDIT_RETENTION_DAYS=365
ELIGIFY_AUDIT_AUTO_CLEANUP=true
ELIGIFY_AUDIT_CLEANUP_SCHEDULE=daily

# Performance - Full optimization
ELIGIFY_OPTIMIZE_QUERIES=true
ELIGIFY_CACHE_CRITERIA=true
ELIGIFY_CACHE_TTL=1440
```

---

### Production (UI Disabled)

```env
# .env.production (headless)
APP_ENV=production
APP_DEBUG=false

# UI - Completely disabled
ELIGIFY_UI_ENABLED=false

# Scoring - Production settings
ELIGIFY_SCORING_PASS_THRESHOLD=70
ELIGIFY_SCORING_METHOD=weighted

# Audit - Enabled for compliance
ELIGIFY_AUDIT_ENABLED=true
ELIGIFY_AUDIT_RETENTION_DAYS=730
ELIGIFY_AUDIT_AUTO_CLEANUP=true

# Performance - Maximum optimization
ELIGIFY_OPTIMIZE_QUERIES=true
ELIGIFY_CACHE_CRITERIA=true
ELIGIFY_CACHE_TTL=2880
```

---

## Common Scenarios

### 1. Enable UI for Development Only

```env
# .env
ELIGIFY_UI_ENABLED=true

# No additional auth needed - auto-allowed in local environment
```

---

### 2. Enable UI with Email Whitelist

```env
# .env
ELIGIFY_UI_ENABLED=true
ELIGIFY_UI_GATE=viewEligify
ELIGIFY_ALLOWED_EMAILS="admin@company.com,developer@company.com"
```

**AppServiceProvider:**

```php
Gate::define('viewEligify', function ($user) {
    $allowedEmails = explode(',', env('ELIGIFY_ALLOWED_EMAILS', ''));
    return in_array($user->email, $allowedEmails);
});
```

---

### 3. Custom Branding

```env
ELIGIFY_UI_ENABLED=true
ELIGIFY_UI_BRAND_NAME="Acme Corp Eligibility"
ELIGIFY_UI_BRAND_LOGO="https://cdn.acme.com/logo.svg"
```

---

### 4. Change Dashboard URL

```env
ELIGIFY_UI_ENABLED=true
ELIGIFY_UI_ROUTE_PREFIX=internal/eligibility-system
```

**Access:** `http://your-app.test/internal/eligibility-system`

---

### 5. Disable Audit Logging (Performance)

```env
ELIGIFY_AUDIT_ENABLED=false
```

> ⚠️ **Warning**: Disabling audit logs removes traceability. Only do this if you have alternative logging mechanisms.

---

### 6. Strict Pass Threshold

```env
ELIGIFY_SCORING_PASS_THRESHOLD=85
ELIGIFY_SCORING_METHOD=weighted
ELIGIFY_SCORING_FAILURE_PENALTY=10
```

---

### 7. High-Security Setup

```env
# UI - Restricted access
ELIGIFY_UI_ENABLED=true
ELIGIFY_UI_ROUTE_PREFIX=secure/eligibility
ELIGIFY_UI_GATE=adminOnlyEligify

# Audit - Maximum retention
ELIGIFY_AUDIT_ENABLED=true
ELIGIFY_AUDIT_RETENTION_DAYS=1825  # 5 years
ELIGIFY_AUDIT_AUTO_CLEANUP=false   # Manual review required

# Performance - Prioritize security over speed
ELIGIFY_OPTIMIZE_QUERIES=true
ELIGIFY_CACHE_CRITERIA=false  # Real-time evaluation only
```

---

### 8. Disable CDN (CSP Compliance)

```env
ELIGIFY_UI_ENABLED=true
ELIGIFY_UI_ASSETS_USE_CDN=false
```

> **Note**: Requires you to compile Tailwind CSS and Alpine.js with your application's build process.

---

### 9. Multi-Environment Config File

Create environment-specific configs:

```bash
# .env.local
include .env.eligify.local

# .env.staging
include .env.eligify.staging

# .env.production
include .env.eligify.production
```

**`.env.eligify.production`:**

```env
ELIGIFY_UI_ENABLED=true
ELIGIFY_UI_ROUTE_PREFIX=admin/eligibility
ELIGIFY_UI_GATE=viewEligify
ELIGIFY_SCORING_PASS_THRESHOLD=70
ELIGIFY_AUDIT_ENABLED=true
ELIGIFY_AUDIT_RETENTION_DAYS=365
ELIGIFY_OPTIMIZE_QUERIES=true
ELIGIFY_CACHE_CRITERIA=true
```

---

## Validation

### Check Your Configuration

```bash
# View compiled config
php artisan config:show eligify

# Clear cached config
php artisan config:clear

# Cache config for production
php artisan config:cache
```

---

### Verify UI Access

```bash
# Test health endpoint
curl http://your-app.test/eligify/_health

# Check authorization (should get 200 or 403)
curl -I http://your-app.test/eligify
```

---

## See Also

- **[UI Setup Guide](ui-setup-guide.md)** - Complete dashboard configuration
- **[Configuration Guide](configuration.md)** - Detailed config options
- **[Production Deployment](production-deployment.md)** - Deployment checklist
- **[Security Best Practices](security-api-stability.md)** - Security guidelines

---

## Need Help?

**Can't find the right configuration?**

- 📖 Check the [Configuration Guide](configuration.md)
- 🔒 Review [Security Best Practices](security-api-stability.md)
- 💬 Ask in [GitHub Discussions](https://github.com/cleaniquecoders/eligify/discussions)
