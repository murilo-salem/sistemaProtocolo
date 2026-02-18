<?php
/**
 * SystemUserReset Active Record
 */
class SystemUserReset extends TRecord
{
    const TABLENAME = 'system_user_reset';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'serial'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('email');
        parent::addAttribute('token');
        parent::addAttribute('created_at');
        parent::addAttribute('expires_at');
        parent::addAttribute('used');
    }
}
