import React, { useState, useEffect, useRef, useCallback } from 'react';
import './Chat.css';

const MERCURE_HUB  = 'https://mercure.jimwilliamsconsulting.com/.well-known/mercure';
const GLOBAL_TOPIC = 'https://pickenchicken.com/chat/global';
const API          = process.env.REACT_APP_API_URL || 'https://api.pickenchicken.com';
const MAX_MESSAGES = 200;
const PING_INTERVAL = 20000;

async function apiFetch(path, opts = {}) {
  const res = await fetch(`${API}${path}`, {
    headers: { 'Content-Type': 'application/json' },
    ...opts,
  });
  if (!res.ok) throw new Error('API error');
  return res.json();
}

export default function Chat({ user }) {
  const [messages, setMessages]   = useState([]);
  const [input, setInput]         = useState('');
  const [connected, setConnected] = useState(false);
  const [onlineCount, setOnlineCount] = useState(null);
  const messagesEndRef = useRef(null);
  const esRef          = useRef(null);
  const pingRef        = useRef(null);

  const addMessage = useCallback((msg) => {
    setMessages(prev => [...prev.slice(-MAX_MESSAGES + 1), msg]);
  }, []);

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };

  useEffect(() => { scrollToBottom(); }, [messages]);

  // Join, get token, open SSE connection
  useEffect(() => {
    if (!user?.id) return;

    let cancelled = false;

    const connect = async () => {
      try {
        // Join room
        const joinData = await apiFetch('/chat/join', {
          method: 'POST',
          body: JSON.stringify({ userId: parseInt(user.id) }),
        });
        if (cancelled) return;
        setOnlineCount(joinData.users?.length ?? null);

        // Get subscriber token
        const { token } = await apiFetch(`/chat/token?userId=${user.id}`);
        if (cancelled) return;

        // Open EventSource
        const url = new URL(MERCURE_HUB);
        url.searchParams.append('topic', GLOBAL_TOPIC);

        const es = new EventSource(`${url.toString()}&authorization=${token}`);
        esRef.current = es;

        es.onopen = () => { if (!cancelled) setConnected(true); };

        es.onmessage = (e) => {
          if (cancelled) return;
          try {
            const data = JSON.parse(e.data);
            if (data.event === 'message') {
              addMessage({ type: 'message', user: data.user, text: data.message, time: data.time, id: `${data.time}-${data.user}` });
            } else if (data.event === 'user_joined') {
              setOnlineCount(data.users?.length ?? null);
              addMessage({ type: 'system', text: `${data.user} joined`, id: `join-${data.time}-${data.user}` });
            } else if (data.event === 'user_left') {
              setOnlineCount(data.users?.length ?? null);
              addMessage({ type: 'system', text: `${data.user} left`, id: `left-${data.time}-${data.user}` });
            }
          } catch {}
        };

        es.onerror = () => {
          if (!cancelled) setConnected(false);
        };

      } catch (err) {
        if (!cancelled) setConnected(false);
      }
    };

    connect();

    // Ping to keep presence alive
    pingRef.current = setInterval(() => {
      apiFetch('/chat/ping', {
        method: 'POST',
        body: JSON.stringify({ userId: parseInt(user.id) }),
      }).catch(() => {});
    }, PING_INTERVAL);

    return () => {
      cancelled = true;
      clearInterval(pingRef.current);
      esRef.current?.close();
      apiFetch('/chat/leave', {
        method: 'POST',
        body: JSON.stringify({ userId: parseInt(user.id) }),
      }).catch(() => {});
    };
  }, [user?.id, addMessage]);

  const sendMessage = async () => {
    const msg = input.trim();
    if (!msg || !connected) return;
    setInput('');
    try {
      await apiFetch('/chat/message', {
        method: 'POST',
        body: JSON.stringify({ userId: parseInt(user.id), message: msg }),
      });
    } catch {}
  };

  const handleKey = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  };

  const myUsername = user?.username || user?.email;

  return (
    <div className="pc-chat">
      <div className="pc-chat-header">
        <span className="pc-chat-title">🐔 Live Chat</span>
        {onlineCount !== null && (
          <span className="pc-chat-presence">{onlineCount} online</span>
        )}
      </div>

      <div className="pc-chat-messages">
        {!connected && (
          <div className="pc-chat-connecting">Connecting...</div>
        )}
        {messages.map(msg => (
          <div key={msg.id} className="pc-chat-msg">
            {msg.type === 'system' ? (
              <div className="pc-chat-msg-bubble is-system">{msg.text}</div>
            ) : (
              <>
                <div className={`pc-chat-msg-meta ${msg.user === myUsername ? 'is-me' : ''}`}>
                  {msg.user}
                </div>
                <div className={`pc-chat-msg-bubble ${msg.user === myUsername ? 'is-me' : ''}`}>
                  {msg.text}
                </div>
              </>
            )}
          </div>
        ))}
        <div ref={messagesEndRef} />
      </div>

      <div className="pc-chat-input-row">
        <input
          className="pc-chat-input"
          value={input}
          onChange={e => setInput(e.target.value)}
          onKeyDown={handleKey}
          placeholder={connected ? 'Say something...' : 'Connecting...'}
          disabled={!connected}
          maxLength={500}
        />
        <button
          className="pc-chat-send"
          onClick={sendMessage}
          disabled={!connected || !input.trim()}
        >
          Send
        </button>
      </div>
    </div>
  );
}
