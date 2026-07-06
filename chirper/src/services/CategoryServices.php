<?php
require_once __DIR__ . '/../repositories/CategoryRepository.php';

class CategoryServices {

    public function criarCategoria(string $nome) {
        $categoria = new CategoryRepository();
        $nome = ucfirst(trim($nome));

        if ($nome == '') {
            throw new InvalidArgumentException('Insira o nome da categoria para continuar.');
        }

        if (strlen($nome) > 50) {
            throw new InvalidArgumentException('O nome a categoria deve ter menos de 50 caracteres.');
        }

        if ($categoria->EncontrarCategoriaPorNome($nome) !== null) {
            throw new InvalidArgumentException('Já existe uma categoria com esse nome.');
        }
        
    }
}
?>