<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/src/db.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

session_start();

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

$app->setBasePath("/SlimDictionaryAPI");

$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

/*
|--------------------------------------------------------------------------
| REGISTER
|--------------------------------------------------------------------------
*/
$app->post('/api/register', function (Request $request, Response $response) use ($pdo) {
    $data = $request->getParsedBody();

    $username = $data['username'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $result = ["message" => "All fields are required"];
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hashedPassword]);
            $result = ["message" => "User registered successfully"];
        } catch (PDOException $e) {
            $result = ["message" => "Registration failed", "error" => $e->getMessage()];
        }
    }

    $response->getBody()->write(json_encode($result));
    return $response->withHeader('Content-Type', 'application/json');
});

/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/
$app->post('/api/login', function (Request $request, Response $response) use ($pdo) {
    $data = $request->getParsedBody();

    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user['email'];
        $result = ["message" => "Login successful"];
    } else {
        $result = ["message" => "Invalid email or password"];
    }

    $response->getBody()->write(json_encode($result));
    return $response->withHeader('Content-Type', 'application/json');
});

/*
|--------------------------------------------------------------------------
| LOGOUT
|--------------------------------------------------------------------------
*/
$app->post('/api/logout', function (Request $request, Response $response) {
    session_destroy();
    $result = ["message" => "Logout successful"];

    $response->getBody()->write(json_encode($result));
    return $response->withHeader('Content-Type', 'application/json');
});

/*
|--------------------------------------------------------------------------
| CREATE DICTIONARY WORD
|--------------------------------------------------------------------------
*/
$app->post('/api/dictionary', function (Request $request, Response $response) use ($pdo) {
    $data = $request->getParsedBody();

    $word = $data['word'] ?? '';
    $meaning = $data['meaning'] ?? '';

    if (empty($word) || empty($meaning)) {
        $result = ["message" => "Word and meaning are required"];
    } else {
        $stmt = $pdo->prepare("INSERT INTO dictionary (word, meaning) VALUES (?, ?)");
        $stmt->execute([$word, $meaning]);
        $result = ["message" => "Dictionary entry added successfully"];
    }

    $response->getBody()->write(json_encode($result));
    return $response->withHeader('Content-Type', 'application/json');
});

/*
|--------------------------------------------------------------------------
| GET ALL DICTIONARY WORDS
|--------------------------------------------------------------------------
*/
$app->get('/api/dictionary', function (Request $request, Response $response) use ($pdo) {
    $stmt = $pdo->query("SELECT * FROM dictionary");
    $words = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response->getBody()->write(json_encode($words));
    return $response->withHeader('Content-Type', 'application/json');
});

/*
|--------------------------------------------------------------------------
| GET ONE DICTIONARY WORD BY ID
|--------------------------------------------------------------------------
*/
$app->get('/api/dictionary/{id}', function (Request $request, Response $response, $args) use ($pdo) {
    $id = $args['id'];

    $stmt = $pdo->prepare("SELECT * FROM dictionary WHERE id = ?");
    $stmt->execute([$id]);
    $word = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($word) {
        $result = $word;
    } else {
        $result = ["message" => "Record not found"];
    }

    $response->getBody()->write(json_encode($result));
    return $response->withHeader('Content-Type', 'application/json');
});

/*
|--------------------------------------------------------------------------
| UPDATE DICTIONARY WORD
|--------------------------------------------------------------------------
*/
$app->put('/api/dictionary', function (Request $request, Response $response) use ($pdo) {
    $data = $request->getParsedBody();

    $id = $data['id'] ?? '';
    $word = $data['word'] ?? '';
    $meaning = $data['meaning'] ?? '';

    if (empty($id) || empty($word) || empty($meaning)) {
        $result = ["message" => "ID, word, and meaning are required"];
    } else {
        $stmt = $pdo->prepare("UPDATE dictionary SET word = ?, meaning = ? WHERE id = ?");
        $stmt->execute([$word, $meaning, $id]);
        $result = ["message" => "Dictionary entry updated successfully"];
    }

    $response->getBody()->write(json_encode($result));
    return $response->withHeader('Content-Type', 'application/json');
});

/*
|--------------------------------------------------------------------------
| DELETE DICTIONARY WORD
|--------------------------------------------------------------------------
*/
$app->delete('/api/dictionary', function (Request $request, Response $response) use ($pdo) {
    $data = $request->getParsedBody();

    $id = $data['id'] ?? '';

    if (empty($id)) {
        $result = ["message" => "ID is required"];
    } else {
        $stmt = $pdo->prepare("DELETE FROM dictionary WHERE id = ?");
        $stmt->execute([$id]);
        $result = ["message" => "Dictionary entry deleted successfully"];
    }

    $response->getBody()->write(json_encode($result));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();