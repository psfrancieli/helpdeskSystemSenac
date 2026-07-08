<?php
require_once __DIR__ . '/../repositories/CategoryRepository.php';

class CategoryServices {

    public function EncontrarCategoriaPorId(Category $categoria, int $id): int {
        $categoria = new CategoryRepository();
        $idCategoria = $categoria->EncontrarCategoriaPorId($id);

        if ($idCategoria <= 0) {
            throw new Exception("ID inválido");
        }
        return $id;
    }

    public function EncontrarTodasCategorias(int $id) {}

    public function EncontrarCategoriaPorNome(string $nome) {

    }

    public function criarCategoria(string $nome): string {
        $categoria = new CategoryRepository();
        $nome = strtolower(trim($nome));

        if ($nome == '') {
            throw new InvalidArgumentException('Insira o nome da categoria para continuar.');
        }

        if (strlen($nome) > 50) {
            throw new InvalidArgumentException('O nome a categoria deve ter menos de 50 caracteres.');
        }

        if ($categoria->EncontrarCategoriaPorNome($nome) !== null) {
            throw new InvalidArgumentException('Já existe uma categoria com esse nome.');
        }
        return $nome;
    }

    public function atualizarCategoria() {}

    // adicionar deletarCategoria() no CategoryRepository
}

?>