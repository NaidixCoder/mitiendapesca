<?php
return [
  // Público
  'GET /'                       => 'public/home',
  'GET /index.php'              => 'public/home',

  // Catálogo
  'GET /productos'              => 'Catalog\\ProductsController@index',
  'GET /producto'               => 'Catalog\\ProductController@show',

  // Auth
  'GET /login'                  => 'auth/login',
  'POST /login'                 => 'auth/login',
  'GET /registro'               => 'auth/registro',
  'POST /registro'              => 'auth/registro',
  'POST /oauth/google'          => 'auth/oauth_google',
  'POST /logout'                => 'auth/logout',

  // Cuenta
  'GET /cuenta'                 => 'cuenta/index',
  'POST /cuenta/sessions/clear' => 'cuenta/sessions_clear',

  // Sistema
  'GET /robots.txt'             => 'system/robot',
  'GET /sitemap.xml'            => 'system/sitemap',

  // Admin
  'GET /admin'                        => 'Admin\\DashboardController@index',
  'GET /admin/dashboard/chart-data'   => 'Admin\\DashboardController@chartData',
  'GET /admin/settings'               => 'Admin\\SettingsController@index',

  // Users (Admin)
  'GET /admin/users'                  => 'Admin\\UsersController@index',
  'GET /admin/users/show'             => 'Admin\\UsersController@show',
  'POST /admin/users/role'            => 'Admin\\UsersController@role',

  // Products (Admin)
  'GET /admin/products'               => 'Admin\\ProductsController@index',
  'GET /admin/products/new'           => 'Admin\\ProductsController@form',
  'GET /admin/products/edit'          => 'Admin\\ProductsController@form',
  'POST /admin/products/save'         => 'Admin\\ProductsController@save',
  'POST /admin/products/toggle'       => 'Admin\\ProductsController@toggle',
  'POST /admin/products/image-upload' => 'Admin\\ProductsController@imageUpload',
  'POST /admin/products/image-delete' => 'Admin\\ProductsController@imageDelete',
  'POST /admin/products/image-cover'  => 'Admin\\ProductsController@imageCover',

  // Imports (Admin)
  'GET /admin/imports'                => 'Admin\\ImportsController@index',
  'GET /admin/imports/upload'         => 'Admin\\ImportsController@upload',
  'POST /admin/imports/upload'        => 'Admin\\ImportsController@uploadPost',

  // Admin → Brands
  'GET /admin/brands'                 => 'Admin\\BrandsController@index',
  'GET /admin/brands/new'             => 'Admin\\BrandsController@form',
  'GET /admin/brands/edit'            => 'Admin\\BrandsController@form',
  'POST /admin/brands/save'           => 'Admin\\BrandsController@save',
  'POST /admin/brands/toggle'         => 'Admin\\BrandsController@toggle',
  'POST /admin/brands/quick'          => 'Admin\\BrandsController@quick',   // alta inline

  // Admin → Categories
  'GET /admin/categories'             => 'Admin\\CategoriesController@index',
  'GET /admin/categories/new'         => 'Admin\\CategoriesController@form',
  'GET /admin/categories/edit'        => 'Admin\\CategoriesController@form',
  'POST /admin/categories/save'       => 'Admin\\CategoriesController@save',
  'POST /admin/categories/toggle'     => 'Admin\\CategoriesController@toggle',
  'POST /admin/categories/quick'      => 'Admin\\CategoriesController@quick', // alta inline
];
