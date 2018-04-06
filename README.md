# task
Projeto para administração de tarefas

## Requerimentos

### Aplicação
* PHP 7.2
* MySQL 5.7

### Ambiente Local - Docker
* Docker version 17.03.2-ce
* docker-compose version 1.17.0
* docker-py version: 2.5.1
* CPython version: 2.7.13

## Instruções
Utilizei o dns 127.0.9.9 com a porta 80 para acesso e 3306 para mysql, caso precise mudar, altere o arquivo `docker-compose.yaml` nos serviços `webserver` e `mysql`.

Para subir o projeto localmente via docker-compose, utilize
```
$ docker-compose up -d
```

Agora precisamos criar a estrutura inicial do banco de dados. Para isso execute:
```
$ docker exec task-mysql /www/env/mysql/migrations.sh
```

Agora precisamos configurar o hosts para acessar o projeto, execute a seguinte linha de comando (como root):

```
# echo "127.0.9.9 api.task.local database.task.local" >> /etc/hosts
```

Pronto! Agora é só acessar http://api.task.local/task