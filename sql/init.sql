CREATE DATABASE smartedu;
USE smartedu;

-- Tabla de roles
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL
);

INSERT INTO roles (nombre) VALUES 
('admin'), ('rector'), ('maestro');

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol_id INT NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rol_id) REFERENCES roles(id)
);

-- Tabla de materias
CREATE TABLE materias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    activa BOOLEAN DEFAULT TRUE
);

-- Tabla de relación maestros-materias (ASIGNACIONES)
CREATE TABLE maestros_materias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    maestro_id INT NOT NULL,
    materia_id INT NOT NULL,
    FOREIGN KEY (maestro_id) REFERENCES usuarios(id),
    FOREIGN KEY (materia_id) REFERENCES materias(id),
    UNIQUE KEY (maestro_id, materia_id) 
);

-- Tabla de grupos
CREATE TABLE grupos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    grado VARCHAR(20) NOT NULL,
    ciclo_escolar VARCHAR(20) NOT NULL,
    maestro_id INT,
    FOREIGN KEY (maestro_id) REFERENCES usuarios(id)
);

-- Tabla de estudiantes
CREATE TABLE estudiantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    grupo_id INT NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (grupo_id) REFERENCES grupos(id)
);

-- Tabla de actividades
CREATE TABLE actividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    porcentaje DECIMAL(5,2) NOT NULL,
    trimestre INT NOT NULL,
    fecha_entrega DATE,
    materia_id INT NOT NULL,
    grupo_id INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (materia_id) REFERENCES materias(id),
    FOREIGN KEY (grupo_id) REFERENCES grupos(id)
);

-- Tabla de notas
CREATE TABLE notas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    actividad_id INT NOT NULL,
    calificacion DECIMAL(5,2) NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id),
    FOREIGN KEY (actividad_id) REFERENCES actividades(id),
    UNIQUE KEY (estudiante_id, actividad_id)
);

-- Tabla de bitácora

CREATE TABLE IF NOT EXISTS bitacora (
    id_reg INT AUTO_INCREMENT PRIMARY KEY,
    usuario_sistema VARCHAR(50) NOT NULL,
    fecha_hora_sistema DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    nombre_tabla VARCHAR(50) NOT NULL,
    accion ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    id_registro_afectado INT,
    valores_anteriores JSON,
    valores_nuevos JSON,
    ip_conexion VARCHAR(45),
    modulo VARCHAR(50)
);


DELIMITER //


-- =============================================
-- TRIGGERS PARA TABLA 'usuarios'
-- =============================================
CREATE TRIGGER tr_bitacora_insert_usuarios
AFTER INSERT ON usuarios
FOR EACH ROW
BEGIN
    INSERT INTO bitacora (
        usuario_sistema, nombre_tabla, accion, id_registro_afectado,
        valores_nuevos, ip_conexion, modulo
    ) VALUES (
        CURRENT_USER(), 'usuarios', 'INSERT', NEW.id,
        JSON_OBJECT(
            'nombre_completo', NEW.nombre_completo,
            'username', NEW.username,
            'rol_id', NEW.rol_id,
            'activo', NEW.activo,
            'fecha_nacimiento', NEW.fecha_nacimiento
        ),
        (SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(USER(), '@', 1), '@', -1)),
        'Gestión de Usuarios'
    );
END //

CREATE TRIGGER tr_bitacora_update_usuarios
AFTER UPDATE ON usuarios
FOR EACH ROW
BEGIN
    INSERT INTO bitacora (
        usuario_sistema, nombre_tabla, accion, id_registro_afectado,
        valores_anteriores, valores_nuevos, ip_conexion, modulo
    ) VALUES (
        CURRENT_USER(), 'usuarios', 'UPDATE', NEW.id,
        JSON_OBJECT(
            'nombre_completo', OLD.nombre_completo,
            'username', OLD.username,
            'rol_id', OLD.rol_id,
            'activo', OLD.activo,
            'fecha_nacimiento', OLD.fecha_nacimiento
        ),
        JSON_OBJECT(
            'nombre_completo', NEW.nombre_completo,
            'username', NEW.username,
            'rol_id', NEW.rol_id,
            'activo', NEW.activo,
            'fecha_nacimiento', NEW.fecha_nacimiento
        ),
        (SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(USER(), '@', 1), '@', -1)),
        'Gestión de Usuarios'
    );
END //

CREATE TRIGGER tr_bitacora_delete_usuarios
AFTER DELETE ON usuarios
FOR EACH ROW
BEGIN
    INSERT INTO bitacora (
        usuario_sistema, nombre_tabla, accion, id_registro_afectado,
        valores_anteriores, ip_conexion, modulo
    ) VALUES (
        CURRENT_USER(), 'usuarios', 'DELETE', OLD.id,
        JSON_OBJECT(
            'nombre_completo', OLD.nombre_completo,
            'username', OLD.username,
            'rol_id', OLD.rol_id
        ),
        (SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(USER(), '@', 1), '@', -1)),
        'Gestión de Usuarios'
    );
END //

-- =============================================
-- TRIGGERS PARA TABLA 'materias'
-- =============================================
CREATE TRIGGER tr_bitacora_insert_materias
AFTER INSERT ON materias
FOR EACH ROW
BEGIN
    INSERT INTO bitacora (
        usuario_sistema, nombre_tabla, accion, id_registro_afectado,
        valores_nuevos, ip_conexion, modulo
    ) VALUES (
        CURRENT_USER(), 'materias', 'INSERT', NEW.id,
        JSON_OBJECT(
            'nombre', NEW.nombre,
            'descripcion', NEW.descripcion,
            'activa', NEW.activa
        ),
        (SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(USER(), '@', 1), '@', -1)),
        'Gestión Académica'
    );
END //

CREATE TRIGGER tr_bitacora_update_materias
AFTER UPDATE ON materias
FOR EACH ROW
BEGIN
    INSERT INTO bitacora (
        usuario_sistema, nombre_tabla, accion, id_registro_afectado,
        valores_anteriores, valores_nuevos, ip_conexion, modulo
    ) VALUES (
        CURRENT_USER(), 'materias', 'UPDATE', NEW.id,
        JSON_OBJECT(
            'nombre', OLD.nombre,
            'descripcion', OLD.descripcion,
            'activa', OLD.activa
        ),
        JSON_OBJECT(
            'nombre', NEW.nombre,
            'descripcion', NEW.descripcion,
            'activa', NEW.activa
        ),
        (SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(USER(), '@', 1), '@', -1)),
        'Gestión Académica'
    );
END //

CREATE TRIGGER tr_bitacora_delete_materias
AFTER DELETE ON materias
FOR EACH ROW
BEGIN
    INSERT INTO bitacora (
        usuario_sistema, nombre_tabla, accion, id_registro_afectado,
        valores_anteriores, ip_conexion, modulo
    ) VALUES (
        CURRENT_USER(), 'materias', 'DELETE', OLD.id,
        JSON_OBJECT(
            'nombre', OLD.nombre,
            'descripcion', OLD.descripcion
        ),
        (SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(USER(), '@', 1), '@', -1)),
        'Gestión Académica'
    );
END //

-- =============================================
-- TRIGGERS PARA TABLA 'grupos'
-- =============================================
CREATE TRIGGER tr_bitacora_insert_grupos
AFTER INSERT ON grupos
FOR EACH ROW
BEGIN
    INSERT INTO bitacora (
        usuario_sistema, nombre_tabla, accion, id_registro_afectado,
        valores_nuevos, ip_conexion, modulo
    ) VALUES (
        CURRENT_USER(), 'grupos', 'INSERT', NEW.id,
        JSON_OBJECT(
            'nombre', NEW.nombre,
            'grado', NEW.grado,
            'ciclo_escolar', NEW.ciclo_escolar,
            'maestro_id', NEW.maestro_id
        ),
        (SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(USER(), '@', 1), '@', -1)),
        'Gestión de Grupos'
    );
END //

CREATE TRIGGER tr_bitacora_update_grupos
AFTER UPDATE ON grupos
FOR EACH ROW
BEGIN
    INSERT INTO bitacora (
        usuario_sistema, nombre_tabla, accion, id_registro_afectado,
        valores_anteriores, valores_nuevos, ip_conexion, modulo
    ) VALUES (
        CURRENT_USER(), 'grupos', 'UPDATE', NEW.id,
        JSON_OBJECT(
            'nombre', OLD.nombre,
            'grado', OLD.grado,
            'ciclo_escolar', OLD.ciclo_escolar,
            'maestro_id', OLD.maestro_id
        ),
        JSON_OBJECT(
            'nombre', NEW.nombre,
            'grado', NEW.grado,
            'ciclo_escolar', NEW.ciclo_escolar,
            'maestro_id', NEW.maestro_id
        ),
        (SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(USER(), '@', 1), '@', -1)),
        'Gestión de Grupos'
    );
END //

CREATE TRIGGER tr_bitacora_delete_grupos
AFTER DELETE ON grupos
FOR EACH ROW
BEGIN
    INSERT INTO bitacora (
        usuario_sistema, nombre_tabla, accion, id_registro_afectado,
        valores_anteriores, ip_conexion, modulo
    ) VALUES (
        CURRENT_USER(), 'grupos', 'DELETE', OLD.id,
        JSON_OBJECT(
            'nombre', OLD.nombre,
            'grado', OLD.grado
        ),
        (SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(USER(), '@', 1), '@', -1)),
        'Gestión de Grupos'
    );
END //

-- =============================================
-- TRIGGERS PARA TABLA 'estudiantes'
-- =============================================
CREATE TRIGGER tr_bitacora_insert_estudiantes
AFTER INSERT ON estudiantes
FOR EACH ROW
BEGIN
    INSERT INTO bitacora (
        usuario_sistema, nombre_tabla, accion, id_registro_afectado,
        valores_nuevos, ip_conexion, modulo
    ) VALUES (
        CURRENT_USER(), 'estudiantes', 'INSERT', NEW.id,
        JSON_OBJECT(
            'nombre_completo', NEW.nombre_completo,
            'fecha_nacimiento', NEW.fecha_nacimiento,
            'grupo_id', NEW.grupo_id,
            'activo', NEW.activo
        ),
        (SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(USER(), '@', 1), '@', -1)),
        'Gestión de Estudiantes'
    );
END //

CREATE TRIGGER tr_bitacora_update_estudiantes
AFTER UPDATE ON estudiantes
FOR EACH ROW
BEGIN
    INSERT INTO bitacora (
        usuario_sistema, nombre_tabla, accion, id_registro_afectado,
        valores_anteriores, valores_nuevos, ip_conexion, modulo
    ) VALUES (
        CURRENT_USER(), 'estudiantes', 'UPDATE', NEW.id,
        JSON_OBJECT(
            'nombre_completo', OLD.nombre_completo,
            'fecha_nacimiento', OLD.fecha_nacimiento,
            'grupo_id', OLD.grupo_id,
            'activo', OLD.activo
        ),
        JSON_OBJECT(
            'nombre_completo', NEW.nombre_completo,
            'fecha_nacimiento', NEW.fecha_nacimiento,
            'grupo_id', NEW.grupo_id,
            'activo', NEW.activo
        ),
        (SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(USER(), '@', 1), '@', -1)),
        'Gestión de Estudiantes'
    );
END //

CREATE TRIGGER tr_bitacora_delete_estudiantes
AFTER DELETE ON estudiantes
FOR EACH ROW
BEGIN
    INSERT INTO bitacora (
        usuario_sistema, nombre_tabla, accion, id_registro_afectado,
        valores_anteriores, ip_conexion, modulo
    ) VALUES (
        CURRENT_USER(), 'estudiantes', 'DELETE', OLD.id,
        JSON_OBJECT(
            'nombre_completo', OLD.nombre_completo,
            'grupo_id', OLD.grupo_id
        ),
        (SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(USER(), '@', 1), '@', -1)),
        'Gestión de Estudiantes'
    );
END //

-- =============================================
-- TRIGGERS PARA TABLA 'actividades'
-- =============================================
CREATE TRIGGER tr_bitacora_insert_actividades
AFTER INSERT ON actividades
FOR EACH ROW
BEGIN
    INSERT INTO bitacora (
        usuario_sistema, nombre_tabla, accion, id_registro_afectado,
        valores_nuevos, ip_conexion, modulo
    ) VALUES (
        CURRENT_USER(), 'actividades', 'INSERT', NEW.id,
        JSON_OBJECT(
            'nombre', NEW.nombre,
            'materia_id', NEW.materia_id,
            'grupo_id', NEW.grupo_id,
            'trimestre', NEW.trimestre,
            'porcentaje', NEW.porcentaje,
            'fecha_entrega', NEW.fecha_entrega
        ),
        (SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(USER(), '@', 1), '@', -1)),
        'Gestión Académica'
    );
END //

CREATE TRIGGER tr_bitacora_update_actividades
AFTER UPDATE ON actividades
FOR EACH ROW
BEGIN
    INSERT INTO bitacora (
        usuario_sistema, nombre_tabla, accion, id_registro_afectado,
        valores_anteriores, valores_nuevos, ip_conexion, modulo
    ) VALUES (
        CURRENT_USER(), 'actividades', 'UPDATE', NEW.id,
        JSON_OBJECT(
            'nombre', OLD.nombre,
            'porcentaje', OLD.porcentaje,
            'trimestre', OLD.trimestre,
            'fecha_entrega', OLD.fecha_entrega
        ),
        JSON_OBJECT(
            'nombre', NEW.nombre,
            'porcentaje', NEW.porcentaje,
            'trimestre', NEW.trimestre,
            'fecha_entrega', NEW.fecha_entrega
        ),
        (SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(USER(), '@', 1), '@', -1)),
        'Gestión Académica'
    );
END //

CREATE TRIGGER tr_bitacora_delete_actividades
AFTER DELETE ON actividades
FOR EACH ROW
BEGIN
    INSERT INTO bitacora (
        usuario_sistema, nombre_tabla, accion, id_registro_afectado,
        valores_anteriores, ip_conexion, modulo
    ) VALUES (
        CURRENT_USER(), 'actividades', 'DELETE', OLD.id,
        JSON_OBJECT(
            'nombre', OLD.nombre,
            'materia_id', OLD.materia_id
        ),
        (SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(USER(), '@', 1), '@', -1)),
        'Gestión Académica'
    );
END //

-- =============================================
-- TRIGGERS PARA TABLA 'notas'
-- =============================================
CREATE TRIGGER tr_bitacora_insert_notas
AFTER INSERT ON notas
FOR EACH ROW
BEGIN
    INSERT INTO bitacora (
        usuario_sistema, nombre_tabla, accion, id_registro_afectado,
        valores_nuevos, ip_conexion, modulo
    ) VALUES (
        CURRENT_USER(), 'notas', 'INSERT', NEW.id,
        JSON_OBJECT(
            'estudiante_id', NEW.estudiante_id,
            'actividad_id', NEW.actividad_id,
            'calificacion', NEW.calificacion
        ),
        (SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(USER(), '@', 1), '@', -1)),
        'Registro de Calificaciones'
    );
END //

CREATE TRIGGER tr_bitacora_update_notas
AFTER UPDATE ON notas
FOR EACH ROW
BEGIN
    INSERT INTO bitacora (
        usuario_sistema, nombre_tabla, accion, id_registro_afectado,
        valores_anteriores, valores_nuevos, ip_conexion, modulo
    ) VALUES (
        CURRENT_USER(), 'notas', 'UPDATE', NEW.id,
        JSON_OBJECT(
            'calificacion', OLD.calificacion
        ),
        JSON_OBJECT(
            'calificacion', NEW.calificacion
        ),
        (SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(USER(), '@', 1), '@', -1)),
        'Registro de Calificaciones'
    );
END //

CREATE TRIGGER tr_bitacora_delete_notas
AFTER DELETE ON notas
FOR EACH ROW
BEGIN
    INSERT INTO bitacora (
        usuario_sistema, nombre_tabla, accion, id_registro_afectado,
        valores_anteriores, ip_conexion, modulo
    ) VALUES (
        CURRENT_USER(), 'notas', 'DELETE', OLD.id,
        JSON_OBJECT(
            'estudiante_id', OLD.estudiante_id,
            'actividad_id', OLD.actividad_id
        ),
        (SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(USER(), '@', 1), '@', -1)),
        'Registro de Calificaciones'
    );
END //

-- =============================================
-- TRIGGERS PARA TABLA 'maestros_materias'
-- =============================================
CREATE TRIGGER tr_bitacora_insert_maestros_materias
AFTER INSERT ON maestros_materias
FOR EACH ROW
BEGIN
    INSERT INTO bitacora (
        usuario_sistema, nombre_tabla, accion, id_registro_afectado,
        valores_nuevos, ip_conexion, modulo
    ) VALUES (
        CURRENT_USER(), 'maestros_materias', 'INSERT', NEW.id,
        JSON_OBJECT(
            'maestro_id', NEW.maestro_id,
            'materia_id', NEW.materia_id
        ),
        (SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(USER(), '@', 1), '@', -1)),
        'Asignación de Materias'
    );
END //

CREATE TRIGGER tr_bitacora_delete_maestros_materias
AFTER DELETE ON maestros_materias
FOR EACH ROW
BEGIN
    INSERT INTO bitacora (
        usuario_sistema, nombre_tabla, accion, id_registro_afectado,
        valores_anteriores, ip_conexion, modulo
    ) VALUES (
        CURRENT_USER(), 'maestros_materias', 'DELETE', OLD.id,
        JSON_OBJECT(
            'maestro_id', OLD.maestro_id,
            'materia_id', OLD.materia_id
        ),
        (SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(USER(), '@', 1), '@', -1)),
        'Asignación de Materias'
    );
END //

DELIMITER ;

DELIMITER //

-- =============================================
-- 1. PROCEDIMIENTOS CRUD PARA USUARIOS
-- =============================================

-- Crear usuario (CREATE)
DROP PROCEDURE IF EXISTS sp_usuario_create //
CREATE PROCEDURE sp_usuario_create(
    IN p_nombre_completo VARCHAR(100),
    IN p_fecha_nacimiento DATE,
    IN p_rol_id INT,
    IN p_materias_id JSON,
    OUT p_resultado VARCHAR(200)
)
BEGIN
    DECLARE v_username VARCHAR(50);
    DECLARE v_password VARCHAR(50);
    DECLARE v_usuario_id INT;
    DECLARE v_contador INT DEFAULT 1;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_resultado = 'Error al crear usuario';
        ROLLBACK;
    END;
    
    START TRANSACTION;
    
    -- Generar username automático
    SET v_username = CONCAT(
        LOWER(SUBSTRING(SUBSTRING_INDEX(p_nombre_completo, ' ', 1), 1, 1)),
        LOWER(SUBSTRING(SUBSTRING_INDEX(p_nombre_completo, ' ', -1), 1, 1)),
        DATE_FORMAT(NOW(), '%y')
    );
    
    -- Verificar username único
    WHILE EXISTS (SELECT 1 FROM usuarios WHERE username = v_username) DO
        SET v_username = CONCAT(
            SUBSTRING(v_username, 1, CHAR_LENGTH(v_username) - CHAR_LENGTH(v_contador) + 1),
            v_contador
        );
        SET v_contador = v_contador + 1;
    END WHILE;
    
    -- Generar password aleatorio seguro
    SET v_password = CONCAT(
        SUBSTRING('ABCDEFGHIJKLMNOPQRSTUVWXYZ', FLOOR(RAND() * 26) + 1, 1),
        FLOOR(RAND() * 10),
        SUBSTRING('!@#$%^&*', FLOOR(RAND() * 8) + 1, 1),
        SUBSTRING('abcdefghijklmnopqrstuvwxyz', FLOOR(RAND() * 26) + 1, 5)
    );
    
    -- Insertar usuario
    INSERT INTO usuarios (
        nombre_completo, fecha_nacimiento, username, password, rol_id, activo
    ) VALUES (
        p_nombre_completo, p_fecha_nacimiento, v_username, 
        SHA2(v_password, 256), p_rol_id, 1
    );
    
    SET v_usuario_id = LAST_INSERT_ID();
    
    -- Asignar materias si es maestro (rol_id = 3)
    IF p_rol_id = 3 AND p_materias_id IS NOT NULL AND JSON_LENGTH(p_materias_id) > 0 THEN
        SET @sql = CONCAT('
            INSERT INTO maestros_materias (maestro_id, materia_id)
            SELECT ', v_usuario_id, ', id 
            FROM materias 
            WHERE id IN (',
            REPLACE(REPLACE(REPLACE(p_materias_id, '[', ''), ']', ''), '"', ''),
            ')
        ');
        
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
    
    SET p_resultado = CONCAT('Usuario creado. Credenciales: ', v_username, '/', v_password);
    COMMIT;
END //

-- Actualizar usuario (UPDATE)
DROP PROCEDURE IF EXISTS sp_usuario_update //
CREATE PROCEDURE sp_usuario_update(
    IN p_usuario_id INT,
    IN p_nombre_completo VARCHAR(100),
    IN p_fecha_nacimiento DATE,
    IN p_rol_id INT,
    IN p_activo BOOLEAN,
    IN p_materias_id JSON,
    OUT p_resultado VARCHAR(200)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_resultado = CONCAT('Error al actualizar usuario: ', SQL_ERROR_MESSAGE);
        ROLLBACK;
    END;
    
    START TRANSACTION;
    
    -- Actualizar datos básicos
    UPDATE usuarios SET
        nombre_completo = p_nombre_completo,
        fecha_nacimiento = p_fecha_nacimiento,
        rol_id = p_rol_id,
        activo = p_activo
    WHERE id = p_usuario_id;
    
    -- Si es maestro, actualizar materias
    IF p_rol_id = 3 THEN
        -- Eliminar asignaciones anteriores
        DELETE FROM maestros_materias WHERE maestro_id = p_usuario_id;
        
        -- Insertar nuevas asignaciones
        IF p_materias_id IS NOT NULL AND JSON_LENGTH(p_materias_id) > 0 THEN
            SET @sql = CONCAT('
                INSERT INTO maestros_materias (maestro_id, materia_id)
                SELECT ', p_usuario_id, ', id 
                FROM materias 
                WHERE id IN (',
                REPLACE(REPLACE(REPLACE(p_materias_id, '[', ''), ']', ''), '"', ''),
                ')
            ');
            
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        END IF;
    END IF;
    
    SET p_resultado = 'Usuario actualizado correctamente';
    COMMIT;
END //

-- Eliminar/Desactivar usuario (DELETE)
DROP PROCEDURE IF EXISTS sp_usuario_delete //
CREATE PROCEDURE sp_usuario_delete(
    IN p_usuario_id INT,
    OUT p_resultado VARCHAR(200)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_resultado = CONCAT('Error al eliminar usuario: ', SQL_ERROR_MESSAGE);
        ROLLBACK;
    END;
    
    START TRANSACTION;
    
    -- Verificar si el usuario existe
    IF NOT EXISTS (SELECT 1 FROM usuarios WHERE id = p_usuario_id) THEN
        SET p_resultado = 'El usuario no existe';
    ELSE
        -- Eliminar asignaciones de materias primero
        DELETE FROM maestros_materias WHERE maestro_id = p_usuario_id;
        
        -- Desactivar usuario (no borrar físicamente)
        UPDATE usuarios SET activo = FALSE WHERE id = p_usuario_id;
        
        SET p_resultado = 'Usuario desactivado correctamente';
    END IF;
    
    COMMIT;
END //

-- =============================================
-- 2. LÓGICA DE NEGOCIO
-- =============================================

-- Calcular promedio de estudiante
DROP PROCEDURE IF EXISTS sp_calcular_promedio_estudiante //
CREATE PROCEDURE sp_calcular_promedio_estudiante(
    IN p_estudiante_id INT,
    IN p_materia_id INT,
    IN p_trimestre INT, -- 0 para promedio general
    OUT p_promedio DECIMAL(5,2),
    OUT p_resultado VARCHAR(200)
)
BEGIN
    DECLARE v_estudiante_valido BOOLEAN;
    DECLARE v_materia_valida BOOLEAN;
    
    -- Validar que el estudiante existe
    SELECT COUNT(*) INTO v_estudiante_valido FROM estudiantes WHERE id = p_estudiante_id AND activo = TRUE;
    -- Validar que la materia existe
    SELECT COUNT(*) INTO v_materia_valida FROM materias WHERE id = p_materia_id AND activa = TRUE;
    
    IF v_estudiante_valido = 0 THEN
        SET p_resultado = 'El estudiante no existe o está inactivo';
        SET p_promedio = 0;
    ELSEIF v_materia_valida = 0 THEN
        SET p_resultado = 'La materia no existe o está inactiva';
        SET p_promedio = 0;
    ELSE
        IF p_trimestre = 0 THEN
            -- Promedio general de todos los trimestres
            SELECT ROUND(AVG(n.calificacion * a.porcentaje / 100), 2) INTO p_promedio
            FROM notas n
            JOIN actividades a ON n.actividad_id = a.id
            WHERE n.estudiante_id = p_estudiante_id
            AND a.materia_id = p_materia_id
            AND a.porcentaje > 0;
        ELSE
            -- Promedio por trimestre específico
            SELECT ROUND(SUM(n.calificacion * a.porcentaje / 100), 2) INTO p_promedio
            FROM notas n
            JOIN actividades a ON n.actividad_id = a.id
            WHERE n.estudiante_id = p_estudiante_id
            AND a.materia_id = p_materia_id
            AND a.trimestre = p_trimestre
            AND a.porcentaje > 0;
        END IF;
        
        IF p_promedio IS NULL THEN
            SET p_resultado = 'No se encontraron calificaciones registradas';
            SET p_promedio = 0;
        ELSE
            SET p_resultado = 'Promedio calculado correctamente';
        END IF;
    END IF;
END //

-- Generar reporte de grupo
DROP PROCEDURE IF EXISTS sp_generar_reporte_grupo //
CREATE PROCEDURE sp_generar_reporte_grupo(
    IN p_grupo_id INT,
    IN p_materia_id INT,
    IN p_trimestre INT
)
BEGIN
    DECLARE v_grupo_valido BOOLEAN;
    DECLARE v_materia_valida BOOLEAN;
    
    -- Validar que el grupo existe
    SELECT COUNT(*) INTO v_grupo_valido FROM grupos WHERE id = p_grupo_id;
    -- Validar que la materia existe
    SELECT COUNT(*) INTO v_materia_valida FROM materias WHERE id = p_materia_id AND activa = TRUE;
    
    IF v_grupo_valido = 0 THEN
        SELECT 'Error: El grupo no existe' AS mensaje;
    ELSEIF v_materia_valida = 0 THEN
        SELECT 'Error: La materia no existe o está inactiva' AS mensaje;
    ELSE
        -- Datos del grupo y materia
        SELECT 
            g.nombre AS grupo_nombre, 
            g.grado, 
            m.nombre AS materia_nombre,
            CASE 
                WHEN p_trimestre = 0 THEN 'Promedio General'
                ELSE CONCAT('Trimestre ', p_trimestre)
            END AS periodo
        FROM grupos g, materias m
        WHERE g.id = p_grupo_id AND m.id = p_materia_id;
        
        -- Calificaciones por estudiante
        SELECT 
            e.id,
            e.nombre_completo,
            ROUND(SUM(n.calificacion * a.porcentaje / 100), 2) AS promedio,
            CASE WHEN SUM(n.calificacion * a.porcentaje / 100) >= 6 THEN 'Aprobado' ELSE 'Reprobado' END AS estado
        FROM estudiantes e
        LEFT JOIN notas n ON n.estudiante_id = e.id
        LEFT JOIN actividades a ON a.id = n.actividad_id
        WHERE e.grupo_id = p_grupo_id
        AND e.activo = TRUE
        AND a.materia_id = p_materia_id
        AND (a.trimestre = p_trimestre OR p_trimestre = 0)
        AND a.porcentaje > 0
        GROUP BY e.id, e.nombre_completo
        ORDER BY e.nombre_completo;
        
        -- Estadísticas generales
        SELECT 
            COUNT(e.id) AS total_estudiantes,
            ROUND(AVG(promedio), 2) AS promedio_grupo,
            SUM(CASE WHEN promedio >= 6 THEN 1 ELSE 0 END) AS aprobados,
            SUM(CASE WHEN promedio < 6 THEN 1 ELSE 0 END) AS reprobados,
            ROUND((SUM(CASE WHEN promedio >= 6 THEN 1 ELSE 0 END) / COUNT(e.id)) * 100, 2) AS porcentaje_aprobacion
        FROM (
            SELECT 
                e.id,
                SUM(n.calificacion * a.porcentaje / 100) AS promedio
            FROM estudiantes e
            LEFT JOIN notas n ON n.estudiante_id = e.id
            LEFT JOIN actividades a ON a.id = n.actividad_id
            WHERE e.grupo_id = p_grupo_id
            AND e.activo = TRUE
            AND a.materia_id = p_materia_id
            AND (a.trimestre = p_trimestre OR p_trimestre = 0)
            AND a.porcentaje > 0
            GROUP BY e.id
        ) AS calificaciones;
    END IF;
END //

DELIMITER ;