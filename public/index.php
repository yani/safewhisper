<?php

use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Xenokore\Utility\Helper\JsonHelper;
use Xenokore\Utility\Helper\FileHelper;
use Xenokore\Utility\Helper\StringHelper;

use Slim\Exception\HttpNotFoundException;

// Load vendor libraries
require __DIR__ . '/../vendor/autoload.php';

//////////////////////////////////////////////////////////////////////////////////////////
//// BOOTSTRAP APP ///////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////

// Set up ENV
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Note count filepath
define('NOTE_COUNT_FILEPATH', __DIR__ . '/../notecount');

// Get Twig Instance
$twig_loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../views');
$twig = new \Twig\Environment($twig_loader, [
    'cache' => __DIR__ . '/../cache',
    'debug' => (bool) ($_ENV['APP_ENV'] === 'dev')
]);

// Add Twig global variables
$twig->addGlobal('note_id_length', \intval($_ENV['APP_NOTE_ID_LENGTH']));
$twig->addGlobal('note_pass_length', \intval($_ENV['APP_NOTE_PASS_LENGTH']));
$twig->addGlobal('sw_version', \file_get_contents(__DIR__ . '/../version'));

// Create Redis client options
$redis_options = [];
if(!empty($_ENV['APP_REDIS_PREFIX'])){
    $redis_options['prefix'] = (string) $_ENV['APP_REDIS_PREFIX'];
}

// Connect to Redis cache
$redis = new Predis\Client($_ENV['APP_REDIS_URI'], $redis_options);

//////////////////////////////////////////////////////////////////////////////////////////
//// ROUTING /////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////

$app = AppFactory::create();

// Homepage
$app->get('/', function (Request $request, Response $response) use ($twig) {
    $response->getBody()->write(
        $twig->render('create.html.twig')
    );
    return $response;
});

// About page
$app->get('/about', function (Request $request, Response $response) use ($twig) {
    $response->getBody()->write(
        $twig->render('about.html.twig')
    );
    return $response;
});

// Read note page
$app->get('/note', function (Request $request, Response $response) use ($twig) {
    $response->getBody()->write(
        $twig->render('note.html.twig')
    );
    return $response;
});

// Info page
$app->get('/info', function (Request $request, Response $response) use ($twig, $redis) {

    // Get 'notes in memory'
    $active_note_count = 0;
    if(empty($_ENV['APP_REDIS_PREFIX'])){
        $active_note_count = $redis->dbsize(); // inaccurate, returns number of ALL keys
    } else {
        foreach (new \Predis\Collection\Iterator\Keyspace($redis, $_ENV['APP_REDIS_PREFIX'] . '*') as $key) {
            $active_note_count++;
        }
    }

    echo $twig->render('info.html.twig', [
        'active_note_count'          => $active_note_count,
        'total_note_count'           => \intval(\file_get_contents(NOTE_COUNT_FILEPATH)),
        'note_id_length'             => (int) $_ENV['APP_NOTE_ID_LENGTH'],
        'note_passkey_length'        => (int) $_ENV['APP_NOTE_PASS_LENGTH'],
        'ttl'                        => (int) $_ENV['APP_NOTE_TTL'],
    ]);
    exit;
});

// Create a note
$app->post('/note/create', function (Request $request, Response $response) use ($redis) {

    // Output JSON
    $response = $response->withHeader('Content-Type', 'application/json');

    $post = $request->getParsedBody();

    // Validate POST input
    if(empty($post['contents']) || !is_string($post['contents'])){
        $response->getBody()->write(
            JsonHelper::encode([
                'success' => false
            ])
        );
        return $response;
    }

    try {

        // Generate unique note ID
        $note_id = StringHelper::generate($_ENV['APP_NOTE_ID_LENGTH']);
        while($redis->exists($note_id)){
            $note_id = StringHelper::generate($_ENV['APP_NOTE_ID_LENGTH']);
        }

        // Add note to redis
        $redis->set($note_id, $post['contents'], 'EX', (int) $_ENV['APP_NOTE_TTL']);

        // Increment note counter
        if($_ENV['APP_TRACK_NOTE_COUNT']){
            try {
                FileHelper::createIfNotExist(NOTE_COUNT_FILEPATH);
                $str = \file_get_contents(NOTE_COUNT_FILEPATH);
                $count = \intval($str) + 1;
                \file_put_contents(NOTE_COUNT_FILEPATH, $count);
            } catch (\Exception $ex) {
            }
        }

        // Send note ID back to client
        $response->getBody()->write(
            JsonHelper::encode([
                'success' => true,
                'id' => $note_id
            ])
        );
        return $response;

    } catch (\Exception $ex) {

        // Something went wrong
        $response->getBody()->write(
            JsonHelper::encode([
                'success' => false
            ])
        );
        return $response;
    }
});


// Get and remove a note
// > The reason we use a POST request here is so the note id won't be logged in any webserver access logs
$app->post('/note/read', function (Request $request, Response $response) use ($redis) {

    // Output JSON
    $response = $response->withHeader('Content-Type', 'application/json');

    $post = $request->getParsedBody();

    // Validate POST input
    if(empty($post['id']) || !is_string($post['id'])){
        $response->getBody()->write(
            JsonHelper::encode([
                'success' => false
            ])
        );
        return $response;
    }

    $id = $post['id'];

    try {

        $contents = $redis->get($id);
        $redis->del($id);

        // Note contents not found
        if(!$contents){
            $response->getBody()->write(
                JsonHelper::encode([
                    'success' => false
                ])
            );
            return $response;
        }

        // Return note contents to client
        $response->getBody()->write(
            JsonHelper::encode([
                'success' => true,
                'contents' => $contents
            ])
        );
        return $response;

    } catch (\Exception $ex) {

        // Something went wrong
        $response->getBody()->write(
            JsonHelper::encode([
                'success' => false
            ])
        );
        return $response;
    }

});

// Serve static `node_modules` files
// > This should only be used during development!
$app->get('/node_modules/{path:.+}', function (Request $request, Response $response, $path) use ($twig) {
    if(is_array($path) && isset($path['path'])){
        $path = $path['path'];
    }
    $file_path_str = '/node_modules/' . StringHelper::replace($path, [
        '../' => '/',
        '//'  => '/'
    ]);
    $file_path = \realpath(__DIR__ . '/..' . $file_path_str);
    FileHelper::outputFileToBrowser($file_path, \basename($file_path));
    exit;
});

try {
    $app->run();
} catch (HttpNotFoundException $ex) {
    \header('HTTP/1.0 404 Not Found');
    echo '<h1>404 Not Found</h1>';
    echo 'The resource could not be found.';
}
