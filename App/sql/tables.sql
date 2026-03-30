SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `page`
(
    `id`              INT(10)                         NOT NULL AUTO_INCREMENT,
    `virtual_title`   VARCHAR(250) CHARACTER SET utf8 NOT NULL,
    `static_url`      VARCHAR(255) CHARACTER SET utf8 NOT NULL,
    `virtual_content` MEDIUMTEXT CHARACTER SET utf8   NOT NULL,
    `created_at`      DATE                            NOT NULL,
    `visible`         INT(10)                         NOT NULL,
    `sort_order`      INT(10)                         NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 11;

CREATE TABLE IF NOT EXISTS `block`
(
    `id`         INT(10)                         NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(255) CHARACTER SET utf8 NOT NULL,
    `sort_order` INT(10)                         NOT NULL,
    `content`    LONGTEXT CHARACTER SET utf8     NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `media`
(
    `name`          VARCHAR(255) CHARACTER SET utf8  NOT NULL,
    `original_name` VARCHAR(255) CHARACTER SET utf8  NOT NULL,
    `extension`     VARCHAR(10) CHARACTER SET utf8   NOT NULL,
    `mime`          VARCHAR(100) CHARACTER SET utf8  NOT NULL,
    `size_bytes`    BIGINT                           NOT NULL DEFAULT 0,
    `width`         INT(10)                          NOT NULL DEFAULT 0,
    `height`        INT(10)                          NOT NULL DEFAULT 0,
    `url`           VARCHAR(500) CHARACTER SET utf8  NOT NULL,
    `uploaded_at`   VARCHAR(50) CHARACTER SET utf8   NOT NULL,
    `records`       LONGTEXT CHARACTER SET utf8      NOT NULL,
    PRIMARY KEY (`name`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

INSERT INTO `page` (`id`, `virtual_title`, `static_url`, `virtual_content`, `created_at`, `visible`, `sort_order`)
VALUES (1, 'Welcome Title', 'welcome-title', 'Welcome content ...', '2025-07-04', 1, 1),
       (2, 'Another Example', 'another-example-page-demo', 'Hello there ...', '2025-07-04', 1, 2);

INSERT INTO `block` (`id`, `name`, `sort_order`, `content`)
VALUES (1, 'cards', 1, '<section class="features py-5">
  <div class="container-fluid">
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card p-4">
          <i class="fas fa-cogs fa-3x mb-3"></i>
          <h5 class="card-title">Funcionalidad 1</h5>
          <p class="card-text">Descripción breve de la funcionalidad o característica 1.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card p-4">
          <i class="fas fa-lightbulb fa-3x mb-3"></i>
          <h5 class="card-title">Funcionalidad 2</h5>
          <p class="card-text">Descripción breve de la funcionalidad o característica 2.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card p-4">
          <i class="fas fa-users fa-3x mb-3"></i>
          <h5 class="card-title">Funcionalidad 3</h5>
          <p class="card-text">Descripción breve de la funcionalidad o característica 3.</p>
        </div>
      </div>
    </div>
  </div>
</section>');
