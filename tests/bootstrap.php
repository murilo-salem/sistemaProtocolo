<?php
/**
 * PHPUnit Bootstrap File
 * 
 * Configura o ambiente de testes para a aplicação Adianti.
 */

// Definir constantes de teste
define('TESTING', true);
define('APP_ROOT', dirname(__DIR__));

// Carregar autoloader do Composer
require_once APP_ROOT . '/vendor/autoload.php';

// Carregar classes da aplicação (Models, Services, Controllers)
spl_autoload_register(function ($class) {
    if (strpos($class, 'Tests\\') === 0) {
        return; // Deixar Composer lidar com Tests
    }

    $folders = [
        APP_ROOT . '/app/model/',
        APP_ROOT . '/app/service/',
        APP_ROOT . '/app/control/',
        APP_ROOT . '/app/control/entregas/',
        APP_ROOT . '/app/control/notificacoes/',
    ];
    
    foreach ($folders as $folder) {
        $file = $folder . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

/**
 * Mock classes para simular comportamento do Adianti sem banco real
 */

// Mock TTransaction para testes unitários
if (!class_exists('TTransaction')) {
    class TTransaction
    {
        private static $conn = null;
        private static $database = null;
        
        public static function open($database)
        {
            self::$database = $database;
            // Em testes, não abre conexão real
        }
        
        public static function close()
        {
            self::$conn = null;
            self::$database = null;
        }
        
        public static function rollback()
        {
            self::$conn = null;
            self::$database = null;
        }
        
        public static function get()
        {
            return self::$conn;
        }
        
        public static function setConnection($conn)
        {
            self::$conn = $conn;
        }
    }
}

// Mock TSession para testes
if (!class_exists('TSession')) {
    class TSession
    {
        private static $data = [];
        
        public static function setValue($key, $value)
        {
            self::$data[$key] = $value;
        }
        
        public static function getValue($key)
        {
            return self::$data[$key] ?? null;
        }
        
        public static function clear()
        {
            self::$data = [];
        }
    }
}

// Mock TRecord base para testes unitários sem banco
if (!class_exists('TRecord')) {
    abstract class TRecord
    {
        protected $data = [];
        public $id;
        
        const TABLENAME = '';
        const PRIMARYKEY = 'id';
        const IDPOLICY = 'max';
        
        public function __construct($id = null, $callObjectLoad = true)
        {
            if ($id && $callObjectLoad) {
                $this->id = $id;
            }
        }
        
        public function __set($name, $value)
        {
            $this->data[$name] = $value;
        }
        
        public function __get($name)
        {
            return $this->data[$name] ?? null;
        }
        
        protected function addAttribute($name)
        {
            // Apenas registra o atributo
        }
        
        public function store()
        {
            if (!$this->id) {
                $this->id = rand(1, 9999);
            }
            return $this->id;
        }
        
        public function delete($id = null)
        {
            // Mock delete
        }
        
        public static function find($id)
        {
            return null; // Override em testes específicos
        }
        
        public static function where($field, $operator, $value)
        {
            return new MockCriteria();
        }
        
        public static function getObjects($criteria = null)
        {
            return [];
        }
        
        protected function getItems($class, $foreign_key)
        {
            return [];
        }
    }
    
    class MockCriteria
    {
        public function where($field, $operator, $value)
        {
            return $this;
        }
        
        public function first()
        {
            return null;
        }
        
        public function load()
        {
            return [];
        }
        
        public function delete()
        {
            return true;
        }
    }
}
