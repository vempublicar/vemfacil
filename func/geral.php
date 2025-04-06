<?php
function badgeStatusCRM($contato): string {
    // Verifica se $contato é um array e tem pelo menos um elemento
    if (!is_array($contato) || empty($contato)) {
        // return 'Dados inválidos!';
    }

    $html = '<div class="mt-2 d-flex flex-wrap gap-1">';

    // STATUS
    $status = strtolower($contato['status'] ?? '');
    $iconeStatus = match ($status) {
        'lead'    => 'bi-person-plus',
        'pago'    => 'bi-check-circle-fill',
        'vencido' => 'bi-x-circle-fill',
        default   => 'bi-info-circle'
    };
    $corStatus = match ($status) {
        'lead'    => 'text-primary',
        'pago'    => 'text-success',
        'vencido' => 'text-danger',
        default   => 'text-muted'
    };
    $html .= "<span class='badge border {$corStatus}'><i class='bi {$iconeStatus} me-1' data-bs-toggle='tooltip' title='Status: " . ucfirst($status) . "'></i> " . ucfirst($status) . "</span>";

    // PRIORIDADE
    if (!empty($contato['prioridade'])) {
        $prioridade = strtolower($contato['prioridade']);
        $iconePrioridade = match ($prioridade) {
            'crítica' => 'bi-exclamation-triangle-fill',
            'alta'    => 'bi-fire',
            'média'   => 'bi-hourglass-split',
            'baixa'   => 'bi-check2-circle',
            default   => 'bi-flag'
        };
        $corPrioridade = match ($prioridade) {
            'crítica' => 'text-danger',
            'alta'    => 'text-warning',
            'média'   => 'text-primary',
            'baixa'   => 'text-muted',
            default   => 'text-secondary'
        };
        $html .= "<span class='badge border {$corPrioridade}'><i class='bi {$iconePrioridade}' data-bs-toggle='tooltip' title='Prioridade: " . ucfirst($prioridade) . "'></i></span>";
    }

    // VARIÁVEL A
    if (!empty($contato['variavelA'])) {
        $html .= "<span class='badge border text-dark'><i class='bi bi-tags' data-bs-toggle='tooltip' title='Etiqueta: " . htmlspecialchars($contato['variavelA']) . "'></i></span>";
    }

    // GRUPO A
    if (!empty($contato['grupoA'])) {
        $html .= "<span class='badge border text-dark'><i class='bi bi-folder-fill' data-bs-toggle='tooltip' title='Grupo: " . htmlspecialchars($contato['grupoA']) . "'></i></span>";
    }

    $html .= '</div>';
    return $html;
}
