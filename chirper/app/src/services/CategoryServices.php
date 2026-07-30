<?php

require_once __DIR__ . '/../repositories/CategoryRepository.php';
require_once __DIR__ . '/../models/Category.php';

class CategoryServices {

    private CategoryRepository $repository;

    public function __construct()
    {
        $this->repository = new CategoryRepository();
    }

    public function EncontrarCategoriaPorId(int $id): ?Category {
        if ($id <= 0) {
            throw new InvalidArgumentException("ID inválido.");
        }

        $categoria = $this->repository->EncontrarCategoriaPorId($id);

        if ($categoria === null) {
            throw new RuntimeException("Categoria não encontrada.");
        }

        return $categoria;
    }

    public function EncontrarTodasCategorias(): array {
        return $this->repository->EncontrarTodasCategorias();
    }

    public function EncontrarCategoriaPorNome(string $nome): ?Category {
        $nome = $this->tratarNome($nome);

        $categoria = $this->repository->EncontrarCategoriaPorNome($nome);

        if ($categoria === null) {
            throw new RuntimeException("Categoria não encontrada.");
        }

        return $categoria;
    }

    public function criarCategoria(string $nome): bool {
        $nome = $this->tratarNome($nome);

        if ($this->repository->EncontrarCategoriaPorNome($nome) !== null) {
            throw new InvalidArgumentException(
                "Já existe uma categoria com esse nome."
            );
        }

        $categoria = new Category(null, $nome);

        return $this->repository->criarCategoria($categoria);
    }

    public function atualizarCategoria(int $id, string $nome): bool {
        if ($id <= 0) {
            throw new InvalidArgumentException("ID inválido.");
        }

        $nome = $this->tratarNome($nome);

        $categoriaExistente = $this->repository->EncontrarCategoriaPorId($id);

        if ($categoriaExistente === null) {
            throw new RuntimeException("Categoria não encontrada.");
        }

        $categoriaNome = $this->repository->EncontrarCategoriaPorNome($nome);

        if ($categoriaNome !== null && $categoriaNome->getId() != $id) {
            throw new InvalidArgumentException(
                "Já existe outra categoria com esse nome."
            );
        }

        $categoria = new Category($id, $nome);

        return $this->repository->atualizarCategoria($categoria);
    }

    private function tratarNome(string $nome): string {
        $nome = trim($nome);

        if ($nome === '') {
            throw new InvalidArgumentException(
                "Insira o nome da categoria."
            );
        }

        if (strlen($nome) > 50) {
            throw new InvalidArgumentException(
                "O nome da categoria deve ter menos de 50 caracteres."
            );
        }

        return ucfirst(strtolower($nome));
    }

}

/*
TESTES CATEGORY SERVICES
*/


// Teste criar categoria
/*
$service = new CategoryServices();

try {

    $resultado = $service->criarCategoria("Rede");

    if ($resultado) {
        echo "Categoria criada com sucesso!";
    } else {
        echo "Erro ao criar categoria.";
    }

} catch(Exception $e) {

    echo "Erro: " . $e->getMessage();

}
*/


// Teste buscar categoria por ID
/*
$service = new CategoryServices();

try {

    $categoria = $service->EncontrarCategoriaPorId(1);

    echo "ID: " . $categoria->getId() . "<br>";
    echo "Nome: " . $categoria->getNome();

} catch(Exception $e) {

    echo "Erro: " . $e->getMessage();

}
*/


// Teste buscar todas categorias
/*
$service = new CategoryServices();

try {

    $categorias = $service->EncontrarTodasCategorias();


    foreach($categorias as $categoria){

        echo "ID: " . $categoria->getId();
        echo " - Nome: " . $categoria->getNome();
        echo "<br>";

    }


} catch(Exception $e){

    echo "Erro: " . $e->getMessage();

}
*/


// Teste buscar por nome
/*
$service = new CategoryServices();

try {

    $categoria = $service->EncontrarCategoriaPorNome("Software");

    echo "ID: " . $categoria->getId() . "<br>";
    echo "Nome: " . $categoria->getNome();


} catch(Exception $e){

    echo "Erro: " . $e->getMessage();

}
*/


// Teste atualizar categoria
/*
$service = new CategoryServices();

try {

    $resultado = $service->atualizarCategoria(
        1,
        "Novo Nome"
    );


    if($resultado){

        echo "Categoria atualizada com sucesso!";

    }else{

        echo "Nenhuma alteração feita.";

    }


} catch(Exception $e){

    echo "Erro: " . $e->getMessage();

}
*/


// Teste validação nome vazio
/*
$service = new CategoryServices();

try {

    $service->criarCategoria("");

} catch(Exception $e){

    echo "Erro esperado: " . $e->getMessage();

}
*/


// Teste categoria duplicada
/*
$service = new CategoryServices();

try {

    $service->criarCategoria("Software");

} catch(Exception $e){

    echo "Erro esperado: " . $e->getMessage();

}
*/
?>