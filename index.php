<?php
// Database connection settings
$db_host = 'localhost';  // Change to your database host
$db_user = 'root';  // Change to your database username
$db_pass = '';  // Change to your database password
$db_name = 'eranpc';  // Change to your database name

// Establish the database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to fetch the tables from the database
$tables_query = $conn->query("SHOW TABLES");
echo "<pre>";
if ($tables_query->num_rows > 0) {
    while ($row = $tables_query->fetch_row()) {
        $table_name = $row[0];
        $singular_name = rtrim($table_name, 's');

        // Fetching columns for the current table
        $columns_query = $conn->query("DESCRIBE $table_name");

        $attributes = [];
        while ($col = $columns_query->fetch_assoc()) {
            $type = (strpos($col['Type'], 'varchar') !== false) ? 'string' : 'integer';
            $attributes[$col['Field']] = ['type' => $type];
        }

        // Instructions for developers
        echo "1. Create a directory structure for the table '$table_name':\n";
        echo "   mkdir -p src/api/$table_name/content-types/$table_name\n";
        echo "   mkdir -p src/api/$table_name/controllers\n";
        echo "   mkdir -p src/api/$table_name/routes\n";
        echo "   mkdir -p src/api/$table_name/services\n\n";

        // Creating schema file
        $schema_json = json_encode([
            'kind' => 'collectionType',
            'collectionName' => $table_name,
            'info' => [
                'singularName' => $singular_name,
                'pluralName' => $table_name,
                'displayName' => $table_name,
            ],
            'options' => ['draftAndPublish' => true],
            'pluginOptions' => [],
            'attributes' => $attributes,
        ], JSON_PRETTY_PRINT);

        echo "2. Create 'src/api/$table_name/content-types/$table_name/schema.json' with the following content:\n";
        echo $schema_json . "\n\n";

        // Creating controller file
        $controller_content = <<<CONTROLLER
'use strict';

/**
 * $singular_name controller
 */

const { createCoreController } = require('@strapi/strapi').factories;
module.exports = createCoreController('api::$table_name.$table_name');
CONTROLLER;

        echo "3. Create 'src/api/$table_name/controllers/$table_name.js' with the following content:\n";
        echo $controller_content . "\n\n";

        // Creating router file
        $router_content = <<<ROUTER
'use strict';

/**
 * $singular_name router
 */

const { createCoreRouter } = require('@strapi/strapi').factories;
module.exports = createCoreRouter('api::$table_name.$table_name');
ROUTER;

        echo "4. Create 'src/api/$table_name/routes/$table_name.js' with the following content:\n";
        echo $router_content . "\n\n";

        // Creating service file
        $service_content = <<<SERVICE
'use strict';

/**
 * $singular_name service
 */

const { createCoreService } = require('@strapi/strapi').factories;
module.exports = createCoreService('api::$table_name.$table_name');
SERVICE;

        echo "5. Create 'src/api/$table_name/services/$table_name.js' with the following content:\n";
        echo $service_content . "\n\n";
    }
} else {
    echo "No tables found.";
}

$conn->close();
?>
