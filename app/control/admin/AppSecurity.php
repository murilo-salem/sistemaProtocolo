<?php

class AppSecurity
{
    public static function checkAccess($tipo_necessario)
    {
        $userid = TSession::getValue('userid');
        $usertype = TSession::getValue('usertype');

        if (!$userid || $usertype != $tipo_necessario) {
            // Redireciona para login se não estiver autorizado
            AdiantiCoreApplication::loadPage('LoginForm');
        }
    }
}
