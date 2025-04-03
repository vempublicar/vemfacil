// crm_mensagens.js

let idContatoAtual = null;

function abrirConversa(id, nome, numero) {
    console.log(numero);
    idContatoAtual = id;
    const modal = new bootstrap.Modal(document.getElementById('modalConversa'));
    document.getElementById('nomeLead').textContent = nome;
    document.getElementById('numeroLead').value = numero;
    carregarMensagens();
    modal.show();
}

function carregarMensagens() {
    if (!idContatoAtual) return;

    const box = document.getElementById('historicoMensagens');
    const estavaNoFinal = (box.scrollTop + box.clientHeight + 50 >= box.scrollHeight);

    fetch(`carregar_mensagens&id=${idContatoAtual}`)
        .then(res => res.json())
        .then(mensagens => {
            box.innerHTML = '';
            if (mensagens.length === 0) {
                box.innerHTML = '<div class="text-muted text-center">Nenhuma mensagem ainda.</div>';
                return;
            }
            mensagens.forEach(msg => {
                const div = document.createElement('div');
                div.className = `mensagem d-flex ${msg.tipo === 'enviada' ? 'justify-content-end' : 'justify-content-start'}`;

                const balao = document.createElement('div');
                balao.className = `balao ${msg.tipo === 'enviada' ? 'direita' : 'esquerda'}`;
                balao.textContent = msg.mensagem;

                div.appendChild(balao);
                box.appendChild(div);
            });
            if (estavaNoFinal) box.scrollTop = box.scrollHeight;
        });
}

async function enviarMensagem() {
    const numero = document.getElementById('numeroLead').value;
    const mensagemInput = document.getElementById('mensagemTexto');
    const mensagem = mensagemInput?.value.trim();

    if (!mensagem) return;

    setTimeout(() => {
        mensagemInput.value = '';
    }, 100);

    try {
        await fetch('init/enviar_mensagem.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `numero=${encodeURIComponent(numero)}&mensagem=${encodeURIComponent(mensagem)}`
        });
        carregarMensagens();
    } catch (err) {
        console.error('Erro ao enviar mensagem:', err);
    }
}

function adicionarLead(id) {
    fetch('init/update_etiqueta.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${id}&etiqueta=Base`
    }).then(() => location.reload());
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[id^="etapa-"]').forEach(coluna => {
        new Sortable(coluna, {
            group: 'etapas',
            animation: 150,
            onAdd: function (evt) {
                const contatoId = evt.item.dataset.id;
                const novaEtapa = evt.to.dataset.etapa;
                fetch('init/update_etiqueta.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${contatoId}&etiqueta=${encodeURIComponent(novaEtapa)}`
                });
            }
        });
    });

    document.getElementById('busca-lead').addEventListener('input', function () {
        const termo = this.value.trim();
        const container = document.getElementById('resultados-leads');
        if (termo.length < 3) {
            container.innerHTML = '';
            return;
        }

        fetch('init/buscar_leads_crm.php?termo=' + encodeURIComponent(termo))
            .then(res => res.json())
            .then(leads => {
                container.innerHTML = '';
                leads.forEach(lead => {
                    const item = document.createElement('a');
                    item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
                    item.innerHTML = `
                        <span><strong>${lead.nome}</strong> - ${lead.telefone}</span>
                        <button class="btn btn-sm btn-outline-primary" onclick="adicionarLead(${lead.id})">Adicionar</button>`;
                    container.appendChild(item);
                });
            });
    });

    document.getElementById('formResposta')?.addEventListener('submit', function (e) {
        e.preventDefault();
        enviarMensagem();
    });
});

document.addEventListener('click', function (e) {
    if (e.target.classList.contains('btn-excluir-etapa')) {
        const id = e.target.dataset.id;
        if (confirm('Tem certeza que deseja excluir esta etapa?')) {
            fetch('init/excluir_etapa.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + encodeURIComponent(id)
            })
                .then(res => res.text())
                .then(msg => {
                    alert(msg);
                    location.reload();
                });
        }
    }
});

setInterval(() => {
    const modalAberto = document.getElementById('modalConversa')?.classList.contains('show');
    if (modalAberto && idContatoAtual) carregarMensagens();
}, 4000);

setInterval(() => {
    fetch('init/notificacoes.php')
        .then(res => res.json())
        .then(notificacoes => {
            document.querySelectorAll('[data-id]').forEach(item => {
                const contatoId = item.getAttribute('data-id');
                const badge = item.querySelector('.badge.bg-success');

                if (notificacoes[contatoId]) {
                    if (!badge) {
                        const nomeDiv = item.querySelector('.fw-bold.d-flex');
                        const span = document.createElement('span');
                        span.className = 'badge bg-success ms-2';
                        span.innerHTML = `<i class="bi bi-chat-dots-fill me-1"></i>${notificacoes[contatoId]}`;
                        nomeDiv.appendChild(span);
                    } else {
                        badge.innerHTML = `<i class="bi bi-chat-dots-fill me-1"></i>${notificacoes[contatoId]}`;
                    }
                } else if (badge) {
                    badge.remove();
                }
            });
        });
}, 4000);
