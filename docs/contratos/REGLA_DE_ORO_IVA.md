# Regla de Oro IVA (Tienda Pesca)

## Contrato único (sin magia)

1) **El costo siempre es SIN IVA (costo neto).**
   - En CSV y en Admin, `costo/cost` significa “precio del proveedor sin IVA”.

2) **El IVA se carga aparte.**
   - `iva_rate` es la alícuota y se normaliza a rate decimal.

3) **El costo final (con IVA) no se guarda como verdad: se deriva.**
   - `costo_final = costo_neto * (1 + iva_rate)`
