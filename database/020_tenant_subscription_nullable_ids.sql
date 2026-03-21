-- ============================================================================
-- 020_tenant_subscription_nullable_ids.sql
-- Allow pre-payment and manual subscription records without forcing gateway IDs.
-- ============================================================================

ALTER TABLE tenant_subscriptions
    MODIFY COLUMN razorpay_subscription_id VARCHAR(100) NULL DEFAULT NULL;

-- ============================================================================
-- End
-- ============================================================================
