<?php

class Ticket
{
    private int $id;
    private string $uuid;
    private string $titulo;
    private string $descricao;
    private string $prioridade;
    private string $data_abertura;
    private ?string $data_encerramento;
    private string $patrimonio;
    private int $id_categoria;
    private int $id_usuario;
    private ?int $id_responsavel;
    private string $status;

    public function __construct(
        int $id,
        string $uuid,
        string $titulo,
        string $descricao,
        string $prioridade,
        string $data_abertura,
        ?string $data_encerramento,
        string $patrimonio,
        int $id_categoria,
        int $id_usuario,
        ?int $id_responsavel,
        string $status
    ) {
        if (trim($titulo) === '') {
            throw new InvalidArgumentException('Titulo invalido');
        }

        if (trim($descricao) === '') {
            throw new InvalidArgumentException('Descricao invalida');
        }

        if (trim($patrimonio) === '') {
            throw new InvalidArgumentException('Patrimonio invalido');
        }

        $this->id = $id;
        $this->uuid = $uuid;
        $this->titulo = $titulo;
        $this->descricao = $descricao;
        $this->prioridade = $prioridade;
        $this->data_abertura = $data_abertura;
        $this->data_encerramento = $data_encerramento;
        $this->patrimonio = $patrimonio;
        $this->id_categoria = $id_categoria;
        $this->id_usuario = $id_usuario;
        $this->id_responsavel = $id_responsavel;
        $this->status = $status;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getTitulo(): string
    {
        return $this->titulo;
    }

    public function getDescricao(): string
    {
        return $this->descricao;
    }

    public function getPrioridade(): string
    {
        return $this->prioridade;
    }

    public function getDataAbertura(): string
    {
        return $this->data_abertura;
    }

    public function getDataEncerramento(): ?string
    {
        return $this->data_encerramento;
    }

    public function getPatrimonio(): string
    {
        return $this->patrimonio;
    }

    public function getIdCategoria(): int
    {
        return $this->id_categoria;
    }

    public function getIdUsuario(): int
    {
        return $this->id_usuario;
    }

    public function getIdResponsavel(): ?int
    {
        return $this->id_responsavel;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
