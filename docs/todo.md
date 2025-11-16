### Containers

##### Public

Containers facing the public internet:

- [x] ragu-webserver
    - nginx
- [ ] ragu-frontend
    - on prod not a container, just static files served by nginx
- [ ] ragu-php-fpm-external
    - auth + light backend communicating with internal API server
- [ ] ragu-pgbouncer
- [x] ragu-monitoring
    - custom monitoring solution with auth etc.
    - using external Beszel and Uptime Kuma

##### Internal

Containers only available to internal network:

- [ ] ragu-php-fpm-internal
    - communicates with language processing containers through queue-messenger
- [ ] ragu-postgres
    - pgvector extension
    - volume: ragu-pgdata
- [ ] ragu-queue-messenger
    - redis, pub-sub, queue
    - frontend for communication with llm containers
- [ ] language processing
    - [ ] ragu-embed
    - [ ] ragu-rerank
    - [ ] ragu-llm
- [ ] ragu-file-parser
    - extracts text from pdf, doc(x), image description(?), 
    - can be php-fpm?