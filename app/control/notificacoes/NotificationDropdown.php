<?php
class NotificationDropdown extends TPage
{
    /**
     * Empty show method to prevent Adianti from rendering anything extra
     */
    public function show()
    {
    }

    /**
     * Endpoint for AJAX dropdown content
     */
    public function getLatest($param)
    {
        NotificationService::getLatestNotifications($param);
    }
}
