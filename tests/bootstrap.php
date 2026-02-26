<?php
/**
 * PHPUnit Bootstrap File
 *
 * Configura o ambiente de testes para a aplicacao Adianti.
 */

define('TESTING', true);
define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT . '/vendor/autoload.php';

spl_autoload_register(function ($class) {
    if (strpos($class, 'Tests\\') === 0) {
        return;
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

if (!class_exists('TTransaction')) {
    class TTransaction
    {
        private static $conn = null;
        private static $defaultConn = null;
        private static $database = null;

        public static function open($database)
        {
            self::$database = $database;
            if (self::$conn === null && self::$defaultConn !== null) {
                self::$conn = self::$defaultConn;
            }
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
            self::$defaultConn = $conn;
        }
    }
}

if (!class_exists('MockDatabase')) {
    class MockDatabase
    {
        private static $records = [];
        private static $autoIncrement = [];
        private static $deleteCalls = [];

        public static function reset(): void
        {
            self::$records = [];
            self::$autoIncrement = [];
            self::$deleteCalls = [];
        }

        public static function seed(string $class, array $records): void
        {
            self::$records[$class] = [];
            foreach ($records as $record) {
                if (!is_object($record)) {
                    continue;
                }

                $id = $record->id ?? null;
                if ($id !== null) {
                    self::$records[$class][$id] = self::normalizeRecord($class, $record);
                    self::$autoIncrement[$class] = max(self::$autoIncrement[$class] ?? 0, (int) $id);
                }
            }
        }

        public static function all(string $class): array
        {
            return array_values(self::$records[$class] ?? []);
        }

        public static function find(string $class, $id)
        {
            $record = self::$records[$class][$id] ?? null;
            return $record ? clone $record : null;
        }

        public static function store(string $class, object $record): int
        {
            $id = $record->id ?? null;
            if (!$id) {
                $next = (self::$autoIncrement[$class] ?? 0) + 1;
                self::$autoIncrement[$class] = $next;
                $record->id = $next;
                $id = $next;
            } else {
                self::$autoIncrement[$class] = max(self::$autoIncrement[$class] ?? 0, (int) $id);
            }

            self::$records[$class][$id] = self::normalizeRecord($class, $record);
            return (int) $id;
        }

        public static function deleteWhere(string $class, array $filters): int
        {
            $before = count(self::$records[$class] ?? []);
            self::$records[$class] = array_filter(self::$records[$class] ?? [], function ($record) use ($filters) {
                return !self::matches($record, $filters);
            });
            $after = count(self::$records[$class] ?? []);

            self::$deleteCalls[] = ['class' => $class, 'filters' => $filters];
            return $before - $after;
        }

        public static function deleteCalls(): array
        {
            return self::$deleteCalls;
        }

        public static function query(string $class, array $filters): array
        {
            $records = self::all($class);
            if (empty($filters)) {
                return $records;
            }

            return array_values(array_filter($records, function ($record) use ($filters) {
                return self::matches($record, $filters);
            }));
        }

        private static function matches(object $record, array $filters): bool
        {
            foreach ($filters as $filter) {
                $field = $filter['field'];
                $operator = strtoupper((string) $filter['operator']);
                $value = $filter['value'] ?? null;
                $actual = $record->{$field} ?? null;

                if ($operator === '=') {
                    if ((string) $actual !== (string) $value) {
                        return false;
                    }
                    continue;
                }

                if ($operator === 'IN') {
                    if (!in_array($actual, (array) $value, true)) {
                        return false;
                    }
                    continue;
                }

                if ($operator === 'IS') {
                    if ($value === null && $actual !== null) {
                        return false;
                    }
                    continue;
                }
            }

            return true;
        }

        private static function normalizeRecord(string $class, object $record): object
        {
            if ($record instanceof $class) {
                return clone $record;
            }

            $typed = new $class();
            foreach (get_object_vars($record) as $name => $value) {
                $typed->{$name} = $value;
            }
            return $typed;
        }
    }
}

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

        public static function freeSession()
        {
            self::clear();
        }
    }
}

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
                $existing = MockDatabase::find(static::class, $id);
                if ($existing) {
                    $this->id = $existing->id;
                    foreach (get_object_vars($existing) as $name => $value) {
                        if ($name !== 'id') {
                            $this->{$name} = $value;
                        }
                    }
                } else {
                    $this->id = $id;
                }
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

        public function __isset($name)
        {
            return array_key_exists($name, $this->data);
        }

        protected function addAttribute($name)
        {
        }

        public function store()
        {
            return MockDatabase::store(static::class, $this);
        }

        public function delete($id = null)
        {
            if ($id === null && $this->id !== null) {
                $id = $this->id;
            }

            if ($id !== null) {
                MockDatabase::deleteWhere(static::class, [['field' => 'id', 'operator' => '=', 'value' => $id]]);
            }
        }

        public static function find($id)
        {
            return MockDatabase::find(static::class, $id);
        }

        public static function where($field, $operator, $value)
        {
            $criteria = new MockCriteria(static::class);
            return $criteria->where($field, $operator, $value);
        }

        public static function getObjects($criteria = null)
        {
            if ($criteria instanceof MockCriteria) {
                return $criteria->load();
            }

            if ($criteria instanceof TCriteria) {
                $filters = [];
                foreach ($criteria->getFilters() as $filter) {
                    $filters[] = [
                        'field' => $filter->field,
                        'operator' => $filter->operator,
                        'value' => $filter->value,
                    ];
                }
                return MockDatabase::query(static::class, $filters);
            }

            return MockDatabase::all(static::class);
        }

        public static function countObjects($criteria = null)
        {
            return count(static::getObjects($criteria));
        }

        protected function getItems($class, $foreign_key)
        {
            return MockDatabase::query($class, [['field' => $foreign_key, 'operator' => '=', 'value' => $this->id]]);
        }
    }

    class MockCriteria
    {
        private $className;
        private $filters = [];

        public function __construct(string $className)
        {
            $this->className = $className;
        }

        public function where($field, $operator, $value)
        {
            $this->filters[] = [
                'field' => $field,
                'operator' => $operator,
                'value' => $value,
            ];
            return $this;
        }

        public function first()
        {
            $items = $this->load();
            return $items[0] ?? null;
        }

        public function load()
        {
            return MockDatabase::query($this->className, $this->filters);
        }

        public function count()
        {
            return count($this->load());
        }

        public function delete()
        {
            MockDatabase::deleteWhere($this->className, $this->filters);
            return true;
        }
    }
}

if (!class_exists('TCriteria')) {
    class TCriteria
    {
        private $filters = [];
        private $properties = [];

        public function add(TFilter $filter)
        {
            $this->filters[] = $filter;
        }

        public function getFilters(): array
        {
            return $this->filters;
        }

        public function setProperty($name, $value)
        {
            $this->properties[$name] = $value;
        }

        public function setProperties($array)
        {
            foreach ((array) $array as $k => $v) {
                $this->properties[$k] = $v;
            }
        }

        public function resetProperties()
        {
            $this->properties = [];
        }
    }
}

if (!class_exists('TFilter')) {
    class TFilter
    {
        public $field;
        public $operator;
        public $value;

        public function __construct($field, $operator, $value)
        {
            $this->field = $field;
            $this->operator = $operator;
            $this->value = $value;
        }
    }
}

if (!class_exists('TRepository')) {
    class TRepository
    {
        private $className;

        public function __construct($className)
        {
            $this->className = $className;
        }

        public function load($criteria)
        {
            if (!$criteria instanceof TCriteria) {
                return MockDatabase::all($this->className);
            }

            $filters = [];
            foreach ($criteria->getFilters() as $filter) {
                $filters[] = [
                    'field' => $filter->field,
                    'operator' => $filter->operator,
                    'value' => $filter->value,
                ];
            }

            return MockDatabase::query($this->className, $filters);
        }

        public function count($criteria)
        {
            return count($this->load($criteria));
        }
    }
}

if (!class_exists('TDate')) {
    class TDate
    {
        public static function convertToMask($value, $from, $to)
        {
            return $value;
        }
    }
}

if (!class_exists('TPage')) {
    class TPage
    {
        public function __construct()
        {
        }

        public function add($content)
        {
        }

        public function show()
        {
        }
    }
}

if (!class_exists('TElement')) {
    class TElement
    {
        public $children = [];
        public $class;
        public $style;
        public $href;
        public $id;

        public function __construct($tag = 'div')
        {
        }

        public function add($child)
        {
            $this->children[] = $child;
        }
    }
}

if (!class_exists('TForm')) {
    class TForm extends TElement
    {
        public function __construct($name = null)
        {
            parent::__construct('form');
        }

        public function setFields($fields)
        {
        }

        public function getData()
        {
            return (object) [];
        }
    }
}

if (!class_exists('TEntry')) {
    class TEntry extends TElement
    {
        public function setProperty($name, $value)
        {
            $this->{$name} = $value;
        }

        public function setSize($size)
        {
        }

        public function addValidation($label, $validator)
        {
        }
    }
}

if (!class_exists('TPassword')) {
    class TPassword extends TEntry
    {
    }
}

if (!class_exists('TCheckButton')) {
    class TCheckButton extends TEntry
    {
        public function setIndexValue($value)
        {
        }
    }
}

if (!class_exists('TRequiredValidator')) {
    class TRequiredValidator
    {
    }
}

if (!class_exists('TButton')) {
    class TButton extends TElement
    {
        public function setAction($action, $label = null)
        {
        }

        public function setImage($image)
        {
        }

        public function addStyleClass($class)
        {
        }
    }
}

if (!class_exists('TAction')) {
    class TAction
    {
        public function __construct($action)
        {
        }
    }
}

if (!class_exists('TDataGrid')) {
    class TDataGrid extends TElement
    {
        public $items = [];
        public $columns = [];

        public function addColumn($column)
        {
            $this->columns[] = $column;
        }

        public function addAction($action)
        {
        }

        public function createModel()
        {
        }

        public function clear()
        {
            $this->items = [];
        }

        public function addItem($item)
        {
            $this->items[] = $item;
        }

        public function getWidth()
        {
            return '100%';
        }
    }
}

if (!class_exists('BootstrapDatagridWrapper')) {
    class BootstrapDatagridWrapper extends TDataGrid
    {
        private $wrapped;

        public function __construct($wrapped)
        {
            $this->wrapped = $wrapped;
        }

        public function __call($name, $arguments)
        {
            if (method_exists($this->wrapped, $name)) {
                return $this->wrapped->{$name}(...$arguments);
            }
            return null;
        }

        public function __get($name)
        {
            return $this->wrapped->{$name} ?? null;
        }

        public function __set($name, $value)
        {
            $this->wrapped->{$name} = $value;
        }
    }
}

if (!class_exists('Adianti\Wrapper\BootstrapDatagridWrapper')) {
    class_alias('BootstrapDatagridWrapper', 'Adianti\Wrapper\BootstrapDatagridWrapper');
}

if (!class_exists('TDataGridColumn')) {
    class TDataGridColumn
    {
        public function __construct($name, $label, $align = null, $width = null)
        {
        }

        public function setTransformer($callback)
        {
        }
    }
}

if (!class_exists('TDataGridAction')) {
    class TDataGridAction
    {
        public function __construct($action, $params = [])
        {
        }

        public function setField($field)
        {
        }

        public function setLabel($label)
        {
        }

        public function setImage($image)
        {
        }

        public function setDisplayCondition($condition)
        {
        }
    }
}

if (!class_exists('TPageNavigation')) {
    class TPageNavigation extends TElement
    {
        public $count = 0;

        public function setAction($action)
        {
        }

        public function setWidth($width)
        {
        }

        public function setCount($count)
        {
            $this->count = $count;
        }

        public function setProperties($param)
        {
        }

        public function setLimit($limit)
        {
        }
    }
}

if (!class_exists('TVBox')) {
    class TVBox extends TElement
    {
    }
}

if (!class_exists('TXMLBreadCrumb')) {
    class TXMLBreadCrumb extends TElement
    {
        public function __construct($menu, $class)
        {
            parent::__construct('div');
        }
    }
}

if (!class_exists('TPanelGroup')) {
    class TPanelGroup extends TElement
    {
        public function __construct($title = '')
        {
            parent::__construct('div');
        }

        public function addFooter($content)
        {
            $this->add($content);
        }
    }
}

if (!class_exists('TMessage')) {
    class TMessage
    {
        public function __construct($type, $message, $action = null)
        {
            TestSpy::$messages[] = ['type' => $type, 'message' => $message, 'action' => $action];
        }
    }
}

if (!class_exists('TScript')) {
    class TScript
    {
        public static function create($script)
        {
            TestSpy::$scripts[] = $script;
        }
    }
}

if (!class_exists('TApplication')) {
    class TApplication
    {
        public static function loadPage($class, $method = null, $params = [])
        {
            TestSpy::$appLoads[] = ['class' => $class, 'method' => $method, 'params' => $params];
        }
    }
}

if (!class_exists('AdiantiCoreApplication')) {
    class AdiantiCoreApplication
    {
        public static function loadPage($class, $method = null, $params = [])
        {
            TestSpy::$coreLoads[] = ['class' => $class, 'method' => $method, 'params' => $params];
        }
    }
}

if (!class_exists('TestSpy')) {
    class TestSpy
    {
        public static $messages = [];
        public static $scripts = [];
        public static $appLoads = [];
        public static $coreLoads = [];

        public static function reset(): void
        {
            self::$messages = [];
            self::$scripts = [];
            self::$appLoads = [];
            self::$coreLoads = [];
        }
    }
}
