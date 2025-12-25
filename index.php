<?php
require_once 'init.php';

$ini = AdiantiApplicationConfig::get();
$theme  = $ini['general']['theme'];
new TSession;

if (!isset($_REQUEST['class'])) {
    $_REQUEST['class'] = 'LoginForm'; // PÁGINA PADRÃO11
}

$method = $_REQUEST['method'] ?? null;
AdiantiCoreApplication::loadPage($_REQUEST['class'], $method, $_REQUEST);

if (isset($_REQUEST['template']) AND $_REQUEST['template'] == 'iframe')
{
	$content = file_get_contents("app/templates/{$theme}/iframe.html");
}   
else
{
	$class = $_REQUEST['class'];

    if ($class === 'WelcomePage') {
    $content = file_get_contents("app/templates/{$theme}/welcome.html");
    $menu_string = '';
    }
    else if ($class === 'LoginForm') {
        $content = file_get_contents("app/templates/{$theme}/login.html");
        $menu_string = '';
    }
    else {
        $content = file_get_contents("app/templates/{$theme}/layout.html");
        // $menu_string = AdiantiMenuBuilder::parse('menu.xml', $theme);
        $user_type = TSession::getValue('usertype');
        if ($user_type == 'gestor') {
            $menu_file = 'menu-gestor.xml';
        } else {
            $menu_file = 'menu-cliente.xml';
        }

        if ($class == 'LoginForm') {
            $menu_string = '';
        } else {
            $menu_string = AdiantiMenuBuilder::parse($menu_file, $theme);
        }
    }

    // $content = str_replace('{content}', AdiantiCoreApplication::getContent(), $content);

}

// $menu_string = AdiantiMenuBuilder::parse('menu.xml', $theme);
$content     = ApplicationTranslator::translateTemplate($content);
$content     = str_replace('{LIBRARIES}', file_get_contents("app/templates/{$theme}/libraries.html"), $content);
$content     = str_replace('{class}', isset($_REQUEST['class']) ? $_REQUEST['class'] : '', $content);
$content     = str_replace('{template}', $theme, $content);
$content     = str_replace('{MENU}', $menu_string, $content);
$content     = str_replace('{MENUTOP}', AdiantiMenuBuilder::parseNavBar('menu-top-public.xml', $theme), $content);
$content     = str_replace('{MENUBOTTOM}', AdiantiMenuBuilder::parseNavBar('menu-bottom-public.xml', $theme), $content);
$content     = str_replace('{lang}', $ini['general']['language'], $content);
$content     = str_replace('{title}', $ini['general']['title'] ?? '', $content);
$content     = str_replace('{template_options}',  json_encode($ini['template'] ?? []), $content);
$content     = str_replace('{adianti_options}',  json_encode($ini['general']), $content);

$css         = TPage::getLoadedCSS();
$js          = TPage::getLoadedJS();
$content     = str_replace('{HEAD}', $css.$js, $content);

echo $content;

if (isset($_REQUEST['class']))
{
    $method = isset($_REQUEST['method']) ? $_REQUEST['method'] : NULL;
    AdiantiCoreApplication::loadPage($_REQUEST['class'], $method, $_REQUEST);
}
