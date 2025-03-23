<?php
include("../class/conexao.php");

// Função para buscar os dados da tabela configuracoes
function buscarConfiguracoes($mysqli)
{
    $sql = "SELECT * FROM configuracoes LIMIT 1";
    $result = $mysqli->query($sql);
    return $result->fetch_assoc(); // Retorna a primeira linha ou null se não houver registros
}


function salvarRepasse($mysqli, $dados) {
    // Verifica o número de registros existentes
    $sqlCount = "SELECT COUNT(*) as total FROM configuracoes_repasse";
    $resultCount = $mysqli->query($sqlCount);
    $rowCount = $resultCount->fetch_assoc();
    $totalRegistros = $rowCount['total'];

    // Limite de 3 registros
    if ($totalRegistros >= 3) {
        throw new Exception("Limite máximo de 3 repasses atingido.");
    }

    // Prepara os dados para salvar
    $sql = "INSERT INTO configuracoes_repasse (
            conta_repasse,
            cpf_conta_repasse,
            porcentagem_valor_repasse,
            agencia_repasse,
            data_cadastro,
            data_atualizacao
        ) VALUES (?, ?, ?, ?, NOW(), NOW())";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param(
        'ssds',
        $dados['conta_repasse'],
        $dados['cpf_conta_repasse'],
        $dados['porcentagem_valor_repasse'],
        $dados['agencia_repasse']
    );

    if ($stmt->execute()) {
        return true;
    } else {
        return false;
    }
}


// Função para salvar ou atualizar as configurações
function salvarConfiguracoes($mysqli, $dados, $tipo_form)
{
    // Busca as configurações existentes
    $configuracoes = buscarConfiguracoes($mysqli);

    // Prepara os dados para salvar
    $campos = [
        'client_id' => $dados['client_id'] ?? ($configuracoes['client_id'] ?? ''),
        'client_secret' => $dados['client_secret'] ?? ($configuracoes['client_secret'] ?? ''),
        'chave_pix' => $dados['chave_pix'] ?? ($configuracoes['chave_pix'] ?? ''),
        'valor_por_id' => $dados['valor_por_id'] ?? ($configuracoes['valor_por_id'] ?? 0),
        'conta_repasse' => $dados['conta_repasse'] ?? ($configuracoes['conta_repasse'] ?? ''),
        'cpf_conta_repasse' => $dados['cpf_conta_repasse'] ?? ($configuracoes['cpf_conta_repasse'] ?? ''),
        'porcentagem_valor_repasse' => $dados['porcentagem_valor_repasse'] ?? ($configuracoes['porcentagem_valor_repasse'] ?? 0),
        'agencia_repasse' => $dados['agencia_repasse'] ?? ($configuracoes['agencia_repasse'] ?? ''),
        'documento_certificado_gerencianet' => $dados['documento_certificado_gerencianet'] ?? ($configuracoes['documento_certificado_gerencianet'] ?? '')
    ];

    if ($configuracoes) {
        // Se já existir um registro, faz um UPDATE
        $sql = "UPDATE configuracoes SET
                client_id = ?,
                client_secret = ?,
                chave_pix = ?,
                valor_por_id = ?,
                conta_repasse = ?,
                cpf_conta_repasse = ?,
                porcentagem_valor_repasse = ?,
                agencia_repasse = ?,
                documento_certificado_gerencianet = ?,
                data_atualizacao = NOW()
                WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param(
            'ssssssdssi',
            $campos['client_id'],
            $campos['client_secret'],
            $campos['chave_pix'],
            $campos['valor_por_id'],
            $campos['conta_repasse'],
            $campos['cpf_conta_repasse'],
            $campos['porcentagem_valor_repasse'],
            $campos['agencia_repasse'],
            $campos['documento_certificado_gerencianet'],
            $configuracoes['id']
        );
    } else {
        // Se não existir um registro, faz um INSERT
        $sql = "INSERT INTO configuracoes (
                client_id,
                client_secret,
                chave_pix,
                valor_por_id,
                conta_repasse,
                cpf_conta_repasse,
                porcentagem_valor_repasse,
                agencia_repasse,
                documento_certificado_gerencianet,
                data_cadastro,
                data_atualizacao
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param(
            'ssssssdss',
            $campos['client_id'],
            $campos['client_secret'],
            $campos['chave_pix'],
            $campos['valor_por_id'],
            $campos['conta_repasse'],
            $campos['cpf_conta_repasse'],
            $campos['porcentagem_valor_repasse'],
            $campos['agencia_repasse'],
            $campos['documento_certificado_gerencianet']
        );
    }

    if ($stmt->execute()) {
        return true;
    } else {
        return false;
    }
}

// Verifica se o formulário foi enviado
if (!empty($_POST)) {
    try {
        $arrayRequest = $_POST;

        if (empty($arrayRequest['tipo_form'])) {
            throw new Exception("É obrigatório um tipo de formulário");
        }

        // Dados do formulário
        $dados = [];
        if ($arrayRequest['tipo_form'] == 'principal') {
            $dados = [
                'client_id' => $arrayRequest['client_id'] ?? '',
                'client_secret' => $arrayRequest['client_secret'] ?? '',
                'chave_pix' => $arrayRequest['chave_pix'] ?? '',
                'valor_por_id' => $arrayRequest['valor_por_id'] ?? 0
            ];

            // Processar o upload do certificado
            if (!empty($_FILES['documento_certificado_gerencianet']['name'])) {
                $uploadDir = '../uploads/certificados/'; // Diretório para salvar os arquivos
                if (!is_dir($uploadDir)) {
                    $criacao = mkdir($uploadDir, 0777, true); // Cria o diretório se não existir
                }

                $fileName = basename($_FILES['documento_certificado_gerencianet']['name']);
                $uploadFilePath = $uploadDir . $fileName;


                // Move o arquivo para o diretório de uploads
                if (move_uploaded_file($_FILES['documento_certificado_gerencianet']['tmp_name'], $uploadFilePath)) {
                    $dados['documento_certificado_gerencianet'] = $uploadFilePath;
                } else {
                    throw new Exception("Erro ao fazer upload do certificado.");
                }
            }

            if (salvarConfiguracoes($mysqli, $dados, null)) {
                echo "<script>alert('Operação efetuada com sucesso!'); location.href='index.php?p=configuracoes';</script>";
            } else {
                throw new Exception("Erro ao salvar as configurações");
            }
        } elseif ($arrayRequest['tipo_form'] == 'repasse') {
            $dados = [
                'conta_repasse' => $arrayRequest['repasse_conta'] ?? '',
                'cpf_conta_repasse' => $arrayRequest['repasse_cpf'] ?? '',
                'porcentagem_valor_repasse' => $arrayRequest['repasse_porcentagem'] ?? 0,
                'agencia_repasse' => $arrayRequest['repasse_agencia'] ?? ''
            ];
        }

        // Salva ou atualiza as configurações
        if (salvarRepasse($mysqli, $dados)) {
            echo "<script>alert('Operação efetuada com sucesso!'); location.href='index.php?p=configuracoes';</script>";
        } else {
            throw new Exception("Erro ao salvar as configurações");
        }
    } catch (Exception $error) {
        echo "<script>alert('Erro: " . $error->getMessage() . "');</script>";
    }
}

// Busca as configurações existentes
$configuracoes = buscarConfiguracoes($mysqli);
?>

<!-- Formulários (mantidos iguais) -->
<div class="container">
    <h2> Configurações </h2>
    <ul class="nav nav-tabs">
        <li class="active"><a data-toggle="tab" href="#form1">Conta Principal</a></li>
        <li><a data-toggle="tab" href="#form2">Repasse</a></li>
    </ul>

    <div class="tab-content">
        <div id="form1" class="tab-pane fade in active">
            <h3> Conta Principal </h3>
            <form method="POST" action="index.php?p=configuracoes" enctype="multipart/form-data">
                <input type="hidden" name="tipo_form" value="principal" />
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label> Informe o CLIENT_ID </label>
                            <input type="text" class="form-control" name="client_id" value="<?= $configuracoes['client_id'] ?? '' ?>" />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label> Informe o CLIENT_SECRET </label>
                            <input type="text" class="form-control" name="client_secret" value="<?= $configuracoes['client_secret'] ?? '' ?>" />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label> Chave PIX </label>
                            <input type="text" class="form-control" name="chave_pix" value="<?= $configuracoes['chave_pix'] ?? '' ?>" />
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label> Valor por ID </label>
                            <input type="text" class="form-control dinheiro" name="valor_por_id" value="<?= $configuracoes['valor_por_id'] ?? '' ?>" />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label> Certificado Gerencianet </label>
                            <input type="file" class="form-control" name="documento_certificado_gerencianet" />
                            <?php if (!empty($configuracoes['documento_certificado_gerencianet'])): ?>
                                <p>Arquivo atual: <?= basename($configuracoes['documento_certificado_gerencianet']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary"> Salvar </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div id="form2" class="tab-pane fade">
            <h3>Conta Repasse </h3>

            <form method="POST" action="index.php?p=configuracoes">
                <input type="hidden" name="tipo_form" value="repasse" />

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label> Nome Completo </label>
                            <input type="text" class="form-control" name="repasse_nome"  required />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label> Agência </label>
                            <input type="text" class="form-control" name="repasse_agencia"  required />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label> Conta </label>
                            <input type="text" class="form-control" name="repasse_conta"  required />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label> CPF </label>
                            <input type="text" class="form-control" name="repasse_cpf"  required />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label> Porcentagem </label>
                            <input type="text" class="form-control" name="repasse_porcentagem" required />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary"> Salvar </button>
                        </div>
                    </div>
                </div>
            </form>

            <h3>Repasses Cadastrados</h3>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome Completo</th>
                            <th>Agência</th>
                            <th>Conta</th>
                            <th>CPF</th>
                            <th>Porcentagem</th>
                            <th>Data Cadastro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Busca os repasses cadastrados
                        $sqlRepasses = "SELECT * FROM configuracoes_repasse ORDER BY data_cadastro DESC";
                        $resultRepasses = $mysqli->query($sqlRepasses);

                        if ($resultRepasses->num_rows > 0) {
                            while ($row = $resultRepasses->fetch_assoc()) {
                                echo "<tr>
                            <td>{$row['id']}</td>
                            <td>{$row['conta_repasse']}</td>
                            <td>{$row['agencia_repasse']}</td>
                            <td>{$row['conta_repasse']}</td>
                            <td>{$row['cpf_conta_repasse']}</td>
                            <td>{$row['porcentagem_valor_repasse']}%</td>
                            <td>{$row['data_cadastro']}</td>
                            <td>
                                 <a href='index.php?p=excluir_repasses&id={$row['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Tem certeza que deseja excluir este repasse?\");'>Excluir</a>
                            </td>
                        </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='9'>Nenhum repasse cadastrado.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>