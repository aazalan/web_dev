<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$app = AppFactory::createFromContainer($container);

$app->addErrorMiddleware(true, true, true);

$users = ['mike', 'mishel', 'adel', 'keks', 'kamila'];

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Slim!');
    return $response;
});

$app->get('/users', function ($request, $response) use ($users) {
    $queries = $request->getQueryParams();
    $term = $queries['term'] ?? '';
    $filtredUsers = array_filter($users, fn($user) => str_contains($user, $term));
    $params = [ 'users' => $filtredUsers ];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
});

// $app->post('/users', function ($request, $response) {
//     return $response->withStatus(302);
// });

$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    $response->getBody()->write("Course id: {$id}");
    return $response;
});

class Validator {
    public function validate ($user) : array
    {
        $errors = [];
        if (empty($user['name'])) {
            $errors['name'] = "Can't be blank";
        }
        if (empty($user['email'])) {
            $errors['email'] = "Can't be blank";
        }
        if (empty($user['password'])) {
            $errors['password'] = "Can't be blank";
        }
        if (empty($user['city'])) {
            $errors['city'] = "Can't be blank";
        }
        return $errors;
    }
}

$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['name' => '', 'email' => '', 'password' => '', 'passwordConfirmation' => '', 'city' => ''],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
});

$app->get('/users/{id}', function ($request, $response, $args) {
    $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
    // Указанный путь считается относительно базовой директории для шаблонов, заданной на этапе конфигурации
    // $this доступен внутри анонимной функции благодаря https://php.net/manual/ru/closure.bindto.php
    // $this в Slim это контейнер зависимостей
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
});

$app->post('/users', function ($request, $response) {
    $body = $request->getParsedBody();
    $user = $body['user'];
    $validator = new Validator();
    $errors = $validator->validate($user);
    if (count($errors) === 0) {
        file_put_contents('registred-users.phtml', json_encode($user) . "\n", FILE_APPEND);
        return $response->withStatus(302);
    }
    $params = [ 'user' => $user, 'errors' => $errors ];
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
});

$app->run();