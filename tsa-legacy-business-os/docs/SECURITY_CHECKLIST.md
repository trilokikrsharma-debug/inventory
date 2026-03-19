# Production Security Checklist

## Application Security
- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] Strong `APP_KEY` in Secret Manager
- [ ] Force HTTPS and HSTS enabled
- [ ] CSP configured and reviewed for third-party scripts
- [ ] CSRF enabled for all state-changing web routes
- [ ] Input validation for every controller action
- [ ] Use prepared statements via Eloquent/Query Builder only

## Authentication & Access
- [ ] Enforce strong password policy
- [ ] Optional MFA for platform admins
- [ ] Rate limits on login and API routes
- [ ] RBAC via Spatie roles/permissions
- [ ] Tenant isolation middleware on all tenant routes

## Billing Security
- [ ] Verify Razorpay payment signatures
- [ ] Verify webhook signatures
- [ ] Log all webhook events and failed attempts
- [ ] Idempotency checks on webhook payment processing

## Data Security
- [ ] At-rest encryption (Cloud SQL, GCS)
- [ ] Encrypted backup policy enabled
- [ ] Database users with least privilege
- [ ] Tenant DB credentials rotated periodically

## Monitoring & Incident Response
- [ ] Central audit logs enabled
- [ ] Failed login monitoring and alerting
- [ ] Cloud Logging alerts for error spikes
- [ ] WAF / Cloud Armor rules for abuse patterns

