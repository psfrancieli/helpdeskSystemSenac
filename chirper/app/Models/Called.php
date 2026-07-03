<?php

class Chamado
{
    public int $id;
    public string $uuid;
    public string $titulo;
    public string $descricao;
    public string $prioridade;
    public string $data_abertura;
    public ?string $data_encerramento;
    public string $patrimonio;
    public int $id_categoria;
    public int $id_usuario;
    public ?int $id_responsavel;
    public int $id_status;
}
