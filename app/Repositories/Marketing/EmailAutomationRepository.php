<?php
namespace App\Repositories\Marketing;

use PDO;

final class EmailAutomationRepository
{
    public function __construct(private PDO $db) {}

    /**
     * Crea/actualiza automatizaciones base y sus pasos.
     * No toca is_enabled si ya existen (respeta decisiones del admin).
     */
    public function ensureDefaults(): void
    {
        $automations = [
            ['code' => 'welcome', 'name' => 'Bienvenida (serie)'],
            ['code' => 'cart_abandoned', 'name' => 'Carrito abandonado'],
            ['code' => 'post_purchase', 'name' => 'Post-compra (gracias / reseña)'],
        ];

        foreach ($automations as $a) {
            $st = $this->db->prepare(
                'INSERT INTO email_automations (code, name, is_enabled, created_at, updated_at)
                 VALUES (:code, :name, 1, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE name = VALUES(name), updated_at = NOW()'
            );
            $st->execute([':code' => $a['code'], ':name' => $a['name']]);
        }

        // Map code -> id
        $ids = $this->db->query('SELECT id, code FROM email_automations')->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $map = [];
        foreach ($ids as $r) $map[(string)$r['code']] = (int)$r['id'];

        $steps = [
            'welcome' => [
                ['code' => 'w0', 'name' => 'Bienvenida inmediata', 'delay_minutes' => 0, 'subject' => '¡Bienvenido/a a Mi Tienda Pesca!', 'template' => 'marketing/welcome_0'],
                ['code' => 'w1', 'name' => 'Tips + recomendados (48h)', 'delay_minutes' => 48*60, 'subject' => 'Elegí mejor: tips y recomendados 🎣', 'template' => 'marketing/welcome_1'],
            ],
            'cart_abandoned' => [
                ['code' => 'c1', 'name' => 'Recordatorio (1h)', 'delay_minutes' => 60, 'subject' => 'Te quedó un carrito pendiente 🛒', 'template' => 'marketing/cart_abandoned_1h'],
                ['code' => 'c24', 'name' => 'Segundo aviso (24h)', 'delay_minutes' => 24*60, 'subject' => '¿Lo dejamos listo para comprar?', 'template' => 'marketing/cart_abandoned_24h'],
            ],
            'post_purchase' => [
                ['code' => 'p0', 'name' => 'Gracias (inmediato)', 'delay_minutes' => 0, 'subject' => '¡Gracias por tu compra! ✅', 'template' => 'marketing/post_purchase_0'],
                ['code' => 'p7', 'name' => 'Pedir reseña (7d)', 'delay_minutes' => 7*24*60, 'subject' => '¿Cómo te fue con tu compra?', 'template' => 'marketing/post_purchase_7d'],
            ],
        ];

        // IMPORTANTE:
        // - NO pisar delay_minutes si el admin lo configuró.
        // - Sí mantenemos name/subject/template actualizados, pero el delay queda como "source of truth" del admin.
        $st = $this->db->prepare(
            'INSERT INTO email_automation_steps
              (automation_id, code, name, delay_minutes, subject, template, is_enabled, created_at, updated_at)
             VALUES
              (:automation_id, :code, :name, :delay_minutes, :subject, :template, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
              name = VALUES(name), subject = VALUES(subject), template = VALUES(template), updated_at = NOW()'
        );

        foreach ($steps as $automationCode => $list) {
            $aid = $map[$automationCode] ?? 0;
            if ($aid <= 0) continue;
            foreach ($list as $s) {
                $st->execute([
                    ':automation_id' => $aid,
                    ':code' => $s['code'],
                    ':name' => $s['name'],
                    ':delay_minutes' => (int)$s['delay_minutes'],
                    ':subject' => $s['subject'],
                    ':template' => $s['template'],
                ]);
            }
        }
    }

    public function listAutomationsWithSteps(): array
    {
        $sql = 'SELECT
                  a.id AS automation_id,
                  a.code AS automation_code,
                  a.name AS automation_name,
                  a.is_enabled AS automation_enabled,
                  s.id AS step_id,
                  s.code AS step_code,
                  s.name AS step_name,
                  s.delay_minutes,
                  s.subject,
                  s.template,
                  s.is_enabled AS step_enabled
                FROM email_automations a
                LEFT JOIN email_automation_steps s ON s.automation_id = a.id
                ORDER BY a.id ASC, s.delay_minutes ASC, s.id ASC';
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getEnabledStepsByAutomationCode(string $automationCode): array
    {
        $sql = 'SELECT s.*
                  FROM email_automation_steps s
                  INNER JOIN email_automations a ON a.id = s.automation_id
                 WHERE a.code = :c AND a.is_enabled = 1 AND s.is_enabled = 1
                 ORDER BY s.delay_minutes ASC, s.id ASC';
        $st = $this->db->prepare($sql);
        $st->execute([':c' => $automationCode]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function setAutomationEnabled(int $id, bool $enabled): bool
    {
        $st = $this->db->prepare('UPDATE email_automations SET is_enabled = :e, updated_at = NOW() WHERE id = :id');
        $st->execute([':e' => $enabled ? 1 : 0, ':id' => $id]);
        return $st->rowCount() > 0;
    }

    public function setStepEnabled(int $id, bool $enabled): bool
    {
        $st = $this->db->prepare('UPDATE email_automation_steps SET is_enabled = :e, updated_at = NOW() WHERE id = :id');
        $st->execute([':e' => $enabled ? 1 : 0, ':id' => $id]);
        return $st->rowCount() > 0;
    }

    public function setStepDelay(int $id, int $delayMinutes): bool
    {
        $delayMinutes = max(0, min(60 * 24 * 365, $delayMinutes)); // 0..365 días
        $st = $this->db->prepare('UPDATE email_automation_steps SET delay_minutes = :m, updated_at = NOW() WHERE id = :id');
        $st->execute([':m' => $delayMinutes, ':id' => $id]);
        return $st->rowCount() > 0;
    }
}
