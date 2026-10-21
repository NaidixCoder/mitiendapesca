# Refactor Roadmap

## ETAPA 0 (completada)

Se reorganizaron controladores de Checkout y Cart para iniciar modularizacion sin romper rutas existentes.

### Cambios aplicados
- Se crearon namespaces anidados para controladores core:
  - `App\\Controllers\\Checkout\\CheckoutController`
  - `App\\Controllers\\Checkout\\PaymentController`
  - `App\\Controllers\\Checkout\\ShippingController`
  - `App\\Controllers\\Cart\\CartController`
- Se conservaron los handlers actuales en `config/routes.php` sin cambios.
- Se dejaron facades de compatibilidad en `app/Controllers/` con nombres legacy:
  - `CheckoutController`
  - `CheckoutPaymentController`
  - `CheckoutShippingController`
  - `CartController`
- Las facades solo delegan en las clases nuevas, sin logica funcional.
- Se verifico autoload PSR-4 (`App\\ => app/`) y se corrio `composer dump-autoload`.
- Se agrego `scripts/build_gate.sh` para validacion de lint + smoke de clases.

## Mapa inicial de modulos

### Checkout (owner: capa Checkout)
- Responsabilidad:
  - Flujo de checkout (pasos, validaciones, confirmaciones de compra)
  - Seleccion de envio y pago en sesion
  - Integracion con servicios de checkout/envios
- Limites:
  - No renderiza ni administra catalogo/productos
  - No maneja autenticacion base (usa guards globales/helpers)
  - No debe incorporar reglas de Admin

### Cart (owner: capa Cart)
- Responsabilidad:
  - Operaciones de carrito (alta, baja, actualizacion, vaciado)
  - Flujos de merge/repeat
  - Respuestas HTML/JSON segun contexto de request
- Limites:
  - No decide reglas de checkout final
  - No contiene logica de pagos/envios externos
  - No mezcla concerns de cuenta/admin

## ETAPA 1 (plan, no implementado)

1. Checkout: extraer logica pesada de controladores a servicios de aplicacion dedicados por caso de uso.
2. Checkout: normalizar contratos de snapshot de envio/pago y fortalecer validaciones server-side.
3. Cart: separar handlers HTML vs API para reducir branching por `wantsJson()`.
4. Cart + Checkout: agregar pruebas de smoke/regresion por rutas clave (`/carrito`, `/checkout`, pago/envio).
5. Rutas: evaluar migracion gradual a handlers namespaced (`Checkout\\...`, `Cart\\...`) cuando no se necesite facade.
6. Observabilidad: agregar checks automatizados para detectar handlers de ruta sin metodo implementado.
