<?php
/**
 * SystemNotification Model
 */
class SystemNotification extends TRecord
{
    const TABLENAME = 'system_notification';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'serial';

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('system_user_id');
        parent::addAttribute('action_url');
        parent::addAttribute('action_label');
        parent::addAttribute('icon');
        parent::addAttribute('title');
        parent::addAttribute('message');
        parent::addAttribute('dt_message');
        parent::addAttribute('checked');
    }

    /**
     * Register a new notification
     */
    public static function register($user_id, $title, $message, $action_url = null, $action_label = null, $icon = null)
    {
        $notification = new self;
        $notification->system_user_id = $user_id;
        $notification->title = $title;
        $notification->message = $message;
        $notification->dt_message = date('Y-m-d H:i:s');
        $notification->checked = 'N';
        
        if ($action_url) {
            $notification->action_url = $action_url;
            $notification->action_label = $action_label;
        }
        
        if ($icon) {
            $notification->icon = $icon;
        }
        
        $notification->store();
        return $notification;
    }
}
