<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConversationRequest;
use App\Models\Conversation;
use App\Models\User;
use App\Services\MessagingService;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function __construct(private MessagingService $messaging) {}

    public function index(Request $request)
    {
        $conversations = $request->user()
            ->conversations()
            ->with(['latestMessage', 'participants'])
            ->latest()
            ->paginate(20);

        return view('conversations.index', compact('conversations'));
    }

    public function create(Request $request)
    {
        // Optional: pre-select a recipient via ?to=user_id
        $recipient = $request->query('to')
            ? User::findOrFail($request->query('to'))
            : null;

        return view('conversations.create', compact('recipient'));
    }

    public function store(StoreConversationRequest $request)
    {
        $recipient = User::findOrFail($request->validated('recipient_id'));

        $conv = $this->messaging->createConversation(
            $request->user(),
            $recipient,
            $request->validated('subject'),
            $request->validated('body')
        );

        return redirect()->route('conversations.show', $conv)
            ->with('success', __('engagement.message_sent'));
    }

    public function show(Request $request, Conversation $conversation)
    {
        $this->authorize('view', $conversation);
        $this->messaging->markRead($conversation, $request->user());

        $messages = $conversation->messages()->with('sender')->get();

        return view('conversations.show', compact('conversation', 'messages'));
    }

    public function reply(Request $request, Conversation $conversation)
    {
        $this->authorize('reply', $conversation);

        $request->validate(['body' => ['required', 'string', 'max:5000']]);

        $this->messaging->reply($conversation, $request->user(), $request->body);

        return redirect()->route('conversations.show', $conversation);
    }
}
