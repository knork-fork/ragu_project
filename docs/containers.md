### prema van

- frontend container s authom sa laganim backendom koji komunicira s internim API serverom
- nginx webserver

### interni, infrastructure

- api server (php-fpm)
    - šalje dokumente u parser container (OCR ako je pdf)
    - chunka parsane dokumente na 500-1000 riječi
    - šalje chunkove u embed container
    - sprema embedane chunkove u bazu
    - šalje user query u embed container
    - dohvati top 20 chunkova iz baze po embedingu user querya
    - pošalje chunkove u rerank container i dohvati top 5
    - pošalje top 5 chunkova + user query u llm container i dohvati response
- postgres baza + pgbouncer
    - pgvector ekstenzija za postgres
- queue-messenger container (redis, pub-sub, queue)
    - front za komunikaciju s llm containerima
- log collector
- monitoring

### interni, language processing

- embed container
    - npr. nomic-embed-text
    - cpu
- rerank container
    - npr. bge-reranker-base
        - note: bge-reranker-base ima 512 token limit, pa chunkovi moraju biti do 400 riječi
    - cpu
- llm container
    - npr. qwen3:4b-instruct-2507-q4_K_M
    - gpu
- parser container
    - extracts text from PDFs/Docs/HTML (e.g. unstructured, Apache Tika, ocrmypdf+Tesseract)
    - savea plain text input u text fileove
    - response je filename plain text filea

----

### feature wishlist

- user-defined tagovi ili topici koji se mogu palit i gasit u UI-u tako da search skipa određene dokumente/chunkove
- api server endpoints:
    - /ask
        - plain text msg
        - searcha chunkove i daje llm response
    - /save
        - plain text msg, upload file
        - spremi info, generic api response bez llm-a
- storage layout:
    - /data/files/             ← raw uploads
    - /data/text/              ← extracted plain text
    - /data/chunks/            ← JSONL or DB
    - /data/embeddings/        ← vector store (pgvector)
    - /data/cache/             ← kv, reranker cache, etc.
- MMR (Maximal Marginal Relevance) workflow:
    - ANN (pgvector) - top 50 (return: chunk_id, text, embedding, meta)
    - Rerank - od 50 ostavi 20 po top score
    - MMR in API over those 20 using cosine(query_embed, chunk_embed) and cosine(chunk_i, chunk_j) → pick final 5–8
    - Build prompt → LLM