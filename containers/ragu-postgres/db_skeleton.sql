CREATE TABLE users
(
    id serial NOT NULL,
    username text NOT NULL,
    password text NOT NULL,
    is_active boolean NOT NULL DEFAULT TRUE,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE (username)
);

CREATE TABLE refresh_tokens
(
    id           SERIAL PRIMARY KEY,
    user_id      INTEGER NOT NULL,
    refresh_token VARCHAR(128) NOT NULL UNIQUE,
    username     VARCHAR(255) NOT NULL,
    valid        TIMESTAMP NOT NULL,
    CONSTRAINT fk_refresh_tokens_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);
CREATE INDEX idx_refresh_tokens_user_id ON refresh_tokens (user_id);
