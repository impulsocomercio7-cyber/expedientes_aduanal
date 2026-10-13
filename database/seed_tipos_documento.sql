USE expedientes_aduanal;
-- Inserta el catálogo inicial solo si está vacío.
INSERT INTO tipos_documento
 (nombre, aplica_fisica, aplica_moral, obligatorio, bloqueante, requiere_emision, requiere_vencimiento, orden)
SELECT x.nombre, x.aplica_fisica, x.aplica_moral, x.obligatorio, x.bloqueante,
       x.requiere_emision, x.requiere_vencimiento, x.orden
FROM (
 SELECT 'Carta de encomienda' AS nombre,1 AS aplica_fisica,1 AS aplica_moral,1 AS obligatorio,1 AS bloqueante,0 AS requiere_emision,0 AS requiere_vencimiento,10 AS orden UNION ALL
 SELECT 'Contrato de prestación de servicios (imprimir a doble cara)',1,1,1,1,0,0,20 UNION ALL
 SELECT 'Carta bajo protesta de decir verdad — Regla 1.4.14',1,1,1,1,0,0,30 UNION ALL
 SELECT 'Carta bajo protesta de datos del importador',1,1,1,1,0,0,40 UNION ALL
 SELECT 'Constancia de Situación Fiscal (reciente)',1,1,1,1,1,0,50 UNION ALL
 SELECT 'Opinión de Cumplimiento SAT (reciente)',1,1,1,1,1,1,60 UNION ALL
 SELECT 'Opinión de cumplimiento IMSS o carta responsiva / opinión sin obligaciones patronales',1,1,1,1,1,1,70 UNION ALL
 SELECT 'Identificación oficial del representante legal',1,1,1,1,0,1,80 UNION ALL
 SELECT 'Comprobante de domicilio donde realiza sus actividades',1,1,1,1,1,1,90 UNION ALL
 SELECT 'Estado de cuenta bancario de la cuenta para transacciones',1,1,1,1,1,0,100 UNION ALL
 SELECT 'Fotografía de la fachada del establecimiento',1,1,1,0,0,0,110 UNION ALL
 SELECT 'Aviso de apertura del establecimiento en el SAT',1,1,1,1,0,0,120 UNION ALL
 SELECT 'Verificación de domicilio SAT o solicitud de verificación',1,1,1,1,0,0,130 UNION ALL
 SELECT 'Contrato de arrendamiento u otro documento de posesión del inmueble',1,1,1,0,1,1,140 UNION ALL
 SELECT 'Escrituras o predial (inmueble propio)',1,1,1,0,1,1,150 UNION ALL
 SELECT 'Fotografía y factura de vehículo(s) de uso empresarial',1,1,1,0,1,0,160 UNION ALL
 SELECT 'Acta constitutiva',0,1,1,1,0,0,170 UNION ALL
 SELECT 'Poder notarial del representante legal (si no está incluido en el acta)',0,1,0,0,0,0,180
) AS x
WHERE NOT EXISTS (SELECT 1 FROM tipos_documento LIMIT 1);
