-- FIX256: marketing_campaign_id columns must match marketing_campaigns.id (BIGINT UNSIGNED)
-- Only needed if upgrading an existing DB. For fresh imports, schema.sql already includes correct types.

-- IMPORTANT: If fk_users_marketing_campaign exists, you must drop and re-add it.
-- Example:
-- ALTER TABLE users DROP FOREIGN KEY fk_users_marketing_campaign;

ALTER TABLE users MODIFY marketing_campaign_id BIGINT UNSIGNED NULL;
ALTER TABLE orders MODIFY marketing_campaign_id BIGINT UNSIGNED NULL;
ALTER TABLE checkout_intents MODIFY marketing_campaign_id BIGINT UNSIGNED NULL;
ALTER TABLE analytics_events MODIFY marketing_campaign_id BIGINT UNSIGNED NULL;

-- Re-add FK if needed:
-- ALTER TABLE users
--   ADD CONSTRAINT fk_users_marketing_campaign
--   FOREIGN KEY (marketing_campaign_id) REFERENCES marketing_campaigns(id)
--   ON DELETE SET NULL;
