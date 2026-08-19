<?php 

class History{
    private int $id;
    private DateTime $data;
    private string $descricao;
    private int $id_usuario_tecnico;
    private int $id_chamado;
    
    public function __construct(?DateTime $data = null , string $descricao = '', int $id_chamado, int $id_usuario_tecnico)
    {
        $this->setData($data ?? new DateTime());
        $this->setDescricao($descricao);
        $this->setChamado($id_chamado);
        $this->setTecnico($id_usuario_tecnico);
    }

    public function getChamado(): int{
        return $this->id_chamado;
    }
    public function getTecnico(): int{
        return $this->id_usuario_tecnico;
    }
    public function getData(): DateTime{
        return $this->data;
    }
    public function getDescricao():string{
        return $this->descricao;
    }

    public function setData(DateTime $data): void {
        if (empty($data)) {
        throw new InvalidArgumentException('Data inválida');
        }
        $this->data = $data;
    }

    public function setChamado(int $id_chamado) {
        if (empty($id_chamado)) {
            throw new InvalidArgumentException('Chamado inexistente');
        }
        $this->id_chamado = $id_chamado;
    }

    public function setTecnico(int $id_usuario_tecnico) {
        if (empty($id_usuario_tecnico)) {
            throw new InvalidArgumentException('Tecnico inexistente');
        }
        $this->id_usuario_tecnico = $id_usuario_tecnico;
    }
    
    public function setDescricao(string $descricao): void {
        if (empty($descricao)) {
            throw new InvalidArgumentException('Descrição não pode estar vazia');
        }
        $this->descricao = $descricao;
    }

}

?>