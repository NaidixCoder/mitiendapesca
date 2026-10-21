<a
    href="<?= base_url('carrito') ?>"
    class="ph-icon-btn ph-cart-btn"
    aria-label="Ver carrito"
    title="Carrito"
    data-cart-icon
    data-cart-icon-context="<?= e((string)($cartIconContext ?? 'shared')) ?>"
>
    <?= ui_icon('cart-outline', 'ph-icon') ?>
    <span
        class="ph-cart-count <?= $cartQty > 0 ?'' : 'ph-is-hidden' ?>"
        data-cart-count>
        <?= $cartQty > 99 ? '99+' : (string)$cartQty ?>
    </span>
</a>
