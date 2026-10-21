# Catalog Stage 02: Atributos de Producto

## Objetivo

Separar conceptualmente la carga de atributos del producto respecto de la generación y edición de variantes.

## Alcance de esta etapa

- agregar tab `Atributos` en el editor de producto
- dejar `Variantes` enfocada en combinaciones y filas generadas
- reutilizar el sistema existente de atributos/valores sin reescribir la lógica

## Decision aplicada

- `Atributos` concentra la selección de atributos y valores del producto
- `Variantes` se enfoca en generar y completar combinaciones
- no se duplica lógica: ambos tabs reutilizan `variantsCtx` y los mismos endpoints de admin

## Archivos involucrados

- [tab_atributos.php](C:/xampp/htdocs/mi-pesca/app/Views/partials/admin/products/tab_atributos.php)
- [tab_variantes.php](C:/xampp/htdocs/mi-pesca/app/Views/partials/admin/products/tab_variantes.php)
- [form_tabs.php](C:/xampp/htdocs/mi-pesca/app/Views/partials/admin/products/form/form_tabs.php)
- [ProductFormPageViewModel.php](C:/xampp/htdocs/mi-pesca/app/Services/Admin/Products/ViewModels/ProductFormPageViewModel.php)
- [tab_venta.php](C:/xampp/htdocs/mi-pesca/app/Views/partials/admin/products/tab_venta.php)
- [index.js](C:/xampp/htdocs/mi-pesca/public_html/assets/js/admin/product_variants/index.js)
- [bindings.js](C:/xampp/htdocs/mi-pesca/public_html/assets/js/admin/product_variants/bindings.js)

## Estado

Implementado.

## Pendiente posterior

- prueba funcional en navegador
- definir si en una etapa futura `Variantes` debe ocultar por completo la parte de configuración y quedarse solo con editor
- evaluar si conviene un tab aún más específico para atributos visibles/no filtrables

