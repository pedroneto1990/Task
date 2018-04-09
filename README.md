# Task Manager
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
Utilizei o dns 127.0.9.9 com a porta 80 para acesso e 3306 para mysql, caso precise mudar, altere o arquivo `docker-compose.yaml` nos serviços `webserver`, `php-fpm` e `mysql`.

Para subir o projeto localmente via docker-compose, utilize
```
$ docker-compose up -d
```

Agora precisamos criar a estrutura inicial do banco de dados. Para isso execute:
```
$ docker exec task-mysql /www/env/mysql/migrations.sh
```

Agora vamos configurar o hosts para acessar o projeto, execute a seguinte linha de comando (como root):

```
# echo "127.0.9.9 api.task.local database.task.local" >> /etc/hosts
```

Por último, vamos baixar as dependências do projeto:
```
$ docker exec task-php-fpm composer install
```

Pronto! Agora é só acessar http://api.task.local/task

## Teste unitário

Para rodar os testes unitários execute o seguinte comando:
```
$ docker exec task-php-fpm /www/vendor/bin/phpunit /www/tests
```

## Endpoints

| Descrição                                    | Verbo  | Endpoint   | Status Code        |
| ---------------------------------------------|--------|------------|--------------------|
| Lista tarefas                                | GET    | /task      | 200, 500           |
| Cria tarefa                                  | POST   | /task      | 201, 400, 500      |
| Detalhes da tarefa                           | GET    | /task/{id} | 200, 404, 500      |
| Remove tarefa                                | DELETE | /task/{id} | 204, 404, 500      |
| Edita toda a tarefa                          | PUT    | /task/{id} | 200, 422, 404, 500 |
| Edita parte da tarefa (ex: reordenar tarefa) | PATCH  | /task/{id} | 204, 404, 500      |

## Entidade

```json
{
    "id_task": 9,
    "uuid": "414807bb-aa1b-528d-9e7a-df1f7fceb48b",
    "type": "work",
    "content": "Task updated",
    "sort_order": 2,
    "done": false,
    "date_created": "2018-04-09 06:41:48"
}
```

## Resposta de erro

```json
{
    "code": 422,
    "message": "The task type you provided is not supported. You can only use shopping or work."
}
```