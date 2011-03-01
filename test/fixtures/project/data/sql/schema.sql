CREATE TABLE sortable_article (id BIGSERIAL, name VARCHAR(255), position BIGINT, PRIMARY KEY(id));
CREATE TABLE sortable_article_unique_by (id BIGSERIAL, name VARCHAR(255), category VARCHAR(255), position BIGINT, PRIMARY KEY(id));
CREATE UNIQUE INDEX sortable_article_position_sortable ON sortable_article (position);
CREATE UNIQUE INDEX sortable_article_unique_by_position_sortable ON sortable_article_unique_by (position, category);
