<?php
require 'vendor/autoload.php';

// Define database information
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '123456');
define('DB_NAME', 'self_slimrestcrud');

// Prepare database connection
ORM::configure(
    array(
        'connection_string' => sprintf('mysql:dbname=%s;host=%s', DB_NAME, DB_HOST),
        'username' => DB_USER,
        'password' => DB_PASS
    )
);

// Create app using Slim
$app = new \Slim\Slim(
    array(
        'debug' => true,
        'mode' => 'development',
    )
);

// Add create action
$app->post('/notes/', function () use ($app) {

    // Get json object data from request body
    $object = json_decode($app->request->getBody());

    // Create new note based on json object data
    $note = ORM::for_table('note')->create();
    $note->set('title', $object->title);
    $note->set('text', $object->text);

    if ($note->save() == false) {
        // Set status code on header as bad request
        $app->response->setStatus(400);
        return;
    }

    // Set status code on header as created
    $app->response->setStatus(201);
    $app->response->headers->set('Location', $app->urlFor('notes/read', ['id' => $note->id()]));

})->name('notes/create');

// Add read action
$app->get('/notes/:id', function ($id) use ($app) {

    // Get note data from table using id
    $note = ORM::for_table('note')->find_one($id);

    if ($note == false) {
        // Set status code on header as not found
        $app->response->setStatus(404);
        return;
    }

    // Put note data to the empty object
    $object = new stdClass();
    $object->id = $note->get('id');
    $object->title = $note->get('title');
    $object->text = $note->get('text');
    $object->created_at = $note->get('created_at');

    // Put note data to the response content as json
    $app->response->setStatus(200);
    $app->response->setBody(json_encode($object));
    $app->response->headers->set('Content-Type', 'application/json');

})->name('notes/read');

// Add update action
$app->put('/notes/:id', function ($id) use ($app) {

    // Retrieve note data from table using id
    $note = ORM::for_table('note')->find_one($id);

    if ($note == false) {
        // Set status code on header as not found
        $app->response->setStatus(404);
        return;
    }

    // Get json object data from request body
    $object = json_decode($app->request->getBody());

    // Update note based on json object data
    $note->set('title', $object->title);
    $note->set('text', $object->text);

    if ($note->save() == false) {
        // Set status code on header as bad request
        $app->response->setStatus(400);
        return;
    }

    // Set status code on header as not content
    $app->response->setStatus(204);

})->name('notes/update');

// Add delete action
$app->delete('/note/:id', function ($id) use ($app) {

    // Find note data from table using id
    $note = ORM::for_table('note')->find_one($id);

    if ($note == false) {
        // Set status code on header as not found
        $app->response->setStatus(404);
        return;
    }

    // Try to delete note from database
    if ($note->delete() == false) {
        // Set status code on header as internal server error
        $app->response->setStatus(500);
        return;
    }

    // Set status code on header as not content
    $app->response->setStatus(200);

})->name('notes/delete');

// Add index action
$app->get('/notes/', function () use ($app) {

    // Get parameters from request
    $start = $app->request->get('start', 0);
    $limit = $app->request->get('limit', 10);

    // Retrieve notes using offset and limit from database
    $notes = ORM::for_table('note')
        ->limit($limit)
        ->offset($start)
        ->find_many();

    // Put notes to the clean objects
    $objects = [];
    foreach ($notes as $note) {
        $object = new stdClass();
        $object->id = $note->get('id');
        $object->title = $note->get('title');
        $object->text = $note->get('text');
        $object->created_at = $note->get('created_at');

        array_push($objects, $object);
    }

    // Put notes data to the response content as json
    $app->response->setStatus(200);
    $app->response->setBody(json_encode($objects));
    $app->response->headers->set('Content-Type', 'application/json');

});

// Run app
$app->run();
