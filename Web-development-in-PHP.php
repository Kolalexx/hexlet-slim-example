19. CRUD

public/index.php
Реализуйте следующие обработчики:
Список постов: /posts
Конкретный пост /posts/:id (например /posts/3)
Посты находятся в репозитории $repo. Каждый пост содержит внутри себя четыре поля:
id
name
body
slug
Каждый пост из списка ведет на страницу конкретного поста. Список нужно вывести с пейджингом по 5 постов на странице. На первой странице первые пять постов, на второй вторые пять и так далее. Переключение между страницами нужно сделать с помощью двух ссылок: назад и вперед. То какая сейчас страница открыта, определяется параметром page. По умолчанию загружается первая страница.

Страница конкретного поста отображает данные поста и позволяет вернуться на список. Если поста не существует, то страница обработчик должен вернуть код ответа 404 и текст Page not found.

templates/posts/index.phtml
Выведите список добавленных постов. Каждый пост это имя, которое представлено ссылкой ведущей на отображение (show).

templates/posts/show.phtml
Вывод информации о конкретном посте. Выводить только имя и содержимое поста.

Подсказки
Для реализации пейджинга понадобится извлечь все посты из репозитория с помощью метода all()

index.php:
<?php

use Slim\Factory\AppFactory;
use DI\Container;

require '/composer/vendor/autoload.php';

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$repo = new App\PostRepository();

$app->get('/', function ($request, $response) {
    return $this->get('renderer')->render($response, 'index.phtml');
});
// BEGIN (write your solution here)
$app->get('/posts', function ($request, $response) use ($repo) {
    $per = 5;
    $page = $request->getQueryParam('page', 1);
    $offset = ($page - 1) * $per;
    $posts = $repo->all();
    $sliceOfPosts = array_slice($posts, $offset, $per);
    $params = [
        'page' => $page,
        'posts' => $sliceOfPosts
    ];
    return $this->get('renderer')->render($response, 'posts/index.phtml', $params);
})->setName('posts');

$app->get('/posts/{id}', function ($request, $response, array $args) use ($repo) {
    $id = $args['id'];
    $post = $repo->find($id);
    if (!$post) {
        return $response->withStatus(404)->write('Page not found');
    }
    $params = [
        'post' => $post,
    ];
    return $this->get('renderer')->render($response, 'posts/show.phtml', $params);
})->setName('post');
// END
$app->run();

show.phtml:
<a href="/posts">Посты</a>

<!-- BEGIN (write your solution here) -->
<h1><?= htmlspecialchars($post['name']) ?></h1>
<div>
    <?= htmlspecialchars($post['body']) ?>
</div>
<!-- END -->

index.phtml:
<!-- BEGIN (write your solution here) -->
<?php foreach ($posts as $post) : ?>
    <div>
      <a href="/posts/<?= $post['id'] ?>"><?= htmlspecialchars($post['name']) ?></a>
    </div>
  <?php endforeach ?>
  <br>
  <div>
  <a href="?page=<?= $page < 2 ? 1 : $page - 1 ?>">Prev</a> <a href="?page=<?= $page + 1 ?>">Next</a>
  </div>
<!-- END -->


20. CRUD: Создание

public/index.php
Реализуйте следующие обработчики:

Форма создания нового поста: GET /posts/new
Создание поста: POST /posts
Посты содержат два поля name и body, которые обязательны к заполнению. Валидация уже написана.

Реализуйте вывод ошибок валидации в форме.
После каждого успешного действия нужно добавлять флеш сообщение и выводить его на списке постов. Текст:

Post has been created
templates/posts/new.phtml
Форма для создания поста

Подсказки
Для редиректов в обработчиках используйте именованный роутинг

public/index.php:

<?php

use Slim\Factory\AppFactory;
use DI\Container;

require '/composer/vendor/autoload.php';

$container = new Container();
$container->set('renderer', function () {
    return new Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new Slim\Flash\Messages();
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$repo = new App\PostRepository();
$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    return $this->get('renderer')->render($response, 'index.phtml');
});

$app->get('/posts', function ($request, $response) use ($repo) {
    $flash = $this->get('flash')->getMessages();

    $params = [
        'flash' => $flash,
        'posts' => $repo->all()
    ];
    return $this->get('renderer')->render($response, 'posts/index.phtml', $params);
})->setName('posts');

// BEGIN (write your solution here)
$app->get('/posts/new', function ($request, $response) {
    $params = [
        'postData' => [],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, 'posts/new.phtml', $params);
});

$app->post('/posts', function ($request, $response) use ($repo, $router) {
    $postData = $request->getParsedBodyParam('post');

    $validator = new App\Validator();
    $errors = $validator->validate($postData);

    if (count($errors) === 0) {
        $repo->save($postData);
        $this->get('flash')->addMessage('success', 'Post has been created');
        return $response->withRedirect($router->urlFor('posts'));
    }

    $params = [
        'postData' => $postData,
        'errors' => $errors
    ];
    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, 'posts/new.phtml', $params);
});
// END
$app->run();


templates/posts/new.phtml:

<a href="/posts">Посты</a>

<form action="/posts" method="post">
  <div>
    <label>
        Имя *
      <input type="text" name="post[name]" value="<?= htmlspecialchars($postData['name'] ?? '') ?>">
    </label>
    <?php if (isset($errors['name'])): ?>
      <div><?= $errors['name'] ?></div>
    <?php endif ?>
    </div>
  <div>
    <label>
      Содержимое *
    </label>
    <textarea type="text" rows="20" cols="80" name="post[body]"><?= htmlspecialchars($postData['body'] ?? '') ?></textarea>
    <?php if (isset($errors['body'])): ?>
      <div><?= $errors['body'] ?></div>
    <?php endif ?>
  </div>
  <input type="submit" value="Create">
</form>


21. CRUD: Обновление

public/index.php
Реализуйте следующие обработчики:
Форма редактирования поста: GET /posts/{id}/edit
Обновление поста: PATCH /posts/{id}
Посты содержат поля name и body, которые обязательны к заполнению. Валидация уже написана. После каждого успешного действия нужно добавлять флеш сообщение и выводить его на списке постов. Текст:
Post has been updated

templates/posts/edit.phtml
Форма для редактирования поста. Общая часть формы уже выделена в шаблон _form, подключите его по аналогии с templates/posts/new.phtml.

Подсказки
Для редиректов в обработчиках используйте именованный роутинг

public/index.php:

<?php

use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Middleware\MethodOverrideMiddleware;

require '/composer/vendor/autoload.php';

$container = new Container();
$container->set('renderer', function () {
    return new Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new Slim\Flash\Messages();
});

$app = AppFactory::createFromContainer($container);
$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true);

$repo = new App\PostRepository();
$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    return $this->get('renderer')->render($response, 'index.phtml');
});

$app->get('/posts', function ($request, $response) use ($repo) {
    $flash = $this->get('flash')->getMessages();

    $params = [
        'flash' => $flash,
        'posts' => $repo->all()
    ];
    return $this->get('renderer')->render($response, 'posts/index.phtml', $params);
})->setName('posts');

$app->get('/posts/new', function ($request, $response) use ($repo) {
    $params = [
        'postData' => [],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, 'posts/new.phtml', $params);
});

$app->post('/posts', function ($request, $response) use ($repo, $router) {
    $postData = $request->getParsedBodyParam('post');

    $validator = new App\Validator();
    $errors = $validator->validate($postData);

    if (count($errors) === 0) {
        $id = $repo->save($postData);
        $this->get('flash')->addMessage('success', 'Post has been created');
        return $response->withHeader('X-ID', $id)
                        ->withRedirect($router->urlFor('posts'));
    }

    $params = [
        'postData' => $postData,
        'errors' => $errors
    ];

    return $this->get('renderer')->render($response->withStatus(422), 'posts/new.phtml', $params);
});

// BEGIN (write your solution here)
$app->get('/posts/{id}/edit', function ($request, $response, array $args) use ($repo) {
    $post = $repo->find($args['id']);
    $params = [
        'post' => $post,
        'errors' => [],
        'postData' => $post  //Вьюха edit выводится не только при открытии страницы редактирования, но и когда не проходит валидация не обновление. В таком случае выводят форму, данные формы и модель (то, что мы редактируем). Если данные не валидные, то их нельзя смешивать. Из поста же мы берем данные ID для роута. Поэтому на view они разделены на post и postData.
    ];
    return $this->get('renderer')->render($response, 'posts/edit.phtml', $params);
});

$app->patch('/posts/{id}', function ($request, $response, array $args) use ($repo, $router) {
    $post = $repo->find($args['id']);
    $postData = $request->getParsedBodyParam('post');

    $validator = new App\Validator();
    $errors = $validator->validate($postData);

    if (count($errors) === 0) {
        $post['name'] = $postData['name'];
        $post['body'] = $postData['body'];
        $repo->save($post);
        $this->get('flash')->addMessage('success', 'Post has been updated');
        return $response->withRedirect($router->urlFor('posts'));
    }

    $params = [
        'post' => $post,
        'postData' => $postData,
        'errors' => $errors
    ];

    return $this->get('renderer')
                ->render($response->withStatus(422), 'posts/edit.phtml', $params);
});
// END

$app->run();


templates/posts/edit.phtml:

<a href="/posts">Посты</a>

<!-- BEGIN (write your solution here) -->
<form action="/posts/<?= $post['id'] ?>" method="post">
  <input type="hidden" name="_METHOD" value="PATCH">
  <?php require '_form.phtml' ?>
  <input type="submit" value="Update">
</form>
<!-- END -->

_form.phtml:

<div>
    <label>
        Имя *
        <input type="text" name="post[name]" value="<?= htmlspecialchars($postData['name'] ?? '') ?>">
    </label>
    <?php if (isset($errors['name'])): ?>
        <div><?= $errors['name'] ?></div>
    <?php endif ?>
</div>
<div>
    <label>
        Содержимое *
    </label>
    <textarea type="text" rows="20" cols="80" name="post[body]"><?= htmlspecialchars($postData['body'] ?? '') ?></textarea>
    <?php if (isset($errors['body'])): ?>
        <div><?= $errors['body'] ?></div>
    <?php endif ?>
</div>


22. CRUD: Удаление

public/index.php
Реализуйте удаление поста (обработчик DELETE /posts/{id})
После каждого успешного действия нужно добавлять флеш сообщение и выводить его на списке постов. Текст:
Post has been removed

templates/posts/index.phtml
Реализуйте вывод списка постов и добавьте к каждому посту кнопку на удаление.

Подсказки
Для редиректов в обработчиках используйте именованный роутинг


public/index.php:

<?php

use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Middleware\MethodOverrideMiddleware;

require '/composer/vendor/autoload.php';

$container = new Container();
$container->set('renderer', function () {
    return new Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new Slim\Flash\Messages();
});

$app = AppFactory::createFromContainer($container);
$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true);

$repo = new App\PostRepository();

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    return $this->get('renderer')->render($response, 'index.phtml');
});

$app->get('/posts', function ($request, $response) use ($repo) {
    $flash = $this->get('flash')->getMessages();

    $params = [
        'flash' => $flash,
        'posts' => $repo->all()
    ];
    return $this->get('renderer')->render($response, 'posts/index.phtml', $params);
})->setName('posts');

$app->get('/posts/new', function ($request, $response) use ($repo) {
    $params = [
        'postData' => [],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, 'posts/new.phtml', $params);
});

$app->post('/posts', function ($request, $response) use ($repo, $router) {
    $postData = $request->getParsedBodyParam('post');

    $validator = new App\Validator();
    $errors = $validator->validate($postData);

    if (count($errors) === 0) {
        $id = $repo->save($postData);
        $this->get('flash')->addMessage('success', 'Post has been created');
        return $response->withHeader('X-ID', $id)
                        ->withRedirect($router->urlFor('posts'));
    }

    $params = [
        'postData' => $postData,
        'errors' => $errors
    ];

    return $this->get('renderer')->render($response->withStatus(422), 'posts/new.phtml', $params);
});

// BEGIN (write your solution here)
$app->delete('/posts/{id}', function ($request, $response, array $args) use ($repo, $router) {
    $repo->destroy($args['id']);
    $this->get('flash')->addMessage('success', 'Post has been deleted');
    return $response->withRedirect($router->urlFor('posts'));
});
// END

$app->run();


templates/posts/index.phtml:

<?php if (count($flash) > 0): ?>
    <ul>
    <?php foreach ($flash as $messages): ?>
        <?php foreach ($messages as $message): ?>
            <li><?= $message ?></li>
        <?php endforeach ?>
    <?php endforeach ?>
    </ul>
  <?php endif ?>
  
  <a href="/posts/new">Новый пост</a>
  
  <!-- BEGIN (write your solution here) -->
  <?php foreach ($posts as $post): ?>
    <div>
      <?= htmlspecialchars($post['name']) ?>
      <form action="/posts/<?= $post['id'] ?>" method="post">
        <input type="hidden" name="_METHOD" value="DELETE">
        <input type="submit" value="Delete">
      </form>
    </div>
  <?php endforeach ?>
  <!-- END -->


  
24. Cookies


public/index.php
Реализуйте два обработчика
POST /cart-items для добавления товаров в корзину
DELETE /cart-items для очистки корзины
Корзина должна храниться на клиенте в куках. Кроме самого товара, необходимо хранить количество единиц. Добавление товара приводит к увеличению счетчика и редиректу на главную. Подробнее смотрите в шаблоне. Для сериализации данных используйте json_encode().


templates/index.php:

<form action="/cart-items" method="post">
    <input type="hidden" name="item[id]" value="1">
    <input type="hidden" name="item[name]" value="One">
    One
    <input type="submit" value="Add">
</form>

<form action="/cart-items" method="post">
    <input type="hidden" name="item[id]" value="2">
    <input type="hidden" name="item[name]" value="Two">
    Two
    <input type="submit" value="Add">
</form>

<form action="/cart-items" method="post">
    <input type="hidden" name="_METHOD" value="DELETE">
    <input type="submit" value="Clean">
</form>

<?php if (count($cart) === 0) : ?>
    <div>Cart is empty</div>
<?php else : ?>
    <?php foreach ($cart as $item) : ?>
        <div>
            <?= htmlspecialchars($item['name']) ?>: <?= htmlspecialchars($item['count']) ?>
        </div>
    <?php endforeach ?>
<?php endif ?>


public/index.php:

<?php

use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Middleware\MethodOverrideMiddleware;

require '/composer/vendor/autoload.php';

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    $cart = json_decode($request->getCookieParam('cart', json_encode([])), true);
    $params = [
        'cart' => $cart
    ];
    return $this->get('renderer')->render($response, 'index.phtml', $params);
});

// BEGIN (write your solution here)
$app->post('/cart-items', function ($request, $response) {
    $item = $request->getParsedBodyParam('item');
    $cart = json_decode($request->getCookieParam('cart', json_encode([])), true);

    $id = $item['id'];
    if (!isset($cart[$id])) {
        $cart[$id] = ['name' => $item['name'], 'count' => 1];
    } else {
        $cart[$id]['count'] += 1;
    }

    $encodedCart = json_encode($cart);
    return $response->withHeader('Set-Cookie', "cart={$encodedCart}")
        ->withRedirect('/');
});

$app->delete('/cart-items', function ($request, $response) {
    $encodedCart = json_encode([]);
    return $response->withHeader('Set-Cookie', "cart={$encodedCart}")
        ->withRedirect('/');
});
// END

$app->run();



25. Сессия


В этой практике необходимо реализовать систему аутентификации. В простейшем случае она состоит из двух маршрутов:
POST /session - создает сессию
DELETE /session - удаляет сессию
После выполнения каждого из этих действий происходит редирект на главную.

templates/index.phtml
Если пользователь не аутентифицирован, то ему показывается форма с текстом "Sign In" полем для ввода имени и пароля. Если аутентифицирован, то его имя и форма с кнопкой "Sign Out".
Для полей формы используйте имена user[name] и user[password].

public/index.php
Реализуйте указанные выше маршруты и дополнительно маршрут /.
Список пользователей с именами и паролями доступен в массиве $users. Обратите внимание на то что пароль хранится в зашифрованном виде (их не хранят в открытом виде). Это значит, что при сравнении необходимо шифровать пароль, приходящий от пользователя, и сравнивать хеши.
Если имя или пароль неверные, то происходит редирект на главную, и показывается флеш сообщение Wrong password or name.


templates/index.phtml:

<?php if (count($flash) > 0): ?>
    <ul>
    <?php foreach ($flash as $messages): ?>
        <?php foreach ($messages as $message): ?>
            <li><?= $message ?></li>
        <?php endforeach ?>
    <?php endforeach ?>
    </ul>
  <?php endif ?>
  
  <!-- BEGIN (write your solution here) -->
<?php if ($currentUser): ?>
    <div><?= $currentUser['name'] ?></div>
    <form action="/session" method="post">
        <input type="hidden" name="_METHOD" value="DELETE">
        <input type="submit" value="Sign Out">
    </form>
<?php else: ?>
    <form action="/session" method="post">
        <input type="text" required name="user[name]" value="">
        <input type="password" required name="user[password]" value="">
        <input type="submit" value="Sign In">
    </form>
<?php endif; ?>
<!-- END -->


public/index.php:

<?php

use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Middleware\MethodOverrideMiddleware;

require '/composer/vendor/autoload.php';

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
$app->addErrorMiddleware(true, true, true);
$app->add(MethodOverrideMiddleware::class);

$users = [
    ['name' => 'admin', 'passwordDigest' => hash('sha256', 'secret')],
    ['name' => 'mike', 'passwordDigest' => hash('sha256', 'superpass')],
    ['name' => 'kate', 'passwordDigest' => hash('sha256', 'strongpass')]
];

// BEGIN (write your solution here)
$app->get('/', function ($request, $response) {
    $flash = $this->get('flash')->getMessages();
    $params = [
        'currentUser' => $_SESSION['user'] ?? null,
        'flash' => $flash
    ];
    return $this->get('renderer')->render($response, 'index.phtml', $params);
});

$app->post('/session', function ($request, $response) use ($users) {
    $userData = $request->getParsedBodyParam('user');

    $user = collect($users)->first(function ($user) use ($userData) {
        return $user['name'] === $userData['name']
            && hash('sha256', $userData['password']) === $user['passwordDigest'];
    });

    if ($user) {
        $_SESSION['user'] = $user;
    } else {
        $this->get('flash')->addMessage('error', 'Wrong password or name');
    }
        return $response->withRedirect('/');
});

$app->delete('/session', function ($request, $response) {
    $_SESSION = [];
    session_destroy();
    return $response->withRedirect('/');
});
// END

$app->run();
