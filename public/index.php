<?php

require __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;

session_start();

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->add(MethodOverrideMiddleware::class);

function readUsersList() {
    $fileToString = file_get_contents('users.json');
    $users = json_decode($fileToString, true);
    return $users;
}

function saveUsersList($users) {
    $json = json_encode($users);
    file_put_contents('users.json', $json);
}

function validate($userData) {
    $errors = [];
    if (empty($userData['name'])) {
        $errors['name'] = "Can't be blank";
    }
    if (empty($userData['email'])) {
        $errors['email'] = "Can't be blank";
    }
    return $errors;
}

function findUser($users, $id) {
    $user = [];
    foreach ($users as $us) {
        if ($us['id'] == $id) {
            return $user = $us;
        }
    }
}

function validateNewId($id, $users) {
    $acc = 0;
    foreach ($users as $user) {
        if ($user['id'] == $id) {
            $acc = 1;
        }
    }
    if ($acc == 1) {
        $newId = $id + 1;
        return validateNewId($newId, $users);
    } else {
        return $id;
    }
}

$app->get('/foo', function ($req, $res) {
    $this->get('flash')->addMessage('success', 'This is a message');
    return $res->withRedirect('/bar');
});

$app->get('/bar', function ($req, $res) {
    $messages = $this->get('flash')->getMessages();
    $params = ['flash' => $messages];
    return $this->get('renderer')->render($res, 'users/bar.phtml', $params);
});

$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    return $response->write('Welcome to Slim!');
});

$app->get('/users', function ($request, $response) {
    $messages = $this->get('flash')->getMessages();
    $users = readUsersList();
    $params = ['users' => $users, 'flash' => $messages];
    return $this->get('renderer')->render($response, "users/index.phtml", $params);
})->setName('users');

$app->get('/users/new', function ($request, $response) {
    $params = [
        'userData' => ['name' => '', 'email' => ''],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
})->setName('newUser');

$app->get('/users/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    $users = readUsersList();
    $user = findUser($users, $id);
    $params = [
        'user' => $user
    ];
    if (!$user) {
        return $response->write('Page not found')
            ->withStatus(404);
    }
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('user');

$router = $app->getRouteCollector()->getRouteParser();

$app->post('/users', function ($request, $response) use ($router) {
    $users = readUsersList();
    $userData = $request->getParsedBodyParam('user');
    $errors = validate($userData);
    if (count($errors) === 0) {
        if (empty($users)) {
            $id = 1;
        } else {
            $id = count($users) + 1;
        }
        $newId = validateNewId($id, $users);
        $userData['id'] = $newId;
        $users[] = $userData;
        saveUsersList($users);
        $this->get('flash')->addMessage('success', 'User has been created');
        $url = $router->urlFor('users');
        return $response->withRedirect($url);
    }
    $params = [
        'userData' => $userData,
        'errors' => $errors
    ];
    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
});

$app->get('/users/{id}/edit', function ($request, $response, array $args) {
    $id = $args['id'];
    $users = readUsersList();
    $user = findUser($users, $id);
    $params = [
        'user' => $user,
        'errors' => [],
        'userData' => $user
    ];
    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
})->setName('editUser');

$app->patch('/users/{id}', function ($request, $response, array $args) use ($router)  {
    $id = $args['id'];
    $users = readUsersList();
    $user = findUser($users, $id);
    $userData = $request->getParsedBodyParam('user');
    $errors = [];
    if (empty($userData['name'])) {
        $errors['name'] = "Can't be blank";
    }
    if (count($errors) === 0) {
        $user['name'] = $userData['name'];
        $this->get('flash')->addMessage('success', 'User has been updated');
        $updateUsers = [];
        foreach ($users as $us) {
            if ($us['id'] == $id) {
                $updateUsers[] = $user;
            } else {
                $updateUsers[] = $us;
            }
        }
        saveUsersList($updateUsers);
        $url = $router->urlFor('users');
        return $response->withRedirect($url);
    }
    $params = [
        'user' => $user,
        'userData' => $userData,
        'errors' => $errors
    ];
    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
});

$app->delete('/users/{id}', function ($request, $response, array $args) use ($router) {
    $id = $args['id'];
    $users = readUsersList();
    $updateUsers = [];
    foreach ($users as $us) {
        if ($us['id'] == $id) {
        } else {
            $updateUsers[] = $us;
        }
    }
    saveUsersList($updateUsers);
    $this->get('flash')->addMessage('success', 'User has been deleted');
    return $response->withRedirect($router->urlFor('users'));
});

$app->run();