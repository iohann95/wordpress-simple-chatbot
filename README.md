# wordpress-simple-chatbot
Very simple, decision-tree based, NOT AI-integrated Chatbot solution.
I developed this plugin because I couldn't find any chatbot that was simple, self-hosted, free, without any AI integration (so no fees/subscriptions).

To install it:
 - Copy the folder "simple-chatbot" to your plugin folder
 - Copy the file "page-chatbot-embed.php" to the folder of the WordPress theme you are currently using.
 - Edit simple-chatbot/embed-chatbot.js, and in the second line change `CHATBOT_URL` to the public URL of your website with `/chatbot-embed/`

By default, all pages will automatically attach the chatbot widget.

If you want to use the chat widget in a external website outside of your WordPress instance, use this script: 
```html
<script src="https://YOURWEBSITE.com/wp-content/plugins/simple-chatbot/embed-chatbot.js"></script>
```

Tested with Wordpress 6.8.1

Developed by Iohann Tachy.
Please give it a star in Github if this project was useful to you. Thanks!
