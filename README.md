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
    sudo apt install php-fpm php-mysql
    ```
    

### Passo 2: Configurar o Nginx

1. **Criar um novo arquivo de configuração para sua aplicação**:
Navegue até o diretório de configuração do Nginx.
    
    ```
    cd /etc/nginx/sites-available/
    ```
    
2. **Criar um novo arquivo de configuração**:
Substitua `sua_aplicacao` pelo nome da sua aplicação.
    
    ```
    sudo nano sua_aplicacao
    ```
    
3. **Adicionar a configuração**:
Aqui está um exemplo básico de configuração do Nginx para uma aplicação PHP:
    
    ```
    server {
        listen 80;
        server_name seu_dominio.com; # Substitua pelo seu domínio ou IP
    
        root /caminho/para/sua_aplicacao; # Substitua pelo caminho real
        index index.php index.html index.htm;
    
        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }
    
        location ~ \.php$ {
            include snippets/fastcgi-php.conf;
            fastcgi_pass unix:/var/run/php/php7.4-fpm.sock; # Verifique a versão do PHP
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            include fastcgi_params;
        }
    
        location ~ /\.ht {
            deny all;
        }
    }
    ```
    
4. **Salvar e sair do editor**: No nano, você pode fazer isso pressionando `CTRL + X`, depois `Y` e `Enter`.

### Passo 3: Ativar a Configuração

1. **Criar um link simbólico para o diretório `sites-enabled`**:
    
    ```
    sudo ln -s /etc/nginx/sites-available/sua_aplicacao /etc/nginx/sites-enabled/
    ```
    
2. **Testar a configuração do Nginx**:
    
    ```
    sudo nginx -t
    ```
    
3. **Recarregar o Nginx** para aplicar as mudanças:
    
    ```
    sudo systemctl reload nginx
    ```
    

### Passo 4: Colocar sua Aplicação no Diretório

1. **Transferir os arquivos da sua aplicação para o servidor**:
Você pode usar SCP, FTP ou qualquer método de transferência que preferir. Coloque os arquivos no diretório especificado em `root` na configuração do Nginx.
2. **Certifique-se de que as permissões estão corretas**:
    
    ```
    sudo chown -R www-data:www-data /caminho/para/sua_aplicacao
    sudo chmod -R 755 /caminho/para/sua_aplicacao
    ```
    

### Passo 5: Acessar sua Aplicação

1. Abra um navegador e digite o domínio ou o endereço IP do seu servidor. Você deve ver sua aplicação PHP rodando.

### Considerações Finais

- **Logs**: Verifique os logs de erro do Nginx em `/var/log/nginx/error.log` se algo não estiver funcionando corretamente.
- **Configurar**: Altere o arquivo src/Database.php tambem a linha 123 do arquivo public/index.php
- **Firewall**: Certifique-se de que a porta 80 (e 443, se usar HTTPS) esteja aberta no seu firewall.

Seguindo esses passos, você deverá conseguir subir sua aplicação PHP com Nginx sem problemas! Se precisar de mais ajuda em algum dos passos, é só avisar!

# Usar o conversor .doc

### Passo 1: Preparar o Ambiente

1. **Criar o diretório de cache do Dconf**:
    
    ```
    sudo mkdir -p /var/www/.cache/dconf
    sudo chown -R www-data:www-data /var/www/.cache
    ```
    
2. **Instalar o LibreOffice** (caso não esteja instalado):
    
    ```
    sudo apt update
    sudo apt install libreoffice
    ```
    

### Passo 2: Converter Arquivos com LibreOffice

1. **Usar o LibreOffice em modo headless para converter arquivos**:
Para converter um arquivo `.doc` para `.docx`, você pode usar o seguinte comando:
    
    ```
    libreoffice --headless --convert-to docx --outdir /caminho/para/output /caminho/para/input.doc
    ```
    
    - Substitua `/caminho/para/output` pelo diretório onde você deseja salvar o arquivo convertido.
    - Substitua `/caminho/para/input.doc` pelo caminho do arquivo `.doc` que você deseja converter.

### Passo 3: (Se não funcionar) Ajustes de Permissão e Configurações

Se a conversão não funcionar, siga os passos abaixo:

1. **Criar o diretório de cache**:
    
    ```
    sudo mkdir -p /var/www/.cache
    sudo chown -R www-data:www-data /var/www/.cache
    ```
    
2. **Instalar Java Runtime Environment (JRE)**:
    
    ```
    sudo apt update
    sudo apt install default-jre
    ```
    
3. **Verificar o JAVA_HOME**:
    
    ```
    echo $JAVA_HOME
    ```
    
4. **Configurar o JAVA_HOME**:
Se o `JAVA_HOME` não estiver configurado, faça o seguinte:
    
    ```
    export JAVA_HOME=/usr/lib/jvm/java-<sua-versao>
    export PATH=$JAVA_HOME/bin:$PATH
    ```
    
    - **Substitua `<sua-versao>` pela versão do Java instalada**. Para encontrar a versão correta, use:
    
    ```
    ls /usr/lib/jvm/
    ```
    

### Passo 4: Verificar Permissões dos Diretórios

1. **Verifique as permissões do diretório de uploads**:
    
    ```
    ls -ld /caminho/seu/uploads/
    ```
    
    O resultado deve ser algo como:
    
    ```
    drwxr-xr-x 2 www-data www-data 4096 Oct 24 10:00 uploads
    ```
    
2. **Se o usuário não tiver permissões de gravação**, conceda as permissões adequadas:
    
    ```
    sudo chown -R www-data:www-data /caminho/seu/uploads/
    sudo chmod -R 755 /caminho/seu/uploads/
    ```
    

### Passo 5: Testar a Conversão

1. **Crie um script PHP para chamar o comando de conversão**:
Aqui está um exemplo básico de como você pode chamar o comando do LibreOffice em um script PHP:
    
    ```
    <?php
    $inputFile = '/caminho/para/input.doc';
    $outputDir = '/caminho/para/output';
    
    $command = "libreoffice --headless --convert-to docx --outdir $outputDir $inputFile";
    
    exec($command, $output, $return_var);
    
    if ($return_var === 0) {
        echo "Arquivo convertido com sucesso!";
    } else {
        echo "Erro na conversão.";
    }
    ?>
    ```
    
2. **Teste o script** para verificar se a conversão funciona corretamente.

### Considerações Finais

- **Dependências**: Certifique-se de que todas as dependências necessárias (LibreOffice e JRE) estejam instaladas e configuradas corretamente.
- **Logs de erro**: Se algo não estiver funcionando como esperado, verifique os logs de erro do Nginx e do PHP para obter mais informações.
- **Segurança**: Ao permitir a execução de comandos via PHP, tenha cuidado com a segurança e valide todos os arquivos de entrada para evitar possíveis vulnerabilidades.

Com essas instruções, você deve conseguir integrar a funcionalidade de conversão de arquivos `.doc` em sua aplicação PHP! Se precisar de mais assistência, estou à disposição!

### Passo 6: Testar a Conversão Usando o Postman

1. **Abra o Postman** e configure uma nova requisição:
    - **Método**: POST
    - **URL**: `http://seu_dominio/index.php` (substitua pelo URL do seu servidor)
    - Na aba **Body**, selecione **form-data** e adicione um novo campo:
        - **Key**: docx
        - **Type**: File
        - **Value**: Selecione o arquivo `.doc` ou `.docx` que deseja converter.
2. **Envie a requisição** e verifique se a conversão foi bem-sucedida. Você deve receber uma resposta informando que o arquivo foi convertido com sucesso.

![image](https://github.com/user-attachments/assets/5070bca2-2237-43e4-b0f0-166a0eafca9b)

![image 1](https://github.com/user-attachments/assets/208f61de-b244-47d0-b0f0-eb0dfcf08453)

