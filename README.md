# Sistema de Protocolo

Este é um sistema de gestão de protocolos e documentos desenvolvido com o Adianti Framework, configurado para rodar em ambiente Docker.

## 🚀 Como Rodar com Docker

Para iniciar o projeto rapidamente, siga os passos abaixo:

1. **Certifique-se de ter o Docker e Docker Compose instalados.**
2. **Clone o repositório e navegue até a pasta raiz.**
3. **Inicie os containers:**
   ```bash
   docker-compose up -d --build
   ```
4. **Acesse o sistema:**
   [http://localhost](http://localhost)

### Preparando o Ambiente IA (Ollama)
Após subir os containers, baixe os modelos necessários para as funcionalidades de IA:
```bash
docker exec -it sistema-protocolo-ollama ollama run gemma2:2b
docker exec -it sistema-protocolo-ollama ollama run moondream
```

---

## 🔑 Credenciais de Root

O sistema possui um usuário **Root** administrativo que não depende da base de dados e tem acesso total à governança do sistema (Observabilidade e Estatísticas).

### Como alterar as credenciais
As credenciais são gerenciadas através de variáveis de ambiente no arquivo `docker-compose.yml`.

1. Abra o arquivo `docker-compose.yml`.
2. Localize a seção `services` -> `app` -> `environment`.
3. Altere os valores de:
   * `ROOT_USER`: Nome de usuário para o login root.
   * `ROOT_PASS`: Senha para o login root.

**Exemplo:**
```yaml
environment:
  ROOT_USER: seu_novo_usuario
  ROOT_PASS: sua_nova_senha_segura
```

Após alterar, reinicie os containers para aplicar as mudanças:
```bash
docker-compose up -d
```

---

## 📧 Configuração de E-mail (SMTP)

O sistema utiliza SMTP para envio de credenciais e recuperação de senha. As configurações estão localizadas em:
`app/config/mail.ini`

Basta editar este arquivo com os dados do seu servidor SMTP (Gmail, Outlook, SendGrid, etc).

---

## 🛠️ Comandos Úteis

* **Ver logs:** `docker-compose logs -f app`
* **Reiniciar sistema:** `docker-compose restart app`
* **Parar tudo:** `docker-compose down`
* **Acessar o terminal do PHP:** `docker exec -it sistema-protocolo-app bash`
