-- FIX506: Percepciones / Retenciones en Compras
-- Agrega campos mínimos a purchase_invoices (NO destructivo)

ALTER TABLE purchase_invoices
  ADD COLUMN perc_iva INT NOT NULL DEFAULT 0 AFTER imp_iva,
  ADD COLUMN perc_iibb INT NOT NULL DEFAULT 0 AFTER perc_iva,
  ADD COLUMN ret_ganancias INT NOT NULL DEFAULT 0 AFTER perc_iibb;
