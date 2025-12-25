<?php
/**
 * Mensagem Record
 * @package    model
 */
class Mensagem extends TRecord
{
    const TABLENAME = 'mensagem';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'max';
    
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('system_user_id');
        parent::addAttribute('system_user_to_id');
        parent::addAttribute('subject');
        parent::addAttribute('message');
        parent::addAttribute('dt_message');
        parent::addAttribute('checked');
    }
    
    public function get_sender()
    {
        return new Usuario($this->system_user_id);
    }
    
    public function get_receiver()
    {
        return new Usuario($this->system_user_to_id);
    }
}
