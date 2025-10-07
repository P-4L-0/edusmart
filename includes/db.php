<?php
class Database {
    private $host = DB_HOST; // Host de la base de datos
    private $user = DB_USER; // Usuario de la base de datos
    private $pass = DB_PASS; // Contraseña de la base de datos
    private $name = DB_NAME; // Nombre de la base de datos
    
    private $dbh; // Manejador de la conexión PDO
    private $stmt; // Declaración preparada
    private $error; // Almacena errores de conexión o ejecución
    
    public function __construct() {
        // Configurar el Data Source Name (DSN)
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->name;
        $options = array(
            PDO::ATTR_PERSISTENT => true, // Conexión persistente para mejorar el rendimiento
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION // Lanzar excepciones en caso de errores
        );
        
        try {
            // Crear una nueva instancia de PDO
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        } catch(PDOException $e) {
            // Capturar errores de conexión y almacenarlos
            $this->error = $e->getMessage();
            echo $this->error;
        }
    }
    
    // Preparar una consulta SQL
    public function query($sql) {
        $this->stmt = $this->dbh->prepare($sql);
    }
    
    // Asociar valores a los parámetros de la consulta
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }
    
    // Ejecutar la consulta preparada
    public function execute() {
        return $this->stmt->execute();
    }
    
    // Obtener múltiples resultados como un array de objetos
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_OBJ);
    }
    
    // Obtener un único resultado como un objeto
    public function single() {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_OBJ);
    }
    
    // Obtener el número de filas afectadas por la última consulta
    public function rowCount() {
        return $this->stmt->rowCount();
    }

    // Iniciar una transacción
    public function beginTransaction() {
        return $this->dbh->beginTransaction();
    }
    
    // Confirmar una transacción
    public function commit() {
        return $this->dbh->commit();
    }
    
    // Revertir una transacción
    public function rollBack() {
        return $this->dbh->rollBack();
    }
    
    // Verificar si hay una transacción activa
    public function inTransaction() {
        return $this->dbh->inTransaction();
    }
    
    // Obtener el ID del último registro insertado
    public function lastInsertId() {
        return $this->dbh->lastInsertId();
    }
}