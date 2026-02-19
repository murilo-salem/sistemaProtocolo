<?php
/**
 * SystemChat
 * Redesigned for #UX-CHAT-001 (Modern UI/UX)
 */
class SystemChat extends TPage
{
    private $chat_container;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->chat_container = new TElement('div');
        $this->chat_container->class = 'chat-layout';
        
        // CSS for Chat (Modern Redesign)
        $css = new TElement('style');
        $css->add('
            /* Layout & Variables */
            :root {
                --chat-primary: #3b82f6; /* Blue 500 */
                --chat-primary-hover: #2563eb; /* Blue 600 */
                --chat-bg-sidebar: #f5f7fb;
                --chat-bg-main: #ffffff;
                --chat-border: #e2e8f0;
                --chat-text-main: #1e293b;
                --chat-text-light: #64748b;
                --chat-bubble-sent: #3b82f6;
                --chat-bubble-received: #f1f5f9;
            }

            .chat-layout { 
                display: flex; 
                height: 80vh; 
                background: var(--chat-bg-main); 
                border-radius: 12px; 
                box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05); 
                overflow: hidden; 
                font-family: "Inter", "Segoe UI", sans-serif; 
                border: 1px solid var(--chat-border);
            }

            /* Sidebar */
            .chat-sidebar { 
                width: 320px; 
                border-right: 1px solid var(--chat-border); 
                display: flex; 
                flex-direction: column; 
                background: var(--chat-bg-sidebar); 
            }

            .chat-header-sidebar { 
                padding: 16px 20px; 
                border-bottom: 1px solid var(--chat-border); 
                font-weight: 700; 
                color: var(--chat-text-main); 
                background: var(--chat-bg-sidebar); 
                font-size: 16px;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }

            .contact-list { 
                flex: 1; 
                overflow-y: auto; 
                padding: 10px;
            }

            .contact-item { 
                padding: 12px; 
                display: flex; 
                gap: 12px; 
                cursor: pointer; 
                transition: all 0.2s ease; 
                border-radius: 8px;
                margin-bottom: 4px;
                position: relative;
            }

            .contact-item:hover { 
                background: rgba(255, 255, 255, 0.6); 
                box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            }

            .contact-item.active { 
                background: #fff; 
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            }
            
            .contact-item.active::before {
                content: "";
                position: absolute;
                left: 0;
                top: 10%;
                height: 80%;
                width: 3px;
                background: var(--chat-primary);
                border-radius: 0 4px 4px 0;
            }

            .contact-avatar { 
                width: 44px; 
                height: 44px; 
                border-radius: 50%; 
                background: #e0e7ff; 
                color: #4f46e5;
                display: flex; 
                align-items: center; 
                justify-content: center; 
                font-weight: 600; 
                font-size: 15px; 
                flex-shrink: 0;
                position: relative; 
            }

            .contact-info { 
                flex: 1; 
                min-width: 0; 
                display: flex;
                flex-direction: column;
                justify-content: center;
            }

            .contact-top-line {
                display: flex;
                justify-content: space-between;
                align-items: baseline;
                margin-bottom: 2px;
            }

            .contact-name { 
                font-weight: 600; 
                color: var(--chat-text-main); 
                font-size: 14px; 
                white-space: nowrap; 
                overflow: hidden; 
                text-overflow: ellipsis; 
            }
            
            .contact-time {
                font-size: 11px;
                color: var(--chat-text-light);
            }

            .contact-last-msg { 
                font-size: 12px; 
                color: var(--chat-text-light); 
                white-space: nowrap; 
                overflow: hidden; 
                text-overflow: ellipsis; 
                display: block;
            }
            
            .contact-status-dot {
                width: 10px; height: 10px; 
                background: #22c55e; 
                border: 2px solid var(--chat-bg-sidebar); 
                border-radius: 50%; 
                position: absolute; 
                bottom: 0; right: 0;
            }

            /* Main Chat Area */
            .chat-main { 
                flex: 1; 
                display: flex; 
                flex-direction: column; 
                background: var(--chat-bg-main); 
                position: relative;
            }

            .chat-main-header { 
                padding: 10px 20px; 
                border-bottom: 1px solid var(--chat-border); 
                display: flex; 
                align-items: center; 
                gap: 12px; 
                background: #fff; 
                z-index: 10; 
                height: 64px;
            }
            
            .btn-back {
                display: none; /* Mobile only */
                background: none;
                border: none;
                font-size: 18px;
                color: var(--chat-text-main);
                cursor: pointer;
                padding: 8px;
                margin-right: -8px;
            }

            .active-user-info {
                display: flex;
                flex-direction: column;
            }
            
            .active-user-name {
                font-weight: 600;
                color: var(--chat-text-main);
                font-size: 15px;
            }
            
            .active-user-status {
                font-size: 12px;
                color: #22c55e;
                display: flex;
                align-items: center;
                gap: 4px;
            }
            
            .active-user-status::before {
                content: "";
                width: 6px;
                height: 6px;
                background: #22c55e;
                border-radius: 50%;
                display: block;
            }

            .messages-area { 
                flex: 1; 
                padding: 20px; 
                overflow-y: auto; 
                background-color: #f8fafc;
                background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
                background-size: 20px 20px;
                display: flex; 
                flex-direction: column; 
                gap: 8px; 
            }

            .message-bubble { 
                max-width: 70%; 
                padding: 10px 14px; 
                font-size: 14px; 
                line-height: 1.5; 
                position: relative; 
                word-wrap: break-word; 
                box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            }

            .message-sent { 
                align-self: flex-end; 
                background: var(--chat-primary); 
                color: #fff; 
                border-radius: 12px 12px 0 12px; 
            }

            .message-received { 
                align-self: flex-start; 
                background: #fff; 
                color: var(--chat-text-main); 
                border-radius: 12px 12px 12px 0; 
                border: 1px solid #e2e8f0;
            }

            .message-time { 
                font-size: 10px; 
                margin-top: 4px; 
                display: block; 
                opacity: 0.8; 
                text-align: right; 
                min-width: 40px;
            }

            /* Input Area */
            .chat-input-area { 
                padding: 16px 20px; 
                background: #fff; 
                display: flex; 
                gap: 12px; 
                align-items: center; 
                border-top: 1px solid var(--chat-border);
            }

            .chat-input { 
                flex: 1; 
                border: 1px solid #cbd5e1 !important; 
                border-radius: 24px !important; 
                padding: 12px 20px !important; 
                outline: none !important; 
                transition: border 0.2s, box-shadow 0.2s !important; 
                font-size: 14px !important; 
                background: #f8fafc !important;
                height: 44px !important;
            }

            .chat-input:focus { 
                border-color: var(--chat-primary) !important; 
                box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1) !important;
                background: #fff !important;
            }

            .btn-send { 
                width: 44px; 
                height: 44px; 
                border-radius: 50%; 
                background: var(--chat-primary); 
                color: #fff; 
                border: none; 
                cursor: pointer; 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                transition: transform 0.1s, background 0.2s; 
                box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
            }

            .btn-send:hover { 
                background: var(--chat-primary-hover); 
                transform: scale(1.05);
            }
            .btn-send:active {
                transform: scale(0.95);
            }

            .unread-badge { 
                background: #ef4444; 
                color: #fff; 
                font-size: 10px; 
                padding: 2px 6px; 
                border-radius: 10px; 
                margin-left: auto; 
                margin-top: 4px;
                font-weight: 700;
                min-width: 18px;
                text-align: center;
            }

            .placeholder-screen { 
                flex: 1; 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                color: var(--chat-text-light); 
                flex-direction: column; 
                gap: 16px; 
                background: #f8fafc;
            }
            
            .placeholder-illustration {
                width: 120px;
                height: 120px;
                background: #e2e8f0;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 20px;
            }

            /* Scrollbar */
            ::-webkit-scrollbar { width: 6px; }
            ::-webkit-scrollbar-track { background: transparent; }
            ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
            ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

            /* Mobile Responsiveness */
            @media (max-width: 768px) {
                .chat-sidebar { width: 100%; border-right: none; }
                .chat-main { display: none; width: 100%; position: absolute; height: 100%; top: 0; left: 0; z-index: 20; }
                .chat-main.active { display: flex; }
                .chat-sidebar.hidden { display: none; }
                .btn-back { display: block; }
                .chat-layout { height: calc(100vh - 80px); border-radius: 0; border: none; }
            }
        ');
        $this->chat_container->add($css);
        
        // --- Sidebar ---
        $sidebar = new TElement('div');
        $sidebar->class = 'chat-sidebar';
        $sidebar->id = 'chat-sidebar';
        $sidebar->add('<div class="chat-header-sidebar"><span>Mensagens</span> <i class="fa fa-edit" style="cursor:pointer; opacity:0.6;"></i></div>');
        
        $contactList = new TElement('div');
        $contactList->class = 'contact-list';
        $contactList->id = 'contact-list';
        
        // Load contacts logic
        $this->loadContacts($contactList);
        
        $sidebar->add($contactList);
        $this->chat_container->add($sidebar);
        
        // --- Main Area ---
        $main = new TElement('div');
        $main->class = 'chat-main';
        $main->id = 'chat-main';
        
        // Default State (Placeholder)
        $placeholder = new TElement('div');
        $placeholder->class = 'placeholder-screen';
        $placeholder->id = 'chat-placeholder';
        $placeholder->add('
            <div style="text-align:center;">
                <div style="font-size: 64px; color: #cbd5e1; margin-bottom: 20px;"><i class="fa fa-comments"></i></div>
                <h3 style="color: #334155; margin-bottom: 8px;">Suas Mensagens</h3>
                <p style="color: #64748b;">Selecione uma conversa para começar a interagir.</p>
            </div>
        ');
        $main->add($placeholder);
        
        // Chat Content (Hidden initially)
        $content = new TElement('div');
        $content->id = 'chat-content';
        $content->style = 'display: none; flex: 1; flex-direction: column; height: 100%;';
        
        $content->add('
            <div class="chat-main-header">
                <button class="btn-back" onclick="toggleMobileView(false)"><i class="fa fa-arrow-left"></i></button>
                <div class="contact-avatar" id="active-avatar" style="width:36px; height:36px; font-size:13px;">U</div>
                <div class="active-user-info">
                    <div class="active-user-name" id="active-name">Usuário</div>
                    <div class="active-user-status">Online</div>
                </div>
                <div style="margin-left:auto;">
                    <i class="fa fa-ellipsis-v" style="color:var(--chat-text-light); cursor:pointer; padding:8px;"></i>
                </div>
            </div>
            <div class="messages-area" id="messages-area">
                <!-- Messages loaded via JS -->
            </div>
            <div class="chat-input-area">
                <input type="hidden" id="receiver_id">
                <input type="text" class="chat-input" id="message-input" placeholder="Digite sua mensagem..." onkeypress="if(event.keyCode==13) sendMessage()">
                <button class="btn-send" onclick="sendMessage()">
                    <i class="fa fa-paper-plane"></i>
                </button>
            </div>
        ');
        
        $main->add($content);
        $this->chat_container->add($main);
        
        // --- JavaScript ---
        $script = new TElement('script');
        $script->add("
            var currentReceiverId = null;
            var pollingInterval = null;
            
            function selectContact(element, receiverId, name, initials) {
                // UI update
                document.querySelectorAll('.contact-item').forEach(el => el.classList.remove('active'));
                element.classList.add('active');
                
                // Show Chat
                document.getElementById('chat-placeholder').style.display = 'none';
                document.getElementById('chat-content').style.display = 'flex';
                
                // Update Info
                document.getElementById('receiver_id').value = receiverId;
                document.getElementById('active-name').innerText = name;
                document.getElementById('active-avatar').innerText = initials;
                
                currentReceiverId = receiverId;
                document.getElementById('messages-area').innerHTML = ''; // Clear prev chat
                
                loadMessages();
                toggleMobileView(true);
                
                // Start polling
                if (pollingInterval) clearInterval(pollingInterval);
                pollingInterval = setInterval(checkNewMessages, 3000);
                
                // Update global badge
                if (typeof checkChatMessages === 'function') setTimeout(checkChatMessages, 1000);
            }
            
            function toggleMobileView(showChat) {
                const sidebar = document.getElementById('chat-sidebar');
                const main = document.getElementById('chat-main');
                
                if (window.innerWidth <= 768) {
                    if (showChat) {
                        sidebar.classList.add('hidden');
                        main.classList.add('active');
                    } else {
                        sidebar.classList.remove('hidden');
                        main.classList.remove('active');
                        // De-select if going back
                        currentReceiverId = null;
                        if (pollingInterval) clearInterval(pollingInterval);
                    }
                }
            }
            
            function loadMessages() {
                if (!currentReceiverId) return;
                
                __adianti_ajax_exec('class=SystemChat&method=onLoadMessages&user_id=' + currentReceiverId, function(response) {
                     // Handled by PHP, but update badge after
                     if (typeof checkChatMessages === 'function') setTimeout(checkChatMessages, 500);
                });
            }
            
            function sendMessage() {
                var input = document.getElementById('message-input');
                var message = input.value;
                if (!message.trim() || !currentReceiverId) return;
                
                // Optimistic UI could be added here
                
                __adianti_ajax_exec('class=SystemChat&method=onSend&receiver_id=' + currentReceiverId + '&message=' + encodeURIComponent(message));
                
                input.value = '';
                input.focus();
            }
            
            function checkNewMessages() {
                if (!currentReceiverId) return;
                __adianti_ajax_exec('class=SystemChat&method=checkNewMessages&receiver_id=' + currentReceiverId + '&last_id=' + getLastMessageId(), function() {
                     // On success checking (potentially reading) messages
                     if (typeof checkChatMessages === 'function') checkChatMessages();
                });
            }
            
            function getLastMessageId() {
                var msgs = document.querySelectorAll('.message-bubble');
                if (msgs.length > 0) {
                    return msgs[msgs.length - 1].getAttribute('data-id') || 0;
                }
                return 0;
            }
            
            function appendMessage(html) {
                var area = document.getElementById('messages-area');
                var div = document.createElement('div');
                div.innerHTML = html;
                var newContent = div.firstChild;
                area.appendChild(newContent);
                scrollToBottom();
            }
            
            function scrollToBottom() {
                var area = document.getElementById('messages-area');
                area.scrollTop = area.scrollHeight;
            }
        ");
        $this->chat_container->add($script);
        
        parent::add($this->chat_container);
    }
    
    public function loadContacts($container)
    {
        try {
            TTransaction::open('database');
            $logged_id = TSession::getValue('userid');
            $logged_user = Usuario::find($logged_id);
            
            // If logged user is NOT a gestor, they can only see gestors
            // If logged user IS a gestor, they see everyone (active)
            
            $criteria = new TCriteria;
            $criteria->add(new TFilter('id', '!=', $logged_id));
            $criteria->add(new TFilter('ativo', '=', '1'));
            
            if ($logged_user->tipo !== 'gestor') {
                $criteria->add(new TFilter('tipo', '=', 'gestor'));
            }
            
            $repository = new TRepository('Usuario');
            $users = $repository->load($criteria);
            
            if ($users) {
                foreach ($users as $user) {
                    $u_nome = $user->nome ?? '';
                    $initials = (!empty($u_nome)) ? strtoupper(substr($u_nome, 0, 2)) : '??';
                    
                    // Fetch Last Message & Unread
                    $last_msg_obj = ChatMessage::where('sender_id', '=', $logged_id)->where('receiver_id', '=', $user->id)
                                        ->orWhere('sender_id', '=', $user->id)->where('receiver_id', '=', $logged_id)
                                        ->orderBy('id', 'desc')
                                        ->first();
                                        
                    $last_msg_text = "Iniciar conversa";
                    $last_msg_time = "";
                    $unread = 0;
                    
                    if ($last_msg_obj) {
                        $prefix = ($last_msg_obj->sender_id == $logged_id) ? "Você: " : "";
                        $last_msg_text = $prefix . (mb_strlen($last_msg_obj->message) > 25 ? mb_substr($last_msg_obj->message, 0, 25) . '...' : $last_msg_obj->message);
                        $last_msg_time = date('H:i', strtotime($last_msg_obj->created_at));
                        
                        // Count unread
                        $unread = ChatMessage::where('sender_id', '=', $user->id)
                                            ->where('receiver_id', '=', $logged_id)
                                            ->where('is_read', '=', 'N')
                                            ->count();
                    }
                    
                    $badge = $unread > 0 ? "<div class='unread-badge'>{$unread}</div>" : "";
                    $boldClass = $unread > 0 ? "font-weight:700; color:#1e293b;" : "";
                    
                    $html = "
                    <div class='contact-item' id='contact-{$user->id}' onclick='selectContact(this, {$user->id}, \"{$u_nome}\", \"{$initials}\")'>
                        <div class='contact-avatar'>
                            {$initials}
                            <div class='contact-status-dot'></div>
                        </div>
                        <div class='contact-info'>
                            <div class='contact-top-line'>
                                <span class='contact-name' style='{$boldClass}'>{$u_nome}</span>
                                <span class='contact-time'>{$last_msg_time}</span>
                            </div>
                            <div style='display:flex; justify-content:space-between; align-items:center;'>
                                <span class='contact-last-msg'>{$last_msg_text}</span>
                                {$badge}
                            </div>
                        </div>
                    </div>";
                    
                    $container->add($html);
                }
            }
            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }

    /**
     * API Method to get unread message count for the current user (Badge)
     */
    public static function onGetUnreadCount($param)
    {
        try {
            TTransaction::open('database');
            $userid = TSession::getValue('userid');
            
            if ($userid) {
                $count = ChatMessage::where('receiver_id', '=', $userid)
                                   ->where('is_read', '=', 'N')
                                   ->count();
                
                echo json_encode(['count' => $count, 'status' => 'success']);
                exit;
            } else {
                echo json_encode(['count' => 0, 'status' => 'error']);
                exit;
            }
            TTransaction::close();
        } catch (Exception $e) {
            echo json_encode(['count' => 0, 'status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }
    
    public function onLoad($param)
    {
        // Auto-select if target_id is present in REQUEST (from Chat button in ClientList)
        if (isset($param['target_id'])) {
            $target_id = $param['target_id'];
            TScript::create("
                setTimeout(function() {
                    var contact = document.getElementById('contact-{$target_id}');
                    if (contact) {
                        contact.click();
                        contact.scrollIntoView({block: 'center'});
                    }
                }, 500);
            ");
        }
    }
    
    public static function onLoadMessages($param)
    {
        try {
            $current_user = TSession::getValue('userid');
            $other_user = $param['user_id'];
            
            TTransaction::open('database');
            
            // Mark as read
            ChatMessage::where('sender_id', '=', $other_user)
                       ->where('receiver_id', '=', $current_user)
                       ->set('is_read', 'Y')
                       ->update();
            
            $messages = ChatMessage::where('sender_id', '=', $current_user)->where('receiver_id', '=', $other_user)
                            ->orWhere('sender_id', '=', $other_user)->where('receiver_id', '=', $current_user)
                            ->orderBy('id', 'asc')
                            ->load();
                            
            $html = '';
            if ($messages) {
                foreach ($messages as $msg) {
                    $type = ($msg->sender_id == $current_user) ? 'message-sent' : 'message-received';
                    $time = date('H:i', strtotime($msg->created_at));
                    
                    $html .= "<div class='message-bubble {$type}' data-id='{$msg->id}'>
                                {$msg->message}
                                <span class='message-time'>{$time}</span>
                              </div>";
                }
            }
            
            TTransaction::close();
            
            $js = "document.getElementById('messages-area').innerHTML = " . json_encode($html) . "; scrollToBottom();";
            TScript::create($js);
            
        } catch (Exception $e) {
            
        }
    }
    
    public static function onSend($param)
    {
        try {
            $sender = TSession::getValue('userid');
            $receiver = $param['receiver_id'];
            $text = $param['message'];
            
            if (empty($text)) return;
            
            TTransaction::open('database');
            
            $msg = new ChatMessage;
            $msg->sender_id = $sender;
            $msg->receiver_id = $receiver;
            $msg->message = $text;
            $msg->created_at = date('Y-m-d H:i:s');
            $msg->is_read = 'N';
            $msg->store();
            
            $msg_id = $msg->id;
            TTransaction::close();
            // *** Transação fechada — mensagem salva ***
            
            // Notificar destinatário (em transação isolada)
            // REMOVIDO: Chat não deve gerar notificação no sininho, apenas no badge de chat.
            
            $time = date('H:i');
            $html = "<div class='message-bubble message-sent' data-id='{$msg_id}'>{$text}<span class='message-time'>{$time}</span></div>";
            TScript::create("appendMessage(" . json_encode($html) . ");");
            
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
    
    /**
     * Endpoint for AJAX dropdown content (Last conversations)
     */
    public static function getLatestMessages($param)
    {
        // Limpar output buffers para evitar warnings do PHP no retorno AJAX
        while (ob_get_level() > 0) { ob_end_clean(); }
        
        try {
            TTransaction::open('database');
            $userid = TSession::getValue('userid');
            
            $conn = TTransaction::get();
            // Get last 20 messages involving this user
            $sql = "SELECT m.id, m.message, m.created_at, m.sender_id, m.receiver_id,
                           u_sender.nome as sender_name, u_receiver.nome as receiver_name
                    FROM chat_messages m
                    LEFT JOIN usuario u_sender ON m.sender_id = u_sender.id
                    LEFT JOIN usuario u_receiver ON m.receiver_id = u_receiver.id
                    WHERE m.sender_id = {$userid} OR m.receiver_id = {$userid}
                    ORDER BY m.created_at DESC LIMIT 20";
            
            $res = $conn->query($sql);
            $conversations = [];
            $partners = [];
            
            if ($res) {
                foreach ($res as $row) {
                    $partner_id = ($row['sender_id'] == $userid) ? $row['receiver_id'] : $row['sender_id'];
                    $partner_name = ($row['sender_id'] == $userid) ? $row['receiver_name'] : $row['sender_name'];
                    
                    if (in_array($partner_id, $partners)) continue;
                    $partners[] = $partner_id;
                    $conversations[] = $row;
                    if (count($conversations) >= 5) break; 
                }
            }
            
            $html = '';
            if ($conversations) {
                foreach ($conversations as $c) {
                    $partner_id = ($c['sender_id'] == $userid) ? $c['receiver_id'] : $c['sender_id'];
                    $partner_name = ($c['sender_id'] == $userid) ? $c['receiver_name'] : $c['sender_name'];
                    $msg = mb_strlen($c['message']) > 30 ? mb_substr($c['message'], 0, 30) . '...' : $c['message'];
                    if ($c['sender_id'] == $userid) $msg = "Você: " . $msg;
                    $time = date('H:i', strtotime($c['created_at']));
                    $initials = mb_substr($partner_name, 0, 2);
                    
                    // On click: Open Chat with this user
                    $onclick = "Adianti.openPage('SystemChat', 'target_id={$partner_id}');";
                    
                    $html .= "<li><a class='dropdown-item' href='#' onclick=\"{$onclick}\">";
                    $html .= "<div class='d-flex align-items-center'>";
                    $html .= "<div class='flex-shrink-0' style='width:30px;height:30px;background:#e2e8f0;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:bold'>{$initials}</div>";
                    $html .= "<div class='flex-grow-1 ms-3'>";
                    $html .= "<h6 class='mb-0' style='font-size:0.9rem'>{$partner_name}</h6>";
                    $html .= "<small class='text-muted' style='font-size:0.75rem'>{$msg}</small>";
                    $html .= "</div>";
                    $html .= "<div class='text-muted ms-2' style='font-size:0.65rem'>{$time}</div>";
                    $html .= "</div></a></li>";
                    $html .= "<li><hr class='dropdown-divider'></li>";
                }
                $html .= "<li><a class='dropdown-item text-center text-primary' href='#' onclick=\"Adianti.openPage('SystemChat')\">Abrir Messenger</a></li>";
            } else {
                 $html .= "<li><span class='dropdown-item text-muted text-center'>Nenhuma mensagem recente</span></li>";
            }
            TTransaction::close();
            echo $html;
            exit;
        } catch (Exception $e) {
            echo "<li><span class='dropdown-item text-danger'>Erro ao carregar</span></li>";
            exit;
        }
    }

    public static function checkNewMessages($param)
    {
        try {
            $sender = $param['receiver_id']; 
            $receiver = TSession::getValue('userid');
            $last_id = isset($param['last_id']) ? $param['last_id'] : 0;
            
            TTransaction::open('database');
            
            $messages = ChatMessage::where('sender_id', '=', $sender)
                                   ->where('receiver_id', '=', $receiver)
                                   ->where('id', '>', $last_id)
                                   ->orderBy('id', 'asc')
                                   ->load();
                                   
            if ($messages) {
                 ChatMessage::where('sender_id', '=', $sender)
                            ->where('receiver_id', '=', $receiver)
                            ->where('id', '>', $last_id)
                            ->set('is_read', 'Y')
                            ->update();
                            
                foreach ($messages as $msg) {
                    $time = date('H:i', strtotime($msg->created_at));
                    $html = "<div class='message-bubble message-received' data-id='{$msg->id}'>{$msg->message}<span class='message-time'>{$time}</span></div>";
                    TScript::create("appendMessage(" . json_encode($html) . ");");
                }
            }
            
            TTransaction::close();
        } catch (Exception $e) {
            
        }
    }
    

}
