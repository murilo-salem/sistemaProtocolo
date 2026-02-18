<?php

class NotificationList extends TPage
{
    private $datagrid;
    private $pageNavigation;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        
        // Define columns
        $col_icon = new TDataGridColumn('icon', '', 'center', '5%');
        $col_title = new TDataGridColumn('title', 'Título', 'left', '20%');
        $col_message = new TDataGridColumn('message', 'Mensagem', 'left', '50%');
        $col_date = new TDataGridColumn('created_at', 'Data', 'center', '15%');
        $col_status = new TDataGridColumn('read_at', 'Status', 'center', '10%');
        
        $col_icon->setTransformer(function($value, $object, $row) {
            $icon = new TElement('i');
            $icon->class = $value ? $value : 'fa fa-bell';
            return $icon;
        });

        $col_date->setTransformer(function($value) {
            return TDate::convertToMask($value, 'yyyy-mm-dd hh:ii:ss', 'dd/mm/yyyy hh:ii');
        });

        $col_status->setTransformer(function($value) {
            if ($value) {
                return '<span class="badge bg-secondary">Lida</span>';
            }
            return '<span class="badge bg-primary">Não lida</span>';
        });

        $this->datagrid->addColumn($col_icon);
        $this->datagrid->addColumn($col_title);
        $this->datagrid->addColumn($col_message);
        $this->datagrid->addColumn($col_date);
        $this->datagrid->addColumn($col_status);
        
        // Action: Read and View
        $action_view = new TDataGridAction([$this, 'onView']);
        $action_view->setField('id');
        $action_view->setLabel('Visualizar');
        $action_view->setImage('fa:eye blue');
        $this->datagrid->addAction($action_view);
        
        $this->datagrid->createModel();
        
        // Container
        $container = new TVBox;
        $container->style = 'width: 100%';
        try {
            $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        } catch (Exception $e) {
            // ignore if not in menu
        }
        
        // Toolbar
        $toolbar = new TElement('div');
        $toolbar->class = 'btn-toolbar';
        $toolbar->style = 'margin: 10px 0; display: flex; justify-content: space-between;';
        
        $btn_mark_all = new TButton('mark_all');
        $btn_mark_all->setAction(new TAction([$this, 'onMarkAllAsRead']), 'Marcar todas como lidas');
        $btn_mark_all->addStyleClass('btn btn-success');
        $btn_mark_all->setImage('fa:check');
        
        $toolbar->add($btn_mark_all);
        $container->add($toolbar);
        
        // Panel
        $panel = new TPanelGroup('Notificações');
        $panel->add($this->datagrid);
        $container->add($panel);
        
        parent::add($container);
    }
    
    public function onReload()
    {
        try {
            TTransaction::open('database');
            $repo = new TRepository('Notification');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('system_user_id', '=', TSession::getValue('userid')));
            $criteria->setProperty('order', 'created_at');
            $criteria->setProperty('direction', 'desc');
            
            $notifications = $repo->load($criteria);
            $this->datagrid->clear();
            
            if ($notifications) {
                foreach ($notifications as $notification) {
                    $this->datagrid->addItem($notification);
                }
            }
            
            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
    
    public function onView($param)
    {
        try {
            TTransaction::open('database');
            $notification = new Notification($param['id']);
            
            if (!$notification->read_at) {
                $notification->read_at = date('Y-m-d H:i:s');
                $notification->store();
            }
            
            $action_url = $notification->action_url;
            TTransaction::close();
            
            if ($action_url) {
                // If it's a class action
                if (strpos($action_url, 'class=') !== false) {
                    parse_str($action_url, $params);
                    $class = $params['class'];
                    unset($params['class']);
                    TApplication::loadPage($class, 'onLoad', $params);
                } else {
                    // External or other URL
                    TScript::create("window.location.href = '{$action_url}';");
                }
            } else {
                $this->onReload();
            }
            
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
    
    public function onMarkAllAsRead()
    {
        NotificationService::markAllAsRead(TSession::getValue('userid'));
        $this->onReload();
    }
    
    public function show()
    {
        $this->onReload();
        parent::show();
    }
    
    /**
     * Endpoint for AJAX polling
     */
    public static function getUnreadCount($param = null)
    {
        try {
            $count = NotificationService::getUnreadCount(TSession::getValue('userid'));
            echo json_encode(['count' => $count]);
        } catch (Exception $e) {
            echo json_encode(['count' => 0]);
        }
    }
    
    /**
     * Endpoint for AJAX dropdown content
     */
    public static function getLatestNotifications($param = null)
    {
        try {
            TTransaction::open('database');
            $repo = new TRepository('Notification');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('system_user_id', '=', TSession::getValue('userid')));
            $criteria->setProperty('order', 'created_at');
            $criteria->setProperty('direction', 'desc');
            $criteria->setProperty('limit', 5);
            
            $notifications = $repo->load($criteria);
            
            $html = '';
            
            if ($notifications) {
                foreach ($notifications as $n) {
                    $icon = $n->icon ? $n->icon : 'fa-regular fa-bell';
                    $title = mb_strlen($n->title) > 30 ? mb_substr($n->title, 0, 30) . '...' : $n->title;
                    $msg = mb_strlen($n->message) > 40 ? mb_substr($n->message, 0, 40) . '...' : $n->message;
                    $time = TDate::convertToMask($n->created_at, 'yyyy-mm-dd hh:ii:ss', 'dd/mm hh:ii');
                    $readClass = $n->read_at ? 'text-muted' : 'fw-bold';
                    
                    // Action URL logic (similar to onView)
                    $href = "#";
                    $onclick = "";
                    
                    if ($n->action_url) {
                         if (strpos($n->action_url, 'class=') !== false) {
                            parse_str($n->action_url, $params);
                            $class = $params['class'];
                            unset($params['class']);
                            $query = http_build_query($params);
                            $onclick = "Adianti.openPage('{$class}', '{$query}');";
                         } else {
                            $onclick = "window.location.href = '{$n->action_url}';";
                         }
                    }
                    
                    // Mark as read onclick too? Ideally yes, but tricky in simple dropdown. 
                    // Let's just navigate. The view action will mark as read if it goes to NotificationList/onView
                    // But here we might go directly to target. 
                    // Better verify logic: NotificationList::onView handles marking read AND Redirect.
                    // So we should route through NotificationList::onView!
                    $onclick = "Adianti.openPage('NotificationList', 'method=onView&id={$n->id}');";
                    
                    $html .= "<li><a class='dropdown-item' href='#' onclick=\"{$onclick}\">";
                    $html .= "<div class='d-flex align-items-center'>";
                    $html .= "<div class='flex-shrink-0'><i class='{$icon}'></i></div>";
                    $html .= "<div class='flex-grow-1 ms-3'>";
                    $html .= "<h6 class='mb-0 {$readClass}' style='font-size:0.9rem'>{$title}</h6>";
                    $html .= "<small class='text-muted' style='font-size:0.75rem'>{$msg}</small>";
                    $html .= "<div class='text-muted' style='font-size:0.65rem'>{$time}</div>";
                    $html .= "</div>";
                    $html .= "</div></a></li>";
                    $html .= "<li><hr class='dropdown-divider'></li>";
                }
                $html .= "<li><a class='dropdown-item text-center text-primary' href='#' onclick=\"Adianti.openPage('NotificationList')\">Ver todas as notificações</a></li>";
            } else {
                $html .= "<li><span class='dropdown-item text-muted text-center'>Nenhuma notificação</span></li>";
            }
            
            TTransaction::close();
            echo $html;
            
        } catch (Exception $e) {
            echo "<li><span class='dropdown-item text-danger'>Erro ao carregar</span></li>";
        }
    }
}
