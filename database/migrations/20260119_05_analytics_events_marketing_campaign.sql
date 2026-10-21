-- Agrega marketing_campaign_id a analytics_events para atribuir eventos (ej: add_to_cart) a campañas.

ALTER TABLE analytics_events
  ADD COLUMN marketing_campaign_id INT UNSIGNED NULL AFTER referrer,
  ADD KEY idx_campaign_date (marketing_campaign_id, created_at);
