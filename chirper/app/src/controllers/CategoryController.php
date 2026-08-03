<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../services/CategoryServices.php';

class CategoryController extends Controller
{
    private CategoryServices $service;

    public function __construct()
    {
        $this->service = new CategoryServices();
    }

    public function buscaCategorias(): void
    {
        try {

            $categorias = $this->service->EncontrarTodasCategorias();
            $resultado = [];
            foreach ($categorias as $categoria) {
                $resultado[] = [
                    "id" => $categoria->getId(),
                    "nome" => $categoria->getNome()
                ];
            }

            $this->response([
                "success" => true,
                "data" => $resultado
            ]);

        } catch (Throwable $e) {

            $this->response([
                "success" => false,
                "message" => $e->getMessage()
            ], 400);

        }
    }

    public function buscaCategoriasId(int $id): void
    {
        try {

            $categoria = $this->service->EncontrarCategoriaPorId($id);

            $resultado = [
                "id" => $categoria->getId(),
                "nome" => $categoria->getNome()
            ];

            $this->response([
                "success" => true,
                "data" => $resultado
            ]);

        } catch (Throwable $e) {

            $this->response([
                "success" => false,
                "message" => $e->getMessage()
            ], 400);

        }
    }

    public function criaCategoria(): void
    {
        try {

            $dados = $this->getBody();

            $this->service->criarCategoria($dados['nome']);

            $this->response([
                "success" => true,
                "message" => "Categoria criada com sucesso."
            ], 201);

        } catch (Throwable $e) {

            $this->response([
                "success" => false,
                "message" => $e->getMessage()
            ], 400);

        }
    }

    public function atualizaCategoria(int $id): void
    {
        try {

            $dados = $this->getBody();

            $this->service->atualizarCategoria($id, $dados['nome']);

            $this->response([
                "success" => true,
                "message" => "Categoria atualizada com sucesso."
            ]);

        } catch (Throwable $e) {

            $this->response([
                "success" => false,
                "message" => $e->getMessage()
            ], 400);

        }
    }
}