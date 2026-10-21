-- FIX556: habilitar roles mayoristas en users.role
-- IMPORTANTE: este cambio es necesario para poder asignar mayorista1/mayorista2 desde Admin.
-- Es una alteración segura (no destructiva) ampliando el ENUM.

ALTER TABLE users
  MODIFY role ENUM('admin','customer','mayorista1','mayorista2') NOT NULL DEFAULT 'customer';
