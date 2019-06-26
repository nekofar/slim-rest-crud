<?php
require 'vendor/autoload.php';

use Slim\App as App;
use Slim\Http\Request as Request;
use Slim\Http\Response as Response;

// Prepare database connection
ORM::configure('sqlite:' . dirname( __FILE__ ) . '/database.sqlite');
ORM::configure('return_result_sets', true);
ORM::configure('logging',true);

// Creates the table if it doesn't already exist.
$db = ORM::get_db();
$db->exec( "
        CREATE TABLE IF NOT EXISTS note (
            id INTEGER PRIMARY KEY, 
            title TEXT, 
            content TEXT,
            created TEXT
        );"

);

// Create app using Slim
$app = new App( [
	'settings' => [
		'displayErrorDetails' => true
	]
] );

// Add create action
$app->post('/notes/', function ( Request $request, Response $response, $args = []) {

    // Get json object data from request body
    $object = (object) $request->getParsedBody();

    // Create new note based on json object data
    $note = ORM::for_table('note')->create();
	$note->set('title', $object->title);
    $note->set('content', $object->content);
    $note->set('created', date(DateTime::ISO8601));

	if ($note->save() == false) {
        // Set status code on header as bad request
		return $response->withStatus(400);
    }

    // Set status code on header as created
    return $response->withStatus(201)
             ->withHeader('Location', $this->router->pathFor('notes/read', ['id' => $note->id()]));

})->setName('notes/create');

// Add read action
$app->get('/notes/{id}', function ( Request $request, Response $response, $args = []) {

    // Get note data from table using id
    $note = ORM::for_table('note')->find_one($args['id']);

    if ($note == false) {
        // Set status code on header as not found
	    return $response->withStatus(404);
    }

    // Put note data to the empty object
    $object = new stdClass();
    $object->id = $note->get('id');
    $object->title = $note->get('title');
    $object->content = $note->get('content');
    $object->created = $note->get('created');

    // Put note data to the response content as json
    return $response->withJson($object);

})->setName('notes/read');

// Add update action
$app->put('/notes/{id}', function ( Request $request, Response $response, $args = []) {

    // Retrieve note data from table using id
    $note = ORM::for_table('note')->find_one($args['id']);

    if ($note == false) {
        // Set status code on header as not found
	    return $response->withStatus(404);
    }

    // Get json object data from request body
	$object = (object) $request->getParsedBody();

    // Update note based on json object data
    $note->set('title', $object->title);
    $note->set('content', $object->content);

    if ($note->save() == false) {
        // Set status code on header as bad request
	    return $response->withStatus(400);
    }

    // Set status code on header as not content
	return $response->withStatus(204);

})->setName('notes/update');

// Add delete action
$app->delete('/note/{id}', function ( Request $request, Response $response, $args = []) {

    // Find note data from table using id
    $note = ORM::for_table('note')->find_one($args['id']);

    if ($note == false) {
        // Set status code on header as not found
        return $response->withStatus(404);
    }

    // Try to delete note from database
    if ($note->delete() == false) {
        // Set status code on header as internal server error
        return $response->withStatus(500);
    }

    // Set status code on header as not content
    return $response->withStatus(200);

})->setName('notes/delete');

// Add index action
$app->get('/notes/', function ( Request $request, Response $response, $args = []) {

    // Get parameters from request
    $start = $request->getParam('start', 0);
    $limit = $request->getParam('limit', 10);

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
        $object->content = $note->get('content');
        $object->created = $note->get('created');

        array_push($objects, $object);
    }

    // Put notes data to the response content as json
	return $response->withJson( $objects );

});

// Run app
$app->run();