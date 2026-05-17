<aside class="sidebar">
    <a class="brand white" href="{{ route('home') }}">Corleone App Hub</a>

    <button class="compose-btn" type="button" onclick="openCompose()">
        @include('partials.icon', ['name' => 'plus'])
        <span>Compose</span>
    </button>

    <button class="menu active" type="button" data-box="inbox" onclick="showInbox()">
        <span class="menu-label">@include('partials.icon', ['name' => 'inbox']) Inbox</span>
        <span>3</span>
    </button>
    <button class="menu" type="button" data-box="sent" onclick="showSent()">
        <span class="menu-label">@include('partials.icon', ['name' => 'sent']) Sent</span>
        <span id="sentCount">0</span>
    </button>
    <button class="menu" type="button" data-box="drafts" onclick="showDrafts()">
        <span class="menu-label">@include('partials.icon', ['name' => 'draft']) Drafts</span>
        <span>0</span>
    </button>
    <button class="menu" type="button" data-box="archived" onclick="showArchived()">
        <span class="menu-label">@include('partials.icon', ['name' => 'archive']) Archived</span>
        <span>4</span>
    </button>

    <a class="menu link-menu" href="{{ route('ai-chatbot') }}">
        <span class="menu-label">@include('partials.icon', ['name' => 'chat']) AI Chatbot</span>
        <span>Open</span>
    </a>

    <form class="logout-menu" method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="menu" type="submit">
            <span class="menu-label">@include('partials.icon', ['name' => 'logout']) Logout</span>
            <span>Exit</span>
        </button>
    </form>
</aside>
