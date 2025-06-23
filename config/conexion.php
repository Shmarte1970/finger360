<?php
/**
 * Archivo de conexión a la base de datos
 * 
 * Establece la conexión con la base de datos MySQL utilizando PDO
 */

// Configuración de la base de datos
define('DB_HOST', 'agent.mysql.database.azure.com');
define('DB_PORT', 3306);
define('DB_NAME', 'agent');
define('DB_USER', 'admin');
define('DB_PASS', 'admin2023');
define('DB_CHARSET', 'utf8mb4');
// Configuración de SendGrid
define('SENDGRID_API_KEY', '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');



// Configuración de JWT
define('JWT_SECRET', 'your_secret_key_here'); // Cambiar en producción
define('JWT_EXPIRATION', 3600); // 1 hora en segundos

// Configuración de la aplicación
define('APP_DEBUG', true);
define('APP_URL', 'http://localhost/agent');

// Función para configurar cabeceras CORS para API
function setCorsHeaders() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Origin, Content-Type, Authorization, X-Requested-With');
    header('Content-Type: application/json');
    
    // Si es una solicitud OPTIONS, terminar aquí (preflight CORS)
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }
}

class Conexion {
    // Parámetros de conexión
    private $host;
    private $port;
    private $dbname;
    private $username;
    private $password;
    private $charset;
    private $connection;
    
    /**
     * Constructor que inicializa los parámetros de conexión
     */
    public function __construct() {
        $this->host = DB_HOST;
        $this->port = DB_PORT;
        $this->dbname = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
        $this->charset = DB_CHARSET;
        $this->connect();
    }
    
    /**
     * Establece la conexión a la base de datos
     */
    private function connect() {
        $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset={$this->charset}";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        try {
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            
            // Establecer zona horaria para esta conexión (UTC+1 para España)
            $this->connection->exec("SET time_zone = '+01:00'");
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
    
    /**
     * Obtiene la conexión a la base de datos
     * 
     * @return PDO Objeto de conexión PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Ejecuta una consulta SQL con parámetros
     * 
     * @param string $sql Consulta SQL
     * @param array $params Parámetros para la consulta
     * @return PDOStatement Resultado de la consulta
     */
    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Obtiene un solo registro de la base de datos
     * 
     * @param string $sql Consulta SQL
     * @param array $params Parámetros para la consulta
     * @return array|false Registro encontrado o false si no hay resultados
     */
    public function getRow($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Obtiene múltiples registros de la base de datos
     * 
     * @param string $sql Consulta SQL
     * @param array $params Parámetros para la consulta
     * @return array Registros encontrados
     */
    public function getRows($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Inserta un registro y devuelve el ID insertado
     * 
     * @param string $table Nombre de la tabla
     * @param array $data Datos a insertar (columna => valor)
     * @return int ID del registro insertado
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));
        
        return $this->connection->lastInsertId();
    }
    
    /**
     * Actualiza registros en la base de datos
     * 
     * @param string $table Nombre de la tabla
     * @param array $data Datos a actualizar (columna => valor)
     * @param string $where Condición WHERE
     * @param array $params Parámetros para la condición WHERE
     * @return int Número de filas afectadas
     */
    public function update($table, $data, $where, $params = []) {
        $sets = [];
        foreach (array_keys($data) as $column) {
            $sets[] = "{$column} = ?";
        }
        
        $sql = "UPDATE {$table} SET " . implode(', ', $sets) . " WHERE {$where}";
        $stmt = $this->query($sql, array_merge(array_values($data), $params));
        
        return $stmt->rowCount();
    }
    
    /**
     * Elimina registros de la base de datos
     * 
     * @param string $table Nombre de la tabla
     * @param string $where Condición WHERE
     * @param array $params Parámetros para la condición WHERE
     * @return int Número de filas afectadas
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $params);
        
        return $stmt->rowCount();
    }
}