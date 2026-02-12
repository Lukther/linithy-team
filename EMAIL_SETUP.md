# Configuração de e-mail (PHPMailer)

## 1) Instalar dependência
```bash
composer require phpmailer/phpmailer
```

## 2) Criar arquivo `.env`
Copie o exemplo e preencha os dados:
```bash
cp .env.example .env
```

## 3) Exemplo com os dados informados
> **Atenção:** para Gmail é recomendado usar **App Password** (senha de app), não a senha normal da conta.

```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=luktheer@gmail.com
SMTP_PASS=COLOQUE_AQUI_SUA_APP_PASSWORD
MAIL_FROM=luktheer@gmail.com
MAIL_FROM_NAME=Linithy Team
MAIL_TO=luktheer@gmail.com
```

## 4) Executar localmente
```bash
php -S 0.0.0.0:4173
```

O formulário de contato (`index.html`) envia para `send-email.php` via `fetch`.