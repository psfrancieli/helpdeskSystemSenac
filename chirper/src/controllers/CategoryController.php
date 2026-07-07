<?php
require_once __DIR__ . "/../services/CategoryService.php";

class CategoryController
{
    private CategoryService $service;

    public function __construct(CategoryService $service)
    {
        $this->service = $service;
    }

    public function Index(): void
    {
        $categorias = $this->service->listarTodas();
        require __DIR__ . '/../Views/categoria/index.php';
    }

    public function Search(int $id): void
    {
        $categoria = $this->service->buscarPorId($id);

        if (!$categoria) {
            http_response_code(404);
            echo "Categoria não encontrada.";
            return;
        }

        require __DIR__ . '/../Views/categoria/search.php';
    }


    public function Create(): void
    {
        $nome = $_POST['nome'] ?? '';

        try {
            $this->service->criar($nome);
            header('Location: /categoria');
        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            echo $e->getMessage();
        }
    }


    public function Update(int $id): void
    {
        $nome = $_POST['nome'] ?? '';

        try {
            $this->service->atualizar($id, $nome);
            header('Location: /categoria/' . $id);
        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            echo $e->getMessage();
        }
    }
}