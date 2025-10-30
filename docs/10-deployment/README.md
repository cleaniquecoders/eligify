# Deployment

This section covers deploying Eligify applications to production.

## Overview

Best practices and guidelines for production deployment of Eligify-powered applications.

## Documentation in this Section

- **[Production Guide](production.md)** - Production deployment checklist
- **[Optimization](optimization.md)** - Performance optimization
- **[Monitoring](monitoring.md)** - Logging, metrics, health checks
- **[Troubleshooting](troubleshooting.md)** - Common issues and solutions

## Pre-Deployment Checklist

### Configuration

- [ ] Review and optimize `config/eligify.php`
- [ ] Set appropriate cache TTL values
- [ ] Configure audit retention policies
- [ ] Enable production-ready cache driver (Redis)
- [ ] Set proper environment variables

### Security

- [ ] Enable audit logging
- [ ] Restrict UI access with middleware
- [ ] Validate input data
- [ ] Review custom operators for security
- [ ] Enable rate limiting

### Performance

- [ ] Enable caching
- [ ] Warm caches before going live
- [ ] Optimize database queries
- [ ] Profile slow evaluations
- [ ] Set up Redis for caching

### Testing

- [ ] Run full test suite
- [ ] Perform load testing
- [ ] Test failover scenarios
- [ ] Verify audit logging works
- [ ] Test cache invalidation

## Deployment Steps

### 1. Prepare Environment

```bash
# Set production environment
APP_ENV=production

# Configure cache
CACHE_DRIVER=redis
ELIGIFY_CACHE_ENABLED=true
ELIGIFY_CACHE_TTL=3600

# Configure audit
ELIGIFY_AUDIT_ENABLED=true
ELIGIFY_AUDIT_RETENTION_DAYS=365
```

### 2. Run Migrations

```bash
php artisan migrate --force
```

### 3. Warm Caches

```bash
php artisan eligify:cache:warm
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 4. Verify Deployment

```bash
php artisan eligify:health-check
```

## Production Best Practices

1. **Use Redis for caching** - File/database cache won't scale
2. **Monitor audit logs** - Watch for unusual patterns
3. **Set up alerts** - For evaluation failures
4. **Regular backups** - Criteria and audit data
5. **Version control** - Track criteria changes
6. **Load testing** - Before major releases
7. **Gradual rollout** - Test with subset of users

## Scaling Strategies

### Horizontal Scaling

- Stateless evaluations work well with load balancers
- Shared Redis cache across instances
- Database read replicas for audit logs

### Vertical Scaling

- Increase Redis memory
- Optimize database indexes
- Profile and cache expensive operations

## Related Sections

- [Configuration](../06-configuration/) - Production configuration
- [Security](../11-security/) - Security best practices
- [Testing](../09-testing/) - Pre-deployment testing
