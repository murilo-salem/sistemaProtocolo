<?php

class NotificationService
{
    /**
     * Send a single message from one user to another
     */
    public static function send($from_id, $to_id, $subject, $message)
    {
        $close_transaction = false;
        try {
            if (!TTransaction::get()) {
                TTransaction::open('database');
                $close_transaction = true;
            }
            
            $msg = new Mensagem;
            $msg->system_user_id = $from_id;
            $msg->system_user_to_id = $to_id;
            $msg->subject = $subject;
            $msg->message = $message;
            $msg->dt_message = date('Y-m-d H:i:s');
            $msg->checked = 'N';
            $msg->store();
            
            if ($close_transaction) {
                TTransaction::close();
            }
            return true;
        } catch (Exception $e) {
            if ($close_transaction) {
                TTransaction::rollback();
            }
            return false;
        }
    }

    /**
     * Send a message to all users in the 'Gestor' or 'Admin' groups
     */
    public static function notifyGestores($from_id, $subject, $message)
    {
        try {
            TTransaction::open('database');
            
            // Find users with 'Gestor' or 'Admin' role
            // Assuming SystemUserGroup table links SystemUser and SystemGroup
            // Group IDs: 1 (Admin), 2 (Gestor) - Verify IDs in database or use names
            
            $conn = TTransaction::get();
            
            // Query to find managers. Adjust Group IDs as per actual DB. 
            // Usually Admin=1. Let's assume Gestor is 2.
            $sql = "SELECT system_user_id FROM system_user_group WHERE system_group_id IN (1, 2)";
            
            $result = $conn->query($sql);
            $recipients = [];
            
            if ($result) {
                foreach ($result as $row) {
                    $recipients[] = $row['system_user_id'];
                }
            }
            
            TTransaction::close();
            
            foreach (array_unique($recipients) as $to_id) {
                // Avoid sending to self if the sender is also a manager
                if ($to_id != $from_id) {
                    self::send($from_id, $to_id, $subject, $message);
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }
}
