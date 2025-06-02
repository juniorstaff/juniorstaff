const express = require('express');
const mysql = require('mysql2');
const cors = require('cors');

const app = express();
app.use(express.json());
app.use(cors()); // Permite requisições de diferentes origens (CORS)

// Conexão com MySQL
const db = mysql.createConnection({
    host: 'localhost',
    user: 'root', // Substitua pelo seu usuário do MySQL
    password: '', // Substitua pela sua senha do MySQL
    database: 'sistema_servicos'
});

// Verificar conexão com o banco de dados
db.connect(err => {
    if (err) {
        console.error('Erro ao conectar ao MySQL:', err);
        return;
    }
    console.log('Conectado ao MySQL!');
});

// Rota para inserir dados no banco de dados
app.post('/api/cadastro_onu', (req, res) => {
    const { mac, hfc, descricao, quantidade, tecnOrigem, dataRecebimento, notaFiscal, rma } = req.body;

    const sql = "INSERT INTO equipamentos (mac, hfc, descricao, quantidade, tecn_origem, data_recebimento, nota_fiscal, rma) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    db.query(sql, [mac, hfc, descricao, quantidade, tecnOrigem, dataRecebimento, notaFiscal, rma], (err, result) => {
        if (err) {
            console.error('Erro ao inserir no banco de dados:', err);
            res.status(500).send('Erro ao salvar os dados');
        } else {
            res.status(200).send('Dados inseridos com sucesso!');
        }
    });
});

// Rota para obter os dados do banco
app.get('/api/equipamentos', (req, res) => {
    const sql = "SELECT * FROM equipamentos";

    db.query(sql, (err, result) => {
        if (err) {
            console.error('Erro ao buscar os dados:', err);
            res.status(500).send('Erro ao buscar dados');
        } else {
            res.json(result);
        }
    });
});

// Rota para excluir um item
app.delete('/api/equipamentos/:id', (req, res) => {
    const { id } = req.params;
    const sql = "DELETE FROM equipamentos WHERE id = ?";

    db.query(sql, [id], (err, result) => {
        if (err) {
            console.error('Erro ao excluir:', err);
            res.status(500).send('Erro ao excluir o item');
        } else {
            res.status(200).send('Item excluído com sucesso!');
        }
    });
});

// Servidor rodando na porta 3000
app.listen(3000, () => {
    console.log('Servidor rodando na porta 3000...');
});
