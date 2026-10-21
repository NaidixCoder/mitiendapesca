-- Marketing attribution: nuevos usuarios por campaña
-- Agrega marketing_campaign_id a users para saber desde qué /promo/* se registró.

ALTER TABLE users
  ADD COLUMN marketing_campaign_id INT UNSIGNED NULL AFTER role;

ALTER TABLE users
  ADD KEY idx_users_marketing_campaign_created (marketing_campaign_id, created_at);

ALTER TABLE users
  ADD CONSTRAINT fk_users_marketing_campaign
    FOREIGN KEY (marketing_campaign_id) REFERENCES marketing_campaigns(id)
    ON DELETE SET NULL;
