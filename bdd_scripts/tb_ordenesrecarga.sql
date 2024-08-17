-- Table: logistica.tb_ordenestrabajo
DROP TABLE IF EXISTS logistica.tb_ordenestrabajo;

CREATE TABLE IF NOT EXISTS logistica.tb_ordenesrecarga (
    orden_id integer NOT NULL DEFAULT nextval(
        'logistica.tb_ordenesrecarga_orden_id_seq' :: regclass
    ),
    orden_registro timestamp without time zone DEFAULT ('now' :: text) :: timestamp(0) with time zone,
    orden_estado text COLLATE pg_catalog."default" DEFAULT 'EMITIDA' :: text,
    fk_usuario_id integer NOT NULL,
    fk_unidad_id integer NOT NULL,
    fk_estacion_id integer NOT NULL,
    fk_ppersonal_id integer,
    responsable_unidad integer NOT NULL,
    tecnico_servicios integer NOT NULL,
    orden_codigo text COLLATE pg_catalog."default" NOT NULL,
    orden_serie integer NOT NULL DEFAULT 0,
    orden_tipo text COLLATE pg_catalog."default",
    orden_destino text COLLATE pg_catalog."default" NOT NULL,
    orden_fecha_emision timestamp without time zone DEFAULT ('now' :: text) :: timestamp(0) with time zone,
    orden_fecha_validez date,
    orden_fecha_utilizacion date,
    orden_observaciones text COLLATE pg_catalog."default",
    tecnico_puesto text COLLATE pg_catalog."default" DEFAULT 'TÉCNICO EN ATENCIÓN PREHOSPITALARIA' :: text,
    CONSTRAINT tb_ordenestrabajo_pkey PRIMARY KEY (orden_id),
    CONSTRAINT tb_ordenestrabajo_responsable_unidad_fkey FOREIGN KEY (responsable_unidad) REFERENCES tthh.tb_personal_puestos (ppersonal_id) MATCH SIMPLE ON UPDATE NO ACTION ON DELETE NO ACTION,
    CONSTRAINT tb_ordenestrabajo_fk_ppersonal_fkey FOREIGN KEY (fk_ppersonal_id) REFERENCES tthh.tb_personal_puestos (ppersonal_id) MATCH SIMPLE ON UPDATE NO ACTION ON DELETE NO ACTION,
    CONSTRAINT tb_ordenestrabajo_fk_estacion_id_fkey FOREIGN KEY (fk_estacion_id) REFERENCES tthh.tb_estaciones (estacion_id) MATCH SIMPLE ON UPDATE NO ACTION ON DELETE NO ACTION,
    CONSTRAINT tb_ordenestrabajo_fk_unidad_id_fkey FOREIGN KEY (fk_unidad_id) REFERENCES logistica.tb_unidades (unidad_id) MATCH SIMPLE ON UPDATE NO ACTION ON DELETE NO ACTION,
    CONSTRAINT tb_ordenestrabajo_fk_usuario_id_fkey FOREIGN KEY (fk_usuario_id) REFERENCES admin.tb_usuarios (usuario_id) MATCH SIMPLE ON UPDATE NO ACTION ON DELETE NO ACTION,
    -- CONSTRAINT tb_ordenestrabajo_operador_id_fkey FOREIGN KEY (operador_id) REFERENCES tthh.tb_conductores (conductor_id) MATCH SIMPLE ON UPDATE NO ACTION ON DELETE NO ACTION,
    CONSTRAINT tb_ordenestrabajo_tecnico_servicios_fkey FOREIGN KEY (tecnico_servicios) REFERENCES tthh.tb_personal_puestos (ppersonal_id) MATCH SIMPLE ON UPDATE NO ACTION ON DELETE NO ACTION
) WITH (OIDS = FALSE) TABLESPACE pg_default;

ALTER TABLE
    IF EXISTS logistica.tb_ordenesrecarga OWNER to postgres;

-- Trigger: actions_on_after
-- DROP TRIGGER IF EXISTS actions_on_after ON logistica.tb_ordenesrecarga;
CREATE TRIGGER actions_on_after
AFTER
INSERT
    OR
UPDATE
    ON logistica.tb_ordenesrecarga FOR EACH ROW EXECUTE PROCEDURE logistica.actions_on_after();

-- Trigger: validations_on_before
-- DROP TRIGGER IF EXISTS validations_on_before ON logistica.tb_ordenesrecarga;
CREATE TRIGGER validations_on_before BEFORE
INSERT
    OR
UPDATE
    ON logistica.tb_ordenesrecarga FOR EACH ROW EXECUTE PROCEDURE logistica.validations_on_before();

ALTER TABLE
    logistica.tb_ordenesrecarga DISABLE TRIGGER validations_on_before;