// admin/product_images/uploader.js
import { CONCURRENCY, REQUEST_TIMEOUT_MS, RETRY_COUNT, RETRY_BACKOFF_MS } from './config.js';

let controllers = new Set();

export function cancelAll() {
  for (const c of controllers) { try { c.abort(); } catch {} }
  controllers.clear();
}

function sleep(ms){ return new Promise(r => setTimeout(r, ms)); }

/**
 * Sube archivos con concurrencia + retries.
 * @param {Object} opts
 * @param {File[]} opts.files
 * @param {(file: File, signal: AbortSignal) => Promise<any>} opts.doRequest
 * @param {(file: File, idx: number) => void} [opts.onItemStart]
 * @param {(file: File, idx: number, result: any, error?: any) => void} [opts.onItemDone]
 * @param {() => void} [opts.onBatchDone]
 * @param {number} [opts.concurrency]
 * @param {number} [opts.timeoutMs]
 * @param {number} [opts.retryCount]
 * @param {number} [opts.retryBackoffMs]
 */
export async function uploadQueue(opts={}) {
  const files = Array.from(opts.files || []);
  if (files.length === 0) { opts.onBatchDone?.(); return; }

  const limit = Math.max(1, opts.concurrency || CONCURRENCY);
  const timeoutMs = Math.max(1, opts.timeoutMs || REQUEST_TIMEOUT_MS);
  const maxRetry = Math.max(0, opts.retryCount ?? RETRY_COUNT);
  const backoffBase = Math.max(0, opts.retryBackoffMs ?? RETRY_BACKOFF_MS);

  let next = 0;
  let running = 0;

  async function runOne(file, idx) {
    let attempt = 0;
    while (true) {
      const controller = new AbortController();
      const timer = setTimeout(() => controller.abort(), timeoutMs);
      controllers.add(controller);
      try {
        const res = await opts.doRequest(file, controller.signal);
        clearTimeout(timer);
        controllers.delete(controller);
        return res;
      } catch (err) {
        clearTimeout(timer);
        controllers.delete(controller);
        const retriable = (err && (err.name === 'AbortError' || err.message?.includes('Network') || err.message?.includes('HTTP 5')));
        if (attempt < maxRetry && retriable) {
          const delay = backoffBase * Math.pow(2, attempt);
          await sleep(delay);
          attempt++;
          continue;
        }
        throw err;
      }
    }
  }

  return new Promise((resolve) => {
    const launchNext = () => {
      while (running < limit && next < files.length) {
        const idx = next++;
        const file = files[idx];
        running++;

        opts.onItemStart?.(file, idx);
        runOne(file, idx)
          .then((res) => opts.onItemDone?.(file, idx, res, undefined))
          .catch((err) => opts.onItemDone?.(file, idx, undefined, err))
          .finally(() => {
            running--;
            if (next >= files.length && running === 0) {
              opts.onBatchDone?.();
              resolve();
            } else {
              launchNext();
            }
          });
      }
    };
    launchNext();
  });
}
