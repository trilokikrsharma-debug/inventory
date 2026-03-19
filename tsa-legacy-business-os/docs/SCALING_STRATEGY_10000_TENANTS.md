# Scalability Strategy (10,000+ Tenants)

## 1. Multi-Tenant Data Topology
- Use one central metadata database.
- Use per-tenant databases for isolation and noisy-neighbor protection.
- Group tenants by size tier and route to dedicated DB clusters where needed.

## 2. Compute Scaling
- Cloud Run autoscaling with min/max instance controls.
- Separate services:
  - `web-app` (HTTP requests)
  - `queue-worker` (async jobs, billing processing)
  - `report-worker` (long-running analytics exports)

## 3. Caching & Queues
- Redis for:
  - session store
  - cache store
  - queue backend
- Tag cache by tenant ID to avoid cross-tenant bleed.

## 4. Database Performance
- Add indexes on:
  - subscription and payment statuses
  - invoice dates
  - stock and SKU columns
  - frequent foreign keys
- Use read replicas for reporting queries.
- Periodic archive strategy for old ledger/payment logs.

## 5. Async & Backpressure
- Queue all heavy operations:
  - tenant provisioning
  - invoice generation
  - report exports
  - webhook retries
- Add retry strategy with dead-letter queues.

## 6. Observability
- Metrics:
  - request latency p95/p99
  - queue depth
  - DB CPU/connection saturation
  - tenant-level error rates
- Alerts:
  - webhook failures > threshold
  - failed login spikes
  - error budget burn rate

