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
CREATE PROCEDURE sp_crear_usuario(
    IN p_nombre_completo VARCHAR(100),
    IN p_fecha_nacimiento DATE,
    IN p_rol_id INT,
    IN p_materias_id TEXT, -- JSON array: "[1,3,5]"
    OUT p_username VARCHAR(50),
    OUT p_password_plain VARCHAR(50)
BEGIN
    DECLARE v_username VARCHAR(50);
    DECLARE v_password VARCHAR(255);
    DECLARE v_usuario_id INT;
    
    -- Generar credenciales
    SET v_username = CONCAT(
        LOWER(SUBSTRING(SUBSTRING_INDEX(p_nombre_completo, ' ', 1), 1, 1)),
        LOWER(SUBSTRING(SUBSTRING_INDEX(p_nombre_completo, ' ', -1), 1, 1)),
        DATE_FORMAT(NOW(), '%y')
    );
    
    -- Verificar username único
    SET @counter = 1;
    WHILE EXISTS (SELECT 1 FROM usuarios WHERE username = v_username) DO
        SET v_username = CONCAT(v_username, @counter);
        SET @counter = @counter + 1;
    END WHILE;
    
    SET v_password = CONCAT(
        SUBSTRING('ABCDEFGHIJKLMNOPQRSTUVWXYZ', FLOOR(RAND() * 26) + 1, 1),
        FLOOR(RAND() * 10),
        SUBSTRING('!@#$%^&*', FLOOR(RAND() * 8) + 1, 1),
        SUBSTRING('abcdefghijklmnopqrstuvwxyz', FLOOR(RAND() * 26) + 1, 5)
    );
    
    -- Insertar usuario
    INSERT INTO usuarios (
        nombre_completo, 
        fecha_nacimiento, 
        username, 
        password, 
        rol_id
    ) VALUES (
        p_nombre_completo,
        p_fecha_nacimiento,
        v_username,
        SHA2(v_password, 256),
        p_rol_id
    );
    
    SET v_usuario_id = LAST_INSERT_ID();
    
    -- Asignar materias si es maestro
    IF p_rol_id = 3 AND p_materias_id IS NOT NULL THEN
        SET @sql = CONCAT('
            INSERT INTO maestros_materias (maestro_id, materia_id)
            SELECT ', v_usuario_id, ', id 
            FROM materias 
            WHERE id IN (', REPLACE(REPLACE(REPLACE(p_materias_id, '[', ''), ']', ''), ')
        ');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
    
    -- Retornar credenciales
    SET p_username = v_username;
    SET p_password_plain = v_password;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE sp_crear_actividad(
    IN p_maestro_id INT,
    IN p_nombre VARCHAR(100),
    IN p_descripcion TEXT,
    IN p_porcentaje DECIMAL(5,2),
    IN p_trimestre INT,
    IN p_fecha_entrega DATE,
    IN p_materia_id INT,
    IN p_grupo_id INT,
    OUT p_resultado VARCHAR(100))
BEGIN
    DECLARE v_permiso BOOLEAN;
    DECLARE v_total_porcentaje DECIMAL(5,2);
    
    -- Verificar permisos
    SELECT COUNT(*) INTO v_permiso
    FROM maestros_materias mm
    JOIN grupos g ON g.maestro_id = p_maestro_id
    WHERE mm.maestro_id = p_maestro_id
    AND mm.materia_id = p_materia_id
    AND g.id = p_grupo_id;
    
    IF v_permiso = 0 THEN
        SET p_resultado = 'Error: Sin permisos';
    ELSE
        -- Validar porcentaje
        SELECT COALESCE(SUM(porcentaje), 0) INTO v_total_porcentaje
        FROM actividades
        WHERE grupo_id = p_grupo_id
        AND materia_id = p_materia_id
        AND trimestre = p_trimestre;
        
        IF (v_total_porcentaje + p_porcentaje) > 100 THEN
            SET p_resultado = 'Error: Porcentaje excede 100%';
        ELSE
            -- Crear actividad
            INSERT INTO actividades (
                nombre, descripcion, porcentaje, 
                trimestre, fecha_entrega, 
                materia_id, grupo_id
            ) VALUES (
                p_nombre, p_descripcion, p_porcentaje,
                p_trimestre, p_fecha_entrega,
                p_materia_id, p_grupo_id
            );
            SET p_resultado = CONCAT('Actividad creada ID: ', LAST_INSERT_ID());
        END IF;
    END IF;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE sp_generar_reporte(
    IN p_maestro_id INT,
    IN p_materia_id INT,
    IN p_grupo_id INT,
    IN p_trimestre INT)
BEGIN
    -- Verificar permisos primero
    IF EXISTS (
        SELECT 1 FROM maestros_materias mm
        JOIN grupos g ON g.maestro_id = mm.maestro_id
        WHERE mm.maestro_id = p_maestro_id
        AND mm.materia_id = p_materia_id
        AND g.id = p_grupo_id
    ) THEN
        -- Datos del grupo y materia
        SELECT 
            g.nombre AS grupo_nombre,
            g.grado,
            m.nombre AS materia_nombre
        FROM grupos g, materias m
        WHERE g.id = p_grupo_id AND m.id = p_materia_id;
        
        -- Calificaciones por estudiante
        IF p_trimestre = 0 THEN
            -- Promedio final (todos los trimestres)
            SELECT 
                e.id,
                e.nombre_completo,
                ROUND(AVG(
                    (SELECT SUM(n.calificacion * a.porcentaje / 100)
                    FROM notas n
                    JOIN actividades a ON a.id = n.actividad_id
                    WHERE n.estudiante_id = e.id
                    AND a.materia_id = p_materia_id
                    AND a.trimestre = t.trimestre
                ), 2) AS promedio
            FROM estudiantes e
            CROSS JOIN (SELECT 1 AS trimestre UNION SELECT 2 UNION SELECT 3) t
            WHERE e.grupo_id = p_grupo_id
            GROUP BY e.id, e.nombre_completo;
        ELSE
            -- Por trimestre específico
            SELECT 
                e.id,
                e.nombre_completo,
                ROUND(SUM(n.calificacion * a.porcentaje / 100), 2) AS promedio
            FROM estudiantes e
            JOIN notas n ON n.estudiante_id = e.id
            JOIN actividades a ON a.id = n.actividad_id
            WHERE e.grupo_id = p_grupo_id
            AND a.materia_id = p_materia_id
            AND a.trimestre = p_trimestre
            GROUP BY e.id, e.nombre_completo;
        END IF;
    ELSE
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Acceso no autorizado';
    END IF;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE sp_actualizar_usuario(
    IN p_usuario_id INT,
    IN p_nombre_completo VARCHAR(100),
    IN p_fecha_nacimiento DATE,
    IN p_rol_id INT,
    IN p_activo BOOLEAN,
    IN p_materias_id TEXT) -- JSON array: "[1,3,5]"
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
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
        IF p_materias_id IS NOT NULL THEN
            SET @sql = CONCAT('
                INSERT INTO maestros_materias (maestro_id, materia_id)
                SELECT ', p_usuario_id, ', id 
                FROM materias 
                WHERE id IN (', REPLACE(REPLACE(REPLACE(p_materias_id, '[', ''), ']', ''), ')
            ');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        END IF;
    END IF;
    
    COMMIT;
END //
DELIMITER ;