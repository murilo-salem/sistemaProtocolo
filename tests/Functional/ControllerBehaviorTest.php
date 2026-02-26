<?php

namespace Tests\Functional;

use Entrega;
use Tests\TestCase;
use Usuario;

require_once APP_ROOT . '/app/control/admin/AppSecurity.php';
require_once APP_ROOT . '/app/control/admin/LoginForm.php';
require_once APP_ROOT . '/app/control/entregas/EntregaList.php';
require_once APP_ROOT . '/app/control/entregas/ConsolidarEntregaV2.php';

class ControllerBehaviorTest extends TestCase
{
    public function testAppSecurityRedirectsWhenUserIsUnauthorized(): void
    {
        \TSession::setValue('userid', null);
        \TSession::setValue('usertype', 'cliente');

        \AppSecurity::checkAccess('gestor');

        $this->assertCount(1, \TestSpy::$coreLoads);
        $this->assertSame('LoginForm', \TestSpy::$coreLoads[0]['class']);
    }

    public function testAppSecurityAllowsAuthorizedUser(): void
    {
        \TSession::setValue('userid', 10);
        \TSession::setValue('usertype', 'gestor');

        \AppSecurity::checkAccess('gestor');

        $this->assertCount(0, \TestSpy::$coreLoads);
    }

    public function testLoginFormAuthenticatesRootUser(): void
    {
        putenv('ROOT_USER=root');
        putenv('ROOT_PASS=123');

        \LoginForm::onLogin(['login' => 'root', 'senha' => '123']);

        $this->assertSame('root', \TSession::getValue('usertype'));
        $this->assertNotEmpty(\TestSpy::$scripts);
        $this->assertStringContainsString('DashboardRoot', \TestSpy::$scripts[0]);
    }

    public function testLoginFormAuthenticatesApplicationUser(): void
    {
        $this->seedRecords(Usuario::class, [
            (object) [
                'id' => 77,
                'nome' => 'Gestor Teste',
                'login' => 'gestor1',
                'senha' => password_hash('Senha@123', PASSWORD_DEFAULT),
                'tipo' => 'gestor',
                'ativo' => 1,
            ],
        ]);

        putenv('ROOT_USER');
        putenv('ROOT_PASS');

        \LoginForm::onLogin(['login' => 'gestor1', 'senha' => 'Senha@123']);

        $this->assertSame(77, \TSession::getValue('userid'));
        $this->assertSame('gestor', \TSession::getValue('usertype'));
        $this->assertNotEmpty(\TestSpy::$scripts);
        $this->assertStringContainsString('DashboardGestor', \TestSpy::$scripts[0]);
    }

    public function testLoginFormReturnsErrorForInvalidCredentials(): void
    {
        putenv('ROOT_USER');
        putenv('ROOT_PASS');

        \LoginForm::onLogin(['login' => 'inexistente', 'senha' => 'x']);

        $this->assertCount(1, \TestSpy::$messages);
        $this->assertSame('error', \TestSpy::$messages[0]['type']);
        $this->assertStringContainsString('inv', strtolower(\TestSpy::$messages[0]['message']));
    }

    public function testEntregaListQuickFilterStoresSessionAndReloadsPage(): void
    {
        \EntregaList::onQuickFilter(['filter' => 'aprovado']);

        $this->assertSame('aprovado', \TSession::getValue('EntregaList_quickfilter'));
        $this->assertNull(\TSession::getValue('EntregaList_filter'));
        $this->assertCount(1, \TestSpy::$appLoads);
        $this->assertSame('EntregaList', \TestSpy::$appLoads[0]['class']);
    }

    public function testConsolidarEntregaV2ValidatesMissingId(): void
    {
        \ConsolidarEntregaV2::onConsolidar([]);

        $this->assertCount(1, \TestSpy::$messages);
        $this->assertSame('error', \TestSpy::$messages[0]['type']);
        $this->assertStringContainsString('ID', \TestSpy::$messages[0]['message']);
    }
}
