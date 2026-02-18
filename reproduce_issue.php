<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'todo');

require_once 'library/SQLQuery.class.php';

class TestModel extends SQLQuery {
    protected $_table = 'items';
    function __construct() {
        $this->connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    }
}

$model = new TestModel();
$result = $model->selectAll();

echo "Result:\n";
print_r($result);

// Debug table names
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
$res = $db->query("SELECT * FROM items");
$fields = $res->fetch_fields();
echo "Table names from fetch_fields:\n";
foreach ($fields as $field) {
    echo "Field: {$field->name}, Table: {$field->table}\n";
}
