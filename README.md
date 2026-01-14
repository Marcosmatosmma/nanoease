# Larasonic 🚀

![Larasonic](public/images/og.webp)

Larasonic is a modern, open-source SaaS starter kit with Laravel, Vue.js, TailwindCSS, and Inertia.

![GitHub Repo stars](https://img.shields.io/github/stars/pushpak1300/Larasonic?style=for-the-badge) [![Licence](https://img.shields.io/github/license/Ileriayo/markdown-badges?style=for-the-badge)](./LICENSE.md) [![Github-sponsors](https://img.shields.io/badge/sponsor-30363D?style=for-the-badge&logo=GitHub-Sponsors&logoColor=#EA4AAA)](https://github.com/sponsors/pushpak1300)

## ✨ Features

- ⚡ 10x Dev Experience
- 🐳 Production Docker Ready
- 🔑 Advanced Authentication
- 💳 Payment Ready
- 🌐 API Ready
- 🎨 Customizable UI
- 🧠 AI Integration Ready
- 📊 FilamentPHP Admin
- ✨ Evolving Features

## Quick Start

```bash
laravel new larasonic --using=shipfastlabs/larasonic-vue
```

For detailed installation instructions and documentation, visit [docs.larasonic.com](https://docs.larasonic.com).

## 📧 Automações de E-mail

Este projeto inclui um sistema avançado de automações de e-mail com IA que detecta e processa e-mails automaticamente em tempo real.

### 🚀 Modo de Operação

O sistema oferece **2 modos** de execução:

#### 🔥 **Modo Watch (Recomendado para Produção)**
Observa continuamente novos e-mails em tempo real:
```bash
php artisan automations:watch --interval=30
```
- ✅ **Tempo real**: Processa e-mails assim que chegam
- ✅ **Fica rodando continuamente** até você parar (Ctrl+C)
- ✅ **Intervalo configurável**: `--interval=30` (segundos entre verificações)
- ✅ **Ideal para produção**: Menor latência entre chegada e processamento

#### ⏰ **Modo Scheduler (Alternativa)**
Executa periodicamente via cron (a cada 5 minutos):
```bash
php artisan automations:run-email --minutes-ago=5
```
- ✅ **Automático**: Configurado no Laravel Scheduler
- ✅ **Menos recursos**: Não fica rodando continuamente
- ✅ **Ideal para**: Servidores compartilhados ou baixo volume

---

### 📋 Comandos Disponíveis

#### 1. **Observador em Tempo Real** ⭐ RECOMENDADO
```bash
php artisan automations:watch --interval=30
```
- Fica **rodando continuamente** verificando novos e-mails
- Processa **imediatamente** quando detecta match
- Intervalo padrão: **30 segundos** (ajustável)
- Mostra **log em tempo real** no terminal
- Pressione **Ctrl+C** para parar
- **Uso:** Produção (maior responsividade)

**Exemplo de saída:**
```
👀 Observando e-mails a cada 30 segundos...
🛑 Pressione Ctrl+C para parar

🔄 Verificação #1 - 21:10:30
   💤 Nenhum e-mail novo

🔄 Verificação #2 - 21:11:00
   ✅ NOVO: A NF-e da compra #2000011041539887...
      Automação: Nota fiscal
      🚀 Ação executada com sucesso!
   📊 Processados: 1 | Executados: 1
```

#### 2. **Teste de Automação (Debug)**
```bash
php artisan automations:test {automation_id} --limit=20
```
- Testa **uma automação específica** 
- Busca os **20 e-mails mais recentes** (sem filtro de data)
- Mostra **decisão detalhada da IA**
- **NÃO executa ações** (apenas simula)
- **NÃO salva no banco**
- **Uso:** Debug e desenvolvimento

**Exemplo:**
```bash
php artisan automations:test 5 --limit=50
```

#### 3. **Execução Manual (Simulação)**
```bash
php artisan automations:run-email --minutes-ago=60 --dry-run
```
- Busca e-mails dos **últimos X minutos**
- **`--dry-run`** = simula sem executar ações
- **Salva** log no banco
- **Uso:** Teste antes de produção

#### 4. **Execução Real (Pontual)**
```bash
php artisan automations:run-email --minutes-ago=5
```
- Busca e-mails dos **últimos 5 minutos**
- **EXECUTA AÇÕES DE VERDADE**
- Evita processar duplicados
- **Uso:** Execução manual ou scheduler

---

### 🔧 Configuração para Produção

#### **Opção 1: Modo Watch (Recomendado)** ⭐

Rode o observador em background usando **Supervisor** (recomendado):

**1. Instalar Supervisor:**
```bash
# Ubuntu/Debian
sudo apt-get install supervisor

# CentOS/RHEL
sudo yum install supervisor
```

**2. Criar arquivo de configuração:**
```bash
sudo nano /etc/supervisor/conf.d/larasonic-automations.conf
```

**3. Adicionar configuração:**
```ini
[program:larasonic-automations]
process_name=%(program_name)s
command=php /caminho/completo/do/projeto/artisan automations:watch --interval=30
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/caminho/completo/do/projeto/storage/logs/automations.log
stopwaitsecs=3600
```

**4. Ativar e iniciar:**
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start larasonic-automations
```

**5. Verificar status:**
```bash
sudo supervisorctl status larasonic-automations
```

**Comandos úteis do Supervisor:**
```bash
# Ver status
sudo supervisorctl status

# Parar
sudo supervisorctl stop larasonic-automations

# Reiniciar
sudo supervisorctl restart larasonic-automations

# Ver logs
tail -f /caminho/do/projeto/storage/logs/automations.log
```

---

#### **Opção 2: Modo Scheduler (Alternativa)**

Use o Laravel Scheduler com cron:

**1. Adicionar ao crontab:**
```bash
crontab -e
```

**2. Adicionar linha:**
```bash
* * * * * cd /caminho/do/projeto && php artisan schedule:run >> /dev/null 2>&1
```

O scheduler executa `automations:run-email` automaticamente a cada 5 minutos (configurado em `routes/console.php`).

---

#### **Opção 3: Background Manual (Desenvolvimento)**

Para testes rápidos em desenvolvimento:

```bash
# Rodar em background
nohup php artisan automations:watch --interval=30 > storage/logs/automations.log 2>&1 &

# Ver processo rodando
ps aux | grep "automations:watch"

# Matar processo
pkill -f "automations:watch"

# Ver logs em tempo real
tail -f storage/logs/automations.log
```

---

### 📊 Comparação de Modos

| Modo | Latência | Recursos | Facilidade | Recomendado |
|------|----------|----------|------------|-------------|
| **Watch + Supervisor** | ~30s | Médio | ⭐⭐⭐ | ✅ Produção |
| **Scheduler (Cron)** | ~5min | Baixo | ⭐⭐⭐⭐⭐ | ✅ Baixo volume |
| **Background Manual** | ~30s | Médio | ⭐⭐ | ⚠️ Só dev |

---

### 🔍 Monitoramento

**Ver últimas execuções:**
```bash
php artisan tinker
>>> DB::table('automation_executions')->latest()->limit(10)->get();
```

**Ver estatísticas:**
```bash
php artisan tinker
>>> DB::table('automation_executions')
       ->selectRaw('status, count(*) as total')
       ->groupBy('status')
       ->get();
```

**Ver logs em tempo real (Supervisor):**
```bash
tail -f storage/logs/automations.log
```

---

### ⚡ Performance

- **Intervalo recomendado:** 30 segundos (balanço entre latência e recursos)
- **Intervalo mínimo:** 10 segundos (proteção contra sobrecarga)
- **Recursos:** ~10-20MB RAM por worker
- **Gmail API:** Limite de 250 quotas/segundo (mais que suficiente)

---

### 🐛 Troubleshooting

**Worker não inicia:**
```bash
# Verificar permissões
sudo chown -R www-data:www-data /caminho/do/projeto/storage

# Verificar logs
tail -f /caminho/do/projeto/storage/logs/laravel.log
```

**Supervisor não reinicia:**
```bash
sudo supervisorctl stop larasonic-automations
sudo supervisorctl start larasonic-automations
```

**Ver processos rodando:**
```bash
ps aux | grep artisan
```

## Hosting

Proudly hosted and sponsored by [Sevalla.com](https://sevalla.com/?ref=larasonic).

## Security

Report vulnerabilities to pushpak1300@gmail.com

## License

[MIT](https://opensource.org/licenses/MIT)

## Screenshots

| ![Screenshot 4](https://github.com/user-attachments/assets/d7c4eaa9-b547-4952-8ade-4b0ae62aee0e) | ![Screenshot 2](https://github.com/user-attachments/assets/b2d5a28c-9b1b-40bb-82f0-fb9fa932165c) | ![Screenshot 3](https://github.com/user-attachments/assets/d8b15834-bcc2-4028-9d73-a0bb9983c6b7) |
| :----------------------------------------------------------------------------------------------: | :----------------------------------------------------------------------------------------------: | :----------------------------------------------------------------------------------------------: |
| ![Screenshot 1](https://github.com/user-attachments/assets/21c34465-a193-4373-9862-0843f11b957c) | ![Screenshot 5](https://github.com/user-attachments/assets/fba2d341-40c3-4244-8b02-82891c42f2d5) | ![Screenshot 6](https://github.com/user-attachments/assets/37ce7a37-121d-41b1-b3e6-09714cb5c884) |
