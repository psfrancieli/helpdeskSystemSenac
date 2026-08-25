CREATE TYPE  nivel_usuario AS ENUM(
  'adm',
  'analista',
  'tecnico',
  'usuario'
);
CREATE TYPE  status_pedido AS ENUM(
  'pendente',
  'cancelado',
  'concluido',
  'não resolvido'
);
CREATE TYPE  prioridade_chamado AS ENUM(
  'baixa',
  'media',
  'alta',
  'muito alta'
);
CREATE TABLE IF NOT EXISTS "USUARIO" (
    "id" SERIAL NOT NULL,
    "uuid" UUID NOT NULL UNIQUE,
    "nome" VARCHAR(100) NOT NULL,
    "CPF" VARCHAR(14) NOT NULL UNIQUE,
    "telefone" VARCHAR(15) NOT NULL UNIQUE,
    "email" VARCHAR(80) NOT NULL UNIQUE,
    "senha" VARCHAR(300) NOT NULL,
    "nivel" nivel_usuario NOT NULL DEFAULT 'usuario',
    "ativo" BOOLEAN NOT NULL,
    PRIMARY KEY("id")
);
 
CREATE INDEX "USUARIO_index_0"
ON "USUARIO" ("CPF");
CREATE INDEX "USUARIO_index_1"
ON "USUARIO" ("email");
 
CREATE TABLE IF NOT EXISTS "CHAMADO" (
  "id" SERIAL NOT NULL,
  "uuid" UUID NOT NULL UNIQUE,
  "titulo" VARCHAR(255) NOT NULL,
  "descricao" TEXT NOT NULL,
  "prioridade" prioridade_chamado NOT NULL,
  "data_abertura" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "data_encerramento" TIMESTAMP,
  "patrimonio" VARCHAR(20) NOT NULL,
  "id_categoria" INTEGER NOT NULL,
  "id_usuario" INTEGER NOT NULL,
  "id_responsavel" INTEGER,
  "status" status_pedido DEFAULT 'pendente' NOT NULL,
  PRIMARY KEY("id")
);
 
CREATE INDEX "CHAMADO_index_0"
ON "CHAMADO" ("data_abertura");
CREATE INDEX "CHAMADO_index_1"
ON "CHAMADO" ("data_encerramento");
CREATE INDEX "CHAMADO_index_2"
ON "CHAMADO" ("id_categoria");
CREATE INDEX "CHAMADO_index_3"
ON "CHAMADO" ("patrimonio");
CREATE TABLE IF NOT EXISTS "CATEGORIA" (
    "id" SERIAL NOT NULL,
    "nome" VARCHAR(80) NOT NULL,
    PRIMARY KEY("id")
);
 
CREATE TABLE IF NOT EXISTS "HISTORICO" (
    "id" SERIAL NOT NULL,
    "uuid" UUID NOT NULL UNIQUE,
    "descricao" TEXT NOT NULL,
    "data" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "id_usuario_tecnico" INTEGER NOT NULL,
    "id_chamado" INTEGER NOT NULL,
    PRIMARY KEY("id")
);
 
CREATE INDEX "HISTORICO_index_0"
ON "HISTORICO" ("id_usuario_tecnico");
CREATE INDEX "HISTORICO_index_1"
ON "HISTORICO" ("data");
 
 
ALTER TABLE "CHAMADO"
ADD FOREIGN KEY("id_categoria") REFERENCES "CATEGORIA"("id")
ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE "CHAMADO"
ADD FOREIGN KEY("id_usuario") REFERENCES "USUARIO"("id")
ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE "HISTORICO"
ADD FOREIGN KEY("id_usuario_tecnico") REFERENCES "USUARIO"("id")
ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE "HISTORICO"
ADD FOREIGN KEY("id_chamado") REFERENCES "CHAMADO"("id")
ON UPDATE CASCADE ON DELETE RESTRICT;
 
ALTER TABLE "CHAMADO"
ADD FOREIGN KEY("id_responsavel")
REFERENCES "USUARIO"("id")
ON UPDATE CASCADE
ON DELETE SET NULL;
 