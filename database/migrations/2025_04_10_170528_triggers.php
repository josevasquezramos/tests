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
        // Trigger after delete
        DB::unprepared('
            CREATE TRIGGER `articulo_salidas_after_delete` AFTER DELETE ON `articulo_salidas` FOR EACH ROW BEGIN
                DECLARE v_fraccionable BOOLEAN;
                DECLARE v_contenido DECIMAL(10,2);
                DECLARE v_restante DECIMAL(10,2);
                
                SELECT fraccionable, contenido
                INTO v_fraccionable, v_contenido
                FROM articulos WHERE id = OLD.articulo_id;
                
                IF v_fraccionable THEN
                    IF OLD.articulo_abierto_id IS NOT NULL THEN
                        UPDATE articulo_abiertos 
                        SET restante = restante + OLD.cantidad,
                            deleted_at = NULL,
                            updated_at = NOW()
                        WHERE id = OLD.articulo_abierto_id;
                        
                        SELECT restante INTO v_restante 
                        FROM articulo_abiertos 
                        WHERE id = OLD.articulo_abierto_id;
                        
                        IF v_restante >= v_contenido THEN
                            DELETE FROM articulo_abiertos WHERE id = OLD.articulo_abierto_id;
                            UPDATE articulos SET stock = stock + 1 WHERE id = OLD.articulo_id;
                        END IF;
                    END IF;
                ELSE
                    UPDATE articulos 
                    SET stock = stock + OLD.cantidad
                    WHERE id = OLD.articulo_id;
                END IF;
            END
        ');

        // Trigger before insert
        DB::unprepared('
            CREATE TRIGGER `articulo_salidas_before_insert` BEFORE INSERT ON `articulo_salidas` FOR EACH ROW BEGIN
                DECLARE v_fraccionable BOOLEAN;
                DECLARE v_contenido DECIMAL(10,2);
                DECLARE v_stock DECIMAL(10,2);
                DECLARE v_precio DECIMAL(10,2);
                DECLARE v_unidad_id INT;
                DECLARE v_restante DECIMAL(10,2);
                
                SELECT fraccionable, contenido, stock, precio, unidad_id
                INTO v_fraccionable, v_contenido, v_stock, v_precio, v_unidad_id
                FROM articulos WHERE id = NEW.articulo_id;
                
                SET NEW.unidad_id = v_unidad_id;
                
                IF v_fraccionable THEN
                    SET NEW.precio = v_precio / v_contenido;
                ELSE
                    SET NEW.precio = v_precio;
                END IF;
                
                IF NEW.articulo_abierto_id IS NULL THEN
                    IF v_fraccionable THEN
                        IF NEW.cantidad > v_contenido THEN
                            SIGNAL SQLSTATE "45000"
                            SET MESSAGE_TEXT = "Cantidad excede el contenido del artículo";
                        END IF;
                        
                        IF v_stock < 1 THEN
                            SIGNAL SQLSTATE "45000"
                            SET MESSAGE_TEXT = "Stock insuficiente";
                        END IF;
                        
                        UPDATE articulos SET stock = stock - 1 WHERE id = NEW.articulo_id;
                        
                        INSERT INTO articulo_abiertos (articulo_id, restante, created_at, updated_at)
                        VALUES (NEW.articulo_id, v_contenido - NEW.cantidad, NOW(), NOW());
                        
                        SET NEW.articulo_abierto_id = LAST_INSERT_ID();
                        
                        -- Check if restante is zero or negative and mark as deleted
                        SET v_restante = v_contenido - NEW.cantidad;
                        IF v_restante <= 0 THEN
                            UPDATE articulo_abiertos 
                            SET deleted_at = NOW(),
                                updated_at = NOW()
                            WHERE id = NEW.articulo_abierto_id;
                        END IF;
                    ELSE
                        IF NEW.cantidad > v_stock THEN
                            SIGNAL SQLSTATE "45000"
                            SET MESSAGE_TEXT = "Stock insuficiente";
                        END IF;
                        
                        UPDATE articulos SET stock = stock - NEW.cantidad WHERE id = NEW.articulo_id;
                    END IF;
                ELSE
                    IF NOT EXISTS (
                        SELECT 1 FROM articulo_abiertos
                        WHERE id = NEW.articulo_abierto_id
                        AND deleted_at IS NULL
                        AND restante >= NEW.cantidad
                    ) THEN
                        SIGNAL SQLSTATE "45000"
                        SET MESSAGE_TEXT = "Abierto no válido o cantidad excede el restante";
                    END IF;
                    
                    UPDATE articulo_abiertos 
                    SET restante = restante - NEW.cantidad,
                        updated_at = NOW()
                    WHERE id = NEW.articulo_abierto_id;
                    
                    SELECT restante INTO v_restante FROM articulo_abiertos WHERE id = NEW.articulo_abierto_id;
                    
                    IF v_restante <= 0 THEN
                        UPDATE articulo_abiertos 
                        SET deleted_at = NOW(),
                            updated_at = NOW()
                        WHERE id = NEW.articulo_abierto_id;
                    END IF;
                END IF;
            END
        ');

        DB::unprepared('
            CREATE TRIGGER `articulo_salidas_before_update` BEFORE UPDATE ON `articulo_salidas` FOR EACH ROW BEGIN
                DECLARE v_fraccionable BOOLEAN;
                DECLARE v_contenido DECIMAL(10,2);
                DECLARE v_restante DECIMAL(10,2);
                
                SELECT fraccionable, contenido
                INTO v_fraccionable, v_contenido
                FROM articulos WHERE id = OLD.articulo_id;
                
                IF v_fraccionable THEN
                    IF OLD.articulo_abierto_id IS NOT NULL THEN
                        UPDATE articulo_abiertos 
                        SET restante = restante + OLD.cantidad - NEW.cantidad,
                            updated_at = NOW(),
                            deleted_at = NULL
                        WHERE id = OLD.articulo_abierto_id;
                        
                        SELECT restante INTO v_restante 
                        FROM articulo_abiertos 
                        WHERE id = OLD.articulo_abierto_id;
                        
                        IF v_restante < 0 THEN
                            SIGNAL SQLSTATE "45000"
                            SET MESSAGE_TEXT = "Cantidad excede el restante";
                        END IF;
                        
                        IF v_restante = 0 THEN
                            UPDATE articulo_abiertos 
                            SET deleted_at = NOW(),
                                updated_at = NOW()
                            WHERE id = OLD.articulo_abierto_id;
                        END IF;
                    END IF;
                ELSE
                    UPDATE articulos 
                    SET stock = stock + OLD.cantidad - NEW.cantidad
                    WHERE id = OLD.articulo_id;
                END IF;
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
