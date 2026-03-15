CREATE DATABASE IF NOT EXISTS shopora_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE shopora_db;


CREATE TABLE IF NOT EXISTS customers (
    user_id     INT           AUTO_INCREMENT PRIMARY KEY,
    first_name  VARCHAR(60)   NOT NULL,
    last_name   VARCHAR(60)   NOT NULL,
    email       VARCHAR(120)  NOT NULL UNIQUE,
    password    VARCHAR(255)  NOT NULL,
    phone       VARCHAR(20)   DEFAULT NULL,
    avatar      VARCHAR(255)  DEFAULT NULL,
    created_at  DATETIME      DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS products (
    product_id  INT             AUTO_INCREMENT PRIMARY KEY,
    owner_id    INT             DEFAULT NULL,
    name        VARCHAR(150)    NOT NULL,
    description TEXT            DEFAULT NULL,
    price       DECIMAL(10,2)   NOT NULL,
    stock       INT             DEFAULT 100,
    category    VARCHAR(80)     DEFAULT NULL,
    image_path  VARCHAR(255)    DEFAULT NULL,
    created_at  DATETIME        DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (owner_id) REFERENCES customers(user_id) ON DELETE CASCADE
);


CREATE TABLE IF NOT EXISTS cart (
    cart_id     INT       AUTO_INCREMENT PRIMARY KEY,
    user_id     INT       NOT NULL,
    product_id  INT       NOT NULL,
    quantity    INT       NOT NULL DEFAULT 1,
    added_at    DATETIME  DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY one_item_per_user (user_id, product_id),
    FOREIGN KEY (user_id)    REFERENCES customers(user_id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);


CREATE TABLE IF NOT EXISTS reviews (
    id           INT       AUTO_INCREMENT PRIMARY KEY,
    product_id   INT       NOT NULL,
    customer_id  INT       NOT NULL,
    vathmologia  TINYINT   NOT NULL CHECK (vathmologia BETWEEN 1 AND 5),
    sxolio       TEXT      DEFAULT NULL,
    created_at   DATETIME  DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY one_review_per_customer (product_id, customer_id),
    FOREIGN KEY (product_id)  REFERENCES products(product_id)  ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(user_id)    ON DELETE CASCADE
);


CREATE TABLE IF NOT EXISTS comments (
    id           INT       AUTO_INCREMENT PRIMARY KEY,
    product_id   INT       NOT NULL,
    customer_id  INT       NOT NULL,
    body         TEXT      NOT NULL,
    created_at   DATETIME  DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id)  REFERENCES products(product_id)  ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(user_id)    ON DELETE CASCADE
);
