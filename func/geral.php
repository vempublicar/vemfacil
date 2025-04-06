<?php
function badgeStatusCRM($contato): string {
    if (!is_array($contato) || empty($contato)) {
        return 'Dados inválidos!';
    }

    $html = '<div class="mt-2 d-flex flex-wrap gap-1">';

    // STATUS
    $status = strtolower($contato['status'] ?? '');
    $iconeStatus = 'bi-info-circle'; // Default
    $corStatus = 'text-muted'; // Default

    // Substitui o 'match' por 'switch' ou 'if'
    switch ($status) {
        case 'lead':
            $iconeStatus = 'bi-person-plus';
            $corStatus = 'text-primary';
            break;
        case 'pago':
            $iconeStatus = 'bi-check-circle-fill';
            $corStatus = 'text-success';
            break;
        case 'vencido':
            $iconeStatus = 'bi-x-circle-fill';
            $corStatus = 'text-danger';
            break;
    }

    $html .= "<span class='badge border {$corStatus}'><i class='bi {$iconeStatus} me-1' data-bs-toggle='tooltip' title='Status: " . ucfirst($status) . "'></i> " . ucfirst($status) . "</span>";

    // PRIORIDADE
    if (!empty($contato['prioridade'])) {
        $prioridade = strtolower($contato['prioridade']);
        $iconePrioridade = 'bi-flag'; // Default
        $corPrioridade = 'text-secondary'; // Default

        switch ($prioridade) {
            case 'crítica':
                $iconePrioridade = 'bi-exclamation-triangle-fill';
                $corPrioridade = 'text-danger';
                break;
            case 'alta':
                $iconePrioridade = 'bi-fire';
                $corPrioridade = 'text-warning';
                break;
            case 'média':
                $iconePrioridade = 'bi-hourglass-split';
                $corPrioridade = 'text-primary';
                break;
            case 'baixa':
                $iconePrioridade = 'bi-check2-circle';
                $corPrioridade = 'text-muted';
                break;
        }

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
