export function initOrderAddressModalBridge() {
  const openButtons = document.querySelectorAll('[data-order-address-edit-open]');
  if (!openButtons.length) return;

  openButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      const modal = document.getElementById('checkout-address-modal');
      const form = modal ? modal.querySelector('form[data-address-form]') : null;
      if (form) {
        let orderIdField = form.querySelector('input[name="order_id"]');
        if (!orderIdField) {
          orderIdField = document.createElement('input');
          orderIdField.type = 'hidden';
          orderIdField.name = 'order_id';
          form.appendChild(orderIdField);
        }
        orderIdField.value = String(btn.dataset.orderId || '');
      }

      if (typeof window.checkoutOpenAddressModal === 'function') {
        window.checkoutOpenAddressModal({
          mode: 'edit',
          meta: {
            id: btn.dataset.orderId || '',
            fullName: btn.dataset.name || '',
            phone: btn.dataset.phone || '',
            street: btn.dataset.street || '',
            number: btn.dataset.number || '',
            floor: btn.dataset.floor || '',
            apartment: btn.dataset.apartment || '',
            zip: btn.dataset.zip || '',
            city: btn.dataset.city || '',
            state: btn.dataset.state || '',
            notes: btn.dataset.notes || '',
          },
        });
      }
    });
  });
}
