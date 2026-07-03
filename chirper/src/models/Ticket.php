<?php

date_default_timezone_set('America/Sao_Paulo');

class Ticket {
    protected ?int $id;
    protected ?string $uuid;
    protected string $titulo;
    protected string $descricao;
    protected ?string $prioridade;
    protected string $patrimonio;
    protected string $status;
    protected ?DateTime $dataAbertura;
    protected ?DateTime $dataEncerramento;
    protected ?int $id_categoria;
    protected int $id_usuario;
    protected ?int $id_responsavel;

    public function __construct(
        ?int $id = null,                 
        ?string $uuid = null,             
        string $titulo = '',             
        string $descricao = '',          
        ?string $prioridade = null,       
        string $patrimonio = '',         
        string $status = 'Aberto',       
        ?int $id_categoria = null,        
        int $id_usuario = 0,              
        ?int $id_responsavel = null,      
        ?DateTime $dataAbertura = null,   
        ?DateTime $dataEncerramento = null 
    )
    {
        if (empty($titulo)) {
            throw new InvalidArgumentException('Título inválido');
        }

        if (empty($descricao)) {
            throw new InvalidArgumentException('Descrição inválida');
        }

        $this->id = $id;
        $this->uuid = $uuid;
        $this->titulo = $titulo;
        $this->descricao = $descricao;
        $this->prioridade = $prioridade;
        $this->patrimonio = $patrimonio;
        $this->status = $status;
        $this->dataAbertura = $dataAbertura;
        $this->dataEncerramento = $dataEncerramento;
        $this->id_categoria = $id_categoria;
        $this->id_usuario = $id_usuario;
        $this->id_responsavel = $id_responsavel;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getUuid(): ?string {
        return $this->uuid;
    }

    public function getTitulo(): string {
        return $this->titulo;
    }

    public function getDescricao(): string {
        return $this->descricao;
    }

    public function getPrioridade(): ?string {
        return $this->prioridade;
    }

    public function getPatrimonio(): string {
        return $this->patrimonio;
    }

    public function getStatus(): string {
        return $this->status;
    }

    // Adicionado '?' no retorno das datas
    public function getDataAbertura(): ?DateTime {
        return $this->dataAbertura;
    }

    public function getDataEncerramento(): ?DateTime {
        return $this->dataEncerramento;
    }

    public function getIdCategoria(): ?int {
        return $this->id_categoria;
    }

    public function getIdUsuario(): int {
        return $this->id_usuario;
    }

    public function getIdResponsavel(): ?int {
        return $this->id_responsavel;
    }

    public function getHistorico(): array {
        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'descricao' => $this->descricao,
            'patrimonio' => $this->patrimonio,
        ];
    } 

    public function getAll(): array {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'titulo' => $this->titulo,
            'descricao' => $this->descricao,
            'prioridade' => $this->prioridade,
            'patrimonio' => $this->patrimonio,
            'status' => $this->status,
            'dataAbertura' => $this->dataAbertura ? $this->dataAbertura->format('Y-m-d H:i:s') : null,
            'dataEncerramento' => $this->dataEncerramento ? $this->dataEncerramento->format('Y-m-d H:i:s') : null,
            'id_categoria' => $this->id_categoria,
            'id_usuario' => $this->id_usuario,
            'id_responsavel' => $this->id_responsavel,
        ];
    }

    public function toJson(): string {
        return json_encode($this->getAll());
    }

    public function setTitulo(string $titulo): void {
        if (empty($titulo)) {
            throw new InvalidArgumentException('Título inválido');
        }
        $this->titulo = $titulo;
    }

    public function setDescricao(string $descricao): void {
        if (empty($descricao)) {
            throw new InvalidArgumentException('Descrição inválida');
        }
        $this->descricao = $descricao;
    }

    public function setPrioridade(string $prioridade): void {
        $this->prioridade = $prioridade;
    }

    public function setPatrimonio(string $patrimonio): void {
        if (empty($patrimonio)) {
            throw new InvalidArgumentException('Patrimônio inválido');
        }
        $this->patrimonio = $patrimonio;
    }

    public function setStatus(string $status): void {
        $this->status = $status;
    }

    public function setIdCategoria(int $id_categoria): void {
        $this->id_categoria = $id_categoria;
    }

    public function setIdUsuario(int $id_usuario): void {
        $this->id_usuario = $id_usuario;
    }

    public function setIdResponsavel(?int $id_responsavel): void {
        $this->id_responsavel = $id_responsavel;
    }
}

 

// $teste = new Ticket(1, "titulo", "descricao descricao", "baixa", "pat-01", "ativo", 1, 1, 1, null, new DateTime("2026-01-01"), new DateTime("2026-01-01"));
// echo "<pre>";
// echo $teste->toJson();
// echo "</pre>";
?>