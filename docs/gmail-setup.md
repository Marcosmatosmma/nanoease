# Configuração Gmail API (Google Cloud Console)

Guia rápido para habilitar Gmail API, criar credenciais OAuth e preencher o `.env` do app.

## 1) Projeto e API
- Acesse: https://console.developers.google.com/apis/dashboard
- Selecione o projeto (ou crie um novo) e confirme o `Project ID`.
- Vá em **APIs & Services > Library** e habilite **Gmail API**.

## 2) Consent Screen (OAuth)
- Vá em **APIs & Services > OAuth consent screen**.
- Tipo: **External** (se não houver organização) ou **Internal** se aplicável.
- Adicione nome do app, e-mail de suporte e um logo opcional.
- Em **Authorized domains**, adicione o domínio da sua aplicação (ex.: `example.com`).
- Salve e continue; você pode pular escopos adicionais aqui (vamos definir nas credenciais).

## 3) Credenciais OAuth (Client ID/Secret)
- Vá em **APIs & Services > Credentials > Create Credentials > OAuth client ID**.
- Tipo: **Web application**.
- Em **Authorized redirect URIs**, adicione a rota de callback do app:
  - Em produção: `https://SEU_DOMINIO/auth/callback/google`
  - Em dev (Vite/Inertia local): `http://localhost:8000/auth/callback/google` (ajuste porta se diferente)
- Após criar, anote **Client ID** e **Client Secret**.

## 4) Escopos necessários
Inclua estes escopos na integração (já aplicados no código):
- `https://www.googleapis.com/auth/gmail.readonly`
- `https://www.googleapis.com/auth/gmail.modify`
- `https://www.googleapis.com/auth/gmail.send`
- `https://www.googleapis.com/auth/userinfo.email`
- `https://www.googleapis.com/auth/userinfo.profile`

## 5) Variáveis de ambiente (.env)
```
GOOGLE_CLIENT_ID=coloque_aqui_o_client_id
GOOGLE_CLIENT_SECRET=coloque_aqui_o_client_secret
GOOGLE_REDIRECT_URI=/auth/callback/google
```
- Use a mesma URI definida nas credenciais.
- Mantenha `access_type=offline` e `prompt=consent` (já no código) para garantir refresh_token.

## 6) Teste da conexão
1. Limpe cache se necessário: `php artisan optimize:clear`.
2. Acesse o app, vá em Integrações e clique em “Conectar Gmail”.
3. Faça o consentimento Google (verá os escopos de Gmail). Aceite.
4. Verifique no banco a tabela `integrations` se `provider=gmail` está `status=connected` e `metadata` contém `token` e `refresh_token`.

## 7) Envio via Gmail API
- O app envia com o token da integração; se o `refresh_token` estiver presente, ele renova automaticamente.
- Se receber erro 403 `accessNotConfigured`, volte ao passo 1 e confirme que a Gmail API está habilitada no projeto correto.

## 8) Outras plataformas de e-mail
- Caso não haja integração Gmail ou tokens válidos, o app cai no mailer padrão (`MAIL_*` no `.env`).
- Para outros provedores via API, adicione um sender específico e registre no `EmailSenderManager`.

## 9) Segurança
- Restrinja o Client Secret; não commit no repositório.
- Use domínios autorizados coerentes com os ambientes (dev/staging/prod).
- Mantenha o projeto com faturamento habilitado se necessário (algumas APIs exigem).

## 10) Resumo mínimo
1. Habilitar Gmail API.
2. Criar OAuth Web Client com redirect URI.
3. Preencher `GOOGLE_CLIENT_ID/SECRET/REDIRECT_URI` no `.env`.
4. Reconectar Gmail no app para capturar token/refresh_token com o escopo de envio.
