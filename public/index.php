<?php

use Xenokore\Utility\Helper\JsonHelper;
use Xenokore\Utility\Helper\FileHelper;
use Xenokore\Utility\Helper\StringHelper;

// Load vendor libraries
require __DIR__ . '/../vendor/autoload.php';

//////////////////////////////////////////////////////////////////////////////////////////
//// BOOTSTRAP APP ///////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////

// Set up ENV
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Note count filepath
$note_count_filepath = __DIR__ . '/../notecount';

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

// Get the route and method
$route = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Homepage
if ($route === '/') {
    echo $twig->render('create.html.twig');
    exit;
}

// About page
if ($route === '/about') {
    echo $twig->render('about.html.twig');
    exit;
}

// Read note page
if($route === '/note'){
    echo $twig->render('note.html.twig');
    exit;
}

// Info page
if($route === '/info'){
    echo $twig->render('info.html.twig', [
        'active_notes'               => $redis->dbsize(),
        'total_note_count'           => \intval(\file_get_contents($note_count_filepath)),
        'note_id_length'             => (int) $_ENV['APP_NOTE_ID_LENGTH'],
        'note_passkey_length'        => (int) $_ENV['APP_NOTE_PASS_LENGTH'],
        'ttl'                        => (int) $_ENV['APP_NOTE_TTL'],
    ]);
    exit;
}

// Create a note
if($route === '/note/create' && $method === 'POST'){

    // Define return output
    \header('Content-Type: application/json');

    // Validate POST input
    if(empty($_POST['contents']) || !is_string($_POST['contents'])){
        echo JsonHelper::encode([
            'success' => false
        ]);
        exit;
    }

    try {

        // Generate unique note ID
        $note_id = StringHelper::generate($_ENV['APP_NOTE_ID_LENGTH']);
        while($redis->exists($note_id)){
            $note_id = StringHelper::generate($_ENV['APP_NOTE_ID_LENGTH']);
        }

        // Add note to redis
        $redis->set($note_id, $_POST['contents'], 'EX', (int) $_ENV['APP_NOTE_TTL']);

        // Increment note counter
        if($_ENV['APP_TRACK_NOTE_COUNT']){
            try {
                FileHelper::createIfNotExist($note_count_filepath);
                $str = \file_get_contents($note_count_filepath);
                $count = \intval($str) + 1;
                \file_put_contents($note_count_filepath, $count);
            } catch (\Exception $ex) {
            }
        }

        // Send note ID back to client
        echo JsonHelper::encode([
            'success' => true,
            'id' => $note_id
        ]);
        exit;

    } catch (\Exception $ex) {

        // Something went wrong
        echo JsonHelper::encode([
            'success' => false
        ]);
        exit;
    }

}

// Get and remove a note
// > The reason we use a POST request here is so the note id won't be logged in any webserver access logs
if($route === '/note/read' && $method === 'POST'){

    // Define return output
    \header('Content-Type: application/json');

    // Validate POST input
    if(empty($_POST['id']) || !is_string($_POST['id'])){
        echo JsonHelper::encode([
            'success' => false
        ]);
        exit;
    }

    $id = $_POST['id'];

    try {

        $contents = $redis->get($id);
        $redis->del($id);

        // Note contents not found
        if(!$contents){
            echo JsonHelper::encode([
                'success' => false
            ]);
            exit;
        }

        // Return note contents to client
        echo JsonHelper::encode([
            'success' => true,
            'contents' => $contents
        ]);
        exit;

    } catch (\Exception $ex) {

        // Something went wrong
        echo JsonHelper::encode([
            'success' => false
        ]);
        exit;
    }

}

// Serve static `node_modules` files
if (StringHelper::startsWith($route, '/node_modules/')) {
    $path = StringHelper::replace($route, [
        '../' => '/',
        '//'  => '/'
    ]);
    $path = \realpath(__DIR__ . '/..' . $path);
    FileHelper::outputFileToBrowser($path, \basename($path));
    exit;
}

// 404
\header('HTTP/1.0 404 Not Found');
echo '<h1>404 Not Found</h1>';
echo 'The resource could not be found.';
exit;
