<?php

namespace Erlangb\Api\Functional;

use Erlangb\Phpacto\Test\PactoIntegrationTest;
use Erlangb\Phpacto\Test\PactoIntegrationTestInterface;
use Psr\Http\Message\RequestInterface;
use Silex\WebTestCase;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class GetCollaboratorTest extends WebTestCase implements PactoIntegrationTestInterface
{
    /** @var  PactoIntegrationTest */
    private $pacto;

    public function setUp()
    {
        parent::setUp();

        $this->createPactoInstance();
        $this->pacto->loadContracts(__DIR__.'/../Resources');

        $this->app['db']->beginTransaction();
    }

    public function testItHonorsConsumerContracts()
    {
        $this->pacto->honorContracts(
            function(RequestInterface $r) {
                $client = $this->createClient();
                $client->request($r->getMethod(), $r->getUri()->getPath(), [], [], $r->getHeaders(), $r->getBody());
                $r = $client->getResponse();
                $psr7Factory = new DiactorosFactory();
                return $psr7Factory->createResponse($r);
            },
            function($state) {

               switch($state) {
                   case 'there is a collaborator with id 23':
                       $this->loadCollaborator(['name' => 'John', 'role' => 'any role', 'identifier' => 23]);
                       break;
                   case 'there is a collaborator with identifier 111':
                       $this->loadCollaborator(['name' => 'John', 'role' => 'any role', 'identifier' => 111]);
                       break;

                   default: $this->app['db']->delete('collaborator', array('1' => '1'));
                       break;
               }
            },
            function($state) {
                if($this->app['db']->isTransactionActive()) {
                    $this->app['db']->rollback();
                }
            }
        );
    }

    private function loadCollaborator($collaborator)
    {
        $this->app['db']->insert('collaborator', array(
            'name' => $collaborator['name'],
            'role' => $collaborator['role'],
            'identifier' => $collaborator['identifier']
        ));
    }
    /**
     * Creates the application.
     *
     * @return HttpKernelInterface
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../../src/Application/app.php';

        return $app;
    }

    public function createPactoInstance()
    {
       $this->pacto = new PactoIntegrationTest('collaborator api', false);
    }
}