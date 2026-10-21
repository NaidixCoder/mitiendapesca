import { bindSetsActions } from './sets_actions.js';
import { bindValuesActions } from './values_actions.js';
import { bindGenerationActions } from './generation_actions.js';

export function bindAttributeActions(ctx) {
  const {
    root,
    on,
    fb,
    productId,
    enqueue,
    reload,
    setBusy,
    syncGenerateWrapFromDom,
    setConfigCollapsedDom,
    setConfigCollapsed,
  } = ctx;

  bindSetsActions({
    root,
    on,
    fb,
    productId,
    enqueue,
    reload,
    setBusy,
    syncGenerateWrapFromDom,
  });

  bindValuesActions({
    root,
    on,
    fb,
    productId,
    enqueue,
    reload,
    syncGenerateWrapFromDom,
  });

  bindGenerationActions({
    root,
    on,
    fb,
    productId,
    enqueue,
    reload,
    syncGenerateWrapFromDom,
    setConfigCollapsedDom,
    setConfigCollapsed,
  });
}
