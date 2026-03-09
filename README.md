# Onfly Challenge - API de Gerenciamento de pedidos de viagem

## Overview

API desenvolvida com **Laravel** para gerenciamento de pedidos.

O projeto foi estruturado com foco em simplicidade e separação de responsabilidades:

* **Controllers** lidam apenas com entrada HTTP.
* **Services / Strategies** encapsulam regras de negócio.
* **Events + Listeners** desacoplam ações secundárias (notificações).
* **Notifications** são usadas para comunicação com usuários (Por email e database).
* **Testes automatizados** garantem o funcionamento das regras principais.

### Decisões técnicas

* Uso de **Events e Listeners** para evitar lógica acoplada em controllers.
* Tratamento centralizado de erros usando **Strategy Pattern**.
* Testes utilizando **RefreshDatabase** para garantir isolamento.
* Ambiente de desenvolvimento padronizado com **Laravel Sail**.
* Notificações configuradas para enviar por email e armazenar no banco de dados.
* Configuração de timezone para 'America/Sao_Paulo' para garantir consistência em datas e horários.
* Criação de handler de exceção personalizada para lidar com erros, desde desconhecidos até erros ja tratados, garantindo respostas consistentes e informativas para o cliente, prevenindo vazamento de informações sensíveis.
* Verificação do tipo de usuário (admin ou cliente) no 'Request' para garantir que apenas usuários autorizados possam fazer `Update` de status em pedidos. Garantindo que o `Controller` permaneça limpo    
* Autenticação utilizando **Laravel Sanctum** para proteger as rotas da API, permitindo que apenas usuários autenticados possam acessar os endpoints relacionados a pedidos e notificações. 
---

# Tecnologias utilizadas

* Docker (Laravel Sail)
* Docker Compose (Laravel Sail)
* Git (GitHub)
* Laravel
* MySQL (Banco de dados/Sail)
* Sqlite (Testes)
* PHPUnit (Testes automatizados)
* PHP 8.5 (Sail)
* Sanctum (Autenticação)
---

# Instalação

Instale as dependências do PHP:

```bash 
composer install
```

---

# Configuração do ambiente

Copie o arquivo de ambiente:

```bash 
cp .env.example .env
```

Gere a chave da aplicação:

```bash 
./vendor/bin/sail artisan key:generate
```

Configure as variáveis principais no `.env`:

```env 
APP_NAME=Laravel
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
```

---

# Executar o projeto localmente (Laravel Sail)

O projeto utiliza **Laravel Sail**, que é o ambiente Docker oficial do Laravel.

Suba os containers:

```bash 
./vendor/bin/sail up -d
```

Execute as migrations:

```bash
./vendor/bin/sail artisan migrate
```
E os seeders:

```bash
./vendor/bin/sail artisan db:seed
```

A aplicação ficará disponível em:

```
http://localhost
```

---

# Executar os testes
Rodar as migrations para o ambiente de teste:

```bash
./vendor/bin/sail artisan migrate --env=testing
```

Rodar todos os testes:

```bash
./vendor/bin/sail artisan test
```

Ou usando PHPUnit diretamente:

```bash
./vendor/bin/sail test
```

---
