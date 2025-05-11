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

DELIMITER //

-- --------------------------------------------------------
-- 1. PROCEDIMIENTO PARA CREAR USUARIOS (ADMIN)
-- --------------------------------------------------------
CREATE PROCEDURE `sp_crear_usuario`(
    IN `p_nombre_completo` VARCHAR(100),
    IN `p_fecha_nacimiento` DATE,
    IN `p_rol_id` INT,
    IN `p_materias_id` TEXT,
    OUT `p_username` VARCHAR(50),
    OUT `p_password_plain` VARCHAR(50)
)
BEGIN
    DECLARE `v_username` VARCHAR(50);
    DECLARE `v_password` VARCHAR(50);
    DECLARE `v_usuario_id` INT;
    DECLARE `v_iniciales` VARCHAR(10);
    
    -- Generar iniciales (primera letra de cada palabra)
    SET `v_iniciales` = '';
    SET @`nombre_temp` = TRIM(`p_nombre_completo`);
    
    WHILE LENGTH(@`nombre_temp`) > 0 DO
        SET @`pos` = LOCATE(' ', @`nombre_temp`);
        IF @`pos` = 0 THEN
            SET @`palabra` = @`nombre_temp`;
            SET @`nombre_temp` = '';
        ELSE
            SET @`palabra` = LEFT(@`nombre_temp`, @`pos` - 1);
            SET @`nombre_temp` = SUBSTRING(@`nombre_temp`, @`pos` + 1);
        END IF;
        
        SET `v_iniciales` = CONCAT(`v_iniciales`, LOWER(SUBSTRING(@`palabra`, 1, 1)));
    END WHILE;
    
    -- Generar username base (iniciales + año 2 dígitos)
    SET `v_username` = CONCAT(`v_iniciales`, DATE_FORMAT(NOW(), '%y'));
    
    -- Verificar username único
    SET @`counter` = 1;
    WHILE EXISTS (SELECT 1 FROM `usuarios` WHERE `username` = `v_username`) DO
        SET `v_username` = CONCAT(`v_iniciales`, DATE_FORMAT(NOW(), '%y'), @`counter`);
        SET @`counter` = @`counter` + 1;
    END WHILE;
    
    -- Generar password aleatorio seguro
    SET `v_password` = CONCAT(
        SUBSTRING('ABCDEFGHIJKLMNOPQRSTUVWXYZ', FLOOR(RAND() * 26) + 1, 1),
        FLOOR(RAND() * 10),
        SUBSTRING('!@#$%^&*', FLOOR(RAND() * 8) + 1, 1),
        SUBSTRING('abcdefghijklmnopqrstuvwxyz', FLOOR(RAND() * 26) + 1, 5)
    );
    
    -- Insertar usuario
    INSERT INTO `usuarios` (
        `nombre_completo`, 
        `fecha_nacimiento`, 
        `username`, 
        `password`, 
        `rol_id`,
        `activo`
    ) VALUES (
        `p_nombre_completo`,
        `p_fecha_nacimiento`,
        `v_username`,
        SHA2(`v_password`, 256),
        `p_rol_id`,
        1
    );
    
    SET `v_usuario_id` = LAST_INSERT_ID();
    
    -- Asignar materias si es maestro (rol_id = 3)
    IF `p_rol_id` = 3 AND `p_materias_id` IS NOT NULL AND `p_materias_id` != '[]' THEN
        SET @`materias_list` = REPLACE(REPLACE(`p_materias_id`, '[', ''), ']', '');
        
        SET @`sql` = CONCAT('
            INSERT INTO `maestros_materias` (`maestro_id`, `materia_id`)
            SELECT ', `v_usuario_id`, ', `id` 
            FROM `materias` 
            WHERE `id` IN (', @`materias_list`, ')
        ');
        
        PREPARE `stmt` FROM @`sql`;
        EXECUTE `stmt`;
        DEALLOCATE PREPARE `stmt`;
    END IF;
    
    -- Retornar credenciales
    SET `p_username` = `v_username`;
    SET `p_password_plain` = `v_password`;
END //

-- --------------------------------------------------------
-- 2. PROCEDIMIENTO PARA ACTUALIZAR USUARIOS
-- --------------------------------------------------------
CREATE PROCEDURE `sp_actualizar_usuario`(
    IN `p_usuario_id` INT,
    IN `p_nombre_completo` VARCHAR(100),
    IN `p_fecha_nacimiento` DATE,
    IN `p_rol_id` INT,
    IN `p_activo` BOOLEAN,
    IN `p_materias_id` TEXT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Actualizar datos básicos
    UPDATE `usuarios` SET
        `nombre_completo` = `p_nombre_completo`,
        `fecha_nacimiento` = `p_fecha_nacimiento`,
        `rol_id` = `p_rol_id`,
        `activo` = `p_activo`
    WHERE `id` = `p_usuario_id`;
    
    -- Si es maestro, actualizar materias
    IF `p_rol_id` = 3 THEN
        -- Eliminar asignaciones anteriores
        DELETE FROM `maestros_materias` WHERE `maestro_id` = `p_usuario_id`;
        
        -- Insertar nuevas asignaciones
        IF `p_materias_id` IS NOT NULL AND `p_materias_id` != '[]' THEN
            SET @`materias_list` = REPLACE(REPLACE(`p_materias_id`, '[', ''), ']', '');
            
            SET @`sql` = CONCAT('
                INSERT INTO `maestros_materias` (`maestro_id`, `materia_id`)
                SELECT ', `p_usuario_id`, ', `id` 
                FROM `materias` 
                WHERE `id` IN (', @`materias_list`, ')
            ');
            
            PREPARE `stmt` FROM @`sql`;
            EXECUTE `stmt`;
            DEALLOCATE PREPARE `stmt`;
        END IF;
    END IF;
    
    COMMIT;
END //

-- --------------------------------------------------------
-- 3. PROCEDIMIENTO PARA ELIMINAR USUARIOS
-- --------------------------------------------------------
CREATE PROCEDURE `sp_eliminar_usuario`(IN `p_usuario_id` INT)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Eliminar asignaciones de materias primero (si es maestro)
    DELETE FROM `maestros_materias` WHERE `maestro_id` = `p_usuario_id`;
    
    -- Eliminar el usuario (actualizar a inactivo en lugar de borrar)
    UPDATE `usuarios` SET `activo` = FALSE WHERE `id` = `p_usuario_id`;
    
    COMMIT;
END //

-- --------------------------------------------------------
-- 4. PROCEDIMIENTO PARA ASIGNAR MATERIAS A MAESTROS
-- --------------------------------------------------------
CREATE PROCEDURE `sp_asignar_materias_maestro`(
    IN `p_maestro_id` INT,
    IN `p_materias_id` TEXT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Eliminar asignaciones anteriores
    DELETE FROM `maestros_materias` WHERE `maestro_id` = `p_maestro_id`;
    
    -- Insertar nuevas asignaciones
    IF `p_materias_id` IS NOT NULL AND `p_materias_id` != '[]' THEN
        SET @`materias_list` = REPLACE(REPLACE(`p_materias_id`, '[', ''), ']', '');
        
        SET @`sql` = CONCAT('
            INSERT INTO `maestros_materias` (`maestro_id`, `materia_id`)
            SELECT ', `p_maestro_id`, ', `id` 
            FROM `materias` 
            WHERE `id` IN (', @`materias_list`, ')
        ');
        
        PREPARE `stmt` FROM @`sql`;
        EXECUTE `stmt`;
        DEALLOCATE PREPARE `stmt`;
    END IF;
    
    COMMIT;
END //

-- --------------------------------------------------------
-- 5. PROCEDIMIENTO PARA CREAR ACTIVIDADES (MAESTROS)
-- --------------------------------------------------------
CREATE PROCEDURE `sp_crear_actividad`(
    IN `p_maestro_id` INT,
    IN `p_nombre` VARCHAR(100),
    IN `p_descripcion` TEXT,
    IN `p_porcentaje` DECIMAL(5,2),
    IN `p_trimestre` INT,
    IN `p_fecha_entrega` DATE,
    IN `p_materia_id` INT,
    IN `p_grupo_id` INT,
    OUT `p_resultado` VARCHAR(100)
BEGIN
    DECLARE `v_permiso` BOOLEAN;
    DECLARE `v_total_porcentaje` DECIMAL(5,2);
    
    -- Verificar permisos
    SELECT COUNT(*) INTO `v_permiso`
    FROM `maestros_materias` `mm`
    JOIN `grupos` `g` ON `g`.`maestro_id` = `p_maestro_id`
    WHERE `mm`.`maestro_id` = `p_maestro_id`
    AND `mm`.`materia_id` = `p_materia_id`
    AND `g`.`id` = `p_grupo_id`;
    
    IF `v_permiso` = 0 THEN
        SET `p_resultado` = 'Error: Sin permisos para esta materia/grupo';
    ELSE
        -- Validar porcentaje
        SELECT COALESCE(SUM(`porcentaje`), 0) INTO `v_total_porcentaje`
        FROM `actividades`
        WHERE `grupo_id` = `p_grupo_id`
        AND `materia_id` = `p_materia_id`
        AND `trimestre` = `p_trimestre`;
        
        IF (`v_total_porcentaje` + `p_porcentaje`) > 100 THEN
            SET `p_resultado` = 'Error: Porcentaje excede 100% en el trimestre';
        ELSE
            -- Crear actividad
            INSERT INTO `actividades` (
                `nombre`, `descripcion`, `porcentaje`, 
                `trimestre`, `fecha_entrega`, 
                `materia_id`, `grupo_id`
            ) VALUES (
                `p_nombre`, `p_descripcion`, `p_porcentaje`,
                `p_trimestre`, `p_fecha_entrega`,
                `p_materia_id`, `p_grupo_id`
            );
            SET `p_resultado` = CONCAT('Actividad creada ID: ', LAST_INSERT_ID());
        END IF;
    END IF;
END //

-- --------------------------------------------------------
-- 6. PROCEDIMIENTO PARA REGISTRAR CALIFICACIONES
-- --------------------------------------------------------
CREATE PROCEDURE `sp_registrar_calificacion`(
    IN `p_maestro_id` INT,
    IN `p_estudiante_id` INT,
    IN `p_actividad_id` INT,
    IN `p_calificacion` DECIMAL(5,2),
    OUT `p_resultado` VARCHAR(100))
BEGIN
    DECLARE `v_permiso` BOOLEAN;
    
    -- Verificar que el maestro tiene permiso para esta actividad
    SELECT COUNT(*) INTO `v_permiso`
    FROM `actividades` `a`
    JOIN `maestros_materias` `mm` ON `mm`.`materia_id` = `a`.`materia_id`
    WHERE `a`.`id` = `p_actividad_id`
    AND `mm`.`maestro_id` = `p_maestro_id`;
    
    IF `v_permiso` = 0 THEN
        SET `p_resultado` = 'Error: No tiene permisos para esta actividad';
    ELSE
        -- Insertar o actualizar calificación
        INSERT INTO `notas` (
            `estudiante_id`, 
            `actividad_id`, 
            `calificacion`
        ) VALUES (
            `p_estudiante_id`,
            `p_actividad_id`,
            `p_calificacion`
        ) ON DUPLICATE KEY UPDATE `calificacion` = `p_calificacion`;
        
        SET `p_resultado` = 'Calificación registrada correctamente';
    END IF;
END //

-- --------------------------------------------------------
-- 7. PROCEDIMIENTO PARA GENERAR REPORTES (PDF)
-- --------------------------------------------------------
CREATE PROCEDURE `sp_generar_reporte`(
    IN `p_maestro_id` INT,
    IN `p_materia_id` INT,
    IN `p_grupo_id` INT,
    IN `p_trimestre` INT
)
BEGIN
    -- Verificar permisos primero
    IF NOT EXISTS (
        SELECT 1 FROM `maestros_materias` `mm`
        JOIN `grupos` `g` ON `g`.`maestro_id` = `mm`.`maestro_id`
        WHERE `mm`.`maestro_id` = `p_maestro_id`
        AND `mm`.`materia_id` = `p_materia_id`
        AND `g`.`id` = `p_grupo_id`
    ) THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Acceso no autorizado: El maestro no tiene permisos para este grupo/materia';
    END IF;
    
    -- Datos del grupo y materia
    SELECT 
        `g`.`nombre` AS `grupo_nombre`,
        `g`.`grado`,
        `m`.`nombre` AS `materia_nombre`
    FROM `grupos` `g`, `materias` `m`
    WHERE `g`.`id` = `p_grupo_id` AND `m`.`id` = `p_materia_id`;
    
    -- Calificaciones por estudiante
    IF `p_trimestre` = 0 THEN
        -- Promedio final (todos los trimestres)
        SELECT 
            `e`.`id`,
            `e`.`nombre_completo`,
            ROUND(AVG(
                (SELECT SUM(`n`.`calificacion` * `a`.`porcentaje` / 100)
                FROM `notas` `n`
                JOIN `actividades` `a` ON `a`.`id` = `n`.`actividad_id`
                WHERE `n`.`estudiante_id` = `e`.`id`
                AND `a`.`materia_id` = `p_materia_id`
                AND `a`.`trimestre` = `t`.`trimestre`
                ), 2) AS `promedio`
        FROM `estudiantes` `e`
        CROSS JOIN (SELECT 1 AS `trimestre` UNION SELECT 2 UNION SELECT 3) `t`
        WHERE `e`.`grupo_id` = `p_grupo_id`
        GROUP BY `e`.`id`, `e`.`nombre_completo`;
    ELSE
        -- Por trimestre específico
        SELECT 
            `e`.`id`,
            `e`.`nombre_completo`,
            ROUND(SUM(`n`.`calificacion` * `a`.`porcentaje` / 100), 2) AS `promedio`
        FROM `estudiantes` `e`
        JOIN `notas` `n` ON `n`.`estudiante_id` = `e`.`id`
        JOIN `actividades` `a` ON `a`.`id` = `n`.`actividad_id`
        WHERE `e`.`grupo_id` = `p_grupo_id`
        AND `a`.`materia_id` = `p_materia_id`
        AND `a`.`trimestre` = `p_trimestre`
        GROUP BY `e`.`id`, `e`.`nombre_completo`;
    END IF;
END //

DELIMITER ;