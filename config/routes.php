<?php
return [
  // Público
  'GET /'          => 'Public\\HomeController@index',
  'GET /index.php' => 'Public\\HomeController@index',

  'GET /ayuda'      => 'public/ayuda',

  // Marketing (links cortos / campañas)
  'GET /promo'      => 'Marketing\PromoController@redirect',

  // Newsletter / Email Marketing (Etapa 1)
  'POST /newsletter'             => 'Marketing\NewsletterController@subscribe',
  'GET /newsletter/confirm'      => 'Marketing\NewsletterController@confirm',
  'GET /newsletter/unsubscribe'  => 'Marketing\NewsletterController@unsubscribe',

  // Email Marketing (Etapa 3): tracking de clicks
  'GET /email/click'            => 'Marketing\EmailClickController@click',

  // Email Marketing (Etapa 4): open tracking (pixel)
  'GET /email/open'            => 'Marketing\EmailOpenController@open',

  // Email Marketing (Etapa 5): runner vía web (fallback, con token)
  'GET /email/run'             => 'Marketing\EmailWebRunnerController@run',

  // Shipping (Etapa 4): runner tracking Correo (fallback, con token ENV)
  'GET /shipping/correo/run'   => 'Shipping\\CorreoWebRunnerController@run',

  // Catálogo
  'GET /productos' => 'Catalog\\ProductsController@index',
  'GET /productos/suggest.json' => 'Catalog\\ProductsController@suggest',
  'GET /productos/suggest-fragment' => 'Catalog\\ProductsController@suggestFragment',
  'GET /productos/quick-add' => 'Catalog\\QuickAddController@show',
  'GET /producto'  => 'Catalog\\ProductController@show',

  // Landings SEO
  'GET /categoria' => 'Catalog\\CategoryController@show',
  'GET /marca'     => 'Catalog\\BrandController@show',

  // Índices públicos (navegación)
  'GET /categorias' => 'Catalog\\CategoriesController@index',
  'GET /marcas'     => 'Catalog\\BrandsController@index',

  // Carrito + Checkout
  'GET /carrito'              => 'App\\Controllers\\Cart\\CartController@index',
  'POST /carrito/agregar'     => 'App\\Controllers\\Cart\\CartController@add',
  'POST /carrito/actualizar'  => 'App\\Controllers\\Cart\\CartController@update',
  'POST /carrito/quitar'      => 'App\\Controllers\\Cart\\CartController@remove',
  'POST /carrito/seleccion'   => 'App\\Controllers\\Cart\\CartController@selection',
  'POST /carrito/vaciar'      => 'App\\Controllers\\Cart\\CartController@clear',
  'POST /carrito/repeat/merge' => 'App\\Controllers\\Cart\\CartController@repeatMerge',
  'POST /carrito/repeat/replace' => 'App\\Controllers\\Cart\\CartController@repeatReplace',
  'POST /carrito/repeat/cancel' => 'App\\Controllers\\Cart\\CartController@repeatCancel',
  'POST /carrito/merge/unify' => 'App\\Controllers\\Cart\\CartController@mergeUnify',
  'POST /carrito/merge/replace' => 'App\\Controllers\\Cart\\CartController@mergeReplace',
  'POST /carrito/merge/discard' => 'App\\Controllers\\Cart\\CartController@mergeDiscard',

  'GET /checkout'             => 'App\\Controllers\\Checkout\\CheckoutController@index',
  'POST /checkout/confirmar'  => 'App\\Controllers\\Checkout\\CheckoutController@confirm',
  'POST /checkout/transferencia' => 'App\\Controllers\\Checkout\\CheckoutController@transferencia',
  'POST /checkout/mp/preference' => 'App\\Controllers\\Checkout\\PaymentController@preference',
  'POST /checkout/mp/payment'    => 'App\\Controllers\\Checkout\\PaymentController@payment',
  'POST /checkout/mp/brick-failed' => 'App\\Controllers\\Checkout\\PaymentController@brickFailed',
  'POST /checkout/pagar'      => 'App\\Controllers\\Checkout\\PaymentController@pay',

  'POST /checkout/envio/calcular' => 'App\\Controllers\\Checkout\\ShippingController@calculate',
  'POST /checkout/envio/seleccionar' => 'App\\Controllers\\Checkout\\ShippingController@select',
  'POST /checkout/direccion/seleccionar' => 'App\\Controllers\\Checkout\\CheckoutController@selectAddress',
  'POST /checkout/stock/preflight' => 'App\\Controllers\\Checkout\\PaymentController@stockPreflight',
  'POST /checkout/pago/seleccionar' => 'App\\Controllers\\Checkout\\PaymentController@select',
  'GET /pago/resultado'       => 'App\\Controllers\\Checkout\\PaymentController@result',

  'POST /webhooks/mercadopago' => 'Webhooks\\MercadoPagoWebhookController@handle',

  // Auth
  'GET /login'   => 'Auth\\LoginController@index',
  'POST /login'  => 'Auth\\LoginController@post',
  'POST /logout' => 'Account\\AccountController@logout',

  'GET /registro'  => 'Auth\\RegisterController@index',
  'POST /registro' => 'Auth\\RegisterController@post',

  // Legal
  'GET /terminos'   => 'legal/terminos',
  'GET /privacidad' => 'legal/privacidad',

  // Google OAuth
  'POST /oauth/google' => 'Auth\\GoogleController@oauth',

  // Cuenta
  'GET /cuenta'                 => 'Account\\AccountController@index',
  'GET /cuenta/completar'       => 'Account\\AccountController@complete',
  'POST /cuenta/sessions/clear' => 'Account\\AccountController@clearSessions',
  'GET /cuenta/lealtad'         => 'Account\\LoyaltyController@index',
  'GET /cuenta/favoritos'       => 'Account\\FavoritesController@index',
  'GET /cuenta/favoritos/toggle' => 'Account\\FavoritesController@legacyToggleRedirect',
  'POST /cuenta/favoritos/toggle' => 'Account\\FavoritesController@toggle',

  // Cuenta: Pedidos
  'GET /cuenta/pedidos'         => 'Account\\OrdersController@index',
  'GET /cuenta/pedidos/ver'     => 'Account\\OrdersController@show',
  'GET /cuenta/pedidos/factura' => 'Account\\OrdersController@facturaPdf',
  'POST /cuenta/pedidos/factura-a/solicitar' => 'Account\\OrdersController@requestFacturaA',
  'POST /cuenta/pedidos/repetir' => 'Account\\OrdersController@repeat',
  'POST /cuenta/pedidos/direccion/guardar' => 'Account\\OrdersController@updateShippingAddress',
  'POST /cuenta/pedidos/comprobante/subir' => 'Account\\OrdersController@uploadReceipt',
  'GET /cuenta/pedidos/comprobante/descargar' => 'Account\\OrdersController@downloadReceipt',
  'GET /cuenta/pedidos/comprobante/ver' => 'Account\\OrdersController@viewReceipt',

  // Perfil (mis datos)
  'GET /cuenta/perfil'          => 'Account\\ProfileController@edit',
  'POST /cuenta/perfil'         => 'Account\\ProfileController@update',
  'POST /cuenta/perfil/fiscal'  => 'Account\\FiscalProfileController@update',
  'POST /cuenta/perfil/fiscal/validar-cuit' => 'Account\\FiscalProfileController@validateCuit',

  // Cuenta: Direcciones
  'GET /cuenta/direcciones'         => 'Account\\AddressesController@index',
  'GET /cuenta/direcciones/nueva'   => 'Account\\AddressesController@createForm',
  'POST /cuenta/direcciones/crear'  => 'Account\\AddressesController@create',
  'GET /cuenta/direcciones/editar'  => 'Account\\AddressesController@editForm',
  'POST /cuenta/direcciones/guardar'=> 'Account\\AddressesController@update',
  'POST /cuenta/direcciones/eliminar'=> 'Account\\AddressesController@delete',
  'POST /cuenta/direcciones/principal'=> 'Account\\AddressesController@setDefault',

  // Email verification
  'GET /verificar-email' => 'Auth\\EmailVerificationController@verify',
  'POST /verificar-email/reenviar' => 'Auth\\EmailVerificationController@resend',


    // Password reset
  'GET /olvide-clave'  => 'Auth\\ForgotPasswordController@show',
  'POST /olvide-clave' => 'Auth\\ForgotPasswordController@send',

  'GET /reset-clave'   => 'Auth\\ResetPasswordController@show',
  'POST /reset-clave'  => 'Auth\\ResetPasswordController@update',

  // Seguridad (cuenta)
  'GET /cuenta/seguridad' => 'Auth\\ChangePasswordController@show',
  'POST /cuenta/seguridad/cambiar-clave' => 'Auth\\ChangePasswordController@update',


  // Sistema
  'GET /robots.txt'  => 'System\\MetaController@robots',
  'GET /sitemap.xml' => 'System\\MetaController@sitemap',
  'GET /sitemap_pages.xml'      => 'System\\MetaController@sitemapPages',
  'GET /sitemap_products.xml'   => 'System\\MetaController@sitemapProducts',
  'GET /sitemap_categories.xml' => 'System\\MetaController@sitemapCategories',
  'GET /sitemap_brands.xml'     => 'System\\MetaController@sitemapBrands',

  // =========================
  // Admin (vistas)
  // =========================
  'GET /admin'          => 'Admin\\DashboardController@index',
  'GET /admin/analytics' => 'Admin\\AnalyticsController@index',
  'GET /admin/settings' => 'Admin\\SettingsController@index',
  'POST /admin/settings' => 'Admin\\SettingsController@save',
  'GET /admin/settings/design/home-hero' => 'Admin\\SettingsController@designHomeHero',
  'POST /admin/settings/design/home-hero' => 'Admin\\SettingsController@saveDesignHomeHero',
  'POST /admin/settings/design/home-hero/upload-image' => 'Admin\\SettingsController@uploadHomeHeroImage',
  'POST /admin/settings/design/home-hero/delete-image' => 'Admin\\SettingsController@deleteHomeHeroImage',
  'GET /admin/settings/design/home-sections' => 'Admin\\SettingsController@designHomeSections',
  'POST /admin/settings/design/home-sections' => 'Admin\\SettingsController@saveDesignHomeSections',

  // Envíos (Correo Argentino: MiCorreo / PAQ.AR)
  'POST /admin/settings/envios/correo/test' => 'Admin\\CorreoArgentinoController@testConnection',
  'POST /admin/orders/shipping/correo/track' => 'Admin\\CorreoArgentinoController@trackOrder',
  'POST /admin/orders/shipping/correo/import' => 'Admin\\CorreoArgentinoController@importOrder',

  // ARCA (Admin): diagnóstico rápido
  'GET /admin/arca/test' => 'Admin\\ArcaController@test',
  'GET /admin/arca/test.json' => 'Admin\\ArcaController@testJson',

  // Facturación (ARCA) - Comprobantes emitidos / pendientes
  'GET /admin/facturacion/comprobantes' => 'Admin\\ComprobantesFiscalesController@index',
  'GET /admin/facturacion/comprobantes/show' => 'Admin\\ComprobantesFiscalesController@show',
  'GET /admin/facturacion/comprobantes/pdf' => 'Admin\\ComprobantesFiscalesController@pdf',
  'POST /admin/facturacion/comprobantes/consultar' => 'Admin\\ComprobantesFiscalesController@consultar',
  'POST /admin/facturacion/comprobantes/retry-emit' => 'Admin\\ComprobantesFiscalesController@retryEmit',
  'POST /admin/facturacion/comprobantes/resend-email' => 'Admin\\ComprobantesFiscalesController@resendEmail',

  // Control fiscal (navegación y resumen)
  'GET /admin/facturacion' => 'Admin\\FacturacionControlController@index',
  'GET /admin/facturacion/control' => 'Admin\\FacturacionControlController@index',

  // Facturación (ARCA) - Reportes / export / conciliación
  'GET /admin/facturacion/libro-iva' => 'Admin\\LibroIvaController@index',
  'GET /admin/facturacion/libro-iva/export' => 'Admin\\LibroIvaController@export',
  'GET /admin/facturacion/libro-iva-compras' => 'Admin\\LibroIvaComprasController@index',
  'GET /admin/facturacion/libro-iva-compras/export' => 'Admin\\LibroIvaComprasController@export',

  // Facturación (ARCA) - Compras (IVA compras)
  'GET /admin/facturacion/compras' => 'Admin\\ComprasController@index',
  'GET /admin/facturacion/compras/new' => 'Admin\\ComprasController@form',
  'GET /admin/facturacion/compras/edit' => 'Admin\\ComprasController@form',
  'POST /admin/facturacion/compras/save' => 'Admin\\ComprasController@save',
  'POST /admin/facturacion/compras/adjunto/subir' => 'Admin\\ComprasController@uploadAttachment',
  'POST /admin/facturacion/compras/adjunto/eliminar' => 'Admin\\ComprasController@deleteAttachment',
  'GET /admin/facturacion/compras/adjunto/ver' => 'Admin\\ComprasController@viewAttachment',
  'GET /admin/facturacion/compras/adjunto/descargar' => 'Admin\\ComprasController@downloadAttachment',

    'POST /admin/facturacion/compras/validate' => 'Admin\\ComprasController@validate',
  'POST /admin/facturacion/compras/void' => 'Admin\\ComprasController@void',
  'GET /admin/facturacion/reportes' => 'Admin\\ReportesFiscalesController@index',
  'GET /admin/facturacion/reportes/resumen' => 'Admin\\ReportesFiscalesController@resumen',
  'GET /admin/facturacion/reportes/export' => 'Admin\\ReportesFiscalesController@export',
  'GET /admin/facturacion/reportes/drill-comprobantes' => 'Admin\\ReportesFiscalesController@drillComprobantes',
  'GET /admin/facturacion/reportes/mensual' => 'Admin\\ReporteMensualIvaController@index',
  'GET /admin/facturacion/reportes/mensual/export' => 'Admin\\ReporteMensualIvaController@export',
  'GET /admin/facturacion/reportes/saldo-iva' => 'Admin\ReporteSaldoIvaController@index',
  'GET /admin/facturacion/reportes/saldo-iva/export' => 'Admin\ReporteSaldoIvaController@export',

  // Alertas operativas
  'GET /admin/facturacion/alertas' => 'Admin\FacturacionAlertasController@index',

// Facturación - Cierres mensuales (Snapshot contable)
'GET /admin/facturacion/cierres-mensuales' => 'Admin\AccountingClosuresController@index',
'GET /admin/facturacion/cierres-mensuales/show' => 'Admin\AccountingClosuresController@show',
'POST /admin/facturacion/cierres-mensuales/close' => 'Admin\AccountingClosuresController@close',
'GET /admin/facturacion/cierres-mensuales/export' => 'Admin\AccountingClosuresController@export',

  'GET /admin/facturacion/reportes/pedidos-sin-factura' => 'Admin\\ReportesFiscalesController@pedidosSinFactura',
  'GET /admin/facturacion/reportes/comprobantes-pendientes' => 'Admin\\ReportesFiscalesController@comprobantesPendientes',
  'GET /admin/facturacion/reportes/desvios' => 'Admin\\ReportesFiscalesController@desvios',
  'GET /admin/facturacion/reportes/duplicados-exactos' => 'Admin\\ReportesFiscalesController@duplicadosExactos',
  'GET /admin/facturacion/reportes/pedidos-no-facturables' => 'Admin\\ReportesFiscalesController@pedidosNoFacturables',

  // Marketing (Admin)
'GET /admin/marketing/campanas'      => 'Admin\\MarketingCampaignsController@index',
'GET /admin/marketing/campanas/new'  => 'Admin\\MarketingCampaignsController@form',
'GET /admin/marketing/campanas/edit' => 'Admin\\MarketingCampaignsController@form',
'GET /admin/marketing/campanas/show' => 'Admin\\MarketingCampaignsController@show',
'GET /admin/marketing/campanas/qr'   => 'Admin\\MarketingCampaignsController@qr',
'GET /admin/marketing/campanas/export' => 'Admin\\MarketingCampaignsController@export',
'GET /admin/marketing/campanas/export_one' => 'Admin\\MarketingCampaignsController@exportOne',
'POST /admin/marketing/campanas/save'  => 'Admin\\MarketingCampaignsController@save',
'POST /admin/marketing/campanas/toggle' => 'Admin\\MarketingCampaignsController@toggle',
'POST /admin/marketing/settings/save' => 'Admin\\MarketingCampaignsController@saveSettings',

// Admin → Marketing → Email (Etapa 1)
'GET /admin/marketing/email' => 'Admin\\EmailMarketingController@index',
'GET /admin/marketing/email/suscriptores' => 'Admin\\EmailMarketingController@subscribers',
'POST /admin/marketing/email/suscriptores/estado' => 'Admin\\EmailMarketingController@setSubscriberStatus',
'GET /admin/marketing/email/automatizaciones' => 'Admin\\EmailMarketingController@automations',
'POST /admin/marketing/email/automatizaciones/toggle' => 'Admin\\EmailMarketingController@toggleAutomation',
'POST /admin/marketing/email/pasos/toggle' => 'Admin\\EmailMarketingController@toggleStep',
'GET /admin/marketing/email/cola' => 'Admin\\EmailMarketingController@queue',
'POST /admin/marketing/email/cola/procesar' => 'Admin\\EmailMarketingController@runNow',

// Admin → Marketing → Email (Etapa 3): campañas broadcast
'GET /admin/marketing/email/campanas' => 'Admin\\EmailCampaignsController@index',
'GET /admin/marketing/email/campanas/new' => 'Admin\\EmailCampaignsController@form',
'GET /admin/marketing/email/campanas/edit' => 'Admin\\EmailCampaignsController@form',
'GET /admin/marketing/email/campanas/show' => 'Admin\\EmailCampaignsController@show',
'POST /admin/marketing/email/campanas/save' => 'Admin\\EmailCampaignsController@save',
'POST /admin/marketing/email/campanas/preview' => 'Admin\\EmailCampaignsController@preview',
'POST /admin/marketing/email/campanas/send_test' => 'Admin\\EmailCampaignsController@sendTest',

// Admin → Marketing → Email (Etapa 4): biblioteca de templates
'GET /admin/marketing/email/templates' => 'Admin\\EmailTemplatesController@index',
'GET /admin/marketing/email/templates/show' => 'Admin\\EmailTemplatesController@show',
'POST /admin/marketing/email/templates/create' => 'Admin\\EmailTemplatesController@create',
'POST /admin/marketing/email/templates/delete' => 'Admin\\EmailTemplatesController@delete',
'POST /admin/marketing/email/templates/apply' => 'Admin\\EmailTemplatesController@applyToCampaign',

// Picker de productos para el editor de campañas
'GET /admin/marketing/email/products/search' => 'Admin\\EmailProductsController@search',
'POST /admin/marketing/email/uploads/image' => 'Admin\\EmailCampaignsController@uploadImage',
'POST /admin/marketing/email/campanas/schedule_now' => 'Admin\\EmailCampaignsController@scheduleNow',
'POST /admin/marketing/email/campanas/cancel' => 'Admin\\EmailCampaignsController@cancel',


  // Admin (datos JSON del dashboard)
  'GET /admin/dashboard/trend.json'      => 'Admin\\DashboardDataController@trend',
  'GET /admin/dashboard/kpis.json'       => 'Admin\\DashboardDataController@kpis',
  'GET /admin/dashboard/low-stock.json'  => 'Admin\\DashboardDataController@lowStock',

  // Auditoría (Admin)
  'GET /admin/audit'        => 'Admin\\AuditController@index',
  'GET /admin/audit/show'   => 'Admin\\AuditController@show',
  'GET /admin/audit/logins' => 'Admin\\AuditController@logins',
  'POST /admin/audit/logins/cleanup' => 'Admin\\AuditController@loginsCleanup',

  // Users (Admin)
  'GET /admin/users'       => 'Admin\\UsersController@index',
  'GET /admin/users/show'  => 'Admin\\UsersController@show',
  'POST /admin/users/role' => 'Admin\\UsersController@role',
  'POST /admin/users/force-logout' => 'Admin\\UsersController@forceLogout',

  // Products (Admin)

  // SEO (Admin)
  'GET /admin/seo/auditoria'      => 'Admin\\SeoAuditController@index',
  'GET /admin/products'               => 'Admin\\ProductsController@index',
  'GET /admin/inventario'             => 'Admin\\InventoryController@index',
  'GET /admin/products/new'           => 'Admin\\ProductsController@form',
  'GET /admin/products/edit'          => 'Admin\\ProductsController@form',
  'GET /admin/products/variants'      => 'Admin\\ProductVariantsController@listPage',
  'GET /admin/products/wizard-status.json' => 'Admin\\ProductsController@wizardStatus',
  'POST /admin/products/save'         => 'Admin\\ProductsController@save',
  'POST /admin/products/update-iva.json' => 'Admin\\ProductsController@updateIvaJson',
  'POST /admin/products/toggle'       => 'Admin\\ProductsController@toggle',
  'POST /admin/products/image-upload' => 'Admin\\ProductsController@imageUpload',
  'POST /admin/products/image-delete' => 'Admin\\ProductsController@imageDelete',
  'POST /admin/products/image-cover'  => 'Admin\\ProductsController@imageCover',
  'POST /admin/products/image-assign' => 'Admin\\ProductsController@imageAssign',
  'POST /admin/products/image-bulk-assign' => 'Admin\\ProductsController@imageBulkAssign',
  'POST /admin/products/image-bulk-delete' => 'Admin\\ProductsController@imageBulkDelete',
  'POST /admin/products/image-sort'   => 'Admin\\ProductsController@imageSort',

  // Brands (Admin)
  'GET /admin/proveedores'          => 'Admin\\ProveedoresController@index',
  'GET /admin/proveedores/new'      => 'Admin\\ProveedoresController@form',
  'GET /admin/proveedores/edit'     => 'Admin\\ProveedoresController@form',
  'POST /admin/proveedores/save'    => 'Admin\\ProveedoresController@save',
  'POST /admin/proveedores/delete'  => 'Admin\\ProveedoresController@delete',

  'GET /admin/brands'         => 'Admin\\BrandsController@index',
  'GET /admin/brands/new'     => 'Admin\\BrandsController@form',
  'GET /admin/brands/edit'    => 'Admin\\BrandsController@form',
  'POST /admin/brands/save'   => 'Admin\\BrandsController@save',
  'POST /admin/brands/toggle' => 'Admin\\BrandsController@toggle',
  'POST /admin/brands/quick'  => 'Admin\\BrandsController@quick',

  // Categories (Admin)
  'GET /admin/categories'         => 'Admin\\CategoriesController@index',
  'GET /admin/categories/new'     => 'Admin\\CategoriesController@form',
  'GET /admin/categories/edit'    => 'Admin\\CategoriesController@form',
  'POST /admin/categories/save'   => 'Admin\\CategoriesController@save',
  'POST /admin/categories/toggle' => 'Admin\\CategoriesController@toggle',
  'POST /admin/categories/quick'  => 'Admin\\CategoriesController@quick',


  // Attributes (Admin)
  'GET /admin/attributes'          => 'Admin\\AttributesController@index',
  'GET /admin/attributes/new'      => 'Admin\\AttributesController@form',
  'GET /admin/attributes/edit'     => 'Admin\\AttributesController@form',
  'GET /admin/attributes/catalog.json' => 'Admin\\AttributesController@catalogJson',
  'POST /admin/attributes/save'    => 'Admin\\AttributesController@save',
  'POST /admin/attributes/quick'   => 'Admin\\AttributesController@quick',
  'POST /admin/attributes/value/save'   => 'Admin\\AttributesController@valueSave',
  'POST /admin/attributes/value/delete' => 'Admin\\AttributesController@valueDelete',
  'POST /admin/attributes/value/delete.json' => 'Admin\\AttributesController@valueDeleteJson',
  'GET /admin/attributes/templates/items-fragment' => 'Admin\\AttributesController@valueSetItemsFragment',
'GET /admin/attributes/templates/edit-fragment' => 'Admin\AttributesController@valueSetEditFragment',
'POST /admin/attributes/templates/edit-fragment' => 'Admin\\AttributesController@valueSetEditFragment',
'POST /admin/attributes/templates/save.json' => 'Admin\AttributesController@valueSetSaveJson',
  'POST /admin/attributes/templates/delete.json' => 'Admin\\AttributesController@valueSetDeleteJson',

  // Catalog Tags (Admin)
  'GET /admin/catalog-tags' => 'Admin\\CatalogTagsController@index',
  'POST /admin/catalog-tags/group/save' => 'Admin\\CatalogTagsController@saveGroup',
  'POST /admin/catalog-tags/tag/save' => 'Admin\\CatalogTagsController@saveTag',

  // Variants (Admin, por producto)
  'GET /admin/products/variants.json'          => 'Admin\\ProductVariantsController@index',
  'GET /admin/products/variants/fragment'      => 'Admin\\ProductVariantsController@fragment',
  'POST /admin/products/variants/attributes/add'    => 'Admin\\ProductVariantsController@attributeAdd',
  'POST /admin/products/variants/attributes/remove-preview' => 'Admin\\ProductVariantsController@attributeRemovePreview',
  'POST /admin/products/variants/attributes/remove' => 'Admin\\ProductVariantsController@attributeRemove',
  'POST /admin/products/variants/attributes/update' => 'Admin\\ProductVariantsController@attributeUpdate',
  'POST /admin/products/variants/values/set'        => 'Admin\\ProductVariantsController@valuesSet',
  'POST /admin/products/variants/values/create'     => 'Admin\\ProductVariantsController@valuesCreate',
  'GET /admin/products/variants/values/suggest-fragment' => 'Admin\\ProductVariantsController@valuesSuggestFragment',
  'POST /admin/products/variants/sets/import'          => 'Admin\\ProductVariantsController@importValueSet',
  'POST /admin/products/variants/sets/create'          => 'Admin\\ProductVariantsController@createValueSetFromProduct',
  'POST /admin/products/variants/generate'     => 'Admin\\ProductVariantsController@generate',
  'POST /admin/products/variants/save'         => 'Admin\\ProductVariantsController@saveVariant',
  'POST /admin/products/variants/toggle'       => 'Admin\\ProductVariantsController@toggle',
  'POST /admin/products/variants/delete'       => 'Admin\\ProductVariantsController@deleteVariant',
  'POST /admin/products/variants/delete-incomplete' => 'Admin\\ProductVariantsController@deleteIncomplete',
  'POST /admin/products/variants/cleanup-obsolete' => 'Admin\\ProductVariantsController@cleanupObsolete',
  // Imports (Admin)
  'GET /admin/imports'         => 'Admin\\ImportsController@index',
  // FIX538: flujo en 2 pasos (preview -> aplicar)
  'POST /admin/imports/preview' => 'Admin\\ImportsController@preview',
  'POST /admin/imports/apply'   => 'Admin\\ImportsController@apply',
  // Compatibilidad: viejas UIs postean a /create
  'POST /admin/imports/create'  => 'Admin\\ImportsController@preview',
  'POST /admin/imports/process' => 'Admin\\ImportsController@process',
  'GET /admin/imports/show'    => 'Admin\\ImportsController@show',
    'POST /admin/imports/rollback' => 'Admin\\ImportsController@rollback',
  'GET /admin/imports/template' => 'Admin\\ImportsController@template',
  'GET /admin/imports/export'   => 'Admin\\ImportsController@export',

  // Orders (Admin)
  'GET /admin/orders'         => 'Admin\\OrdersController@index',
  'GET /admin/orders/show'    => 'Admin\\OrdersController@show',
  'GET /admin/orders/comprobante/descargar' => 'Admin\\OrdersController@downloadReceipt',
  'GET /admin/orders/comprobante/ver' => 'Admin\\OrdersController@viewReceipt',
  'POST /admin/orders/mark-paid' => 'Admin\\OrdersController@markPaid',
  'POST /admin/orders/status'   => 'Admin\\OrdersController@status',
  'POST /admin/orders/arca/emit' => 'Admin\\OrdersController@emitArca',
  'POST /admin/orders/arca/emit-nc' => 'Admin\\OrdersController@emitArcaCreditNote',

  // Solicitudes de Factura A (Admin) — Etapa 3
  'POST /admin/fiscal/requests/revalidate' => 'Admin\\FiscalRequestsController@revalidate',
  'POST /admin/fiscal/requests/approve'    => 'Admin\\FiscalRequestsController@approve',
  'POST /admin/fiscal/requests/reject'     => 'Admin\\FiscalRequestsController@reject',
  'POST /admin/fiscal/requests/emit-a'     => 'Admin\\FiscalRequestsController@emitA',

  'POST /admin/orders/shipping' => 'Admin\\OrderShippingController@update',

  // (Fallbacks opcionales para compatibilidad si tenías el flujo viejo)
    'GET /admin/dashboard/trend' => 'Admin\\DashboardController@trend',
];
