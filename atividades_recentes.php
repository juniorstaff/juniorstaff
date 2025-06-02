<?php
/**
 * API para fornecer atividades recentes do sistema
 * Parte do Sistema Integrado de Gerenciamento
 * 
 * Este script retorna um array JSON contendo as atividades
 * recentes registradas no sistema, incluindo tipo, título,
 * descrição, usuário e timestamp.
 */

// Configurar cabeçalhos para JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Em um ambiente de produção, estas informações viriam de um banco de dados
// Função para simular acesso ao banco de dados
function obterAtividadesRecentes() {
    // Simulação de dados do banco de dados
    // Em um ambiente real, isto seria substituído por uma consulta SQL
    
    // Timestamp atual
    $agora = time();
    
    // Array de atividades
    $atividades = [
        [
            'id' => 1043,
            'tipo' => 'cadastro_onu',
            'titulo' => 'ONU cadastrada',
            'descricao' => 'Nova ONU registrada no sistema',
            'usuario' => 'Carlos Mendes',
            'usuario_id' => 15,
            'timestamp' => $agora - 3600, // 1 hora atrás
            'detalhes' => [
                'mac' => 'FF:AA:BB:CC:DD:EE',
                'modelo' => 'ZTE F670L',
                'cliente_id' => 1245
            ]
        ],
        [
            'id' => 1042,
            'tipo' => 'entrega_materiais',
            'titulo' => 'Entrega atualizada',
            'descricao' => 'Status de entrega de materiais atualizado',
            'usuario' => 'Amanda Silva',
            'usuario_id' => 8,
            'timestamp' => $agora - 7200, // 2 horas atrás
            'detalhes' => [
                'entrega_id' => 245,
                'status_anterior' => 'Em rota',
                'status_novo' => 'Entregue',
                'endereco' => 'Av. Principal, 1500'
            ]
        ],
        [
            'id' => 1041,
            'tipo' => 'gerenciamento_contratos',
            'titulo' => 'Contrato modificado',
            'descricao' => 'Termos de contrato atualizados',
            'usuario' => 'Rafael Costa',
            'usuario_id' => 3,
            'timestamp' => $agora - 28800, // 8 horas atrás
            'detalhes' => [
                'contrato_id' => 89,
                'cliente' => 'Empresa ABC Ltda.',
                'modificacao' => 'Alteração de prazo'
            ]
        ],
        [
            'id' => 1040,
            'tipo' => 'cadastro_equipe',
            'titulo' => 'Novo membro da equipe',
            'descricao' => 'Colaborador adicionado à equipe de instalação',
            'usuario' => 'Fernanda Lima',
            'usuario_id' => 2,
            'timestamp' => $agora - 86400, // 1 dia atrás
            'detalhes' => [
                'colaborador_id' => 78,
                'nome' => 'João Paulo Oliveira',
                'equipe' => 'Instalação - Zona Norte'
            ]
        ],
        [
            'id' => 1039,
            'tipo' => 'buscar_onu',
            'titulo' => 'Consulta de ONU',
            'descricao' => 'Busca de ONU por endereço MAC',
            'usuario' => 'Thiago Santos',
            'usuario_id' => 10,
            'timestamp' => $agora - 172800, // 2 dias atrás
            'detalhes' => [
                'mac_buscado' => 'AA:BB:CC:11:22:33',
                'resultado' => 'Encontrado',
                'status' => 'Ativo'
            ]
        ],
        [
            'id' => 1038,
            'tipo' => 'entrega_materiais',
            'titulo' => 'Material recebido em estoque',
            'descricao' => 'Recebimento de novos equipamentos no almoxarifado',
            'usuario' => 'Roberto Alves',
            'usuario_id' => 12,
            'timestamp' => $agora - 259200, // 3 dias atrás
            'detalhes' => [
                'nota_fiscal' => 'NF-e 12345',
                'fornecedor' => 'TechNet Equipamentos',
                'quantidade' => 50,
                'tipo_material' => 'ONU ZTE F601'
            ]
        ],
        [
            'id' => 1037,
            'tipo' => 'gerenciamento_contratos',
            'titulo' => 'Novo contrato assinado',
            'descricao' => 'Contrato de prestação de serviços firmado',
            'usuario' => 'Rafael Costa',
            'usuario_id' => 3,
            'timestamp' => $agora - 345600, // 4 dias atrás
            'detalhes' => [
                'contrato_id' => 88,
                'cliente' => 'Condomínio Jardins',
                'modalidade' => 'Prestação de serviços dedicados'
            ]
        ],
        [
            'id' => 1036,
            'tipo' => 'cadastro_onu',
            'titulo' => 'Atualização de firmware',
            'descricao' => 'Atualização em lote de firmware de ONUs',
            'usuario' => 'Carlos Mendes',
            'usuario_id' => 15,
            'timestamp' => $agora - 432000, // 5 dias atrás
            'detalhes' => [
                'versao_anterior' => 'V1.0.12',
                'versao_nova' => 'V2.1.3',
                'quantidade' => 120,
                'modelo' => 'Huawei HG8245H'
            ]
        ]
    ];
    
    return $atividades;
}

// Parâmetros de paginação
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = isset($_GET['por_pagina']) ? (int)$_GET['por_pagina'] : 8;

// Validação básica
if ($pagina < 1) $pagina = 1;
if ($por_pagina < 1 || $por_pagina > 50) $por_pagina = 8;

// Obter atividades
$todas_atividades = obterAtividadesRecentes();

// Calcular paginação
$total_atividades = count($todas_atividades);
$total_paginas = ceil($total_atividades / $por_pagina);
$inicio = ($pagina - 1) * $por_pagina;

// Limitar atividades para a página atual
$atividades_pagina = array_slice($todas_atividades, $inicio, $por_pagina);

// Simular latência da rede/banco de dados
// Remova esta linha em produção
usleep(mt_rand(100000, 300000)); // 100-300ms

// Formatar resposta
$resposta = [
    'codigo' => 200,
    'mensagem' => 'Atividades recuperadas com sucesso',
    'data' => [
        'atividades' => $atividades_pagina,
        'paginacao' => [
            'pagina_atual' => $pagina,
            'total_paginas' => $total_paginas,
            'por_pagina' => $por_pagina,
            'total_registros' => $total_atividades
        ]
    ]
];

// Retornar JSON
echo json_encode($resposta, JSON_PRETTY_PRINT);
?>