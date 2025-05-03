<?php


declare(strict_types=1); 


require_once dirname(__DIR__) . '/app/config.php';

require_once BASE_PATH . '/vendor/autoload.php';



use App\Core\App; 

try {

    $app = new App();


    $app->run();

} catch (\Throwable $e) {

    error_log("Fatal Error in index.php: " . $e->getMessage() . "\n" . $e->getTraceAsString());

    http_response_code(500);

    echo "<h1>500 Internal Server Error</h1>";
    echo "Виникла непередбачувана помилка. Будь ласка, спробуйте пізніше.";

    if (ini_get('display_errors')) {
        echo "<hr><pre>" . htmlspecialchars($e->getMessage()) . "\n" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    exit;
}

?>