{{-- The support assistant. The page may come from the shared cache, so nothing
     personal is printed here: the widget fetches the chat itself. The socket
     settings are the same for everyone; the widget loads the socket scripts
     only once it has a chat to listen to. --}}
@inject('liveSocket', 'App\Services\LiveSocket')
@php
    $live = $liveSocket->forBrowser();
@endphp
<div class="assistant" id="assistant"
    data-show="{{ route('assistant.show') }}"
    data-send="{{ route('assistant.send') }}"
    data-handoff="{{ route('assistant.handoff') }}"
    data-seen-ms="{{ App\Actions\ReplyToCustomerAction::CHECK_IN_MS }}"
    @if ($live)
        data-live="{{ json_encode($live + ['auth' => route('assistant.channel'), 'scripts' => array_map('asset_v', $liveSocket::SCRIPTS)], JSON_UNESCAPED_SLASHES) }}"
    @endif
    data-site="{{ config('app.url') }}"
    data-hotline="{{ $contact->first()?->contact_phone }}">
    <div class="assistant-launcher">
        <div class="assistant-tip" hidden><b>Cần hỗ trợ?</b>Hỏi về size, đơn hàng hoặc đổi trả.</div>
        <button type="button" class="assistant-open" aria-label="Mở trợ lý Sneaker Square">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/></svg>
            <span class="assistant-dot" hidden></span>
        </button>
    </div>

    <section class="assistant-panel" role="dialog" aria-label="Trợ lý Sneaker Square" hidden>
        <header class="assistant-head">
            <div class="assistant-avatar" aria-hidden="true">SS</div>
            <div class="assistant-title">
                <b>Trợ lý Sneaker Square</b>
                <span>Trả lời tự động từ dữ liệu của shop</span>
            </div>
            <button type="button" class="assistant-close" aria-label="Đóng">&times;</button>
        </header>
        <div class="assistant-body" aria-live="polite"></div>
        <footer class="assistant-foot">
            <form class="assistant-form" autocomplete="off">
                <input type="text" name="message" maxlength="500" placeholder="Nhập câu hỏi…" aria-label="Câu hỏi">
                <button type="submit" aria-label="Gửi">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                </button>
            </form>
            <div class="assistant-note">
                <span class="assistant-note-bot">Câu trả lời tự động. <button type="button" class="assistant-staff">Gặp nhân viên</button></span>
                <span class="assistant-note-staff" hidden>Đang chờ nhân viên</span>
            </div>
        </footer>
    </section>
</div>
