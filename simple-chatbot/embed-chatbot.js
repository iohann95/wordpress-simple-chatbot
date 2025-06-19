;(function(){
  var CHATBOT_URL = 'https://YOURWEBSITE.com/chatbot-embed/';

  var css = `
    #chatbot-container {
      position: fixed;
      bottom: 20px;
      right: 20px;
      width: 90%;
      max-width: 400px;
      height: 80vh;
      max-height: 80vh;
      transform: translateY(120%);
      transition: transform 0.3s ease;
      z-index: 9999;
      overflow: hidden;
      border-radius: 12px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }
    
    #chatbot-container.open {
      transform: translateY(0);
      bottom: 20px;
    }
    
    #chatbot-iframe {
      width: 100%;
      height: 100%;
      border: none;
      display: block;
    }
    
    #chatbot-toggle {
      position: fixed;
      bottom: 20px;
      right: 20px;
      width: 60px;
      height: 60px;
      background: #21759b;
      color: white;
      border: none;
      border-radius: 50%;
      font-size: 24px;
      cursor: pointer;
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
      z-index: 10000;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s, background 0.2s;
    }
    
    #chatbot-toggle:hover {
      background: #1a648c;
      transform: scale(1.05);
    }
    
  }

    @media (max-width: 480px) {
      #chatbot-container {
        width: calc(100% - 30px);
        right: 15px;
        left: 15px;
        max-width: none;
      }
    }
  `;
  
  var style = document.createElement('style');
  style.textContent = css;
  document.head.appendChild(style);

  var container = document.createElement('div');
  container.id = 'chatbot-container';

  var iframe = document.createElement('iframe');
  iframe.id = 'chatbot-iframe';
  iframe.src = CHATBOT_URL;
  container.appendChild(iframe);

  document.body.appendChild(container);

  var btn = document.createElement('button');
  btn.id = 'chatbot-toggle';
  btn.textContent = '?';
  document.body.appendChild(btn);

  btn.addEventListener('click', function(){
    container.classList.toggle('open');
  });

  window.addEventListener('message', function(e){
    if (!e.data || typeof e.data.type !== 'string') return;

    if (e.data.type === 'chatHeight' && e.data.height) {
      const maxHeight = window.innerHeight * 0.8;
      const height = Math.min(e.data.height, maxHeight);
      
        if (Math.abs(parseInt(container.style.height) - height) > 5) {
            container.style.height = height + 'px';
        }
      
      if (e.data.width) {
        iframe.style.width = e.data.width + 'px';
      }
    }

    if (e.data.type === 'chatClose') {
      setTimeout(() => {
        iframe.src = iframe.src;
      }, 300);
      container.classList.remove('open');
    }
    
    if (e.data.type === 'chatReset') {
      iframe.src = iframe.src;
    }
  });

})();
