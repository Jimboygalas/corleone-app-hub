@extends('layouts.app')

@section('title', 'Corleone App Hub AI Chatbot')
@section('bodyClass', 'chatbot-body')

@section('content')
<div class="chatbot-only-page">
    <main class="chat-panel">
        <header class="chat-header">
            <div class="ai-orb">@include('partials.icon', ['name' => 'bot'])</div>
            <div>
                <h2>Corleone AI Assistant</h2>
                <small>Online - Ready to help</small>
            </div>
            <a class="btn compact light panel-link" href="{{ route('mailbox') }}">Mailbox</a>
        </header>

        <section class="chat-window" id="chatWindow" aria-live="polite">
            <div class="chat-message bot show">
                <div class="avatar">AI</div>
                <div class="bubble">
                    Hi! I am your Corleone App Hub AI Assistant. How can I help you today?
                </div>
            </div>
        </section>

        <div class="quick-prompts">
            <button type="button" onclick="quickAsk('Summarize my mailbox')">Summarize mailbox</button>
            <button type="button" onclick="quickAsk('Help me compose an email')">Compose email</button>
            <button type="button" onclick="quickAsk('Explain OTP verification')">OTP help</button>
        </div>

        <footer class="chat-input-bar">
            <input id="chatInput" placeholder="Type your message..." onkeydown="handleChatKey(event)">
            <button type="button" onclick="sendChat()">Send</button>
        </footer>
    </main>
</div>
@endsection
