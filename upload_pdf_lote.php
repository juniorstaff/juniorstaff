<?php
require_once 'db.php';

// Função para limpar o nome do arquivo
function sanitizeFileName($string) {
    $string = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $string);
    return $string;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf_lote'])) {
    $file = $_FILES['pdf_lote'];
    if ($file['error'] === UPLOAD_ERR_OK && strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) === 'pdf') {
        $tmpPath = $file['tmp_name'];
        $uploadDir = 'uploads_pdfs/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $destDir = $uploadDir . 'lote_' . time() . '/';
        if (!is_dir($destDir)) mkdir($destDir, 0777, true);

        $pythonBin = 'python3';
        $script = __DIR__ . '/processa_pdf_lote.py';

        $cmd = escapeshellcmd("$pythonBin $script " . escapeshellarg($tmpPath) . " " . escapeshellarg($destDir));
        $output = [];
        $return_var = 0;
        exec($cmd, $output, $return_var);

        foreach ($output as $linha) {
            $linha = trim($linha);
            if (strpos($linha, '|') !== false) {
                list($contrato, $arquivo) = explode('|', $linha, 2);
                $arquivo = sanitizeFileName($arquivo);
                $stmt = $conn->prepare("UPDATE contratos SET pdf_path=?, status_baixa='baixado' WHERE numero_contrato=?");
                $stmt->execute([$destDir . $arquivo, $contrato]);
            }
        }

        header("Location: index.php?upload_lote=ok");
        exit;
    }
}
header("Location: index.php?upload_lote=erro");
exit;
?>