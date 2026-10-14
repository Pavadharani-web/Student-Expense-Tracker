
stdio@1df97be77d0b:~/phpmysql$ cd /home/workspace/my-project
EXT=$(dirname $(find $PWD/phpext -name pdo_mysql.so))
php -d extension=$EXT/mysqlnd.so -d extension=$EXT/mysqli.so -d extension=$EXT/pdo_mysql.so -S 0.0.0.0:8080 -t expense-track

https://9b1498117dcd-0af40120-8080.ws6.app/
cd /home/workspace/my-project
EXT=$(dirname $(find $PWD/phpext -name pdo_mysql.so))
php -d extension=$EXT/mysqlnd.so -d extension=$EXT/mysqli.so -d extension=$EXT/pdo_mysql.so -S 0.0.0.0:8080 -t expense-tracker

// Empty when t
he site runs at the web root (php -S ... -t expense-tracker).
// On XAMPP set it to '/expense-tracker'.
if (!defined('BASE_URL')) {
    $h = $_SERVER['HTTP_HOST'] ?? '';
    // opened via ...-5300.ws6.app/proxy/8080  -> needs the prefix
    // opened via ...-8080.ws6.app or localhost -> no prefix
    define('BASE_URL', str_contains($h, '-5300.') ? '/proxy/' . ($_SERVER['SERVER_PORT'] ?? 8080) : '');
}
try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Sessions + helper functions (require_login, redirect, e, render_header ...)
require_once __DIR__ . '/functions.php';