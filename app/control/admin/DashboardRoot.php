<?php
class DashboardRoot extends TPage
{
    public function __construct()
    {
        parent::__construct();

        try {
            TTransaction::open('database');

            // Contadores Globais
            $total_gestores = Usuario::where('tipo', '=', 'gestor')->count();
            $total_usuarios = Usuario::where('tipo', '=', 'cliente')->count();
            $total_empresas = CompanyTemplate::count();
            $total_entregas = Entrega::count();

            // RENDERIZA HTML
            $html = new THtmlRenderer('app/resources/dashboard_root.html');

            $replacements = [
                'total_gestores' => $total_gestores,
                'total_usuarios' => $total_usuarios,
                'total_empresas' => $total_empresas,
                'total_entregas' => $total_entregas
            ];

            $html->enableSection('main', $replacements);

            $container = new TVBox;
            $container->style = 'width: 100%';
            $container->add(new TXMLBreadCrumb('menu-root.xml', __CLASS__));
            $container->add($html);

            TTransaction::close();

            parent::add($container);

        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}
