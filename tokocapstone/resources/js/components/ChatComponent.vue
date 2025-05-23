<template>
  <div class="chat-container">
    <div class="chat-messages" ref="messages">
      <div v-for="(message, index) in messages" :key="index" :class="message.type">
        {{ message.text }}
      </div>
    </div>
    <div class="chat-input">
      <input type="text" v-model="userInput" @keyup.enter="sendMessage" placeholder="Type a message...">
      <button @click="sendMessage">Send</button>
    </div>
  </div>
</template>

<script>
import io from 'socket.io-client';

export default {
  name: 'ChatComponent',
  data() {
    return {
      socket: null,
      messages: [],
      userInput: ''
    }
  },
  mounted() {
    this.socket = io('http://localhost:5005');

    this.socket.on('bot_uttered', (data) => {
      this.messages.push({
        text: data.text,
        type: 'bot'
      });
      this.$nextTick(() => {
        this.scrollToBottom();
      });
    });
  },
  methods: {
    sendMessage() {
      if (this.userInput.trim() === '') return;

      // Add user message to chat
      this.messages.push({
        text: this.userInput,
        type: 'user'
      });

      // Send to Rasa
      this.socket.emit('user_uttered', {
        message: this.userInput
      });

      // Clear input
      this.userInput = '';

      this.$nextTick(() => {
        this.scrollToBottom();
      });
    },
    scrollToBottom() {
      const messages = this.$refs.messages;
      messages.scrollTop = messages.scrollHeight;
    }
  }
}
</script>

<style scoped>
.chat-container {
  height: 400px;
  width: 300px;
  border: 1px solid #ccc;
  border-radius: 4px;
  display: flex;
  flex-direction: column;
}

.chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 10px;
}

.chat-input {
  display: flex;
  padding: 10px;
  border-top: 1px solid #ccc;
}

.chat-input input {
  flex: 1;
  padding: 5px;
  margin-right: 10px;
}

.user {
  text-align: right;
  color: blue;
  margin: 5px 0;
}

.bot {
  text-align: left;
  color: green;
  margin: 5px 0;
}
</style>
