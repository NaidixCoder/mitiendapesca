-- Baseline indexes (created_at + FKs) — safe-add helper
-- Run once after applying FIX251 collation normalization
DELIMITER $$
DROP PROCEDURE IF EXISTS sp_add_index_if_missing $$
CREATE PROCEDURE sp_add_index_if_missing(IN p_table VARCHAR(64), IN p_index VARCHAR(64), IN p_cols VARCHAR(255))
BEGIN
  DECLARE v_cnt INT DEFAULT 0;
  SELECT COUNT(1) INTO v_cnt
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND INDEX_NAME = p_index;
  IF v_cnt = 0 THEN
    SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` (', p_cols, ')');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END $$
DELIMITER ;

CALL sp_add_index_if_missing('brands','idx_brands_created_at','`created_at`');
CALL sp_add_index_if_missing('categories','idx_categories_created_at','`created_at`');
CALL sp_add_index_if_missing('categories','idx_categories_parent_id','`parent_id`');
CALL sp_add_index_if_missing('inventory','idx_inventory_product_id','`product_id`');
CALL sp_add_index_if_missing('user_profiles','idx_user_profiles_created_at','`created_at`');
CALL sp_add_index_if_missing('user_addresses','idx_user_addresses_created_at','`created_at`');
CALL sp_add_index_if_missing('email_verifications','idx_email_verifications_created_at','`created_at`');
CALL sp_add_index_if_missing('user_tokens','idx_user_tokens_created_at','`created_at`');
CALL sp_add_index_if_missing('admin_audit_log','idx_admin_audit_log_created_at','`created_at`');
CALL sp_add_index_if_missing('import_jobs','idx_import_jobs_created_at','`created_at`');
CALL sp_add_index_if_missing('orders','idx_orders_user_id','`user_id`');
CALL sp_add_index_if_missing('payments','idx_payments_created_at','`created_at`');
CALL sp_add_index_if_missing('payments','idx_payments_provider_preference_id','`provider_preference_id`');
CALL sp_add_index_if_missing('order_shipments','idx_order_shipments_order_id','`order_id`');
CALL sp_add_index_if_missing('carts','idx_carts_created_at','`created_at`');
CALL sp_add_index_if_missing('carts','idx_carts_merged_into_cart_id','`merged_into_cart_id`');
CALL sp_add_index_if_missing('attributes','idx_attributes_created_at','`created_at`');
CALL sp_add_index_if_missing('attribute_values','idx_attribute_values_created_at','`created_at`');
CALL sp_add_index_if_missing('product_variants','idx_product_variants_created_at','`created_at`');
CALL sp_add_index_if_missing('product_images','idx_product_images_created_at','`created_at`');
CALL sp_add_index_if_missing('order_items','idx_order_items_created_at','`created_at`');
CALL sp_add_index_if_missing('cart_items','idx_cart_items_created_at','`created_at`');
CALL sp_add_index_if_missing('inventory_variants','idx_inventory_variants_variant_id','`variant_id`');
CALL sp_add_index_if_missing('variant_value_sets','idx_variant_value_sets_created_at','`created_at`');
CALL sp_add_index_if_missing('variant_value_set_items','idx_variant_value_set_items_created_at','`created_at`');
CALL sp_add_index_if_missing('seo_settings','idx_seo_settings_created_at','`created_at`');
CALL sp_add_index_if_missing('seo_redirects','idx_seo_redirects_created_at','`created_at`');
CALL sp_add_index_if_missing('seo_slug_history','idx_seo_slug_history_created_at','`created_at`');
CALL sp_add_index_if_missing('product_seo_blocks','idx_product_seo_blocks_created_at','`created_at`');
CALL sp_add_index_if_missing('category_seo_blocks','idx_category_seo_blocks_created_at','`created_at`');
CALL sp_add_index_if_missing('brand_seo_blocks','idx_brand_seo_blocks_created_at','`created_at`');
CALL sp_add_index_if_missing('marketing_campaigns','idx_marketing_campaigns_created_at','`created_at`');
CALL sp_add_index_if_missing('email_automations','idx_email_automations_created_at','`created_at`');
CALL sp_add_index_if_missing('email_automation_steps','idx_email_automation_steps_created_at','`created_at`');
CALL sp_add_index_if_missing('email_queue','idx_email_queue_created_at','`created_at`');
CALL sp_add_index_if_missing('email_queue','idx_email_queue_entity_id','`entity_id`');
CALL sp_add_index_if_missing('email_send_logs','idx_email_send_logs_entity_id','`entity_id`');
CALL sp_add_index_if_missing('email_send_logs','idx_email_send_logs_queue_id','`queue_id`');
CALL sp_add_index_if_missing('email_send_logs','idx_email_send_logs_subscriber_id','`subscriber_id`');
CALL sp_add_index_if_missing('email_send_logs','idx_email_send_logs_user_id','`user_id`');
CALL sp_add_index_if_missing('email_click_tokens','idx_email_click_tokens_queue_id','`queue_id`');
CALL sp_add_index_if_missing('email_click_tokens','idx_email_click_tokens_subscriber_id','`subscriber_id`');
CALL sp_add_index_if_missing('email_click_tokens','idx_email_click_tokens_user_id','`user_id`');

DROP PROCEDURE IF EXISTS sp_add_index_if_missing;