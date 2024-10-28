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

### Passo 2: Configurar o Nginx


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

![image](https://github.com/user-attachments/assets/5070bca2-2237-43e4-b0f0-166a0eafca9b)

![image 1](https://github.com/user-attachments/assets/208f61de-b244-47d0-b0f0-eb0dfcf08453)

