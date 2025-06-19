jQuery(document).ready(function($) {
    $('#chatbot-toggle').click(function() {
        $('#chatbot-container').slideToggle();
        if ($('#chatbot-conversation').is(':empty')) {
            showHomeScreen();
        }
    });
    
    $('#chatbot-close').click(function() {
        $('#chatbot-container').slideUp();
    });
    
    function showHomeScreen() {
        $('#chatbot-conversation').html(`
            <div class="chatbot-home">
                <h3>${chatbotData.home_title}</h3>
                <p>${chatbotData.home_subtitle}</p>
                <button id="chatbot-start">${chatbotData.home_button}</button>
            </div>
        `);
        $('#chatbot-options').empty();
        $('#chatbot-start').click(function() {
            loadChatNodes(0);
        });
    }
    
    function loadChatNodes(parentId) {
        $.ajax({
            url: chatbotData.ajax_url,
            type: 'POST',
            data: {
                action: 'chatbot_get_nodes',
                parent_id: parentId
            },
            success: function(response) {
                if (response.success) {
                    displayNodes(response.data);
                }
            }
        });
    }
    
    function displayNodes(nodes) {
        $('#chatbot-conversation').empty();
        $('#chatbot-options').empty();
        
        nodes.forEach(node => {
            if (node.node_type === 'answer') {
                addBotMessage(node.node_text);
            }
        });
        
        const questions = nodes.filter(node => node.node_type === 'question');
        if (questions.length > 0) {
            questions.forEach(node => {
                const button = $(`<button class="chat-option" data-id="${node.id}">${node.node_text}</button>`);
                button.click(function() {
                    addUserMessage(node.node_text);
                    $('#chatbot-options').empty();
                    loadChatNodes(node.id);
                });
                $('#chatbot-options').append(button);
            });
        } else {
            showActionButtons();
        }
    }
    
function showActionButtons() {
    const restartBtn = $(`<button class="chat-option restart">${chatbotData.restart_text}</button>`);
    restartBtn.click(function() {
        showHomeScreen();
        if (window.self !== window.top) {
            parent.postMessage({ type: 'chatReset' }, '*');
        }
    });
    
    const closeBtn = $(`<button class="chat-option close">${chatbotData.close_text}</button>`);
    closeBtn.click(function() {
        $('#chatbot-container').slideUp();
        if (window.self !== window.top) {
            parent.postMessage({ type: 'chatClose' }, '*');
        }
        $('#chatbot-conversation').empty();
    });
    
    $('#chatbot-options').append(restartBtn, closeBtn);
}
    
    function addBotMessage(text) {
    text = text.replace(/\*([^*]+)\*/g, '<strong>$1</strong>');
    text = text.replace(/\\"/g, '"');

        const linkedText = text.replace(
            /(https?:\/\/[^\s]+)/g, 
            '<a href="$1" target="_blank">$1</a>'
        );
        
        $('#chatbot-conversation').append(
            `<div class="chatbot-message bot-message">${linkedText}</div>`
        );
        scrollToBottom();
    }
    
    function addUserMessage(text) {
        $('#chatbot-conversation').append(
            `<div class="chatbot-message user-message">${text}</div>`
        );
        scrollToBottom();
    }
    
    function scrollToBottom() {
        const container = $('#chatbot-conversation');
        container.scrollTop(container[0].scrollHeight);
    }
    
    showHomeScreen();
});
