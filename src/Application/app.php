<?php

require_once __DIR__.'/../../vendor/autoload.php';

use Doctrine\DBAL\Schema\Table;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app = new Silex\Application();

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => 'pdo_sqlite',
        'path'     => __DIR__.'/app.db',
    ),
));

$schema = $app['db']->getSchemaManager();
if (!$schema->tablesExist('collaborator')) {
    $collaborator = new Table('collaborator');
    $collaborator->addColumn('id', 'integer', array('unsigned' => true, 'autoincrement' => true));
    $collaborator->setPrimaryKey(array('id'));
    $collaborator->addColumn('name', 'string', array('length' => 32));
    $collaborator->addColumn('role', 'string', array('length' => 32));
    $collaborator->addColumn('identifier', 'integer', array('unsigned' => true));
    $collaborator->addUniqueIndex(['identifier']);

    $schema->createTable($collaborator);
}

$app->get('/collaborators/{id}', function($id) use($app) {
    $sql = "SELECT * FROM collaborator WHERE identifier = ?";

    $collaborator = $app['db']->fetchAssoc($sql, array($id));

    $response = [
        'name' => $collaborator['name'],
        'role' => $collaborator['role'],
    ];

    if($collaborator) {
        return $app->json($response, 200);
    }

    return $app->json('', 404);

});

$app->post('/collaborators', function(Request $request) use($app) {
    //create it really
    return $app->json(['name' => 'a name', 'username' => 'a username', 'role' => 'any role'], 200);
});

$app->delete('/collaborators/{identifier}', function($identifier) use($app) {
    $sql = "SELECT * FROM collaborator WHERE identifier = ?";

    $collaborator = $app['db']->fetchAssoc($sql, array($identifier));

    $statusCode = 200;

    if($collaborator) {
        $deleted = $app['db']->delete('collaborator', ['identifier' => $identifier]);

        if (!$deleted) {
            $statusCode = 500;
        }
    } else {
        $statusCode = 404;
    }

    return $app->json('', $statusCode);

});

$app->after(function (Request $request, Response $response) {
    $response->headers->add(['Content-Type' => 'application/json']);
});


$app->error(function (\Exception $e, Request $request, $code) {
    return new Response(json_encode(['error' => $e->getMessage()]), 500, ['Content-Type' => 'application/json']);
});

return $app;
