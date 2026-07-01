## Instalação de gerenciamento de dependências (Obrigatório para rodar)

1. Instale PHP

2. Instale Composer

3. Instale Node.js

4. git clone https://github.com/rubim666/helpdeskSystemSenac.git

5. Dentro do repositório: dê npm install e composer install

6. npm run dev:all

## Configurando o PHP para rodar com PostgreSQL no XAMPP

1. Localize o arquivo `php.ini`  
   Geralmente está no diretório:

2. Abra o arquivo em um editor de texto (como Notepad++ ou VS Code).

3. Procure pelas linhas:
```ini
;extension=pdo_pgsql
;extension=pgsql

extension=pdo_pgsql
extension=pgsql
