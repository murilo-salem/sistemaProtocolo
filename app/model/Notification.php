<?php
/**
 * Notification Model
 * @package    model
 */
class Notification extends TRecord
{
    const TABLENAME = 'notification';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'serial'; // Using serial for auto-increment

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('system_user_id');
        parent::addAttribute('type'); // info, success, warning, danger
        parent::addAttribute('title');
        parent::addAttribute('message');
        parent::addAttribute('reference_type'); // document, entrega, etc.
        parent::addAttribute('reference_id');
        parent::addAttribute('action_url');
        parent::addAttribute('action_label');
        parent::addAttribute('icon');
        parent::addAttribute('read_at');
        parent::addAttribute('created_at');
    }

    /**
     * Get the user associated with the notification
     */
    public function get_user()
    {
        return new SystemUser($this->system_user_id);
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead()
    {
        $this->read_at = date('Y-m-d H:i:s');
        $this->store();
    }
    
    /**
     * Check if notification is read
     */
    public function isRead()
    {
        return !empty($this->read_at);
    }
}
