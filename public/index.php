<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Validator;
use App\Repo;
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Middleware\MethodOverrideMiddleware;

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
$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) use ($router) {
    $router->urlFor('users');
    $router->urlFor('user', ['id' => 4]);
    return $response;
});

$app->get('/users', function ($request, $response) {
    $queries = $request->getQueryParams();
    $term = $queries['term'] ?? '';
    $users = new Repo();
    $filtredUsers = $users->findByName($term);
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
        $repo = new Repo();
        $repo->save($user);
        //file_put_contents('registred-users.phtml', json_encode($user) . ";\n", FILE_APPEND);
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

$app->get('/users/{id}', function ($request, $response, $args) {
    $id = $args['id'];
    $users = new Repo();
    $currentUser = $users->findById($id);
    if (empty($currentUser)) {
        return $response->withStatus(404);
    }
    $params = [ 'name' => $currentUser['name'],
    'email' => $currentUser['email'],
    'city' => $currentUser['city'] 
    ];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('user');

$app->get('/users/{id}/edit', function ($request, $response, $args) {
    $id = $args['id'];
    $repo = new Repo();
    $currentUser = $repo->findById($id);
    $params = [
        'user' => $currentUser,
        'errors' => []
    ];
    $this->get('flash')->addMessage('success', 'User has been updated');
    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
})->setName('editUser');

$app->patch('/users/{id}', function ($request, $response, $args) use ($router) {
    $repo = new Repo();
    $id = $args['id'];
    $user = $repo->findById($id);
    $body = $request->getParsedBody();
    $newData = $body['user'];

    $validator = new Validator();
    $errors = $validator->updatingValidate($newData);
    
    if (count($errors) === 0) {
        $user['name'] = $newData['name'];
        $user['email'] = $newData['email'];
        $repo->save($user);
        $messages = $this->get('flash')->getMessages();
        $params = ['flash' => $messages];
        return $this->get('renderer')->render($response, 'users/index.phtml', $params);
    }
    $params = [ 
        'user' => $user,
        'errors' => $errors
        ];
    return $this->get('renderer')->render($response->withStatus(422), 'users/edit.phtml', $params);
});

$app->get('/users/{id}/delete', function($request, $response, $args) {
    $repo = new Repo();
    $id = $args['id'];
    $currentUser = $repo->findById($id);
    $params = ['user' => $currentUser ];
    $this->get('flash')->addMessage('success', 'User has been deleted');
    return $this->get('renderer')->render($response, 'users/delete.phtml', $params);
});

$app->delete('/users/{id}', function ($request, $response, $args) {
    $repo = new Repo();
    $id = $args['id'];
    $repo->delete($id);
    $messages = $this->get('flash')->getMessages();
    $params = ['flash' => $messages];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
});

// $app->get('/check', function ($request, $response) {
//     $repo = new Repo();
//     $user = $repo->findById('');
//     print_r($user);
//     $params = ['repo' => $user ];
//     return $this->get('renderer')->render($response, 'users/index.phtml', $params);
// }); 

$app->run();