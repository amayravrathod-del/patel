<?php
// Simple database connection test
echo "<h2>Database Connection Test</h2>";

try {
    $host = 'localhost';
    $db_name = 'u261459251_software';
    $username = 'u261459251_hotel';
    $password = 'Vishraj@9884';
    
    echo "<p>Testing connection with:</p>";
    echo "<ul>";
    echo "<li>Host: " . htmlspecialchars($host) . "</li>";
    echo "<li>Database: " . htmlspecialchars($db_name) . "</li>";
    echo "<li>Username: " . htmlspecialchars($username) . "</li>";
    echo "<li>Password: " . str_repeat('*', strlen($password)) . "</li>";
    echo "</ul>";
    
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db_name;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "<p style='color: green; font-weight: bold;'>✅ Connection successful!</p>";
    
    // Test a simple query
    $stmt = $pdo->query("SELECT DATABASE() as current_db, NOW() as current_time");
    $result = $stmt->fetch();
    
    echo "<p>Current Database: <strong>" . htmlspecialchars($result['current_db']) . "</strong></p>";
    echo "<p>Server Time: <strong>" . htmlspecialchars($result['current_time']) . "</strong></p>";
    
    // Check if tables exist
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "<p style='color: orange;'>⚠️ No tables found. Please run <a href='setup_database.php'>Database Setup</a></p>";
    } else {
        echo "<p>✅ Found " . count($tables) . " tables:</p>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>" . htmlspecialchars($table) . "</li>";
        }
        echo "</ul>";
    }
    
    echo "<hr>";
    echo "<p><a href='setup_database.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Setup Database</a></p>";
    echo "<p><a href='login.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Go to Login</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ Connection failed!</p>";
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    
    echo "<h3>Troubleshooting:</h3>";
    echo "<ul>";
    echo "<li>Check if the database name is correct: <strong>u261459251_software</strong></li>";
    echo "<li>Check if the username is correct: <strong>u261459251_hotel</strong></li>";
    echo "<li>Check if the password is correct</li>";
    echo "<li>Make sure the database exists in your hosting panel</li>";
    echo "<li>Check if the user has proper permissions</li>";
    echo "</ul>";
}
?>