<?php

require_once __DIR__ . '/../repositories/UserRepository.php';

class UserController
{
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    public function getById(int $id): User
    {
        return $this->userRepository->listarUsuarioPorId($id);
    }

    public function createUser(User $usuario): bool
    {
        return $this->userRepository->criarUsuario($usuario);
    }
}
