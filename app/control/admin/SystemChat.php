<?php
/**
 * SystemChat
 * Internal Chat Controller (Messenger Style - Full Implementation)
 */
class SystemChat extends TPage
{
    private $form;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->form = new TForm('form_chat');
        
        // Styles for WhatsApp-like layout - Injected via JS to ensure it persists and overrides
        $css = "
            .chat-container {
                display: flex !important;
                height: 75vh !important;
                border: 1px solid #ddd;
                background: #fff;
                border-radius: 4px;
                overflow: hidden;
                font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            }
            .chat-list-panel {
                width: 320px !important;
                border-right: 1px solid #ddd;
                display: flex !important;
                flex-direction: column !important;
                background: #fff;
            }
            .chat-list-header {
                padding: 10px 15px;
                background: #f0f2f5;
                border-bottom: 1px solid #ddd;
                height: 60px;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            .chat-contact-list {
                flex: 1;
                overflow-y: auto;
            }
            .chat-main-panel {
                flex: 1 !important;
                display: flex !important;
                flex-direction: column !important;
                background: #efe7dd;
                position: relative;
            }
            .chat-window-header {
                padding: 10px 15px;
                background: #f0f2f5;
                border-bottom: 1px solid #ddd;
                height: 60px;
                display: flex;
                align-items: center;
            }
            .chat-messages {
                flex: 1 !important;
                overflow-y: auto;
                padding: 20px;
                display: flex !important;
                flex-direction: column !important;
                background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png');
            }
            .chat-input-area {
                padding: 10px 15px;
                background: #f0f2f5;
                border-top: 1px solid #ddd;
                display: flex; /* Removed !important to allow toggling */
                align-items: center;
                min-height: 60px;
                margin-top: auto !important; /* Force bottom */
            }
            .chat-input {
                flex: 1;
                margin-right: 15px;
                padding: 10px;
                border: 1px solid #fff;
                border-radius: 20px;
                background: #fff;
                resize: none;
                height: 40px;
                line-height: 20px;
                outline: none;
            }
            .chat-input:focus {
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .chat-send-btn {
                border: none;
                background: none;
                color: #54656f;
                font-size: 24px;
                cursor: pointer;
                padding: 5px;
            }
            .chat-send-btn:hover {
                color: #00a884;
            }
            .message-bubble {
                max-width: 65%;
                margin-bottom: 8px;
                padding: 8px 12px;
                border-radius: 7.5px;
                position: relative;
                font-size: 14.2px;
                line-height: 19px;
                box-shadow: 0 1px 0.5px rgba(0,0,0,0.13);
                word-wrap: break-word;
            }
            .message-bubble.me {
                align-self: flex-end;
                background: #d9fdd3;
                border-top-right-radius: 0;
            }
            .message-bubble.other {
                align-self: flex-start;
                background: #fff;
                border-top-left-radius: 0;
            }
            .message-time {
                display: block;
                font-size: 11px;
                color: #999;
                text-align: right;
                margin-top: 4px;
                margin-bottom: -4px;
                float: right;
                margin-left: 10px;
            }
            .chat-contact {
                padding: 12px 15px;
                border-bottom: 1px solid #f0f0f0;
                cursor: pointer;
                transition: background 0.2s;
            }
            .chat-contact:hover {
                background: #f5f6f6;
            }
            .chat-contact-active {
                background: #e9edef;
            }
            .chat-contact-avatar {
                width: 45px;
                height: 45px;
                border-radius: 50%;
                background: #dfe5e7;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                font-size: 18px;
                font-weight: 500;
                margin-right: 15px;
                flex-shrink: 0;
            }
            .chat-contact-info h3 {
                font-size: 16px;
                color: #111b21;
                font-weight: 400;
                margin-bottom: 3px;
            }
            .chat-last-message {
                font-size: 13px;
                color: #667781;
            }
            .chat-empty-state {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                height: 100%;
                color: #667781;
                text-align: center;
            }
        ";
        
        // Compact CSS to single line for JS safety
        $css = str_replace(["\r", "\n"], ' ', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        
        TScript::create("$('head').append('<style>{$css}</style>');");
        
        // Main Container
        $container = new TElement('div');
        $container->class = 'chat-container';
        
        // --- Left Panel: Sidebar ---
        $listPanel = new TElement('div');
        $listPanel->class = 'chat-list-panel';
        
        // Header
        $header = new TElement('div');
        $header->class = 'chat-list-header';
        $header->style = 'display: flex; justify-content: space-between; align-items: center;';
        $header->add(new TElement('span', 'Conversas'));
        
        $addBtn = new TElement('a');
        $addBtn->class = 'btn btn-sm btn-primary btn-circle';
        $addBtn->href = '#';
        $addBtn->onclick = "Adianti.waitMessage = false; __adianti_post_data('form_chat', 'class=SystemChat&method=onShowNewMessage'); return false;";
        $addBtn->add('<i class="fa fa-plus"></i>');
        $addBtn->title = "Nova Conversa";
        $header->add($addBtn);
        
        $listPanel->add($header);
        
        // Contact List (Container)
        $contactList = new TElement('div');
        $contactList->id = 'chat_contact_list';
        $contactList->class = 'chat-contact-list';
        
        // Initial Load
        $contactList->add(self::renderHistory());
        
        $listPanel->add($contactList);
        $container->add($listPanel);
        
        // --- Right Panel: Chat Window ---
        $mainPanel = new TElement('div');
        $mainPanel->class = 'chat-main-panel';
        
        // Search Header - visible by default
        $searchHeader = new TElement('div');
        $searchHeader->class = 'chat-window-header';
        $searchHeader->style = 'display: flex; align-items: center; padding: 10px 15px;';
        
        // User search combo
        $searchCombo = new TDBCombo('search_user', 'database', 'Usuario', 'id', 'nome', 'nome');
        $searchCombo->setSize('100%');
        $searchCombo->id = 'chat_search_user';
        $searchCombo->enableSearch();
        $searchCombo->setChangeAction(new TAction(['SystemChat', 'onSelectContact']));
        $searchCombo->placeholder = 'Buscar usuário para conversar...';
        
        $searchWrapper = new TElement('div');
        $searchWrapper->style = 'flex: 1; margin-right: 10px;';
        $searchWrapper->add($searchCombo);
        
        $searchHeader->add($searchWrapper);
        $mainPanel->add($searchHeader);
        
        // Contact header (shown when a contact is selected)
        $chatHeader = new TElement('div');
        $chatHeader->id = 'chat_window_header'; 
        $chatHeader->class = 'chat-window-header';
        $chatHeader->style = 'display:none;'; 
        $mainPanel->add($chatHeader);

        // Messages Area
        $messagesArea = new TElement('div');
        $messagesArea->id = 'chat_messages_area';
        $messagesArea->class = 'chat-messages';
        $messagesArea->add("<div class='chat-empty-state'><i class='fa fa-comments-o' style='font-size:48px; color:#ddd; margin-bottom:10px;'></i><br>Busque um usuário acima para iniciar uma conversa</div>");
        $mainPanel->add($messagesArea);
        
        // Input Area
        $inputArea = new TElement('div');
        $inputArea->class = 'chat-input-area';
        
        $messageInput = new TText('message_input');
        $messageInput->class = 'chat-input';
        $messageInput->placeholder = 'Digite sua mensagem...';
        $messageInput->id = 'chat_message_input';
        // Improved Enter Key Handler
        $messageInput->onkeypress = "if(event.key === 'Enter' && !event.shiftKey) { document.getElementById('btn_send_chat').click(); return false; }";
        
        $sendBtn = new TElement('button');
        $sendBtn->id = 'btn_send_chat';
        $sendBtn->class = 'chat-send-btn';
        $sendBtn->add('<i class="fa fa-paper-plane"></i>');
        $sendBtn->action = "Adianti.waitMessage = false; __adianti_post_data('form_chat', 'class=SystemChat&method=onSendMessage'); return false;";
        $sendBtn->onclick = $sendBtn->action;
        
        $inputArea->add($messageInput);
        $inputArea->add($sendBtn);
        
        $targetId = new THidden('target_id');
        $targetId->id = 'chat_target_id';
        $inputArea->add($targetId);
        
        $inputArea->id = 'chat_input_container';
        // Input is now visible by default at the bottom 
        
        $mainPanel->add($inputArea);
        $container->add($mainPanel);
        
        // Register all form fields properly
        $this->form->setFields([$searchCombo, $messageInput, $targetId]);
        
        $this->form->add($container);
        parent::add($this->form);
    }
    
    /**
     * Helper to render history HTML
     */
    public static function renderHistory()
    {
        $html = "";
        try {
            TTransaction::open('database');
            $me_id = TSession::getValue('userid');
            $conn = TTransaction::get();
            
            $sql_ids = "SELECT DISTINCT system_user_id as uid FROM mensagem WHERE system_user_to_id = {$me_id}
                        UNION
                        SELECT DISTINCT system_user_to_id as uid FROM mensagem WHERE system_user_id = {$me_id}";
            $res = $conn->query($sql_ids);
            
            $contacts = [];
            if ($res) {
                foreach ($res as $row) {
                    $user = new Usuario($row['uid']);
                    if (empty($user->id)) continue; // avoid broken users
                    
                    $c_msg = new TCriteria;
                    $c_msg->add(new TFilter('system_user_id', 'IN', [$me_id, $user->id]));
                    $c_msg->add(new TFilter('system_user_to_id', 'IN', [$me_id, $user->id]));
                    $c_msg->setProperty('order', 'dt_message desc');
                    $c_msg->setProperty('limit', 1);
                    
                    $last_msgs = Mensagem::getObjects($c_msg);
                    $last_msg = $last_msgs ? $last_msgs[0] : null;
                    
                    $contacts[] = (object) [
                        'user' => $user,
                        'last_msg' => $last_msg
                    ];
                }
            }
            
            usort($contacts, function($a, $b) {
                $t1 = $a->last_msg ? strtotime($a->last_msg->dt_message) : 0;
                $t2 = $b->last_msg ? strtotime($b->last_msg->dt_message) : 0;
                return $t2 - $t1;
            });
            
            if (!empty($contacts)) {
                foreach ($contacts as $item) {
                    $u = $item->user;
                    $msg = $item->last_msg;
                    $u_nome = $u->nome ?? 'Unknown';
                    $initials = strtoupper(substr($u_nome, 0, 2));
                    $txt = $msg ? $msg->message : '';
                    if (strlen($txt) > 30) $txt = substr($txt, 0, 30) . '...';
                    // Escape single quotes for JS usage in onclick
                    $safe_target = $u->id;
                    
                    $html .= "
                    <div id='chat_contact_{$u->id}' class='chat-contact' onclick=\"Adianti.waitMessage = false; __adianti_post_data('form_chat', 'class=SystemChat&method=onSelectContact&target_id={$safe_target}');\" style='display:flex; align-items:center; cursor:pointer;'>
                        <div class='chat-contact-avatar'>{$initials}</div>
                        <div class='chat-contact-info'>Erro no upload: {"type":"error","msg":"O servidor não recebeu o arquivo. Verifique os limites do servidor. O limite atual é upload_max_filesize: 40M"}
                            <h3>{$u_nome}</h3>
                            <p class='chat-last-message'>{$txt}</p>
                        </div>
                        <div class='chat-contact-delete' onclick=\"event.stopPropagation(); if(confirm('Excluir esta conversa?')) { __adianti_load_page('index.php?class=SystemChat&method=onDeleteConversation&target_id={$safe_target}'); }\" title='Excluir conversa' style='margin-left:auto; padding:10px; color:#dc3545; cursor:pointer;'>
                            <i class='fa fa-trash'></i>
                        </div>
                    </div>";
                }
            } else {
                $html = "<div style='padding:20px;text-align:center;color:#999;'>Nenhuma conversa.<br>Clique no (+) acima.</div>";
            }
            
            TTransaction::close();
        } catch (Exception $e) {
            $html = "Erro ao carregar";
        }
        return $html;
    }
    
    public static function onSelectContact($param)
    {
        // Support both sidebar click (target_id) and search combo (search_user)
        $target_id = $param['target_id'] ?? $param['search_user'] ?? null;
        
        if (empty($target_id)) return;
        
        TScript::create("$('#chat_target_id').val('{$target_id}');");
        TScript::create("$('#chat_input_container').css('display', 'flex');");
        
        // Remove active class from all, add to this one? We lost ID references in string HTML.
        // We can re-render history to set active state if we pass target_id to renderHistory, but simpler is valid.
        
        self::loadMessages($target_id);
    }
    
    public static function loadMessages($target_id)
    {
        try {
            TTransaction::open('database');
            $me_id = TSession::getValue('userid');
            $target = new Usuario($target_id);
            
            $initials = (!empty($target->nome)) ? strtoupper(substr($target->nome, 0, 2)) : '??';
            $headerHtml = "
                <div class='chat-contact-avatar' style='width:36px;height:36px;font-size:12px;margin-right:10px;'>{$initials}</div>
                <div style='display:flex; flex-direction:column;'>
                    <span style='font-weight:bold; color:#333;'>{$target->nome}</span>
                    <span style='font-size:11px; color:#4caf50;'>Online</span>
                </div>
            ";
            TScript::create("$('#chat_window_header').html(\"{$headerHtml}\");");
            TScript::create("$('#chat_window_header').show();");
            
            $repo = new TRepository('Mensagem');
            $criteria = new TCriteria;
            $c1 = new TCriteria; $c1->add(new TFilter('system_user_id', '=', $me_id)); $c1->add(new TFilter('system_user_to_id', '=', $target_id));
            $c2 = new TCriteria; $c2->add(new TFilter('system_user_id', '=', $target_id)); $c2->add(new TFilter('system_user_to_id', '=', $me_id));
            $criteria->add($c1, TExpression::OR_OPERATOR);
            $criteria->add($c2, TExpression::OR_OPERATOR);
            $criteria->setProperties(['order' => 'dt_message', 'direction' => 'asc']);
            
            $msgs = $repo->load($criteria);
            
            $html = "";
            if ($msgs) {
                foreach ($msgs as $msg) {
                    $is_me = ($msg->system_user_id == $me_id);
                    $class = $is_me ? 'me' : 'other';
                    $time = date('H:i', strtotime($msg->dt_message));
                    $message_content = nl2br(htmlspecialchars($msg->message)); // XSS Protection
                    $html .= "<div class='message-bubble {$class}'>";
                    $html .= $message_content;
                    $html .= "<span class='message-time'>{$time}</span>";
                    $html .= "</div>";
                }
            } else {
                 $html = "<div class='chat-empty-state'>Inicie a conversa com {$target->nome}</div>";
            }
            
            TScript::create("$('#chat_messages_area').html(\"{$html}\");");
            TScript::create("var d = $('#chat_messages_area'); d.scrollTop(d.prop('scrollHeight'));");
            TTransaction::close();
        } catch (Exception $e) {}
    }

    public static function onShowNewMessage($param) {
         $window = TWindow::create('Nova Conversa', 0.5, 0.4);
         $combo = new TCombo('new_chat_user');
         $combo->enableSearch();
         $combo->setSize('100%');
         try {
            TTransaction::open('database');
            $me = new Usuario(TSession::getValue('userid'));
            $repo = new TRepository('Usuario');
            $criteria = new TCriteria;
            if ($me->tipo == 'gestor') $criteria->add(new TFilter('tipo', '=', 'cliente'));
            else $criteria->add(new TFilter('tipo', '=', 'gestor'));
            $criteria->add(new TFilter('ativo', '=', '1'));
            $users = $repo->load($criteria);
            $items = [];
            foreach($users as $u) {
                $label = $u->nome;
                if (!empty($u->cpf)) {
                    $label .= ' - CPF: ' . $u->cpf;
                }
                $items[$u->id] = $label;
            }
            $combo->addItems($items);
            TTransaction::close();
         } catch (Exception $e) {}
         
         $form = new BootstrapFormBuilder('form_new');
         $form->addFields([new TLabel('Para:')], [$combo]);
         $form->addAction('Abrir', new TAction(['SystemChat', 'onStartNewChat']), 'fa:check blue');
         $window->add($form);
         $window->show();
    }
    
    public static function onStartNewChat($param) {
        if (!empty($param['new_chat_user'])) {
            TWindow::closeWindow();
            $param['target_id'] = $param['new_chat_user'];
            self::onSelectContact($param);
        }
    }
    
    public static function onSendMessage($param) {
        try {
            if (empty($param['message_input']) || empty($param['target_id'])) return;
            
            TTransaction::open('database');
            $msg = new Mensagem;
            $msg->system_user_id = TSession::getValue('userid');
            $msg->system_user_to_id = $param['target_id'];
            $msg->message = $param['message_input'];
            $msg->dt_message = date('Y-m-d H:i:s');
            // Check valid IDs? Constraint handles it.
            $msg->store();
            TTransaction::close();
            
            // Clear input and focus
            TScript::create("$('#chat_message_input').val('').focus();");
            
            // Reload Messages
            self::loadMessages($param['target_id']);
            
            // Reload Sidebar (to update snippet and order)
            $historyHtml = self::renderHistory();
            // Need to escape double quotes in HTML for JS string
            TScript::create("$('#chat_contact_list').html(" . json_encode($historyHtml) . ");");
            
        } catch (Exception $e) {}
    }

    public static function onDeleteConversation($param)
    {
        try {
            if (empty($param['target_id'])) return;
            $target_id = $param['target_id'];
            $me_id = TSession::getValue('userid');
            
            TTransaction::open('database');
            // Delete messages where (sender=me AND receiver=target) OR (sender=target AND receiver=me)
            $repo = new TRepository('Mensagem');
            $criteria = new TCriteria;
            $c1 = new TCriteria; 
            $c1->add(new TFilter('system_user_id', '=', $me_id)); 
            $c1->add(new TFilter('system_user_to_id', '=', $target_id));
            
            $c2 = new TCriteria; 
            $c2->add(new TFilter('system_user_id', '=', $target_id)); 
            $c2->add(new TFilter('system_user_to_id', '=', $me_id));
            
            $criteria->add($c1, TExpression::OR_OPERATOR);
            $criteria->add($c2, TExpression::OR_OPERATOR);
            
            $repo->delete($criteria);
            
            TTransaction::close();
            
            // Reload the contact list to reflect the deletion
            $historyHtml = self::renderHistory();
            TScript::create("$('#chat_contact_list').html(" . json_encode($historyHtml) . ");");
            
            // If the deleted chat was currently open, clear the main window
            TScript::create("
                var currentTarget = $('#chat_target_id').val();
                if (currentTarget == '{$target_id}') {
                    $('#chat_target_id').val('');
                    $('#chat_window_header').hide();
                    $('#chat_messages_area').html(\"<div class='chat-empty-state'><i class='fa fa-comments-o' style='font-size:48px; color:#ddd; margin-bottom:10px;'></i><br>Busque um usuário acima para iniciar uma conversa</div>\");
                }
            ");
            
            new TMessage('info', 'Conversa excluída com sucesso!');
            
        } catch (Exception $e) {
            new TMessage('error', 'Erro ao excluir: ' . $e->getMessage());
            TTransaction::rollback();
        }
    }
}
