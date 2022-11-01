<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Validator;
use Slim\Factory\AppFactory;
use DI\Container;

session_start();

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

$app = AppFactory::createFromContainer($container);

$app->addErrorMiddleware(true, true, true);

$registredUsersJson = explode(';', file_get_contents('registred-users.phtml'));
$registredUsers = array_map(fn($user) => json_decode($user, true), $registredUsersJson);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) use ($router) {
    // $response->getBody()->write('Welcome to Slim!');
    // return $response;

    $router->urlFor('users');
    $router->urlFor('user', ['id' => 4]);
    return $response;
});

$app->get('/users', function ($request, $response) use ($registredUsers) {
    $queries = $request->getQueryParams();
    $term = $queries['term'] ?? '';
    $usersNames = array_map(fn($user) =>$user['name'] , $registredUsers);
    $filtredUsers = array_filter($usersNames, fn($user) => str_contains(strtolower($user), strtolower($term)));
    if (empty($filtredUsers)) {
        return $response->withStatus(404);
    }
    $params = [ 'users' => $filtredUsers, 'term' => $term ];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users');

$app->post('/users', function ($request, $response) {
    $body = $request->getParsedBody();
    $user = $body['user'];
    $user['id'] = uniqid();
    $validator = new Validator();
    $errors = $validator->validate($user);
    if (count($errors) === 0) {
        file_put_contents('registred-users.phtml', json_encode($user) . ";\n", FILE_APPEND);
        $messages = $this->get('flash')->getMessages();
        $params = ['flash' => $messages];
        return $this->get('renderer')->render($response, 'users/index.phtml', $params);
    }
    $params = [ 'user' => $user, 'errors' => $errors ];
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
});

$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['name' => '', 'email' => '', 'password' => '', 'passwordConfirmation' => '', 'city' => '', 'id' => ''],
        'errors' => []
    ];
    $this->get('flash')->addMessage('success', 'User was added successfully');
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
});

$app->get('/users/{id}', function ($request, $response, $args) use ($registredUsers) {
    
    $currentUser = array_filter($registredUsers, function ($user) use ($args) {
        return $user['id'] === $args['id'];
    });
    if (empty($currentUser)) {
        return $response->withStatus(404);
    }
    $currentUserValues = array_values($currentUser)[0];
    $params = [ 'name' => $currentUserValues['name'],
    'email' => $currentUserValues['email'],
    'city' => $currentUserValues['city'] 
    ];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('user');

$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    $response->getBody()->write("Course id: {$id}");
    return $response;
});

$app->run();