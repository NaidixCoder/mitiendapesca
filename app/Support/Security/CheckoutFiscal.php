<?php
namespace App\Support\Security;

/**
 * CheckoutFiscal
 * Estado fiscal mínimo del cliente durante el checkout.
 *
 * MVP (listo para crecer):
 * - condición frente al IVA (CF / RI / Monotributo)
 * - CUIT (solo si aplica)
 *
 * Se guarda en sesión para que:
 * - el usuario no pierda lo que completó
 * - el checkout_intent pueda snapshotearlo (webhook no depende de sesión)
 */
final class CheckoutFiscal
{
    private const S_TAX_COND = 'checkout_customer_tax_condition';
    private const S_CUIT     = 'checkout_customer_cuit';
    private const S_VALIDATION_STATUS = 'checkout_customer_cuit_validation_status';
    private const S_LEGAL_NAME        = 'checkout_customer_cuit_legal_name';
    private const S_VALIDATION_ERROR  = 'checkout_customer_cuit_validation_error';
    private const S_VALIDATED_AT      = 'checkout_customer_cuit_validated_at';
    private const S_PREFILLED         = 'checkout_customer_fiscal_prefilled';

    /** @return array{tax_condition:string,cuit:?string,validation_status:string,legal_name:string,validation_error:string,validated_at:?string} */
    public static function getSession(): array
    {
        $cond = (string)($_SESSION[self::S_TAX_COND] ?? 'consumidor_final');
        $cuit = $_SESSION[self::S_CUIT] ?? null;

        $status = (string)($_SESSION[self::S_VALIDATION_STATUS] ?? 'unverified');
        $legal  = (string)($_SESSION[self::S_LEGAL_NAME] ?? '');
        $err    = (string)($_SESSION[self::S_VALIDATION_ERROR] ?? '');
        $vat    = $_SESSION[self::S_VALIDATED_AT] ?? null;
        $vat    = is_string($vat) ? $vat : null;

        $cond = self::normalizeTaxCondition($cond);
        $cuit = self::normalizeCuit(is_string($cuit) ? $cuit : '');

        // CF no debería persistir CUIT
        if ($cond === 'consumidor_final') {
            $cuit = null;
            $status = 'unverified';
            $legal = '';
            $err = '';
            $vat = null;
        }

        // Normaliza status
        $allowed = ['unverified','valid','invalid','pending'];
        if (!in_array($status, $allowed, true)) $status = 'unverified';

        return [
            'tax_condition' => $cond,
            'cuit' => $cuit,
            'validation_status' => $status,
            'legal_name' => $legal,
            'validation_error' => $err,
            'validated_at' => $vat,
        ];
    }

    /**
     * @param array{tax_condition?:mixed,cuit?:mixed,validation_status?:mixed,legal_name?:mixed,validation_error?:mixed,validated_at?:mixed,prefilled?:mixed} $in
     * @return array{tax_condition:string,cuit:?string,validation_status:string,legal_name:string,validation_error:string,validated_at:?string,changed:bool}
     */
    public static function saveToSession(array $in): array
    {
        $before = self::getSession();

        $cond = self::normalizeTaxCondition((string)($in['tax_condition'] ?? $before['tax_condition']));
        $cuit = self::normalizeCuit((string)($in['cuit'] ?? ($before['cuit'] ?? '')));
        if ($cond === 'consumidor_final') {
            $cuit = null;
        }

        // Si cambiaron condición o CUIT, reseteamos validación (salvo que venga explícita)
        $changedId = ($before['tax_condition'] !== $cond) || ((string)($before['cuit'] ?? '') !== (string)($cuit ?? ''));

        $status = (string)($in['validation_status'] ?? ($changedId ? 'unverified' : $before['validation_status']));
        $legal  = (string)($in['legal_name'] ?? ($changedId ? '' : $before['legal_name']));
        $err    = (string)($in['validation_error'] ?? ($changedId ? '' : $before['validation_error']));
        $vat    = $in['validated_at'] ?? ($changedId ? null : $before['validated_at']);
        $vat    = is_string($vat) ? $vat : null;

        $allowed = ['unverified','valid','invalid','pending'];
        if (!in_array($status, $allowed, true)) $status = 'unverified';

        $_SESSION[self::S_TAX_COND] = $cond;
        if ($cuit === null || $cuit === '') {
            unset($_SESSION[self::S_CUIT]);
        } else {
            $_SESSION[self::S_CUIT] = $cuit;
        }

        $_SESSION[self::S_VALIDATION_STATUS] = $status;
        $_SESSION[self::S_LEGAL_NAME] = $legal;
        if ($err === '') unset($_SESSION[self::S_VALIDATION_ERROR]); else $_SESSION[self::S_VALIDATION_ERROR] = $err;
        if ($vat === null || $vat === '') unset($_SESSION[self::S_VALIDATED_AT]); else $_SESSION[self::S_VALIDATED_AT] = $vat;

        if (array_key_exists('prefilled', $in)) {
            $_SESSION[self::S_PREFILLED] = !empty($in['prefilled']) ? 1 : 0;
        }

        $after = self::getSession();
        $changed = ($before['tax_condition'] !== $after['tax_condition'])
            || ((string)($before['cuit'] ?? '') !== (string)($after['cuit'] ?? ''))
            || ((string)($before['validation_status'] ?? '') !== (string)($after['validation_status'] ?? ''))
            || ((string)($before['legal_name'] ?? '') !== (string)($after['legal_name'] ?? ''));

        return $after + ['changed' => $changed];
    }

    /** Si el checkout aún no guardó nada en sesión, permite prefill desde perfil. */
    public static function hasSessionData(): bool
    {
        return isset($_SESSION[self::S_TAX_COND]) || isset($_SESSION[self::S_CUIT]) || isset($_SESSION[self::S_VALIDATION_STATUS]);
    }

    /**
     * Prefill 1 vez desde user_fiscal_profiles.
     * @param array<string,mixed> $profileRow
     */
    public static function prefillFromUserProfile(array $profileRow): void
    {
        if (self::hasSessionData()) return;

        $tax = (string)($profileRow['tax_condition'] ?? 'consumidor_final');
        $cuit = (string)($profileRow['cuit'] ?? '');
        $status = (string)($profileRow['validation_status'] ?? 'unverified');
        $legal = (string)($profileRow['legal_name'] ?? '');
        $err = (string)($profileRow['validation_error'] ?? '');
        $vat = $profileRow['validated_at'] ?? null;
        $vat = is_string($vat) ? $vat : null;

        self::saveToSession([
            'tax_condition' => $tax,
            'cuit' => $cuit,
            'validation_status' => $status,
            'legal_name' => $legal,
            'validation_error' => $err,
            'validated_at' => $vat,
            'prefilled' => 1,
        ]);
    }

    public static function normalizeTaxCondition(string $v): string
    {
        $v = strtolower(trim($v));
        $map = [
            'cf' => 'consumidor_final',
            'consumidor' => 'consumidor_final',
            'consumidor final' => 'consumidor_final',
            'consumidor_final' => 'consumidor_final',
            'ri' => 'responsable_inscripto',
            'responsable inscripto' => 'responsable_inscripto',
            'responsable_inscripto' => 'responsable_inscripto',
            'monotributo' => 'monotributo',
            'mono' => 'monotributo',
        ];
        $v = $map[$v] ?? $v;
        $allowed = ['consumidor_final','responsable_inscripto','monotributo'];
        if (!in_array($v, $allowed, true)) {
            $v = 'consumidor_final';
        }
        return $v;
    }

    /** Normaliza CUIT: solo dígitos, 11 chars o null. */
    public static function normalizeCuit(string $v): ?string
    {
        $digits = preg_replace('/\D+/', '', (string)$v);
        $digits = is_string($digits) ? $digits : '';
        $digits = trim($digits);
        if ($digits === '') return null;
        if (strlen($digits) !== 11) {
            // si viene incompleto lo dejamos en null para no romper el flujo
            return null;
        }
        return $digits;
    }

    /** Valida CUIT por dígito verificador (11 dígitos). */
    public static function isValidCuit(string $digits11): bool
    {
        $d = preg_replace('/\D+/', '', (string)$digits11);
        $d = is_string($d) ? $d : '';
        if (strlen($d) !== 11) return false;
        if (!ctype_digit($d)) return false;

        $weights = [5,4,3,2,7,6,5,4,3,2];
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += ((int)$d[$i]) * $weights[$i];
        }
        $mod = $sum % 11;
        $check = 11 - $mod;
        if ($check === 11) $check = 0;
        if ($check === 10) $check = 9;
        return (int)$d[10] === $check;
    }
}

