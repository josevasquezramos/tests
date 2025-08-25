<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared("

CREATE TRIGGER trg_md_bi_asignar
BEFORE INSERT ON maleta_detalles
FOR EACH ROW
BEGIN
  IF NEW.herramienta_id IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'herramienta_id requerido';
  END IF;

  UPDATE herramientas
     SET stock = stock - 1,
         asignadas = asignadas + 1
   WHERE id = NEW.herramienta_id
     AND stock >= 1;

  IF ROW_COUNT() = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock insuficiente para asignar herramienta';
  END IF;

  IF NEW.ultimo_estado IS NULL OR NEW.ultimo_estado <> 'OPERATIVO' THEN
    SET NEW.ultimo_estado = 'OPERATIVO';
  END IF;
  SET NEW.deleted_at = NULL;
END

        ");

        DB::unprepared("

CREATE TRIGGER trg_md_bu_cambiar_herramienta
BEFORE UPDATE ON maleta_detalles
FOR EACH ROW
BEGIN
  IF NEW.herramienta_id <> OLD.herramienta_id THEN
    UPDATE herramientas
       SET asignadas = asignadas - 1,
           stock     = stock + 1
     WHERE id = OLD.herramienta_id
       AND asignadas >= 1;
    IF ROW_COUNT() = 0 THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Asignadas insuficientes en herramienta anterior';
    END IF;

    UPDATE herramientas
       SET stock     = stock - 1,
           asignadas = asignadas + 1
     WHERE id = NEW.herramienta_id
       AND stock >= 1;
    IF ROW_COUNT() = 0 THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock insuficiente en herramienta nueva';
    END IF;

    IF NEW.ultimo_estado IS NULL OR NEW.ultimo_estado <> 'OPERATIVO' THEN
      SET NEW.ultimo_estado = 'OPERATIVO';
    END IF;
    SET NEW.deleted_at = NULL;
  END IF;
END

        ");

        DB::unprepared("

CREATE TRIGGER trg_md_bd_liberar
BEFORE DELETE ON maleta_detalles
FOR EACH ROW
BEGIN
  IF (OLD.ultimo_estado IS NULL OR OLD.ultimo_estado = 'OPERATIVO') THEN
    UPDATE herramientas
       SET asignadas = asignadas - 1,
           stock     = stock + 1
     WHERE id = OLD.herramienta_id
       AND asignadas >= 1;
    IF ROW_COUNT() = 0 THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Asignadas insuficientes al eliminar detalle';
    END IF;
  END IF;
END

        ");

        DB::unprepared("

CREATE TRIGGER trg_cm_bi_set_propietario
BEFORE INSERT ON control_maletas
FOR EACH ROW
BEGIN
  DECLARE v_prop BIGINT UNSIGNED;
  IF NEW.propietario_id IS NULL THEN
    SELECT propietario_id INTO v_prop
      FROM maletas
      WHERE id = NEW.maleta_id
      LIMIT 1;
    IF v_prop IS NOT NULL THEN
      SET NEW.propietario_id = v_prop;
    END IF;
  END IF;
END

        ");

        DB::unprepared("

CREATE TRIGGER trg_cm_ai_crear_detalles
AFTER INSERT ON control_maletas
FOR EACH ROW
BEGIN
  INSERT INTO control_maleta_detalles
    (control_maleta_id, maleta_detalle_id, herramienta_id, estado, prev_estado, prev_deleted_at)
  SELECT NEW.id, md.id, md.herramienta_id, 'OPERATIVO', md.ultimo_estado, md.deleted_at
    FROM maleta_detalles md
   WHERE md.maleta_id = NEW.maleta_id
     AND md.deleted_at IS NULL;
END

        ");

        DB::unprepared("

CREATE TRIGGER trg_cm_bd_revertir_cascada
BEFORE DELETE ON control_maletas
FOR EACH ROW
BEGIN
  DECLARE v_dummy INT;

  SELECT 1 INTO v_dummy
  FROM (
    SELECT h.id
      FROM herramientas h
      JOIN (
        SELECT herramienta_id, COUNT(*) cnt
          FROM control_maleta_detalles
         WHERE control_maleta_id = OLD.id AND estado = 'MERMA'
         GROUP BY herramienta_id
      ) x ON x.herramienta_id = h.id
     WHERE h.mermas < x.cnt
     LIMIT 1
  ) t;
  IF v_dummy = 1 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No hay mermas suficientes para revertir al eliminar el control';
  END IF;

  SELECT 1 INTO v_dummy
  FROM (
    SELECT h.id
      FROM herramientas h
      JOIN (
        SELECT herramienta_id, COUNT(*) cnt
          FROM control_maleta_detalles
         WHERE control_maleta_id = OLD.id AND estado = 'PERDIDO'
         GROUP BY herramienta_id
      ) y ON y.herramienta_id = h.id
     WHERE h.perdidas < y.cnt
     LIMIT 1
  ) t2;
  IF v_dummy = 1 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No hay perdidas suficientes para revertir al eliminar el control';
  END IF;

  UPDATE herramientas h
  JOIN (
    SELECT herramienta_id, COUNT(*) cnt
      FROM control_maleta_detalles
     WHERE control_maleta_id = OLD.id AND estado = 'MERMA'
     GROUP BY herramienta_id
  ) x ON x.herramienta_id = h.id
     SET h.mermas = h.mermas - x.cnt,
         h.asignadas = h.asignadas + x.cnt;

  UPDATE herramientas h
  JOIN (
    SELECT herramienta_id, COUNT(*) cnt
      FROM control_maleta_detalles
     WHERE control_maleta_id = OLD.id AND estado = 'PERDIDO'
     GROUP BY herramienta_id
  ) y ON y.herramienta_id = h.id
     SET h.perdidas = h.perdidas - y.cnt,
         h.asignadas = h.asignadas + y.cnt;

  UPDATE maleta_detalles md
  JOIN control_maleta_detalles cmd
    ON cmd.maleta_detalle_id = md.id
   AND cmd.control_maleta_id = OLD.id
     SET md.ultimo_estado = cmd.prev_estado,
         md.deleted_at    = cmd.prev_deleted_at;
END

        ");

        DB::unprepared("

CREATE TRIGGER trg_cmd_bi_guardasaneidad
BEFORE INSERT ON control_maleta_detalles
FOR EACH ROW
BEGIN
  DECLARE v_h BIGINT UNSIGNED;
  DECLARE v_maleta BIGINT UNSIGNED;
  DECLARE v_maleta_control BIGINT UNSIGNED;
  DECLARE v_prev_estado ENUM('OPERATIVO','MERMA','PERDIDO');
  DECLARE v_prev_deleted_at DATETIME;

  SELECT herramienta_id, maleta_id, ultimo_estado, deleted_at
    INTO v_h, v_maleta, v_prev_estado, v_prev_deleted_at
    FROM maleta_detalles
   WHERE id = NEW.maleta_detalle_id
   LIMIT 1;

  IF v_h IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'maleta_detalle inexistente al crear control detalle';
  END IF;

  SELECT maleta_id INTO v_maleta_control
    FROM control_maletas
   WHERE id = NEW.control_maleta_id
   LIMIT 1;

  IF v_maleta_control IS NULL OR v_maleta_control <> v_maleta THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El maleta_detalle no pertenece a la maleta del control';
  END IF;

  SET NEW.herramienta_id = v_h;

  IF NEW.prev_estado IS NULL THEN
    SET NEW.prev_estado = v_prev_estado;
  END IF;
  IF NEW.prev_deleted_at IS NULL THEN
    SET NEW.prev_deleted_at = v_prev_deleted_at;
  END IF;
END

        ");

        DB::unprepared("

CREATE TRIGGER trg_cmd_bu_transiciones
BEFORE UPDATE ON control_maleta_detalles
FOR EACH ROW
BEGIN
  DECLARE v_old ENUM('OPERATIVO','MERMA','PERDIDO');
  DECLARE v_new ENUM('OPERATIVO','MERMA','PERDIDO');
  DECLARE old_norm ENUM('OPERATIVO','MERMA','PERDIDO');
  DECLARE new_norm ENUM('OPERATIVO','MERMA','PERDIDO');

  DECLARE v_md BIGINT UNSIGNED;
  DECLARE v_h  BIGINT UNSIGNED;

  SET v_old = OLD.estado;
  SET v_new = NEW.estado;

  SELECT herramienta_id
    INTO v_h
    FROM maleta_detalles
   WHERE id = OLD.maleta_detalle_id
   LIMIT 1;
  IF v_h IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'maleta_detalle inexistente';
  END IF;

  SET NEW.herramienta_id = v_h;
  SET v_md = OLD.maleta_detalle_id;

  IF NOT (v_old <=> v_new) THEN
    SET old_norm = IFNULL(v_old, 'OPERATIVO');
    SET new_norm = IFNULL(v_new, 'OPERATIVO');

    IF old_norm = 'OPERATIVO' AND new_norm = 'MERMA' THEN
      UPDATE herramientas
         SET asignadas = asignadas - 1,
             mermas    = mermas + 1
       WHERE id = v_h AND asignadas >= 1;
      IF ROW_COUNT() = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No hay asignadas suficientes para MERMA';
      END IF;
      UPDATE maleta_detalles
         SET ultimo_estado = 'MERMA',
             deleted_at    = IFNULL(deleted_at, NOW())
       WHERE id = v_md;

    ELSEIF old_norm = 'OPERATIVO' AND new_norm = 'PERDIDO' THEN
      UPDATE herramientas
         SET asignadas = asignadas - 1,
             perdidas  = perdidas + 1
       WHERE id = v_h AND asignadas >= 1;
      IF ROW_COUNT() = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No hay asignadas suficientes para PERDIDO';
      END IF;
      UPDATE maleta_detalles
         SET ultimo_estado = 'PERDIDO',
             deleted_at    = IFNULL(deleted_at, NOW())
       WHERE id = v_md;

    ELSEIF old_norm = 'MERMA' AND new_norm = 'OPERATIVO' THEN
      UPDATE herramientas
         SET mermas    = mermas - 1,
             asignadas = asignadas + 1
       WHERE id = v_h AND mermas >= 1;
      IF ROW_COUNT() = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No hay mermas suficientes para volver a OPERATIVO';
      END IF;
      UPDATE maleta_detalles
         SET ultimo_estado = 'OPERATIVO',
             deleted_at    = NULL
       WHERE id = v_md;

    ELSEIF old_norm = 'PERDIDO' AND new_norm = 'OPERATIVO' THEN
      UPDATE herramientas
         SET perdidas  = perdidas - 1,
             asignadas = asignadas + 1
       WHERE id = v_h AND perdidas >= 1;
      IF ROW_COUNT() = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No hay perdidas suficientes para volver a OPERATIVO';
      END IF;
      UPDATE maleta_detalles
         SET ultimo_estado = 'OPERATIVO',
             deleted_at    = NULL
       WHERE id = v_md;

    ELSEIF old_norm = 'MERMA' AND new_norm = 'PERDIDO' THEN
      UPDATE herramientas
         SET mermas   = mermas - 1,
             perdidas = perdidas + 1
       WHERE id = v_h AND mermas >= 1;
      IF ROW_COUNT() = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No hay mermas suficientes para mover a PERDIDO';
      END IF;
      UPDATE maleta_detalles
         SET ultimo_estado = 'PERDIDO',
             deleted_at    = IFNULL(deleted_at, NOW())
       WHERE id = v_md;

    ELSEIF old_norm = 'PERDIDO' AND new_norm = 'MERMA' THEN
      UPDATE herramientas
         SET perdidas = perdidas - 1,
             mermas   = mermas + 1
       WHERE id = v_h AND perdidas >= 1;
      IF ROW_COUNT() = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No hay perdidas suficientes para mover a MERMA';
      END IF;
      UPDATE maleta_detalles
         SET ultimo_estado = 'MERMA',
             deleted_at    = IFNULL(deleted_at, NOW())
       WHERE id = v_md;
    END IF;
  END IF;
END

        ");

        DB::unprepared("

CREATE TRIGGER trg_cmd_bd_revertir_en_delete
BEFORE DELETE ON control_maleta_detalles
FOR EACH ROW
BEGIN
  DECLARE v_h BIGINT UNSIGNED;
  SET v_h = OLD.herramienta_id;

  IF OLD.estado = 'MERMA' THEN
    UPDATE herramientas
       SET mermas    = mermas - 1,
           asignadas = asignadas + 1
     WHERE id = v_h AND mermas >= 1;
    IF ROW_COUNT() = 0 THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No hay mermas suficientes para revertir al eliminar el detalle';
    END IF;
  ELSEIF OLD.estado = 'PERDIDO' THEN
    UPDATE herramientas
       SET perdidas  = perdidas - 1,
           asignadas = asignadas + 1
     WHERE id = v_h AND perdidas >= 1;
    IF ROW_COUNT() = 0 THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No hay perdidas suficientes para revertir al eliminar el detalle';
    END IF;
  END IF;

  UPDATE maleta_detalles
     SET ultimo_estado = OLD.prev_estado,
         deleted_at    = OLD.prev_deleted_at
   WHERE id = OLD.maleta_detalle_id;
END

        ");

        DB::unprepared("

CREATE TRIGGER trg_hed_bi_sumar_stock
BEFORE INSERT ON herramienta_entrada_detalles
FOR EACH ROW
BEGIN
  UPDATE herramientas
     SET stock = stock + NEW.cantidad
   WHERE id = NEW.herramienta_id;
END

        ");

        DB::unprepared("

CREATE TRIGGER trg_hed_bu_ajustar_stock
BEFORE UPDATE ON herramienta_entrada_detalles
FOR EACH ROW
BEGIN
  IF NEW.herramienta_id <> OLD.herramienta_id THEN
    UPDATE herramientas
       SET stock = stock - OLD.cantidad
     WHERE id = OLD.herramienta_id
       AND stock >= OLD.cantidad;
    IF ROW_COUNT() = 0 THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock insuficiente para mover detalle de entrada';
    END IF;

    UPDATE herramientas
       SET stock = stock + NEW.cantidad
     WHERE id = NEW.herramienta_id;
  ELSE
    IF NEW.cantidad <> OLD.cantidad THEN
      IF NEW.cantidad > OLD.cantidad THEN
        UPDATE herramientas
           SET stock = stock + (NEW.cantidad - OLD.cantidad)
         WHERE id = NEW.herramienta_id;
      ELSE
        UPDATE herramientas
           SET stock = stock - (OLD.cantidad - NEW.cantidad)
         WHERE id = NEW.herramienta_id
           AND stock >= (OLD.cantidad - NEW.cantidad);
        IF ROW_COUNT() = 0 THEN
          SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock insuficiente para reducir cantidad en detalle de entrada';
        END IF;
      END IF;
    END IF;
  END IF;
END

        ");

        DB::unprepared("

CREATE TRIGGER trg_hed_bd_restar_stock
BEFORE DELETE ON herramienta_entrada_detalles
FOR EACH ROW
BEGIN
  UPDATE herramientas
     SET stock = stock - OLD.cantidad
   WHERE id = OLD.herramienta_id
     AND stock >= OLD.cantidad;
  IF ROW_COUNT() = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock insuficiente para eliminar detalle de entrada';
  END IF;
END

        ");

        DB::unprepared("

CREATE TRIGGER trg_he_bd_revertir_cascada
BEFORE DELETE ON herramienta_entradas
FOR EACH ROW
BEGIN
  DECLARE v_dummy INT;

  SELECT 1 INTO v_dummy
  FROM (
    SELECT h.id
      FROM herramientas h
      JOIN (
        SELECT herramienta_id, SUM(cantidad) cnt
          FROM herramienta_entrada_detalles
         WHERE herramienta_entrada_id = OLD.id
         GROUP BY herramienta_id
      ) x ON x.herramienta_id = h.id
     WHERE h.stock < x.cnt
     LIMIT 1
  ) t;
  IF v_dummy = 1 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock insuficiente para borrar la entrada (reversión)';
  END IF;

  UPDATE herramientas h
  JOIN (
    SELECT herramienta_id, SUM(cantidad) cnt
      FROM herramienta_entrada_detalles
     WHERE herramienta_entrada_id = OLD.id
     GROUP BY herramienta_id
  ) x ON x.herramienta_id = h.id
     SET h.stock = h.stock - x.cnt;
END

        ");

        DB::unprepared("

CREATE TRIGGER trg_hi_bi_prep
BEFORE INSERT ON herramienta_incidencias
FOR EACH ROW
BEGIN
  DECLARE v_h  BIGINT UNSIGNED;
  DECLARE v_md BIGINT UNSIGNED;
  DECLARE v_maleta BIGINT UNSIGNED;
  DECLARE v_prop BIGINT UNSIGNED;
  DECLARE v_prev_estado ENUM('OPERATIVO','MERMA','PERDIDO');
  DECLARE v_prev_deleted_at DATETIME;

  SET v_md = NEW.maleta_detalle_id;

  SELECT herramienta_id, maleta_id, ultimo_estado, deleted_at
    INTO v_h, v_maleta, v_prev_estado, v_prev_deleted_at
    FROM maleta_detalles
   WHERE id = v_md
   LIMIT 1;

  IF v_h IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'maleta_detalle inexistente';
  END IF;

  SELECT propietario_id INTO v_prop
    FROM maletas
   WHERE id = v_maleta
   LIMIT 1;

  IF NEW.propietario_id IS NULL THEN
    SET NEW.propietario_id = v_prop;
  END IF;

  SET NEW.prev_estado    = v_prev_estado;
  SET NEW.prev_deleted_at= v_prev_deleted_at;

  IF NEW.motivo = 'MERMA' THEN
    UPDATE herramientas
       SET asignadas = asignadas - 1,
           mermas    = mermas + 1
     WHERE id = v_h AND asignadas >= 1;
    IF ROW_COUNT() = 0 THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No hay asignadas suficientes para MERMA';
    END IF;
    UPDATE maleta_detalles
       SET ultimo_estado = 'MERMA',
           deleted_at    = IFNULL(deleted_at, NOW())
     WHERE id = v_md;

  ELSEIF NEW.motivo = 'PERDIDO' THEN
    UPDATE herramientas
       SET asignadas = asignadas - 1,
           perdidas  = perdidas + 1
     WHERE id = v_h AND asignadas >= 1;
    IF ROW_COUNT() = 0 THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No hay asignadas suficientes para PERDIDO';
    END IF;
    UPDATE maleta_detalles
       SET ultimo_estado = 'PERDIDO',
           deleted_at    = IFNULL(deleted_at, NOW())
     WHERE id = v_md;
  END IF;
END

        ");

        DB::unprepared("

CREATE TRIGGER trg_hi_bu_transiciones
BEFORE UPDATE ON herramienta_incidencias
FOR EACH ROW
BEGIN
  DECLARE v_h_old BIGINT UNSIGNED;
  DECLARE v_h_new BIGINT UNSIGNED;
  DECLARE v_maleta_new BIGINT UNSIGNED;
  DECLARE v_prop_new BIGINT UNSIGNED;
  DECLARE v_prev_estado_new ENUM('OPERATIVO','MERMA','PERDIDO');
  DECLARE v_prev_deleted_new DATETIME;

  IF NEW.maleta_detalle_id <> OLD.maleta_detalle_id THEN
    SELECT herramienta_id INTO v_h_old FROM maleta_detalles WHERE id = OLD.maleta_detalle_id LIMIT 1;

    IF OLD.motivo = 'MERMA' THEN
      UPDATE herramientas
         SET mermas = mermas - 1,
             asignadas = asignadas + 1
       WHERE id = v_h_old AND mermas >= 1;
      IF ROW_COUNT() = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No hay mermas suficientes para revertir (cambio de MD)';
      END IF;
    ELSEIF OLD.motivo = 'PERDIDO' THEN
      UPDATE herramientas
         SET perdidas = perdidas - 1,
             asignadas = asignadas + 1
       WHERE id = v_h_old AND perdidas >= 1;
      IF ROW_COUNT() = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No hay perdidas suficientes para revertir (cambio de MD)';
      END IF;
    END IF;

    UPDATE maleta_detalles
       SET ultimo_estado = OLD.prev_estado,
           deleted_at    = OLD.prev_deleted_at
     WHERE id = OLD.maleta_detalle_id;

    SELECT herramienta_id, maleta_id, ultimo_estado, deleted_at
      INTO v_h_new, v_maleta_new, v_prev_estado_new, v_prev_deleted_new
      FROM maleta_detalles
     WHERE id = NEW.maleta_detalle_id
     LIMIT 1;

    IF v_h_new IS NULL THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nuevo maleta_detalle inexistente';
    END IF;

    SELECT propietario_id INTO v_prop_new FROM maletas WHERE id = v_maleta_new LIMIT 1;
    SET NEW.propietario_id  = v_prop_new;
    SET NEW.prev_estado     = v_prev_estado_new;
    SET NEW.prev_deleted_at = v_prev_deleted_new;

    IF NEW.motivo = 'MERMA' THEN
      UPDATE herramientas
         SET asignadas = asignadas - 1,
             mermas    = mermas + 1
       WHERE id = v_h_new AND asignadas >= 1;
      IF ROW_COUNT() = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No hay asignadas suficientes para MERMA (nuevo MD)';
      END IF;
      UPDATE maleta_detalles
         SET ultimo_estado = 'MERMA',
             deleted_at    = IFNULL(deleted_at, NOW())
       WHERE id = NEW.maleta_detalle_id;

    ELSEIF NEW.motivo = 'PERDIDO' THEN
      UPDATE herramientas
         SET asignadas = asignadas - 1,
             perdidas  = perdidas + 1
       WHERE id = v_h_new AND asignadas >= 1;
      IF ROW_COUNT() = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No hay asignadas suficientes para PERDIDO (nuevo MD)';
      END IF;
      UPDATE maleta_detalles
         SET ultimo_estado = 'PERDIDO',
             deleted_at    = IFNULL(deleted_at, NOW())
       WHERE id = NEW.maleta_detalle_id;
    END IF;

  ELSEIF NEW.motivo <> OLD.motivo THEN
    SELECT herramienta_id INTO v_h_old FROM maleta_detalles WHERE id = OLD.maleta_detalle_id LIMIT 1;

    IF OLD.motivo = 'MERMA' AND NEW.motivo = 'PERDIDO' THEN
      UPDATE herramientas
         SET mermas = mermas - 1,
             perdidas = perdidas + 1
       WHERE id = v_h_old AND mermas >= 1;
      IF ROW_COUNT() = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No hay mermas suficientes para cambiar a PERDIDO';
      END IF;
      UPDATE maleta_detalles
         SET ultimo_estado = 'PERDIDO',
             deleted_at    = IFNULL(deleted_at, NOW())
       WHERE id = OLD.maleta_detalle_id;

    ELSEIF OLD.motivo = 'PERDIDO' AND NEW.motivo = 'MERMA' THEN
      UPDATE herramientas
         SET perdidas = perdidas - 1,
             mermas   = mermas + 1
       WHERE id = v_h_old AND perdidas >= 1;
      IF ROW_COUNT() = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No hay perdidas suficientes para cambiar a MERMA';
      END IF;
      UPDATE maleta_detalles
         SET ultimo_estado = 'MERMA',
             deleted_at    = IFNULL(deleted_at, NOW())
       WHERE id = OLD.maleta_detalle_id;
    END IF;
  END IF;
END

        ");

        DB::unprepared("

CREATE TRIGGER trg_hi_bd_revertir
BEFORE DELETE ON herramienta_incidencias
FOR EACH ROW
BEGIN
  DECLARE v_h BIGINT UNSIGNED;
  SELECT herramienta_id INTO v_h FROM maleta_detalles WHERE id = OLD.maleta_detalle_id LIMIT 1;

  IF OLD.motivo = 'MERMA' THEN
    UPDATE herramientas
       SET mermas = mermas - 1,
           asignadas = asignadas + 1
     WHERE id = v_h AND mermas >= 1;
    IF ROW_COUNT() = 0 THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No hay mermas suficientes para revertir incidencia';
    END IF;

  ELSEIF OLD.motivo = 'PERDIDO' THEN
    UPDATE herramientas
       SET perdidas = perdidas - 1,
           asignadas = asignadas + 1
     WHERE id = v_h AND perdidas >= 1;
    IF ROW_COUNT() = 0 THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No hay perdidas suficientes para revertir incidencia';
    END IF;
  END IF;

  UPDATE maleta_detalles
     SET ultimo_estado = OLD.prev_estado,
         deleted_at    = OLD.prev_deleted_at
   WHERE id = OLD.maleta_detalle_id;
END

        ");

        DB::unprepared("

CREATE TRIGGER trg_md_bu_toggle_activo
BEFORE UPDATE ON maleta_detalles
FOR EACH ROW
BEGIN
  IF OLD.ultimo_estado = 'OPERATIVO'
     AND (NEW.ultimo_estado <=> OLD.ultimo_estado) THEN

    IF OLD.deleted_at IS NULL AND NEW.deleted_at IS NOT NULL THEN
      UPDATE herramientas
         SET asignadas = asignadas - 1,
             stock     = stock + 1
       WHERE id = OLD.herramienta_id
         AND asignadas >= 1;
      IF ROW_COUNT() = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Asignadas insuficientes para desasignar';
      END IF;

    ELSEIF OLD.deleted_at IS NOT NULL AND NEW.deleted_at IS NULL THEN
      UPDATE herramientas
         SET stock     = stock - 1,
             asignadas = asignadas + 1
       WHERE id = OLD.herramienta_id
         AND stock >= 1;
      IF ROW_COUNT() = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock insuficiente para re-asignar';
      END IF;
    END IF;
  END IF;
END

        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
