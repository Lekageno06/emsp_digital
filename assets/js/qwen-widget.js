// Aligne TP CRUD V2 - Widget Assistant IA OpenAI fetch securise & RG-31

$(function () {
    if (!$('body').data('auth')) {
        return;
    }

    const widget = $(`
        <div class="qwen-widget position-fixed bottom-0 end-0 m-3" style="z-index: 1050; width: min(360px, calc(100vw - 2rem));">
            <button class="btn btn-emsp-primary shadow w-100" type="button" data-bs-toggle="collapse" data-bs-target="#qwenPanel" aria-expanded="false" aria-controls="qwenPanel">Assistant IA</button>
            <div class="collapse mt-2" id="qwenPanel">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div id="qwenMessages" class="border rounded p-2 mb-3 bg-light" style="height: 180px; overflow-y: auto;"></div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="qwenConsent">
                            <label class="form-check-label" for="qwenConsent">J'accepte la journalisation de mes messages IA.</label>
                        </div>
                        <form id="qwenForm">
                            <label class="form-label" for="qwenInput">Message</label>
                            <textarea class="form-control mb-2" id="qwenInput" rows="3" maxlength="1500" required></textarea>
                            <button class="btn btn-emsp-success w-100" type="submit">Envoyer</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    `);

    $('body').append(widget);

    function appendMessage(author, text) {
        const block = $('<div class="mb-2">');
        block.append($('<strong>').text(author + ' : '));
        block.append($('<span>').text(text));
        $('#qwenMessages').append(block);
        $('#qwenMessages').scrollTop($('#qwenMessages')[0].scrollHeight);
    }

    $('#qwenForm').on('submit', function (event) {
        event.preventDefault();

        const input = $('#qwenInput');
        const message = input.val().trim();
        const consent = $('#qwenConsent').is(':checked');

        if (!message) {
            return;
        }

        appendMessage('Vous', message);
        input.val('').prop('disabled', true);

        fetch('/emsp-digital/api/openai-proxy.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'chat',
                message: message,
                consent: consent
            })
        }).then(function (response) {
            return response.json();
        }).then(function (payload) {
            if (!payload.success) {
                appendMessage('Systeme', payload.message);
                return;
            }

            appendMessage('IA', payload.data.answer);
        }).catch(function () {
            appendMessage('Systeme', 'Erreur reseau.');
        }).finally(function () {
            input.prop('disabled', false).focus();
        });
    });
});
