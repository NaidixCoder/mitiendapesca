import { bindPricingIvaActions } from './pricing_iva_actions.js';
import { bindPricingBulkActions } from './pricing_bulk_actions.js';

export function bindPricingActions(ctx) {
  bindPricingIvaActions(ctx);
  bindPricingBulkActions(ctx);
}
