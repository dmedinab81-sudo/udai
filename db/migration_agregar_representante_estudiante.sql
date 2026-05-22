-- Migración: Agregar representante principal a la tabla estudiante
-- Fecha: 2025-05-22

-- Agregar columna id_representante_principal a la tabla estudiante
ALTER TABLE estudiante 
ADD COLUMN id_representante_principal INT(11) NULL DEFAULT NULL AFTER grado,
ADD INDEX idx_id_representante_principal (id_representante_principal) USING BTREE,
ADD CONSTRAINT fk_estudiante_representante FOREIGN KEY (id_representante_principal) 
  REFERENCES representante_legal (id) ON DELETE SET NULL ON UPDATE CASCADE;
