<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\TestCase;

class NoteTest extends TestCase {
	private $client;

	public function setUp(): void {
		$this->client = new Client( [ 'base_uri' => 'http://localhost:8000' ] );
	}

	public function tearDown(): void {
		$this->client = null;
	}

	public function testListNotes() {

		/** @var Response $response */
		$response = $this->client->get( '/notes/' );

		$this->assertEquals( 200, $response->getStatusCode()) ;

		$body = $response->getBody();
		$data = json_decode($body, true);
		$this->assertIsArray( $data );
	}

	public function testCreateNote() {

		/** @var Response $response */
		$response = $this->client->post('/notes/', [
			RequestOptions::JSON => [
				'title' => 'Note title',
				'content' => 'Note content',
			],
			RequestOptions::HEADERS => [
				'Content-Type' => 'application/json'
			]
		]);

		$this->assertEquals( 201, $response->getStatusCode() );
	}

	public function testReadNote() {

		/** @var Response $response */
		$response = $this->client->get( '/notes/1' );

		$this->assertEquals( 200, $response->getStatusCode() );

		$body = $response->getBody();
		$data = json_decode($body, true);
		$this->assertIsArray( $data );

		$this->assertArrayHasKey('id', $data);
		$this->assertArrayHasKey('title', $data);
		$this->assertArrayHasKey('content', $data);
		$this->assertArrayHasKey('created', $data);
	}

	public function testUpdateNote() {

		/** @var Response $response */
		$response = $this->client->put('/notes/2', [
			RequestOptions::JSON => [
				'title' => 'Note title',
				'content' => 'Note content',
			],
			RequestOptions::HEADERS => [
				'Content-Type' => 'application/json'
			]
		]);

		$this->assertEquals( 204, $response->getStatusCode() );
	}

	public function testDeleteNote() {

		/** @var Response $response */
		$response = $this->client->delete( '/notes/1' );

		$this->assertEquals( 200, $response->getStatusCode() );
	}

}