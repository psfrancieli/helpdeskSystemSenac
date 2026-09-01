<?php

class History
{
    private int $id;
    private DateTime $data;
    private string $descricao;
    private int $id_usuario;
    private int $id_chamado;

    public function __construct(
        string $descricao,
        int $id_chamado,
        int $id_usuario,
        ?DateTime $data = null
    ) {
        $this->setDescricao($descricao);
        $this->setChamado($id_chamado);
        $this->setUsuario($id_usuario);
        $this->setData($data ?? new DateTime());
    }

    public function getChamado(): int
    {
        return $this->id_chamado;
    }

    public function getUsuario(): int
    {
        return $this->id_usuario;
    }

    public function getData(): DateTime
    {
        return $this->data;
    }

    public function getDescricao(): string
    {
        return $this->descricao;
    }

    public function setData(DateTime $data): void
    {
        $this->data = $data;
    }

    public function setChamado(int $id_chamado): void
    {
        if ($id_chamado <= 0) {
            throw new InvalidArgumentException('Chamado inexistente');
        }

        $this->id_chamado = $id_chamado;
    }

    public function setUsuario(int $id_usuario): void
    {
        if ($id_usuario <= 0) {
            throw new InvalidArgumentException('Usuário inexistente');
        }

        $this->id_usuario = $id_usuario;
    }

    public function setDescricao(string $descricao): void
    {
        $descricao = trim($descricao);

        if ($descricao === '') {
            throw new InvalidArgumentException(
                'Descrição não pode estar vazia'
            );
        }

        $this->descricao = $descricao;
    }
}