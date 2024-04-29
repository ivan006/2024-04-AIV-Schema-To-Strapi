<?php

function connectToDatabase() {
    try {
        // Replace with your DB credentials
        $dsn = 'mysql:host=localhost;dbname=eranpc';
        $username = 'root';
        $password = '';
        return new PDO($dsn, $username, $password);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

function getTables(PDO $pdo) {
    $query = "SHOW TABLES";
    $stmt = $pdo->query($query);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getTableSchema(PDO $pdo, $table) {
    $query = "DESCRIBE $table";
    $stmt = $pdo->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function createStrapiFiles($table, $schema) {
    // Directory structure
    $baseDir = __DIR__ . "/src/api/$table/";

    if (!is_dir($baseDir)) {
        mkdir($baseDir . 'content-types/' . $table, 0777, true);
        mkdir($baseDir . 'controllers/', 0777, true);
        mkdir($baseDir . 'routes/', 0777, true);
        mkdir($baseDir . 'services/', 0777, true);
    }

    // Schema file
    $attributes = array_reduce($schema, function($acc, $col) {
        $acc[$col['Field']] = ['type' => mapColumnType($col['Type'])];
        return $acc;
    }, []);

    $schemaFile = [
        'kind' => 'collectionType',
        'collectionName' => $table . 's',
        'info' => [
            'singularName' => $table,
            'pluralName' => $table . 's',
            'displayName' => $table
        ],
        'options' => [
            'draftAndPublish' => true
        ],
        'attributes' => $attributes,
    ];

    file_put_contents($baseDir . "content-types/$table/schema.json", json_encode($schemaFile, JSON_PRETTY_PRINT));

    // Create JavaScript files
    $controllerContent = "const { createCoreController } = require('@strapi/strapi').factories;\n\n";
    $controllerContent .= "module.exports = createCoreController('api::$table.$table');\n";

    $routerContent = "const { createCoreRouter } = require('@strapi/strapi').factories;\n\n";
    $routerContent .= "module.exports = createCoreRouter('api::$table.$table');\n";

    $serviceContent = "const { createCoreService } = require('@strapi/strapi').factories;\n\n";
    $serviceContent .= "module.exports = createCoreService('api::$table.$table');\n";

    file_put_contents($baseDir . "controllers/$table.js", $controllerContent);
    file_put_contents($baseDir . "routes/$table.js", $routerContent);
    file_put_contents($baseDir . "services/$table.js", $serviceContent);
}


function mapColumnType($dbType) {
    if (stripos($dbType, 'int') !== false) return 'integer';
    if (stripos($dbType, 'varchar') !== false) return 'string';
    // Extend mappings as needed
}

$pdo = connectToDatabase();
$tables = getTables($pdo);

foreach ($tables as $table) {
    $schema = getTableSchema($pdo, $table);
    createStrapiFiles($table, $schema);
}
?>
