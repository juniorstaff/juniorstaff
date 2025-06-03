# Sistema de Serviços - PHP

Este repositório contém um sistema desenvolvido em PHP para gerenciamento de serviços, equipes, contratos, materiais e usuários, com operações integradas ao banco de dados.

## Estrutura do Projeto

O projeto está organizado em módulos, com arquivos principais para cada funcionalidade do sistema:

- **Autenticação:** login, logout, autenticar
- **Cadastros:** cadastro de ONUs, equipes, usuários, materiais, entre outros
- **Consultas e Relatórios:** consultas SQL, exportação para Excel, relatórios financeiros e estatísticas
- **Uploads:** uploads de arquivos e PDFs
- **Gerenciamento de contratos, materiais e equipes**
- **Logs de acesso e histórico de movimentações**

Pastas principais:
- `composer/` – Dependências do Composer
- `vendor/` – Bibliotecas de terceiros (não versionar)
- `uploads/` e `uploads_pdfs/` – Armazenamento de arquivos enviados
- `app/`, `db/`, `config/` – Lógica da aplicação, banco de dados e configurações

## Banco de Dados

O sistema utiliza um banco de dados relacional com tabelas como:

- `cadastro_onu`
- `contratos`
- `entregas_materiais`
- `equipamentos`
- `equipe`
- `historico_movimentacoes`
- `logs_acesso`
- `miscelaneas`
- `neighborhoods`
- `services`
- `technicians`
- `usuarios`

> Recomenda-se adicionar um arquivo `db/estrutura.sql` com a estrutura das tabelas.

## Como Subir para o GitHub

1. **Remova dados sensíveis** como senhas e configurações pessoais.
2. **Adicione um `.gitignore`** para não versionar as pastas `vendor/`, `uploads/`, `uploads_pdfs/`, arquivos `.lock`, temporários, etc.
3. **Documente as dependências** e instruções de instalação no README.
4. **Suba a estrutura do banco** (opcional, mas recomendado).

Exemplo de `.gitignore`:
```gitignore
/vendor/
/uploads/
/uploads_pdfs/
*.log
*.tmp
composer.lock
```

## Instalação Rápida

1. Clone o repositório:
    ```sh
    git clone https://github.com/seuusuario/seurepo.git
    ```
2. Instale as dependências:
    ```sh
    composer install
    ```
3. Importe a estrutura do banco de dados.
4. Configure o acesso ao banco em `config/` conforme necessário.
5. Ajuste permissões das pastas de upload:
    ```sh
    chmod -R 775 uploads uploads_pdfs
    ```
6. Acesse pelo navegador e faça o login.

## Observações

- O diretório `uploads/` e seus subdiretórios não devem ser versionados.
- Sempre mantenha o backup do banco de dados.
- Contribuições são bem-vindas!
