CREATE DATABASE IF NOT EXISTS kart_center CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kart_center;

CREATE TABLE IF NOT EXISTS usuarios (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 nome VARCHAR(150) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 senha VARCHAR(255) NOT NULL,
 tipo ENUM('usuario','administrador') NOT NULL DEFAULT 'usuario',
 criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS baterias (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 data DATE NOT NULL,
 horario TIME NOT NULL,
 duracao SMALLINT UNSIGNED NOT NULL DEFAULT 20,
 preco DECIMAL(10,2) NOT NULL,
 total_vagas SMALLINT UNSIGNED NOT NULL,
 vagas_disponiveis SMALLINT UNSIGNED NOT NULL,
 status ENUM('disponivel','lotada','cancelada') NOT NULL DEFAULT 'disponivel',
 criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_baterias_data_hora (data, horario)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reservas (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 usuario_id INT UNSIGNED NOT NULL,
 bateria_id INT UNSIGNED NOT NULL,
 quantidade_participantes SMALLINT UNSIGNED NOT NULL,
 valor_total DECIMAL(10,2) NOT NULL,
 horario_inicio DATETIME NOT NULL,
 data_reserva TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_reservas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
 CONSTRAINT fk_reservas_bateria FOREIGN KEY (bateria_id) REFERENCES baterias(id) ON DELETE RESTRICT,
 INDEX idx_reservas_usuario (usuario_id, data_reserva),
 INDEX idx_reservas_horario_inicio (usuario_id, horario_inicio),
 INDEX idx_reservas_bateria (bateria_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS solicitacoes_redefinicao (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 usuario_id INT UNSIGNED NOT NULL,
 email VARCHAR(190) NOT NULL,
 token_hash CHAR(64) NOT NULL,
 expira_em DATETIME NOT NULL,
 utilizado TINYINT(1) NOT NULL DEFAULT 0,
 criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_reset_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
 INDEX idx_reset_token (token_hash)
) ENGINE=InnoDB;

ALTER TABLE reservas
  ADD CONSTRAINT chk_reservas_qtd CHECK (quantidade_participantes > 0),
  ADD CONSTRAINT chk_reservas_valor CHECK (valor_total >= 0);

UPDATE reservas r
JOIN baterias b ON b.id = r.bateria_id
SET r.horario_inicio = TIMESTAMP(b.data, b.horario)
WHERE r.horario_inicio IS NULL;

CREATE UNIQUE INDEX ux_reservas_usuario_horario_inicio
  ON reservas(usuario_id, horario_inicio);

-- Conta administrativa de demonstração: admin@kartcenter.local / KartAdmin#2026
INSERT INTO usuarios (nome,email,senha,tipo) VALUES
('Administrador Kart Center','admin@kartcenter.local','$2y$12$ENuQkAKnINb.Iwcg9KTeQ.CjYlyFfZMxOdTFex5xre6f6vrqsE/I6','administrador')
ON DUPLICATE KEY UPDATE email=VALUES(email);

-- Baterias futuras de demonstração. Ajuste as datas se executar o script em outro período.
INSERT INTO baterias (data,horario,duracao,preco,total_vagas,vagas_disponiveis,status) VALUES
(DATE_ADD(CURDATE(), INTERVAL 1 DAY),'10:00:00',20,75.00,12,12,'disponivel'),
(DATE_ADD(CURDATE(), INTERVAL 1 DAY),'14:30:00',20,85.00,10,10,'disponivel'),
(DATE_ADD(CURDATE(), INTERVAL 2 DAY),'16:00:00',25,95.00,8,8,'disponivel'),
(DATE_ADD(CURDATE(), INTERVAL 3 DAY),'11:00:00',20,75.00,14,14,'disponivel'),
(DATE_ADD(CURDATE(), INTERVAL 4 DAY),'19:00:00',30,110.00,6,6,'disponivel');
