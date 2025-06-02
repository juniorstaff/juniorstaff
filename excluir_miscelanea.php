<?php
include 'conexao.php';

// Verifica se recebeu o ID pela URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: cadastro_miscelaneas.php");
    exit;
}

$id = intval($_GET['id']);

// Exclui a miscelânea do banco usando prepared statement
$sql = "DELETE FROM miscelaneas WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    // Redireciona de volta para a lista com mensagem de sucesso
    header("Location: cadastro_miscelaneas.php?msg=excluido");
    exit;
} else {
    // Redireciona de volta para a lista com mensagem de erro
    header("Location: cadastro_miscelaneas.php?erro=nao_excluido");
    exit;
}
?>