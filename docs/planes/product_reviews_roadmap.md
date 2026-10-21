# Roadmap Resenas y Valoracion

## Objetivo
Implementar un sistema de resenas reales por producto, ligado a compras verificadas, para habilitar opinion publica, promedio de valoracion y orden `mejor_valorado` en catalogo.

## Alcance recomendado
Primera version:
- solo usuarios con compra verificada pueden dejar resena
- 1 resena por producto por usuario o por item comprado
- moderacion desde Admin
- mostrar promedio y cantidad de resenas en ficha de producto
- actualizar `products.rating` y `products.reviews`

## Modelo de datos
Tabla sugerida: `product_reviews`
- `id`
- `product_id`
- `user_id`
- `order_id`
- `order_item_id` nullable
- `rating` tinyint unsigned
- `title` varchar nullable
- `body` text nullable
- `status` enum: `pending`, `approved`, `rejected`
- `is_verified_purchase` tinyint(1)
- `created_at`
- `updated_at`
- `approved_at` nullable
- `approved_by` nullable

Indices sugeridos:
- `(product_id, status, created_at)`
- `(user_id, product_id)`
- `(order_id)`
- unique para evitar duplicado segun regla elegida

Campos cacheados en `products`
- `rating` decimal(2,1)
- `reviews` int unsigned

## Reglas de negocio
1. Solo puede dejar resena un usuario logueado con compra verificada del producto.
2. La compra deberia estar en estado entregado, completado o equivalente.
3. No permitir resena duplicada para el mismo producto segun criterio definido.
4. La resena nueva no impacta frontend hasta estar `approved`, salvo que decidas autoaprobar.
5. Al aprobar, editar o rechazar una resena, recalcular:
- `products.rating`
- `products.reviews`

## Donde interactua el usuario
### Opcion principal
Cuenta > Mis pedidos > detalle del pedido
- CTA: `Valorar producto`
- mejor momento: despues de entrega o finalizacion

### Opcion secundaria
Ficha del producto
- mostrar opiniones aprobadas
- si el usuario cumple condiciones, permitir `Dejar resena`
- si no cumple, solo lectura

### Opcion futura
Email post compra
- disparar invitacion 7 a 10 dias despues de entrega

## Pantallas a agregar
### Publico
- bloque `Opiniones` en pagina de producto
- formulario de resena para usuario elegible
- listado de resenas aprobadas

### Cuenta
- CTA por item comprado dentro de pedido
- estado: pendiente de resena / resena enviada

### Admin
- listado de resenas
- filtros por estado, producto, fecha
- aprobar, rechazar, editar, ocultar

## Arquitectura sugerida
### Backend
- `app/Controllers/Account/ProductReviewsController.php`
- `app/Controllers/Admin/ProductReviewsController.php`
- `app/Repositories/Catalog/ProductReviewRepository.php`
- `app/Services/Catalog/ProductReviewService.php`
- `app/Services/Catalog/ProductRatingAggregatorService.php`

### Views
- partial de reviews en pagina de producto
- vista admin index/show o panel simple de moderacion
- CTA en pedidos de cuenta

## Flujo tecnico
1. Usuario elegible envia resena.
2. Se guarda en `product_reviews` con `status = pending`.
3. Admin aprueba.
4. Servicio agregador recalcula promedio y cantidad.
5. `products.rating` y `products.reviews` quedan listos para:
- ficha de producto
- cards
- orden `mejor_valorado`

## Fases recomendadas
### Fase 1
- tabla `product_reviews`
- submit desde cuenta/pedido
- admin moderacion basica
- recalculo de rating/reviews

### Fase 2
- mostrar reviews en pagina de producto
- estrellas promedio y cantidad visible
- activar `mejor_valorado` en catalogo

### Fase 3
- email post compra
- fotos en resenas
- votos utiles
- respuesta del negocio

## Decisiones pendientes
1. Una resena por producto por usuario o por item comprado.
2. Autoaprobacion o moderacion manual.
3. Si las resenas editadas vuelven a `pending`.
4. Estado de pedido minimo habilitante para reseñar.
5. Si mostrar solo texto o tambien titulo y fotos.

## Recomendacion
No activar `mejor_valorado` hasta que exista:
- dato real
- moderacion
- compra verificada
- recalculo confiable del promedio
