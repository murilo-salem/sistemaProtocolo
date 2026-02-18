<?php

class NotificationService
{
    /**
     * Send a single message from one user to another
     */
    /**
     * Create a new notification
     * 
     * @param int $toUserId Recipient ID
     * @param string $title Notification title
     * @param string $message Notification message body
     * @param string $type success, info, warning, danger
     * @param string $refType document, entrega, etc.
     * @param int $refId ID of the referenced object
     * @param string $actionUrl Optional URL for action
     * @param string $actionLabel Optional label for action button
     * @param string $icon Optional icon class
     */
    public static function create($toUserId, $title, $message, $type = 'info', $refType = null, $refId = null, $actionUrl = null, $actionLabel = null, $icon = null)
    {
        try {
            TTransaction::open('database');
            
            $notification = new Notification;
            $notification->system_user_id = $toUserId;
            $notification->title = $title;
            $notification->message = $message;
            $notification->type = $type;
            $notification->reference_type = $refType;
            $notification->reference_id = $refId;
            $notification->action_url = $actionUrl;
            $notification->action_label = $actionLabel;
            $notification->icon = $icon;
            $notification->created_at = date('Y-m-d H:i:s');
            $notification->store();
            
            TTransaction::close();
            return true;
        } catch (Exception $e) {
            TTransaction::rollback();
            return false;
        }
    }
    
    /**
     * Notify a manager group
     */
    public static function notifyManagers($title, $message, $type = 'info', $refType = null, $refId = null, $actionUrl = null)
    {
        try {
            TTransaction::open('database');
            
            // Buscar gestores e administradores na tabela Usuario
            $gestores = Usuario::where('tipo', 'IN', ['admin', 'gestor'])->load();
            
            if ($gestores) {
                foreach ($gestores as $user) {
                    self::create($user->id, $title, $message, $type, $refType, $refId, $actionUrl, 'Visualizar', 'fa fa-info-circle');
                }
            }
            
            TTransaction::close();
        } catch (Exception $e) {
            // Silently fail
            TTransaction::rollback();
        }
    }

    /**
     * Notify a specific client
     */
    public static function notifyClient($clientId, $title, $message, $type = 'info', $refType = null, $refId = null, $actionUrl = null)
    {
        return self::create($clientId, $title, $message, $type, $refType, $refId, $actionUrl, 'Ver Detalhes', 'fa fa-bell');
    }
    
    /**
     * Get unread count for user
     */
    public static function getUnreadCount($userId)
    {
        try {
            TTransaction::open('database');
            $count = Notification::where('system_user_id', '=', $userId)
                                ->where('read_at', 'IS', NULL)
                                ->count();
            TTransaction::close();
            return $count;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Mark all as read for user
     */
    public static function markAllAsRead($userId)
    {
        try {
            TTransaction::open('database');
            $notifications = Notification::where('system_user_id', '=', $userId)
                                       ->where('read_at', 'IS', NULL)
                                       ->load();
            if ($notifications) {
                foreach ($notifications as $notification) {
                    $notification->read_at = date('Y-m-d H:i:s');
                    $notification->store();
                }
            }
            TTransaction::close();
        } catch (Exception $e) {
            TTransaction::rollback();
        }
    }

    // Keep existing methods for backward compatibility if needed, or deprecate them.
    // For now, replacing the whole class content with relevant logic based on the user request context is safer 
    // but the instruction was to "Update". 
    // I will append/replace methods. The existing file had 'send' and 'notifyGestores'.
    
    public static function send($from_id, $to_id, $subject, $message) {
        // Wrapper for new system or keep legacy? 
        // Let's keep legacy for chat/messages table but redirect 'notifications' to new table if generic.
        // Actually, 'Mensagem' is different from 'Notification'. 
        // I will keep existing methods and ADD the new ones.
        return self::create($to_id, $subject, $message, 'info');
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
                    
                    // Route through NotificationList/onView to mark as read
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
