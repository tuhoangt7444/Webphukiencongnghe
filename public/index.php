<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Request;
use App\Core\Router;
use App\Middlewares\AuthMiddleware;

$request = new Request();
$router  = new Router($request);

// khai báo routes
$router->get('/', 'HomeController@index');
$router->get('/products', 'ProductController@index');
$router->get('/products/{id}', 'ProductController@show');
$router->get('/ping', 'HomeController@ping');
$router->get('/go', 'HomeController@go');
$route = $router->get('/admin', 'AdminController@index');
$route->middleware([AuthMiddleware::class]);
$router->get('/fake-login', 'HomeController@fakeLogin');
$router->get('/db-test', 'HomeController@dbTest');
$r = $router->get('/admin/products', 'AdminProductController@index');
$r->middleware([AuthMiddleware::class]);

$r = $router->get('/admin/products/create', 'AdminProductController@create');
$r->middleware([AuthMiddleware::class]);

$r = $router->post('/admin/products', 'AdminProductController@store');
$r->middleware([AuthMiddleware::class]);

$r = $router->get('/admin/products/{id}/edit', 'AdminProductController@edit');
$r->middleware([AuthMiddleware::class]);

$r = $router->post('/admin/products/{id}', 'AdminProductController@update');
$r->middleware([AuthMiddleware::class]);

$r = $router->post('/admin/products/{id}/delete', 'AdminProductController@destroy');
$r->middleware([AuthMiddleware::class]);
$router->dispatch();
$router->get('/admin/products-test', 'HomeController@ping');

$r = $router->get('/admin', 'AdminController@index');
$r->middleware([AuthMiddleware::class]);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ... các phần require autoload ...