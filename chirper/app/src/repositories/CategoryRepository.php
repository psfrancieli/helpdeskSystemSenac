<?php 
require_once __DIR__ . "/../configs/Database.php";
require_once __DIR__ . "/../models/Category.php";

class CategoryRepository{
    public function EncontrarCategoriaPorId(int $id): ?Category{
        try{
            $sql = 'SELECT * FROM "CATEGORIA" WHERE id = ?';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([$id]);
            $dados = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$dados) {
                return null;
            }
            return new Category($dados['id'], $dados['nome']);
        }catch(PDOException $e){
            throw new RuntimeException("Erro ao buscar categoria no banco",0 , $e);
        }
    }

    public function EncontrarTodasCategorias():array{
        try{
            $sql = 'SELECT * FROM "CATEGORIA"';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute();
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $categorias = [];
            foreach ($dados as $categoria) {
                $categorias[] = new Category(    
                    $categoria['id'],       
                    $categoria['nome'],
                );
            }
            return $categorias;
        }catch(PDOException $e){
            throw new RuntimeException("Erro ao buscar categorias no banco",0 , $e);
        }
    }

    //Tratar os nomes na Service
    public function EncontrarCategoriaPorNome(string $nome): ?Category{
        try{
            $sql = 'SELECT * FROM "CATEGORIA" WHERE nome = ?';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([$nome]);
            $dados = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$dados) {
                return null;
            }
            return new Category($dados['id'], $dados['nome']);
        }catch(PDOException $e){ 
            throw new RuntimeException("Erro ao buscar categoria no banco",0 , $e);
        }
    }

    public function criarCategoria(Category $categoria):bool{
        try{
            $sql = 'INSERT INTO "CATEGORIA" (nome) VALUES (?)';
            $stmt = Database::getConnection()->prepare($sql);
            return $stmt->execute([$categoria->getNome()]);
        }catch(PDOException $e){
            throw new RuntimeException("Erro ao criar categoria ",0 , $e);
        }
       
    }

    public function atualizarCategoria(Category $categoria): bool{
        try {
            $sql = 'UPDATE "CATEGORIA" SET  nome = ?  WHERE id = ?';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([$categoria->getNome() , $categoria->getId()]);
            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            throw new RuntimeException("Não foi possivel atualizar a categoria " , 0 , $e);
        }
    }
    
}
/* 
Testes da classe 
 */

/*$categoriaRepo = new CategoryRepository(); 
$categoria = $categoriaRepo->EncontrarCategoriaPorId(1);
if ($categoria) {
    echo "ID: " . $categoria->getId() . "<br>";
    echo "Nome: " . $categoria->getNome() . "<br>";
} else {
    echo "Categoria não encontrada.";
}*/


/*$dao = new CategoryRepository(); 
$categorias = $dao->EncontrarTodasCategorias();

if (count($categorias) > 0) {
    foreach ($categorias as $cat) {
        echo "ID: " . $cat->getId() . " - Nome: " . $cat->getNome() . "<br>";
    }
} else {
    echo "Nenhuma categoria encontrada.";
}*/


/*$dao = new CategoryRepository(); 

$novaCategoria = new Category(null, "Categoria Teste dois");
$sucesso = $dao->criarCategoria($novaCategoria);

if ($sucesso) {
    echo "Categoria criada com sucesso!";
} else {
    echo "Falha ao criar categoria.";
}*/


/*$dao = new CategoryRepository();
$categoria = $dao->EncontrarCategoriaPorNome("Software");

if ($categoria) {
    echo "ID: " . $categoria->getId() . "<br>";
    echo "Nome: " . $categoria->getNome() . "<br>";
} else {
    echo "Categoria não encontrada.";
*/

/*$categoriaRepo = new CategoryRepository();
$categoria = new Category(4);
$categoria->setNome('Softwarezinho');
$sucesso = $categoriaRepo->atualizarCategoria($categoria);

if ($sucesso) {
    echo "Categoria atualizada com sucesso!<br>";
    echo "ID: " . $categoria->getId() . "<br>";
    echo "Novo nome: " . $categoria->getNome() . "<br>";
} else {
    echo "Nenhuma categoria foi atualizada (id não encontrado ou nome igual).";
}*/
?>