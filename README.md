# Documentacao avaliacao


Para subir a aplicação PHP usando o Nginx como servidor web, siga este passo a passo. Assumiremos que você já tem o Nginx e o PHP instalados no seu servidor. Se não, instale-os primeiro.

### Passo 1: Preparar o Ambiente

1. **Instalar Nginx** (se ainda não estiver instalado):
    
    ```
    sudo apt update
    sudo apt install nginx
    ```
2. **Instalar PHP e extensões necessárias**:
    
    ```
    sudo apt install php7.4-fpm php7.4-mysql
    ```

3. **Configure o DB em src/Database.php**:
    
    ```
    sudo apt install php7.4-fpm php7.4-mysql
    ```

### Passo 2: Configurar o Nginx
arquivo nginx.conf

    ```
    user www-data;
    worker_processes auto;
    worker_cpu_affinity auto;
    pid /run/nginx.pid;
    error_log /var/log/nginx/error.log;
    include /etc/nginx/modules-enabled/*.conf;

    events {
        worker_connections 768;
    }

    http {
        sendfile on;
        tcp_nopush on;
        types_hash_max_size 2048;
        server_tokens off;
    
        include /etc/nginx/mime.types;
        default_type application/octet-stream;
    
        error_log /var/log/nginx/error.log;
        access_log /var/log/nginx/access.log;
    
        gzip on;

        server {
            listen 80;
            server_name localhost;
    
            root /home/kali/Documents/HelloWorld/inovafood-service/public;  # Verifique se este caminho está correto
            index index.php index.html;
    
            location / {
                try_files $uri $uri/ /index.php?$query_string;
            }
    
            location ~ \.php$ {
                include snippets/fastcgi-php.conf;
                fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;  # Verifique se este caminho está correto
                fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
                include fastcgi_params;
            }
        }
    }
    ```


### Passo 4: Colocar sua Aplicação no Diretório

1. **Certifique-se de que as permissões estão corretas**:
    
    ```
    sudo chown -R www-data:www-data /caminho/para/sua_aplicacao
    sudo chmod -R 755 /caminho/para/sua_aplicacao
    ```
### Passo 5: Testar a Conversão Usando o Postman

1. **Abra o Postman** e configure uma nova requisição:
    - **Método**: POST
    - **URL**: `(http://localhost/index.php)` (substitua pelo URL do seu servidor)
    - Na aba **Body**, selecione **form-data** e adicione um novo campo:
        - **Key**: docxFile
        - **Type**: File
        - **Value**: Selecione o arquivo `.docx` que deseja converter.

