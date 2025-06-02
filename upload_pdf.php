<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['contrato_id']) && isset($_FILES['pdf_file'])) {
    $contratoId = (int)$_POST['contrato_id'];
    $pdfFile = $_FILES['pdf_file'];

    if ($pdfFile['error'] === UPLOAD_ERR_OK && strtolower(pathinfo($pdfFile['name'], PATHINFO_EXTENSION)) === 'pdf') {
        $uploadDir = 'uploads_pdfs/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $newFileName = 'contrato_' . $contratoId . '_' . time() . '.pdf';
        $destPath = $uploadDir . $newFileName;

        if (move_uploaded_file($pdfFile['tmp_name'], $destPath)) {
            $stmt = $conn->prepare("UPDATE contratos SET pdf_path=?, status_baixa='baixado' WHERE id=?");
            $stmt->execute([$destPath, $contratoId]);
            header("Location: index.php?upload=ok");
            exit;
        }
    }
    header("Location: index.php?upload=erro");
    exit;
}
header("Location: index.php?upload=erro2");
exit;
?>