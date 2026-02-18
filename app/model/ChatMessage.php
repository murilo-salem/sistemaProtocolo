<?php
/**
 * ChatMessage Model
 */
class ChatMessage extends TRecord
{
    const TABLENAME = 'chat_messages';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'serial'; // Using serial for Postgres

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('sender_id');
        parent::addAttribute('receiver_id');
        parent::addAttribute('message');
        parent::addAttribute('is_read');
        parent::addAttribute('created_at');
    }

    public function get_sender()
    {
        return Usuario::find($this->sender_id);
    }

    public function get_receiver()
    {
        return Usuario::find($this->receiver_id);
    }
}
