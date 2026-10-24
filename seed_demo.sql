-- Datos ficticios para probar el dashboard y el historial.
-- Ejecute este archivo después de database.sql. No modifica usuarios existentes.
USE control_asistencia;

-- INSERT IGNORE permite volver a ejecutar el archivo sin repetir empleados.
INSERT IGNORE INTO empleados
  (cedula, nombre, correo, telefono, codigo_acceso, estado)
VALUES
  ('9-9000-0001', 'Ana Solís', 'ana.solis@example.com', '8888-1001', 'A7F294', 1),
  ('9-9000-0002', 'Carlos Rojas', 'carlos.rojas@example.com', '8888-1002', 'B8C315', 1),
  ('9-9000-0003', 'Lucía Mora', 'lucia.mora@example.com', '8888-1003', 'C2D674', 1),
  ('9-9000-0004', 'Marco Vargas', 'marco.vargas@example.com', '8888-1004', 'D9E803', 0);

-- Ana completó un turno hoy; sirve para comprobar las horas trabajadas.
INSERT INTO asistencias (empleado_id, fecha, hora_entrada, hora_salida)
SELECT e.id, CURDATE(), TIMESTAMP(CURDATE(), '07:58:00'), TIMESTAMP(CURDATE(), '17:04:00')
FROM empleados e
WHERE e.cedula = '9-9000-0001'
  AND NOT EXISTS (
    SELECT 1 FROM asistencias a
    WHERE a.empleado_id = e.id AND a.fecha = CURDATE()
      AND a.hora_entrada = TIMESTAMP(CURDATE(), '07:58:00')
  );

-- Carlos tiene una entrada abierta y aparecerá como persona dentro.
INSERT INTO asistencias (empleado_id, fecha, hora_entrada, hora_salida)
SELECT e.id, CURDATE(), TIMESTAMP(CURDATE(), '09:10:00'), NULL
FROM empleados e
WHERE e.cedula = '9-9000-0002'
  AND NOT EXISTS (
    SELECT 1 FROM asistencias a
    WHERE a.empleado_id = e.id AND a.fecha = CURDATE()
      AND a.hora_entrada = TIMESTAMP(CURDATE(), '09:10:00')
  );

-- Lucía registra un turno terminado hoy y otro el día anterior.
INSERT INTO asistencias (empleado_id, fecha, hora_entrada, hora_salida)
SELECT e.id, CURDATE(), TIMESTAMP(CURDATE(), '08:15:00'), TIMESTAMP(CURDATE(), '16:45:00')
FROM empleados e
WHERE e.cedula = '9-9000-0003'
  AND NOT EXISTS (
    SELECT 1 FROM asistencias a
    WHERE a.empleado_id = e.id AND a.fecha = CURDATE()
      AND a.hora_entrada = TIMESTAMP(CURDATE(), '08:15:00')
  );

INSERT INTO asistencias (empleado_id, fecha, hora_entrada, hora_salida)
SELECT e.id, CURDATE() - INTERVAL 1 DAY,
  TIMESTAMP(CURDATE() - INTERVAL 1 DAY, '08:03:00'),
  TIMESTAMP(CURDATE() - INTERVAL 1 DAY, '17:02:00')
FROM empleados e
WHERE e.cedula = '9-9000-0003'
  AND NOT EXISTS (
    SELECT 1 FROM asistencias a
    WHERE a.empleado_id = e.id AND a.fecha = CURDATE() - INTERVAL 1 DAY
      AND a.hora_entrada = TIMESTAMP(CURDATE() - INTERVAL 1 DAY, '08:03:00')
  );
