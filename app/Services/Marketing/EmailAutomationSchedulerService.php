<?php
namespace App\Services\Marketing;

use App\Repositories\Marketing\EmailAutomationRepository;
use App\Repositories\Marketing\EmailQueueRepository;
use App\Repositories\Marketing\EmailSubscriberRepository;
use PDO;

final class EmailAutomationSchedulerService
{
    public function __construct(private PDO $db) {}

    public function run(int $limitEnqueue = 200): array
    {
        $limitEnqueue = max(10, min(2000, $limitEnqueue));

        $repo = new EmailAutomationRepository($this->db);
        $repo->ensureDefaults();

        $qrepo = new EmailQueueRepository($this->db);

        $stats = [
            'welcome_enqueued' => 0,
            'cart_enqueued' => 0,
            'post_purchase_enqueued' => 0,
            'skipped_unsubscribed' => 0,
        ];

        $stats['welcome_enqueued'] += $this->scheduleWelcome($repo, $qrepo, $limitEnqueue, $stats['skipped_unsubscribed']);
        $remaining = max(0, $limitEnqueue - $stats['welcome_enqueued']);
        if ($remaining > 0) {
            $stats['cart_enqueued'] += $this->scheduleCartAbandoned($repo, $qrepo, $remaining, $stats['skipped_unsubscribed']);
        }
        $remaining = max(0, $limitEnqueue - ($stats['welcome_enqueued'] + $stats['cart_enqueued']));
        if ($remaining > 0) {
            $stats['post_purchase_enqueued'] += $this->schedulePostPurchase($repo, $qrepo, $remaining, $stats['skipped_unsubscribed']);
        }

        return $stats;
    }

    private function scheduleWelcome(EmailAutomationRepository $repo, EmailQueueRepository $qrepo, int $limit, int &$skippedUnsub): int
    {
        $steps = $repo->getEnabledStepsByAutomationCode('welcome');
        if (!$steps) return 0;

        $days = (int)env('EMAIL_MARKETING_WELCOME_BACKFILL_DAYS', 14);
        $days = max(1, min(90, $days));

        $sql = 'SELECT id, email, unsubscribe_token, created_at
                  FROM email_subscribers
                 WHERE status = \'subscribed\'
                   AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                 ORDER BY created_at DESC
                 LIMIT ' . (int)$limit;
        $st = $this->db->prepare($sql);
        $st->execute([':days' => $days]);
        $subs = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $enq = 0;
        foreach ($subs as $s) {
            $sid = (int)($s['id'] ?? 0);
            $email = (string)($s['email'] ?? '');
            $unsubTok = (string)($s['unsubscribe_token'] ?? '');
            $createdAt = (string)($s['created_at'] ?? '');
            if ($sid <= 0 || $email === '' || $unsubTok === '' || $createdAt === '') continue;

            foreach ($steps as $step) {
                if ($enq >= $limit) break 2;
                $stepId = (int)($step['id'] ?? 0);
                $delay = (int)($step['delay_minutes'] ?? 0);
                $subject = (string)($step['subject'] ?? '');
                $template = (string)($step['template'] ?? '');
                if ($stepId <= 0 || $subject === '' || $template === '') continue;

                $dedupeKey = hash('sha256', 'step:' . $stepId . '|sub:' . $sid);
                $scheduledAt = $this->addMinutes($createdAt, $delay);

                $ok = $qrepo->enqueue([
                    'dedupe_key' => $dedupeKey,
                    'to_email' => $email,
                    'subject' => $subject,
                    'template' => $template,
                    'payload_json' => json_encode([
                        'email' => $email,
                        'unsubscribe_token' => $unsubTok,
                    ], JSON_UNESCAPED_UNICODE),
                    'unsubscribe_token' => $unsubTok,
                    'subscriber_id' => $sid,
                    'user_id' => null,
                    'automation_step_id' => $stepId,
                    'entity_type' => 'subscriber',
                    'entity_id' => $sid,
                    'scheduled_at' => $scheduledAt,
                ]);

                if ($ok) $enq++;
            }
        }

        return $enq;
    }

    private function scheduleCartAbandoned(EmailAutomationRepository $repo, EmailQueueRepository $qrepo, int $limit, int &$skippedUnsub): int
    {
        $steps = $repo->getEnabledStepsByAutomationCode('cart_abandoned');
        if (!$steps) return 0;

        $maxDays = (int)env('EMAIL_MARKETING_CART_ABANDONED_MAX_DAYS', 14);
        $maxDays = max(1, min(60, $maxDays));

        $sql = 'SELECT c.id AS cart_id, c.updated_at, u.id AS user_id, u.email
                  FROM carts c
                  INNER JOIN users u ON u.id = c.user_id
                  INNER JOIN user_profiles up ON up.user_id = u.id
                 WHERE c.status = \'active\'
                   AND c.user_id IS NOT NULL
                   AND up.marketing_email_opt_in = 1
                   AND c.updated_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                   AND EXISTS(SELECT 1 FROM cart_items ci WHERE ci.cart_id = c.id)
                 ORDER BY c.updated_at ASC
                 LIMIT ' . (int)$limit;
        $st = $this->db->prepare($sql);
        $st->execute([':days' => $maxDays]);
        $carts = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $subRepo = new EmailSubscriberRepository($this->db);
        $enq = 0;

        foreach ($carts as $c) {
            $cartId = (int)($c['cart_id'] ?? 0);
            $userId = (int)($c['user_id'] ?? 0);
            $email = strtolower(trim((string)($c['email'] ?? '')));
            $updatedAt = (string)($c['updated_at'] ?? '');
            if ($cartId <= 0 || $userId <= 0 || $email === '' || $updatedAt === '') continue;

            // Respeta bajas: si el email está unsubscribed, no enviamos.
            $sub = $subRepo->findByEmail($email);
            if ($sub && ($sub['status'] ?? '') === 'unsubscribed') {
                $skippedUnsub++;
                continue;
            }
            // Si no existe subscriber, lo creamos como subscribed (source=user)
            if (!$sub) {
                $sub = $subRepo->upsertSubscribe($email, 'subscribed', null, hash('sha256', random_bytes(32)), null, 'user');
            }
            $sid = (int)($sub['id'] ?? 0);
            $unsubTok = (string)($sub['unsubscribe_token'] ?? '');
            if ($sid <= 0 || $unsubTok === '') continue;

            $items = $this->cartItemsSnapshot($cartId);

            foreach ($steps as $step) {
                if ($enq >= $limit) break 2;
                $stepId = (int)($step['id'] ?? 0);
                $delay = (int)($step['delay_minutes'] ?? 0);
                $subject = (string)($step['subject'] ?? '');
                $template = (string)($step['template'] ?? '');
                if ($stepId <= 0 || $subject === '' || $template === '') continue;

                $dedupeKey = hash('sha256', 'step:' . $stepId . '|cart:' . $cartId);
                $scheduledAt = $this->addMinutes($updatedAt, $delay);

                $ok = $qrepo->enqueue([
                    'dedupe_key' => $dedupeKey,
                    'to_email' => $email,
                    'subject' => $subject,
                    'template' => $template,
                    'payload_json' => json_encode([
                        'email' => $email,
                        'unsubscribe_token' => $unsubTok,
                        'cart_id' => $cartId,
                        'cart_url' => url('/carrito'),
                        'items' => $items,
                    ], JSON_UNESCAPED_UNICODE),
                    'unsubscribe_token' => $unsubTok,
                    'subscriber_id' => $sid,
                    'user_id' => $userId,
                    'automation_step_id' => $stepId,
                    'entity_type' => 'cart',
                    'entity_id' => $cartId,
                    'scheduled_at' => $scheduledAt,
                ]);

                if ($ok) $enq++;
            }
        }

        return $enq;
    }

    private function schedulePostPurchase(EmailAutomationRepository $repo, EmailQueueRepository $qrepo, int $limit, int &$skippedUnsub): int
    {
        $steps = $repo->getEnabledStepsByAutomationCode('post_purchase');
        if (!$steps) return 0;

        $maxDays = (int)env('EMAIL_MARKETING_POSTPURCHASE_MAX_DAYS', 30);
        $maxDays = max(1, min(180, $maxDays));

        $sql = 'SELECT o.id AS order_id, o.created_at, u.id AS user_id, u.email
                  FROM orders o
                  INNER JOIN users u ON u.id = o.user_id
                  INNER JOIN user_profiles up ON up.user_id = u.id
                 WHERE o.user_id IS NOT NULL
                   AND up.marketing_email_opt_in = 1
                   AND o.created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                   AND o.status IN (\'paid\',\'processing\',\'packed\',\'shipped\',\'delivered\')
                 ORDER BY o.created_at ASC
                 LIMIT ' . (int)$limit;
        $st = $this->db->prepare($sql);
        $st->execute([':days' => $maxDays]);
        $orders = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $subRepo = new EmailSubscriberRepository($this->db);
        $enq = 0;

        foreach ($orders as $o) {
            $orderId = (int)($o['order_id'] ?? 0);
            $userId = (int)($o['user_id'] ?? 0);
            $email = strtolower(trim((string)($o['email'] ?? '')));
            $createdAt = (string)($o['created_at'] ?? '');
            if ($orderId <= 0 || $userId <= 0 || $email === '' || $createdAt === '') continue;

            $sub = $subRepo->findByEmail($email);
            if ($sub && ($sub['status'] ?? '') === 'unsubscribed') {
                $skippedUnsub++;
                continue;
            }
            if (!$sub) {
                $sub = $subRepo->upsertSubscribe($email, 'subscribed', null, hash('sha256', random_bytes(32)), null, 'user');
            }
            $sid = (int)($sub['id'] ?? 0);
            $unsubTok = (string)($sub['unsubscribe_token'] ?? '');
            if ($sid <= 0 || $unsubTok === '') continue;

            $items = $this->orderItemsSnapshot($orderId);

            foreach ($steps as $step) {
                if ($enq >= $limit) break 2;
                $stepId = (int)($step['id'] ?? 0);
                $delay = (int)($step['delay_minutes'] ?? 0);
                $subject = (string)($step['subject'] ?? '');
                $template = (string)($step['template'] ?? '');
                if ($stepId <= 0 || $subject === '' || $template === '') continue;

                $dedupeKey = hash('sha256', 'step:' . $stepId . '|order:' . $orderId);
                $scheduledAt = $this->addMinutes($createdAt, $delay);

                $ok = $qrepo->enqueue([
                    'dedupe_key' => $dedupeKey,
                    'to_email' => $email,
                    'subject' => $subject,
                    'template' => $template,
                    'payload_json' => json_encode([
                        'email' => $email,
                        'unsubscribe_token' => $unsubTok,
                        'order_id' => $orderId,
                        'orders_url' => url('/cuenta/pedidos'),
                        'items' => $items,
                    ], JSON_UNESCAPED_UNICODE),
                    'unsubscribe_token' => $unsubTok,
                    'subscriber_id' => $sid,
                    'user_id' => $userId,
                    'automation_step_id' => $stepId,
                    'entity_type' => 'order',
                    'entity_id' => $orderId,
                    'scheduled_at' => $scheduledAt,
                ]);

                if ($ok) $enq++;
            }
        }

        return $enq;
    }

    private function cartItemsSnapshot(int $cartId): array
    {
        $sql = 'SELECT
                  ci.quantity,
                  p.name AS product_name,
                  p.slug AS product_slug,
                  v.title AS variant_title,
                  COALESCE(v.price, p.price) AS unit_price
                FROM cart_items ci
                INNER JOIN products p ON p.id = ci.product_id
                LEFT JOIN product_variants v ON v.id = ci.variant_id
               WHERE ci.cart_id = :id
               ORDER BY ci.id ASC
               LIMIT 30';
        $st = $this->db->prepare($sql);
        $st->execute([':id' => $cartId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'qty' => (int)($r['quantity'] ?? 0),
                'product_name' => (string)($r['product_name'] ?? ''),
                'product_slug' => (string)($r['product_slug'] ?? ''),
                'variant_title' => (string)($r['variant_title'] ?? ''),
                'unit_price' => (int)($r['unit_price'] ?? 0),
                'product_url' => url('/producto?slug=' . urlencode((string)($r['product_slug'] ?? ''))),
            ];
        }
        return $out;
    }

    private function orderItemsSnapshot(int $orderId): array
    {
        $sql = 'SELECT qty, product_name, variant_title, price
                  FROM order_items
                 WHERE order_id = :id
                 ORDER BY id ASC
                 LIMIT 30';
        $st = $this->db->prepare($sql);
        $st->execute([':id' => $orderId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'qty' => (int)($r['qty'] ?? 0),
                'product_name' => (string)($r['product_name'] ?? ''),
                'variant_title' => (string)($r['variant_title'] ?? ''),
                'unit_price' => (int)($r['price'] ?? 0),
            ];
        }
        return $out;
    }

    private function addMinutes(string $dt, int $minutes): string
    {
        try {
            $t = new \DateTime($dt);
            if ($minutes !== 0) $t->modify(($minutes >= 0 ? '+' : '') . $minutes . ' minutes');
            return $t->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return date('Y-m-d H:i:s');
        }
    }
}
