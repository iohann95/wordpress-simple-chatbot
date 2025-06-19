<?php
/*
Template Name: Chatbot
Description: Blank page rendering only the chatbot
*/

remove_action('wp_footer', 'simple_chatbot_interface', 10);
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <?php wp_head(); ?>
  <style>
    html, body, #chatbot-container {
      height: 100% !important;
      min-height: 100vh;
      width: 100% !important;
      max-width: 100% !important;
      margin: 0 !important;
      padding: 0 !important;
      display: block;
      //background: transparent;
      background: var(--bg-color);
      box-sizing: border-box;
    }
    
    #chatbot-container {
      position: static !important;
      bottom: auto !important;
      right: auto !important;
      box-shadow: none;
      border-radius: 0;
      max-height: none !important;
    }
    
    #chatbot-header, 
    #chatbot-conversation, 
    #chatbot-options, 
    .chatbot-message {
      max-width: 100% !important;
      width: 100% !important;
      box-sizing: border-box;
    }
    
  #chatbot-conversation {
    flex: 1 !important;
    max-height: none !important;
  }

    #chatbot-toggle { 
      display: none !important; 
    }
    
    #chatbot-options {
    display: grid !important;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)) !important;
    gap: 8px !important;
    padding: 10px !important;
    }
    
    .chat-option {
      padding: 8px 12px;
      min-height: 40px;
      white-space: normal;
      word-break: break-word;
    }
    
    /* MOBILE OPTIMIZATION */
    @media (max-width: 480px) {
      .chatbot-message {
        max-width: 90% !important;
      }
      
      #chatbot-options {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body <?php body_class(); ?>>
  <div class="chatbot-fullwidth-wrapper">
    <?php simple_chatbot_interface(); ?>
  </div>

<script>
(function(){
    let lastHeight = 0;
    let debounceTimer;
    
    function sendHeight(){
        const container = document.querySelector('#chatbot-container');
        const h = Math.min(
            document.documentElement.scrollHeight, 
            window.innerHeight
        );
        
        if (Math.abs(h - lastHeight) > 5) {
            lastHeight = h;
            parent.postMessage({ 
                type: 'chatHeight', 
                height: h,
                width: container.scrollWidth 
            }, '*');
        }
    }
    
    function debouncedSendHeight() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(sendHeight, 100);
    }

    window.addEventListener('load', sendHeight);
    
    const contentObserver = new MutationObserver(debouncedSendHeight);
    contentObserver.observe(document.body, {
        childList: true,
        subtree: true,
        characterData: true
    });
    
    document.addEventListener('click', function(e){
        if (e.target.id === 'chatbot-close') {
            parent.postMessage({ type:'chatClose' }, '*');
        }
    });
})();
</script>

  <?php wp_footer(); ?>
</body>
</html>
