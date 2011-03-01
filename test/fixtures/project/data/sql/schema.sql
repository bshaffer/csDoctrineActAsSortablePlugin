CREATE TABLE sortable_article (id BIGSERIAL, name VARCHAR(255), position BIGINT, PRIMARY KEY(id));
CREATE TABLE sortable_article_category (id SERIAL, name VARCHAR(100), PRIMARY KEY(id));
CREATE TABLE sortable_article_unique_by (id BIGSERIAL, name VARCHAR(255), category_id INT NOT NULL, position BIGINT, PRIMARY KEY(id));
CREATE UNIQUE INDEX sortable_article_position_sortable ON sortable_article (position);
CREATE UNIQUE INDEX sortable_article_unique_by_position_sortable ON sortable_article_unique_by (position, category_id);
ALTER TABLE sortable_article_unique_by ADD CONSTRAINT scsi FOREIGN KEY (category_id) REFERENCES sortable_article_category(id) NOT DEFERRABLE INITIALLY IMMEDIATE;
