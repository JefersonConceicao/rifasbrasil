<?php
include("../class/conexao.php");

// Verifica se o parâmetro "id" foi passado na URL
if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // Converte o ID para inteiro (evita SQL Injection)

    // Verifica se o ID é válido
    if ($id > 0) {
        try {
            // Prepara a query de exclusão
            $sql = "DELETE FROM configuracoes_repasse WHERE id = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param('i', $id);

            // Executa a exclusão
            if ($stmt->execute()) {
                // Redireciona com mensagem de sucesso
                header("Location: index.php?p=configuracoes&msg=Repasse excluído com sucesso.");
                exit;
            } else {
                // Redireciona com mensagem de erro
                header("Location: index.php?p=configuracoes&msg=Erro ao excluir o repasse.");
                exit;
            }
        } catch (Exception $error) {
            // Redireciona com mensagem de erro
            header("Location: index.php?p=configuracoes&msg=Erro: " . urlencode($error->getMessage()));
            exit;
        }
    } else {
        // Redireciona com mensagem de erro (ID inválido)
        header("Location: index.php?p=configuracoes&msg=ID inválido.");
        exit;
    }
} else {
    // Redireciona com mensagem de erro (ID não informado)
    header("Location: index.php?p=configuracoes&msg=ID não informado.");
    exit;
}
?>