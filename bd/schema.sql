-- Hielo Moscú · esquema MySQL 8
CREATE DATABASE IF NOT EXISTS hielo_moscu CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE hielo_moscu;

CREATE TABLE users (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(30)  NOT NULL UNIQUE,
  name          VARCHAR(60)  NOT NULL,
  role          ENUM('admin','supervisor','repartidor') NOT NULL DEFAULT 'repartidor',
  password_hash VARCHAR(255) NOT NULL,
  active        TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE routes (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(40) NOT NULL UNIQUE,
  color      CHAR(7)     NOT NULL DEFAULT '#0b5ed7',
  driver_id  BIGINT UNSIGNED NULL,
  active     TINYINT(1)  NOT NULL DEFAULT 1,
  created_at TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE customers (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(120) NOT NULL,
  contact    VARCHAR(120) NULL,
  phone      CHAR(10)     NOT NULL,
  address    VARCHAR(200) NOT NULL,
  lat        DECIMAL(9,6) NOT NULL,
  lng        DECIMAL(9,6) NOT NULL,
  notes      VARCHAR(300) NULL,
  active     TINYINT(1)   NOT NULL DEFAULT 1,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_customers_phone (phone),
  FULLTEXT KEY ft_customers (name, contact, address),
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Un cliente tiene 0 o 1 enfriador (si algún día hay varios, quitar el UNIQUE).
CREATE TABLE coolers (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id  BIGINT UNSIGNED NOT NULL UNIQUE,
  brand        VARCHAR(60) NOT NULL,
  model        VARCHAR(60) NULL,
  capacity     DECIMAL(6,1) NOT NULL,
  unit         ENUM('pies³','litros') NOT NULL DEFAULT 'pies³',
  serial       VARCHAR(60) NOT NULL UNIQUE,
  status       ENUM('Bueno','Regular','Dañado','En reparación','Retirado') NOT NULL,
  ownership    ENUM('Empresa','Cliente','Comodato') NOT NULL,
  delivered_at DATE NULL,
  notes        VARCHAR(300) NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_coolers_status (status),
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Relación cliente · ruta · día · orden de visita
CREATE TABLE route_stops (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  route_id    BIGINT UNSIGNED NOT NULL,
  weekday     TINYINT UNSIGNED NOT NULL,           -- 1 = lunes … 7 = domingo
  customer_id BIGINT UNSIGNED NOT NULL,
  visit_order SMALLINT UNSIGNED NOT NULL,
  UNIQUE KEY uq_stop_customer (route_id, weekday, customer_id),
  UNIQUE KEY uq_stop_order    (route_id, weekday, visit_order),
  KEY idx_stops_customer (customer_id),
  CONSTRAINT chk_weekday CHECK (weekday BETWEEN 1 AND 7),
  FOREIGN KEY (route_id)    REFERENCES routes(id)    ON DELETE CASCADE,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;